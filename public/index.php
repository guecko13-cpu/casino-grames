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
        respond_html(
            'Admin',
            '<section class="grid cols-2">
              <article class="card"><h3>Maintenance</h3><p class="muted">Etat du service et logs.</p><div class="status success">Service OK</div></article>
              <article class="card"><h3>Actions rapides</h3><p class="muted">Réinitialiser les crédits d\'un compte.</p><input class="input" placeholder="ID utilisateur"><button class="button secondary" type="button">Réinitialiser</button></article>
            </section>
            <section class="modal"><h3>Message système</h3><p class="muted">Les actions admin sont journalisées.</p></section>'
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
    case '/api/wallet':
        if ($method !== 'GET') {
            respond_json(['error' => 'Méthode non autorisée.'], 405);
            break;
        }
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
        if (str_starts_with($path, '/api/')) {
            respond_json(['error' => 'Endpoint API inconnu.'], 404);
            break;
        }
        respond_html('Introuvable', '<h1>Page introuvable</h1><p class="muted">Essayez de revenir à l\'accueil.</p>', 404);
        break;
}
