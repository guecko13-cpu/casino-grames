# Modernization Plan

## Objectifs
- Stabiliser le routing et garantir des routes “pretty” stables.
- Conserver une base PHP simple et sécurisée (sessions, CSRF, validation).
- Utiliser une monnaie fictive “credits” avec ledger.
- Outils qualité : lint, smoke tests, détection de conflits.

## Inventaire actuel
- **Frontend** : rendu côté serveur via `public/index.php`.
- **Backend** : PHP 8.4, sessions, endpoints JSON minimalistes.
- **Données** : SQLite local pour le ledger (`data/credits.sqlite`).
- **Logs** : `data/app.log`.

## Plan de modernisation (PRs)
1. **PR1 Audit + outillage**
   - Documenter l’architecture et les endpoints.
   - Ajouter scripts `tools/` (lint/smoke/conflicts).
2. **PR2 Stabilisation routes**
   - Garantir `/login`, `/register`, `/install`, `/api/*`.
3. **PR3 Dé-crypto**
   - Vocabulaire 100% crédits fictifs.
4. **PR4 Ledger & bonus**
   - Transactions, solde, bonus quotidien.
5. **PR5 UI**
   - Design system CSS, pages clés responsive.
6. **PR6 Déploiement**
   - Nginx/YunoHost + doc prod.

## Modèle “credits ledger”
- **Table** `transactions` : `id`, `user_id`, `type`, `amount` (signed), `meta` (json), `created_at`.
- **Solde** : somme de `amount` par `user_id`.
- **Transactions types** : `bonus_daily`, `game_bet`, `game_win`, `adjustment`.
