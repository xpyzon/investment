-- migrations table
CREATE TABLE IF NOT EXISTS migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- users
CREATE TABLE IF NOT EXISTS users (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','support','investor','auditor') DEFAULT 'investor',
  kyc_status ENUM('none','pending','approved','rejected') DEFAULT 'none',
  twofa_secret VARCHAR(64) NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- wallet_admin
CREATE TABLE IF NOT EXISTS wallet_admin (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  currency VARCHAR(10) NOT NULL,
  network VARCHAR(50) NOT NULL,
  address_template TEXT NULL,
  requires_tag TINYINT(1) DEFAULT 0,
  tag_label VARCHAR(50) NULL,
  confirmations INT DEFAULT 3,
  icon_url VARCHAR(255) NULL,
  is_enabled TINYINT(1) DEFAULT 1,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- user_wallets
CREATE TABLE IF NOT EXISTS user_wallets (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  wallet_admin_id BIGINT NOT NULL,
  deposit_address VARCHAR(255) NULL,
  deposit_tag VARCHAR(100) NULL,
  address_generated TINYINT(1) DEFAULT 0,
  status ENUM('active','disabled') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY user_wallet_unique (user_id, wallet_admin_id),
  INDEX idx_wallet_admin_id (wallet_admin_id)
);

-- deposits
CREATE TABLE IF NOT EXISTS deposits (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NULL,
  amount DECIMAL(24,8) NOT NULL,
  currency VARCHAR(10) NOT NULL,
  txid VARCHAR(191) NOT NULL UNIQUE,
  address VARCHAR(255) NULL,
  network VARCHAR(50) NULL,
  status ENUM('pending','confirmed','rejected') DEFAULT 'pending',
  fees DECIMAL(24,8) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- transactions
CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  type ENUM('deposit','withdraw','buy','sell','fee') NOT NULL,
  amount DECIMAL(24,8) NOT NULL,
  currency VARCHAR(10) NOT NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);