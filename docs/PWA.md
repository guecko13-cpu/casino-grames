# PWA / Service Worker

## État actuel
- Aucun Service Worker n'est présent dans ce dépôt.
- Pour éviter toute régression sur le routing (login/logout, redirects), aucun SW n'est activé par défaut.

## Recommandation si un SW est ajouté
- **Ne pas intercepter les navigations de documents** (`request.mode === 'navigate'`).
- Laisser le serveur répondre aux routes (front controller) pour éviter les 404 Nginx ou des redirections cassées.
- Si un cache est nécessaire, limiter aux assets statiques (CSS/JS/images) et contourner les requêtes d'authentification.

## Validation attendue
- Smoke tests HTTP via `./tools/smoke.sh`.
- Navigation manuelle : `/login` puis `/logout` (POST) doivent fonctionner sans mise en cache agressive.
