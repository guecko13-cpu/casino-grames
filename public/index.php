<?php

declare(strict_types=1);

session_start();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

function respond_html(string $title, string $body, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title}</title>
  <style>
    :root { color-scheme: light; font-family: system-ui, -apple-system, sans-serif; }
    body { margin: 0; padding: 24px; background: #f6f7fb; color: #1d1f2a; }
    main { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
    h1 { margin-top: 0; }
    a { color: #4a4df2; text-decoration: none; }
    form { display: grid; gap: 12px; }
    input, button { padding: 12px 14px; border-radius: 10px; border: 1px solid #d2d6e0; font-size: 1rem; }
    button { background: #4a4df2; color: #fff; border: none; cursor: pointer; }
    .muted { color: #5c6275; }
  </style>
</head>
<body>
  <main>
    {$body}
  </main>
</body>
</html>
HTML;
}

function respond_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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
            '<h1>Gaming Star</h1><p class="muted">Bienvenue sur le casino fictif.</p><p><a href="/login">Connexion</a> · <a href="/register">Inscription</a></p>'
        );
        break;
    case '/login':
        if ($method === 'POST') {
            require_csrf();
            respond_html('Connexion', '<h1>Connexion</h1><p class="muted">Connexion simulée.</p><p><a href="/">Retour</a></p>');
            break;
        }
        $token = csrf_token();
        respond_html(
            'Connexion',
            '<h1>Connexion</h1><form method="post"><input type="email" name="email" placeholder="Email" required><input type="password" name="password" placeholder="Mot de passe" required><input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES) . '"><button type="submit">Se connecter</button></form>'
        );
        break;
    case '/register':
        if ($method === 'POST') {
            require_csrf();
            respond_html('Inscription', '<h1>Inscription</h1><p class="muted">Inscription simulée.</p><p><a href="/login">Se connecter</a></p>');
            break;
        }
        $token = csrf_token();
        respond_html(
            'Inscription',
            '<h1>Inscription</h1><form method="post"><input type="text" name="username" placeholder="Pseudo" required><input type="email" name="email" placeholder="Email" required><input type="password" name="password" placeholder="Mot de passe" required><input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES) . '"><button type="submit">Créer un compte</button></form>'
        );
        break;
    case '/logout':
        if ($method !== 'POST') {
            respond_html('Déconnexion', '<h1>Déconnexion</h1><p class="muted">Utilisez POST pour vous déconnecter.</p>', 405);
            break;
        }
        require_csrf();
        session_destroy();
        respond_html('Déconnexion', '<h1>Déconnexion</h1><p class="muted">À bientôt.</p><p><a href="/">Accueil</a></p>');
        break;
    case '/install':
        respond_html('Installation', '<h1>Installation</h1><p class="muted">Assistant d\'installation à définir.</p>');
        break;
    case '/api/health':
        respond_json(['status' => 'ok', 'time' => date(DATE_ATOM)]);
        break;
    default:
        if (str_starts_with($path, '/api/')) {
            respond_json(['error' => 'Endpoint API inconnu.'], 404);
            break;
        }
        respond_html('Introuvable', '<h1>Page introuvable</h1><p class="muted">Essayez de revenir à l\'accueil.</p>', 404);
        break;
}
