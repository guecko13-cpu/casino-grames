<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../src/ledger.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

$ledger = new Ledger(__DIR__ . '/../data/credits.sqlite', __DIR__ . '/../data/app.log');

function render_layout(string $title, string $content): string
{
    $nav = <<<HTML
        <nav class="nav">
          <a href="/lobby">Lobby</a>
          <a href="/profile">Profil</a>
          <a href="/wallet">Crédits</a>
          <a href="/history">Historique</a>
          <a href="/admin">Admin</a>
          <a href="/about">À propos</a>
        </nav>
    HTML;

    return <<<HTML
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title}</title>
  <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
  <div class="app-shell">
    <header class="topbar">
      <div class="topbar-inner">
        <div class="brand">Gaming Star</div>
        {$nav}
      </div>
    </header>
    <main class="page">
      {$content}
    </main>
  </div>
</body>
</html>
HTML;
}

function respond_html(string $title, string $body, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo render_layout($title, $body);
}

function respond_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function require_auth(): int
{
    $userId = current_user_id();
    if ($userId === null) {
        respond_json(['error' => 'Authentification requise.'], 401);
        exit;
    }
    return $userId;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        respond_html('Requête refusée', '<h1>Requête refusée</h1><p class="muted">Token CSRF invalide.</p>', 403);
        exit;
    }
}

function is_maintenance_mode_enabled(): bool
{
    return file_exists(__DIR__ . '/../data/maintenance.flag');
}

function set_maintenance_mode(bool $enabled): void
{
    $flag = __DIR__ . '/../data/maintenance.flag';
    if ($enabled) {
        $directory = dirname($flag);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents($flag, 'on');
        return;
    }
    if (file_exists($flag)) {
        unlink($flag);
    }
}

function enforce_maintenance_mode(string $path): void
{
    if (!is_maintenance_mode_enabled()) {
        return;
    }
    $allowed = ['/admin', '/login', '/logout', '/about', '/install', '/api/health'];
    if (str_starts_with($path, '/assets/')) {
        return;
    }
    if (in_array($path, $allowed, true)) {
        return;
    }
    respond_html(
        'Maintenance',
        '<section class="card"><h1>Maintenance</h1><p class="muted">Le service est en maintenance. Merci de revenir plus tard.</p></section>',
        503
    );
    exit;
}

function rate_limit(string $key, int $limit, int $windowSeconds): void
{
    $now = time();
    $bucket = $_SESSION['rate_limit'][$key] ?? ['start' => $now, 'count' => 0];
    if ($now - $bucket['start'] >= $windowSeconds) {
        $bucket = ['start' => $now, 'count' => 0];
    }
    $bucket['count']++;
    $_SESSION['rate_limit'][$key] = $bucket;
    if ($bucket['count'] > $limit) {
        respond_json(['error' => 'Trop de requêtes.'], 429);
        exit;
    }
}

function get_git_hash(): string
{
    $headPath = __DIR__ . '/../.git/HEAD';
    if (!file_exists($headPath)) {
        return 'unknown';
    }
    $head = trim((string) file_get_contents($headPath));
    if (str_starts_with($head, 'ref:')) {
        $ref = trim(substr($head, 4));
        $refPath = __DIR__ . '/../.git/' . $ref;
        if (file_exists($refPath)) {
            return substr(trim((string) file_get_contents($refPath)), 0, 12);
        }
        return 'unknown';
    }
    return substr($head, 0, 12);
}

function get_recent_logs(int $lines = 12): array
{
    $logPath = __DIR__ . '/../data/app.log';
    if (!file_exists($logPath)) {
        return [];
    }
    $content = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($content === false) {
        return [];
    }
    return array_slice($content, -$lines);
}

function parse_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function idempotency_response(string $key): ?array
{
    return $_SESSION['idempotency'][$key] ?? null;
}

function store_idempotency_response(string $key, array $response): void
{
    $_SESSION['idempotency'][$key] = $response;
}

function require_idempotency_key(): string
{
    $key = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '';
    $key = trim($key);
    if ($key === '') {
        respond_json(['error' => 'Idempotency-Key requis.'], 400);
        exit;
    }
    return $key;
}

enforce_maintenance_mode($path);

