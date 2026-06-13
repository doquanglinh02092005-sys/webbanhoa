CREATE DATABASE IF NOT EXISTS web_ban_hoa
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE web_ban_hoa;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(20) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  loyalty_points INT UNSIGNED NOT NULL DEFAULT 0,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  status ENUM('active', 'hidden') NOT NULL DEFAULT 'active',
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_categories_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NULL,
  name VARCHAR(180) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  sku VARCHAR(60) NOT NULL UNIQUE,
  color VARCHAR(60) NULL,
  occasion VARCHAR(40) NULL,
  short_description VARCHAR(255) NULL,
  description TEXT NULL,
  price BIGINT UNSIGNED NOT NULL DEFAULT 0,
  compare_price BIGINT UNSIGNED NOT NULL DEFAULT 0,
  stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  image_url VARCHAR(500) NULL,
  badge VARCHAR(80) NULL,
  status ENUM('active', 'hidden', 'draft') NOT NULL DEFAULT 'active',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_products_status (status),
  INDEX idx_products_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(30) NOT NULL UNIQUE,
  user_id BIGINT UNSIGNED NULL,
  customer_name VARCHAR(120) NOT NULL,
  customer_email VARCHAR(190) NULL,
  customer_phone VARCHAR(20) NOT NULL,
  delivery_address VARCHAR(500) NOT NULL,
  delivery_date DATE NULL,
  note TEXT NULL,
  subtotal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  shipping_fee BIGINT UNSIGNED NOT NULL DEFAULT 0,
  discount_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
  points_used INT UNSIGNED NOT NULL DEFAULT 0,
  points_discount BIGINT UNSIGNED NOT NULL DEFAULT 0,
  total_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
  payment_method ENUM('cod', 'momo') NOT NULL DEFAULT 'cod',
  status ENUM('pending', 'confirmed', 'preparing', 'shipping', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  payment_status ENUM('unpaid', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
  payment_reference VARCHAR(100) NULL,
  momo_order_id VARCHAR(100) NULL UNIQUE,
  momo_request_id VARCHAR(100) NULL,
  momo_trans_id BIGINT UNSIGNED NULL,
  momo_result_code INT NULL,
  points_earned INT UNSIGNED NOT NULL DEFAULT 0,
  points_awarded_at DATETIME NULL,
  paid_at DATETIME NULL,
  stock_released_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_orders_status (status),
  INDEX idx_orders_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loyalty_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  points INT UNSIGNED NOT NULL,
  type ENUM('earn', 'redeem', 'refund') NOT NULL DEFAULT 'earn',
  status ENUM('active', 'reversed') NOT NULL DEFAULT 'active',
  description VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_loyalty_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_loyalty_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_loyalty_order_type (order_id, type),
  INDEX idx_loyalty_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NULL,
  product_name VARCHAR(180) NOT NULL,
  sku VARCHAR(60) NULL,
  image_url VARCHAR(500) NULL,
  unit_price BIGINT UNSIGNED NOT NULL DEFAULT 0,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  line_total BIGINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
