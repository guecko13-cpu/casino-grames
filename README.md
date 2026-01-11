# Casino Games Fun

Version "argent fictif" de la plateforme casino.

## Démarrage rapide
1. Installer Node.js 20+ et MongoDB.
2. Copier `.env.example` vers `.env` et ajuster les valeurs.
3. Installer les dépendances puis lancer :
   ```bash
   npm install
   npm run start
   ```
4. Ouvrir `http://localhost:3000` (frontend) ou `http://localhost:3000/admin`.

## Endpoints API
- `GET /api/health`
- `GET /api/version`
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/user/me`
- `GET /api/wallet`
- `GET /api/wallet/history`
- `POST /api/wallet/credit`
- `POST /api/wallet/debit`

## Déploiement
Le packaging YunoHost est disponible dans `casino-games-fun_ynh/`.
