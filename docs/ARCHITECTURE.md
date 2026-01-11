# Architecture

## Vue d’ensemble
- **Entrée web** : `public/index.php` (front controller).
- **Rendu HTML** : pages simples générées côté serveur.
- **API JSON** : endpoints `/api/*` exposés par le routeur PHP.
- **Données** : SQLite local (`data/credits.sqlite`).
- **Logs** : `data/app.log`.

## Flux principaux
1. Navigations “pretty” → Nginx `try_files` → `index.php`.
2. API JSON → `index.php` → réponse JSON.
3. Ledger → `src/ledger.php` → transactions/solde.

## Exécution
- **DEV** : `php -S 0.0.0.0:8000 -t public`.
- **PROD** : Nginx + PHP-FPM avec `try_files`.