switch ($path) {
    case '/':
        respond_html(
            'Gaming Star',
            '<section class="hero"><h1>Gaming Star</h1><p>Bienvenue dans un casino fictif 100% gratuit.</p><div class="grid cols-2"><div><p class="muted">Explorez le lobby et recevez des crédits quotidiens.</p><a class="button" href="/lobby">Accéder au lobby</a></div><div><div class="badge success">Bonus quotidien disponible</div><p class="muted">Aucune monnaie réelle — uniquement des crédits fictifs.</p></div></div></section>'
        );
        break;
    case '/lobby':
        respond_html(
            'Lobby',
            '<section class="hero"><h1>Lobby</h1><p>Choisissez un jeu pour gagner des crédits fictifs.</p></section>
            <section class="grid cols-3">
              <article class="card"><h3>Slots Nova</h3><p class="muted">Rapide et coloré.</p><span class="badge">Mise mini: 5 crédits</span></article>
              <article class="card"><h3>Roulette Aurora</h3><p class="muted">Classique et fluide.</p><span class="badge">Mise mini: 10 crédits</span></article>
              <article class="card"><h3>Blackjack Zenith</h3><p class="muted">Stratégie rapide.</p><span class="badge">Mise mini: 20 crédits</span></article>
            </section>'
        );
        break;
    case '/profile':
        respond_html(
            'Profil',
            '<section class="grid cols-2">
              <article class="card"><h3>Profil joueur</h3><p class="muted">Pseudo, niveau et statistiques.</p><div class="list"><div class="list-item"><span>Pseudo</span><strong>Guest</strong></div><div class="list-item"><span>Niveau</span><strong>1</strong></div><div class="list-item"><span>Succès</span><strong>0</strong></div></div></article>
              <article class="card"><h3>Préférences</h3><p class="muted">Gérez votre session.</p><form method="post" action="/logout"><input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '"><button class="button secondary" type="submit">Déconnexion</button></form></article>
            </section>'
        );
        break;
    case '/wallet':
        $userId = current_user_id();
        $balance = $userId ? $ledger->getBalance($userId) : 0;
        $transactions = $userId ? $ledger->getLastTransactions($userId, 3) : [];
        $txList = '';
        foreach ($transactions as $transaction) {
            $amount = $transaction['amount'];
            $badge = $amount >= 0 ? 'badge success' : 'badge error';
            $txList .= '<div class="list-item"><span>' . htmlspecialchars($transaction['type'], ENT_QUOTES) . '</span><span class="' . $badge . '">' . $amount . ' crédits</span></div>';
        }
        if ($txList === '') {
            $txList = '<div class="list-item"><span class="muted">Aucune transaction récente.</span></div>';
        }
        respond_html(
            'Crédits',
            '<section class="grid cols-2">
              <article class="card"><h3>Solde</h3><p class="muted">Crédits disponibles</p><h2>' . $balance . ' crédits</h2><div class="status success">Solde actif</div></article>
              <article class="card"><h3>Bonus quotidien</h3><p class="muted">Ajoutez des crédits gratuits.</p><button class="button">Réclamer 100 crédits</button><p class="muted">Via <code>/api/bonus/daily</code></p></article>
            </section>
            <section class="card"><h3>Dernières transactions</h3><div class="list">' . $txList . '</div></section>'
        );
        break;
    case '/history':
        respond_html(
            'Historique',
            '<section class="card"><h3>Historique des crédits</h3><p class="muted">Toutes les opérations internes.</p><div class="list">
              <div class="list-item"><span>Bonus quotidien</span><span class="badge success">+100</span></div>
              <div class="list-item"><span>Mise blackjack</span><span class="badge error">-20</span></div>
              <div class="list-item"><span>Gain roulette</span><span class="badge success">+45</span></div>
            </div></section>'
        );
        break;
    case '/admin':
        if ($method === 'POST') {
            require_csrf();
            $toggle = ($_POST['maintenance'] ?? '') === 'on';
            set_maintenance_mode($toggle);
        }
        $token = csrf_token();
        $maintenance = is_maintenance_mode_enabled();
        $version = get_git_hash();
        $logs = get_recent_logs(8);
        $logItems = '';
        foreach ($logs as $entry) {
            $logItems .= '<div class="list-item"><span class="muted">' . htmlspecialchars($entry, ENT_QUOTES) . '</span></div>';
        }
        if ($logItems === '') {
            $logItems = '<div class="list-item"><span class="muted">Aucun log récent.</span></div>';
        }
        respond_html(
            'Admin',
            '<section class="grid cols-2">
              <article class="card"><h3>Maintenance</h3><p class="muted">Etat du service et logs.</p><div class="status ' . ($maintenance ? 'error' : 'success') . '">' . ($maintenance ? 'Maintenance activée' : 'Service OK') . '</div><form method="post" class="list" style="margin-top: 12px;"><input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES) . '"><label class="list-item"><span>Mode maintenance</span><input type="checkbox" name="maintenance" value="on" ' . ($maintenance ? 'checked' : '') . '></label><button class="button secondary" type="submit">Enregistrer</button></form></article>
              <article class="card"><h3>Actions rapides</h3><p class="muted">Réinitialiser les crédits d\'un compte.</p><input class="input" placeholder="ID utilisateur"><button class="button secondary" type="button">Réinitialiser</button><p class="muted">Version: ' . htmlspecialchars($version, ENT_QUOTES) . '</p></article>
            </section>
            <section class="grid cols-2">
              <article class="card"><h3>Healthchecks</h3><p class="muted">Endpoints disponibles.</p><div class="list"><div class="list-item"><span>/api/health</span><span class="badge">200</span></div><div class="list-item"><span>/api/wallet</span><span class="badge">401</span></div></div></article>
              <article class="card"><h3>Smoke tests</h3><p class="muted">Exécuter sur serveur :</p><div class="list"><div class="list-item"><span>./tools/smoke.sh https://votre-domaine.tld</span></div></div></article>
            </section>
            <section class="modal"><h3>Logs récents</h3><div class="list">' . $logItems . '</div></section>'
        );
        break;
    case '/login':
        if ($method === 'POST') {
            require_csrf();
            $_SESSION['user_id'] = 1;
            respond_html('Connexion', '<section class="card"><h1>Connexion</h1><p class="muted">Connexion simulée.</p><a class="button secondary" href="/lobby">Retour au lobby</a></section>');
            break;
        }
        $token = csrf_token();
        respond_html(
            'Connexion',
            '<section class="card"><h1>Connexion</h1><form method="post"><input class="input" type="email" name="email" placeholder="Email" required><input class="input" type="password" name="password" placeholder="Mot de passe" required><input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES) . '"><button class="button" type="submit">Se connecter</button></form></section>'
        );
        break;
    case '/register':
        if ($method === 'POST') {
            require_csrf();
            respond_html('Inscription', '<section class="card"><h1>Inscription</h1><p class="muted">Inscription simulée.</p><a class="button secondary" href="/login">Se connecter</a></section>');
            break;
        }
        $token = csrf_token();
        respond_html(
            'Inscription',
            '<section class="card"><h1>Inscription</h1><form method="post"><input class="input" type="text" name="username" placeholder="Pseudo" required><input class="input" type="email" name="email" placeholder="Email" required><input class="input" type="password" name="password" placeholder="Mot de passe" required><input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES) . '"><button class="button" type="submit">Créer un compte</button></form></section>'
        );
        break;
    case '/logout':
        if ($method !== 'POST') {
            respond_html('Déconnexion', '<section class="card"><h1>Déconnexion</h1><p class="muted">Utilisez POST pour vous déconnecter.</p></section>', 405);
            break;
        }
        require_csrf();
        session_destroy();
        respond_html('Déconnexion', '<section class="card"><h1>Déconnexion</h1><p class="muted">À bientôt.</p><a class="button secondary" href="/">Accueil</a></section>');
        break;
    case '/install':
        respond_html('Installation', '<section class="card"><h1>Installation</h1><p class="muted">Assistant d\'installation à définir.</p></section>');
        break;
    case '/about':
        respond_html(
            'À propos',
            '<section class="card"><h1>À propos</h1><p class="muted">Gaming Star est un jeu gratuit. La monnaie interne est fictive (crédits) et ne permet aucun retrait réel.</p><p class="muted">Aucun paiement réel n\'est disponible, et aucune valeur monétaire n\'est associée aux crédits.</p><a class="button secondary" href="/">Accueil</a></section>'
        );
        break;
    case '/api/health':
        respond_json(['status' => 'ok', 'time' => date(DATE_ATOM)]);
        break;
    case '/api/auth/login':
        if ($method !== 'POST') {
            respond_json(['error' => 'Méthode non autorisée.'], 405);
            break;
        }
        $_SESSION['user_id'] = 1;
        respond_json(['status' => 'ok', 'user_id' => 1]);
        break;
    case '/api/credits/balance':
        if ($method !== 'GET') {
            respond_json(['error' => 'Méthode non autorisée.'], 405);
            break;
        }
        $userId = current_user_id();
        $balance = $userId ? $ledger->getBalance($userId) : 0;
        respond_json(['user_id' => $userId, 'balance' => $balance]);
        break;
    case '/api/credits/ledger':
        if ($method !== 'GET') {
            respond_json(['error' => 'Méthode non autorisée.'], 405);
            break;
        }
        $userId = require_auth();
        $limit = (int) ($_GET['limit'] ?? 50);
        $limit = max(1, min($limit, 100));
        respond_json([
            'user_id' => $userId,
            'transactions' => $ledger->getHistory($userId, $limit),
        ]);
        break;
    case '/api/wallet':
        if ($method !== 'GET') {
            respond_json(['error' => 'Méthode non autorisée.'], 405);
            break;
        }
        rate_limit('api_wallet', 30, 60);
        $userId = require_auth();
        respond_json([
            'user_id' => $userId,
            'balance' => $ledger->getBalance($userId),
            'last_transactions' => $ledger->getLastTransactions($userId, 5),
        ]);
        break;
    case '/api/history':
        if ($method !== 'GET') {
            respond_json(['error' => 'Méthode non autorisée.'], 405);
            break;
        }
        rate_limit('api_history', 30, 60);
        $userId = require_auth();
        respond_json([
            'user_id' => $userId,
            'transactions' => $ledger->getHistory($userId, 50),
        ]);
        break;
    case '/api/bonus/daily':
        if ($method !== 'POST') {
            respond_json(['error' => 'Méthode non autorisée.'], 405);
            break;
        }
        rate_limit('api_bonus_daily', 5, 60);
        $userId = require_auth();
        $today = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if ($ledger->hasDailyBonus($userId, $today)) {
            respond_json(['error' => 'Bonus déjà reçu.'], 429);
            break;
        }
        $transaction = $ledger->addTransaction($userId, 'bonus_daily', 100, ['source' => 'daily_bonus']);
        respond_json([
            'transaction' => $transaction,
            'balance' => $ledger->getBalance($userId),
        ]);
        break;
    default:
        if (preg_match('#^/api/games/([a-z0-9_-]+)/play$#', $path, $matches)) {
            if ($method !== 'POST') {
                respond_json(['error' => 'Méthode non autorisée.'], 405);
                break;
            }
            rate_limit('api_game_play', 30, 60);
            $userId = require_auth();
            $idempotencyKey = require_idempotency_key();
            $cached = idempotency_response($idempotencyKey);
            if ($cached !== null) {
                respond_json($cached);
                break;
            }
            $game = $matches[1];
            $allowedGames = ['crash', 'mines', 'slots', 'roulette', 'blackjack'];
            if (!in_array($game, $allowedGames, true)) {
                respond_json(['error' => 'Jeu inconnu.'], 404);
                break;
            }
            $payload = parse_json_body();
            $bet = (int) ($payload['bet'] ?? 0);
            $minBet = 5;
            $maxBet = 500;
            if ($bet < $minBet || $bet > $maxBet) {
                respond_json(['error' => 'Mise invalide.', 'min' => $minBet, 'max' => $maxBet], 422);
                break;
            }
            $balance = $ledger->getBalance($userId);
            if ($balance < $bet) {
                respond_json(['error' => 'Solde insuffisant.'], 422);
                break;
            }
            $ledger->addTransaction($userId, 'game_bet', -$bet, ['game' => $game]);
            $multiplier = random_int(0, 100) < 45 ? 0 : random_int(1, 4);
            $payout = $bet * $multiplier;
            if ($payout > 0) {
                $ledger->addTransaction($userId, 'game_win', $payout, ['game' => $game, 'multiplier' => $multiplier]);
            }
            $response = [
                'game' => $game,
                'bet' => $bet,
                'payout' => $payout,
                'balance' => $ledger->getBalance($userId),
            ];
            store_idempotency_response($idempotencyKey, $response);
            respond_json($response);
            break;
        }
        if (str_starts_with($path, '/api/')) {
            respond_json(['error' => 'Endpoint API inconnu.'], 404);
            break;
        }
        respond_html('Introuvable', '<h1>Page introuvable</h1><p class="muted">Essayez de revenir à l\'accueil.</p>', 404);
        break;
}
