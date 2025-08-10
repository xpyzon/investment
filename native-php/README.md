# Native PHP Invest Platform

- Document root: `native-php/public`
- Installer: visit `/install` (GET shows a form, POST runs the setup)

Quick start:

1. Start built-in server:

   ```sh
   php -S 0.0.0.0:8080 -t native-php/public
   ```

2. Open `http://localhost:8080/install` and submit the form to create the SQLite DB and an admin user.

API routes (Bearer auth with token from /api/login):
- POST `/api/login` { email, password }
- POST `/api/logout`
- GET `/api/user`
- Admin:
  - GET `/api/admin/wallets`
  - POST `/api/admin/wallets`
  - PUT `/api/admin/wallets/{id}`
  - PATCH `/api/admin/wallets/{id}/toggle`
  - POST `/api/admin/wallets/{id}/assign`
  - POST `/api/admin/wallets/{id}/credit-manual`
- User:
  - GET `/api/user/wallets`
  - POST `/api/user/wallets/{wallet_admin_id}/generate-address`
- Webhook:
  - POST `/api/wallets/webhook`