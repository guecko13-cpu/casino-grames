# Audit - Gaming Star (Phase A)

## Arborescence actuelle
```
.
├── AUDIT.md
├── LICENSE
├── README.md
├── docs
│   └── nginx.conf
├── public
│   └── index.php
└── tools
    ├── check_conflicts.sh
    ├── lint_php.sh
    └── smoke.sh
```

## Routes et pages
| Route | Méthode | Statut actuel | Notes |
| --- | --- | --- | --- |
| `/` | GET | OK | Page d'accueil (front controller). |
| `/login` | GET/POST | OK | Formulaire + CSRF simulé. |
| `/register` | GET/POST | OK | Formulaire + CSRF simulé. |
| `/logout` | POST | OK | Logout simulé. |
| `/install` | GET | OK | Placeholder d'installation. |

## API Endpoints
| Endpoint | Méthode | Statut actuel | Notes |
| --- | --- | --- | --- |
| `/api/health` | GET | OK | Healthcheck JSON. |
| `/api/*` | GET/POST | 404 JSON | Réponse JSON uniforme pour endpoints inconnus. |

## Points cassés / risques 404
- Aucun routeur PHP/NGINX existant dans l'ancien dépôt. Toutes les routes requises pouvaient tomber en 404 si le front controller n'était pas installé.
- Pas de config Nginx documentée pour `try_files` vers `index.php`.
- Pas de service worker détecté.

## Plan de PRs proposées
1. **PR A - Stabilisation du routing**
   - Ajouter `public/index.php` (front controller) + routes critiques.
   - Ajouter exemple `docs/nginx.conf` pour `try_files`.
   - Ajouter scripts `tools/*` (lint, smoke, conflits).
2. **PR B - Dé-crypto + ledger**
   - Remplacer tous les termes crypto par crédits.
   - Ajouter ledger (transactions) + calcul de solde.
   - Exposer API `/api/ledger`, `/api/balance`.
3. **PR C - UI mobile-first**
   - Design system CSS + pages clés.
4. **PR D - Admin & health**
   - Maintenance/logs/healthchecks/settings.
5. **PR E - Docs & CI**
   - README d'installation, scripts CI, validations.
