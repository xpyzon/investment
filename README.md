# Native PHP Investment Platform (Wallet Admin APIs)

Minimal native PHP scaffold implementing admin-managed crypto deposit wallets with auto-assignment to all users, user listing and address generation, and a generic deposit webhook.

## Requirements
- PHP 8.1+
- MySQL/MariaDB (or SQLite for quick start)
- Composer

## Setup
1. Copy env and edit credentials:
   ```bash
   cp .env.example .env
   # edit DB_*, ADMIN_API_KEY, WEBHOOK_SECRET
   ```
2. Install deps:
   ```bash
   composer install
   ```
3. Create database and run migrations:
   ```bash
   php scripts/migrate.php
   php scripts/seed.php
   ```
4. Run the server:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```

## Admin UI (Tailwind, black & white)
- Requires header on every request: `X-Admin-Key: <ADMIN_API_KEY>`
- Pages:
  - GET `/admin/ui/wallets` — list wallets, toggle, assign-all
  - GET `/admin/ui/wallets/create` — form to create
  - POST `/admin/ui/wallets/create` — submit create
  - GET `/admin/ui/wallets/{id}/edit` — edit form
  - POST `/admin/ui/wallets/{id}/edit` — update
  - POST `/admin/ui/wallets/{id}/toggle` — enable/disable
  - POST `/admin/ui/wallets/{id}/assign` — assign to all users

Tip: Use a browser extension to inject the `X-Admin-Key` header, or curl with `-H`.

## API (JSON)
- Admin
  - POST /admin/wallets
  - PUT /admin/wallets/{id}
  - PATCH /admin/wallets/{id}/toggle
  - POST /admin/wallets/{id}/assign
  - POST /admin/wallets/{id}/credit-manual
- User
  - GET /user/wallets
  - POST /user/wallets/{wallet_admin_id}/generate-address
- Webhook
  - POST /wallets/webhook (HMAC SHA256 via `X-Webhook-Signature`)

## Notes
- Address derivation using `xpub:` prefix is mocked for demo (deterministic, not real on-chain). Integrate a custody provider or node for production.
- Webhook validates `hash_hmac('sha256', raw_body, WEBHOOK_SECRET)`.
- All queries use prepared statements.