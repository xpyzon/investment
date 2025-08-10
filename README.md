# Native PHP Investment Platform (Wallets, Investments, Withdrawals)

Key features included:
- Admin-managed crypto deposit wallets (auto-assign to all users)
- User wallet listing and address generation (global or xpub-like)
- Webhook for deposit confirmations (HMAC SHA256)
- Products and invest endpoint
- Withdrawals with manual admin approval/rejection
- HTML emails via PHPMailer

## New endpoints
- GET /products
- POST /invest { product_id, amount }
- POST /withdrawals/request { amount, currency, address }
- GET /admin/withdrawals (admin)
- POST /admin/withdrawals/{id}/approve (admin)
- POST /admin/withdrawals/{id}/reject (admin)

Emails are HTML. Configure SMTP in `.env` (MAIL_* keys).