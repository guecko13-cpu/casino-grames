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
## Pages publiques
| Route | Méthode | Statut actuel | Notes |
| --- | --- | --- | --- |
| `/` | GET | OK | Page d'accueil (front controller). |
| `/login` | GET/POST | OK | Formulaire + CSRF simulé. |
| `/register` | GET/POST | OK | Formulaire + CSRF simulé. |
| `/logout` | POST | OK | Logout simulé. |
| `/install` | GET | OK | Placeholder d'installation. |

## Endpoints API
| Endpoint | Méthode | Statut actuel | Notes |
| --- | --- | --- | --- |
| `/api/health` | GET | OK | Healthcheck JSON. |
| `/api/*` | GET/POST | 404 JSON | Réponse JSON uniforme pour endpoints inconnus. |

## Routing & rewrites
- **Nginx** : exemple de `try_files $uri /index.php?$query_string` documenté dans `docs/nginx.conf`.
- **Front controller** : le routing est centralisé dans `public/index.php` via un `switch` sur le `REQUEST_URI`.

## État actuel des problèmes
- **Avant stabilisation** : 404 observables sur `/login`, `/register`, `/api/*`, `/install` si Nginx ne renvoie pas vers `index.php` (absence de front controller).
- **Après stabilisation** : routes critiques servies par `public/index.php`, 404 standard pour endpoints `/api/*` inconnus.

## Service Worker / PWA
- Aucun Service Worker détecté à ce stade.
- Risque potentiel : si un SW est ajouté plus tard, il pourrait casser les redirects ou le caching des routes d'authentification.

## Plan de PRs proposées (ordre recommandé)
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

## Checklists de validation
- [ ] `/login`, `/register`, `/install`, `/api/health` répondent en 200 via `tools/smoke.sh`.
- [ ] `php -l` passe sur tous les fichiers PHP via `tools/lint_php.sh`.
- [ ] Aucun marqueur de conflit via `tools/check_conflicts.sh`.
