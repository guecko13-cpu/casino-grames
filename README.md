# Casino Games Fun (YunoHost package)

Package YunoHost v2.1 pour installer la version "argent fictif" via le panel.

## Installation via le panel
1. YunoHost → Applications → Installer une application personnalisée.
2. URL Git du package :
   ```
   https://github.com/guecko13-cpu/casino-grames.git#ynh
   ```
3. Suivre l'assistant pour choisir le domaine et le chemin.

## Fonctionnement (Option B)
- Nginx sert le frontend statique depuis `__INSTALL_DIR__/www`.
- Nginx sert l'admin statique depuis `__INSTALL_DIR__/www-admin`.
- `/api/` (et `/socket.io/`) sont reverse-proxy vers le service Node local.

## Smoke test
Après installation, exécuter sur le serveur :
```
/usr/local/bin/ynh-app-scripts --app casino-games-fun --run smoke
```
