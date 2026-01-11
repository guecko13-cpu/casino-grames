# casino-grames

## DEV quickstart
1. Installer PHP 8.4+.
2. Lancer le serveur local :
   ```
   php -S 0.0.0.0:8000 -t public
   ```
3. Ouvrir `http://localhost:8000`.

## PROD build
- Déployer derrière Nginx + PHP-FPM.
- Exemple Nginx : `docs/nginx.conf`.

## Qualité
- Lint PHP : `./tools/lint_php.sh` ou `./tools/lint.sh`.
- Smoke tests : `./tools/smoke.sh https://votre-domaine.tld`.
- Conflits : `./tools/check-conflicts.sh`.
