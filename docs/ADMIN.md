# Administration

## Panneau admin
- URL : `/admin`
- Actions disponibles :
  - Activer/désactiver le mode maintenance (flag `data/maintenance.flag`).
  - Consulter la version (hash git si disponible).
  - Voir les logs récents (`data/app.log`).
  - Instructions pour lancer les smoke tests sur le serveur.

## Mode maintenance
- Les pages publiques sont bloquées avec une réponse 503.
- Exceptions autorisées : `/admin`, `/login`, `/logout`, `/about`, `/install`, `/api/health`, `/assets/*`.

## Endpoints sensibles et rate limit
- `/api/wallet` : 30 requêtes/minute (par session).
- `/api/history` : 30 requêtes/minute (par session).
- `/api/bonus/daily` : 5 requêtes/minute (par session).

## Smoke tests (serveur)
```
./tools/smoke.sh https://votre-domaine.tld
```
