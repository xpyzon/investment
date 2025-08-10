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

## Auth (temporary for demo)
- Admin endpoints: provide `X-Admin-Key: <ADMIN_API_KEY>` header
- User endpoints: provide `X-User-Id: <numeric user id>` header (seed creates id=2)

## Endpoints
- POST /admin/wallets
- PUT /admin/wallets/{id}
- PATCH /admin/wallets/{id}/toggle
- POST /admin/wallets/{id}/assign
- POST /admin/wallets/{id}/credit-manual
- GET /user/wallets
- POST /user/wallets/{wallet_admin_id}/generate-address
- POST /wallets/webhook (HMAC SHA256 via `X-Webhook-Signature`)

See `public/index.php` and controllers in `src/Controllers` for payloads and behavior.

## Notes
- Address derivation using `xpub:` prefix is mocked for demo (deterministic, not real on-chain). Integrate a custody provider or node for production.
- Webhook validates `hash_hmac('sha256', raw_body, WEBHOOK_SECRET)`.
- All queries use prepared statements.