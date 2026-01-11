# Déploiement YunoHost

## Prérequis
- Node.js 20 recommandé pour le backend.
- Nginx en reverse proxy.
- Dossier d’app : `/var/www/gaming-star` (adapter selon votre installation).

## Variables d’environnement (exemple)
```
NODE_ENV=production
PORT=5000
JWT_SECRET=change_me
MONGO_URL=mongodb://localhost:27017/gaming_star
LOG_PATH=/var/log/gaming-star/app.log
```

## Backend en systemd
Créer `/etc/systemd/system/gaming-star.service` :
```
[Unit]
Description=Gaming Star Backend
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/gaming-star
EnvironmentFile=/var/www/gaming-star/.env
ExecStart=/usr/bin/node server.js
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Activer le service :
```
systemctl daemon-reload
systemctl enable --now gaming-star.service
```

## Nginx reverse proxy
Exemple de snippet Nginx (adapter domaine/chemins) :
```
location /api/ {
    proxy_pass http://127.0.0.1:5000;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}

location /socket.io/ {
    proxy_pass http://127.0.0.1:5000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}

location / {
    root /var/www/gaming-star/public;
    try_files $uri /index.php?$query_string;
}
```

## Frontend statique
- Build frontend dans `public/` (ou un répertoire dédié).
- Alternative : servir le frontend par le backend Node.

## Logs
- Logs backend : `LOG_PATH` (ex: `/var/log/gaming-star/app.log`).
- Logs Nginx : `/var/log/nginx/`.
