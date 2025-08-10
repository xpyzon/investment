-- products
CREATE TABLE IF NOT EXISTS products (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  type ENUM('stock','crypto','forex','fixed') NOT NULL,
  symbol VARCHAR(50) NULL,
  min_invest DECIMAL(24,8) DEFAULT 0,
  max_invest DECIMAL(24,8) DEFAULT 0,
  enabled TINYINT(1) DEFAULT 1,
  params JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- investments
CREATE TABLE IF NOT EXISTS investments (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  type ENUM('stock','crypto','forex','fixed') NOT NULL,
  units DECIMAL(24,8) NOT NULL DEFAULT 0,
  entry_price DECIMAL(24,8) NOT NULL DEFAULT 0,
  status ENUM('active','closed','pending') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- withdrawals (admin approval required)
CREATE TABLE IF NOT EXISTS withdrawals (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  amount DECIMAL(24,8) NOT NULL,
  currency VARCHAR(10) NOT NULL,
  address VARCHAR(255) NOT NULL,
  txid VARCHAR(191) NULL,
  status ENUM('pending','approved','rejected','broadcast','completed') DEFAULT 'pending',
  fees DECIMAL(24,8) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);