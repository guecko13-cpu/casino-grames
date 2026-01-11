# Administration - Casino Games Fun

## Installation
- Configurer le domaine et le chemin dans l'interface YunoHost.
- Le service installe MongoDB et crée une base dédiée.
- Le backend tourne via systemd et sert l'API sur `/api`.

## Mise à jour
- Utiliser la procédure de mise à jour YunoHost standard.
- Le script recharge les sources, les dépendances et redémarre le service.

## Logs
- Logs applicatifs : `journalctl -u casino-games-fun -f`
- Logs Nginx : `/var/log/nginx/casino-games-fun.access.log` et `casino-games-fun.error.log`

## Healthchecks
- `GET /api/health` : état global + MongoDB.
- `GET /api/version` : version déployée.
