-- Users
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'investor',
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- API tokens
CREATE TABLE IF NOT EXISTS api_tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  token TEXT NOT NULL UNIQUE,
  abilities TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  expires_at TEXT,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Wallet admin
CREATE TABLE IF NOT EXISTS wallet_admin (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  currency TEXT NOT NULL,
  network TEXT NOT NULL,
  address_template TEXT,
  requires_tag INTEGER NOT NULL DEFAULT 0,
  tag_label TEXT,
  confirmations INTEGER NOT NULL DEFAULT 0,
  icon_url TEXT,
  is_enabled INTEGER NOT NULL DEFAULT 1,
  created_by INTEGER,
  updated_by INTEGER,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- User wallets
CREATE TABLE IF NOT EXISTS user_wallets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  wallet_admin_id INTEGER NOT NULL,
  deposit_address TEXT,
  deposit_tag TEXT,
  address_generated INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active',
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  UNIQUE(user_id, wallet_admin_id),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(wallet_admin_id) REFERENCES wallet_admin(id) ON DELETE CASCADE
);

-- Wallet admin changes (audit)
CREATE TABLE IF NOT EXISTS wallet_admin_changes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  wallet_admin_id INTEGER NOT NULL,
  admin_id INTEGER NOT NULL,
  change_type TEXT NOT NULL,
  change_payload TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(wallet_admin_id) REFERENCES wallet_admin(id) ON DELETE CASCADE,
  FOREIGN KEY(admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Deposits
CREATE TABLE IF NOT EXISTS deposits (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER,
  amount NUMERIC NOT NULL,
  currency TEXT NOT NULL,
  txid TEXT NOT NULL UNIQUE,
  address TEXT NOT NULL,
  network TEXT NOT NULL,
  status TEXT NOT NULL,
  fees NUMERIC NOT NULL DEFAULT 0,
  confirmations INTEGER NOT NULL DEFAULT 0,
  wallet_admin_id INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY(wallet_admin_id) REFERENCES wallet_admin(id) ON DELETE CASCADE
);

-- Transactions
CREATE TABLE IF NOT EXISTS transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  amount NUMERIC NOT NULL,
  currency TEXT NOT NULL,
  meta TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);