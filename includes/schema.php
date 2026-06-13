<?php
declare(strict_types=1);

function ensure_shop_schema(mysqli $connection): void
{
    $productsTableExists = $connection->query("SHOW TABLES LIKE 'products'")->num_rows > 0;
    if ($connection->query("SHOW TABLES LIKE 'users'")->num_rows > 0) {
        shop_add_column_if_missing($connection, 'users', 'loyalty_points', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER status');
    }
    $connection->query("CREATE TABLE IF NOT EXISTS categories (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(120) NOT NULL UNIQUE,
        description TEXT NULL,
        status ENUM('active', 'hidden') NOT NULL DEFAULT 'active',
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_categories_status_sort (status, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->query("CREATE TABLE IF NOT EXISTS products (
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
        INDEX idx_products_category (category_id),
        INDEX idx_products_featured (featured)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    shop_add_column_if_missing($connection, 'products', 'occasion', 'VARCHAR(40) NULL AFTER color');
    $connection->query("UPDATE products SET occasion=CASE sku
        WHEN 'LF-001' THEN 'birthday' WHEN 'LF-002' THEN 'wedding' WHEN 'LF-003' THEN 'love'
        WHEN 'LF-004' THEN 'congratulations' WHEN 'LF-005' THEN 'love' WHEN 'LF-006' THEN 'seasonal'
        WHEN 'LF-007' THEN 'birthday' WHEN 'LF-008' THEN 'congratulations' WHEN 'LF-009' THEN 'bouquet'
        WHEN 'LF-010' THEN 'love' WHEN 'LF-011' THEN 'wedding' WHEN 'LF-012' THEN 'basket'
        ELSE occasion END WHERE occasion IS NULL OR occasion=''");

    $connection->query("CREATE TABLE IF NOT EXISTS orders (
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
        INDEX idx_orders_created (created_at),
        INDEX idx_orders_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    shop_add_column_if_missing($connection, 'orders', 'points_used', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER discount_amount');
    shop_add_column_if_missing($connection, 'orders', 'points_discount', 'BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER points_used');
    shop_add_column_if_missing($connection, 'orders', 'payment_method', "ENUM('cod', 'momo') NOT NULL DEFAULT 'cod' AFTER total_amount");
    shop_add_column_if_missing($connection, 'orders', 'payment_reference', 'VARCHAR(100) NULL AFTER payment_status');
    shop_add_column_if_missing($connection, 'orders', 'momo_order_id', 'VARCHAR(100) NULL AFTER payment_reference');
    shop_add_column_if_missing($connection, 'orders', 'momo_request_id', 'VARCHAR(100) NULL AFTER momo_order_id');
    shop_add_column_if_missing($connection, 'orders', 'momo_trans_id', 'BIGINT UNSIGNED NULL AFTER momo_request_id');
    shop_add_column_if_missing($connection, 'orders', 'momo_result_code', 'INT NULL AFTER momo_trans_id');
    shop_add_column_if_missing($connection, 'orders', 'points_earned', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER momo_result_code');
    shop_add_column_if_missing($connection, 'orders', 'points_awarded_at', 'DATETIME NULL AFTER points_earned');
    shop_add_column_if_missing($connection, 'orders', 'paid_at', 'DATETIME NULL AFTER points_awarded_at');
    shop_add_column_if_missing($connection, 'orders', 'stock_released_at', 'DATETIME NULL AFTER paid_at');
    shop_add_unique_index_if_missing($connection, 'orders', 'uniq_orders_momo_order', 'momo_order_id');

    $connection->query("CREATE TABLE IF NOT EXISTS order_items (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->query("CREATE TABLE IF NOT EXISTS loyalty_transactions (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    shop_add_column_if_missing($connection, 'loyalty_transactions', 'type', "ENUM('earn', 'redeem', 'refund') NOT NULL DEFAULT 'earn' AFTER points");
    shop_add_composite_unique_index_if_missing($connection, 'loyalty_transactions', 'uniq_loyalty_order_type', 'order_id,type');
    shop_drop_single_column_unique_index($connection, 'loyalty_transactions', 'order_id');

    if (!$productsTableExists) {
        seed_shop_catalog($connection);
    }
}

function shop_add_column_if_missing(mysqli $connection, string $table, string $column, string $definition): void
{
    if (!in_array($table, ['users', 'products', 'orders', 'loyalty_transactions'], true)) {
        throw new InvalidArgumentException('Bảng migration không hợp lệ.');
    }
    $statement = $connection->prepare('SELECT COUNT(*) total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $statement->bind_param('ss', $table, $column);
    $statement->execute();
    if ((int) $statement->get_result()->fetch_assoc()['total'] === 0) {
        $connection->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function shop_drop_single_column_unique_index(mysqli $connection, string $table, string $column): void
{
    $statement = $connection->prepare('SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND NON_UNIQUE=0 AND INDEX_NAME<>\'PRIMARY\' GROUP BY INDEX_NAME HAVING COUNT(*)=1 AND MAX(COLUMN_NAME)=?');
    $statement->bind_param('ss', $table, $column);
    $statement->execute();
    foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $index = str_replace('`', '``', $row['INDEX_NAME']);
        $connection->query("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
    }
}

function shop_add_composite_unique_index_if_missing(mysqli $connection, string $table, string $index, string $columns): void
{
    $statement = $connection->prepare('SELECT COUNT(*) total FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?');
    $statement->bind_param('ss', $table, $index);
    $statement->execute();
    if ((int) $statement->get_result()->fetch_assoc()['total'] === 0) {
        $connection->query("ALTER TABLE `{$table}` ADD UNIQUE INDEX `{$index}` ({$columns})");
    }
}

function shop_add_unique_index_if_missing(mysqli $connection, string $table, string $index, string $column): void
{
    $statement = $connection->prepare('SELECT COUNT(*) total FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND NON_UNIQUE = 0');
    $statement->bind_param('ss', $table, $column);
    $statement->execute();
    if ((int) $statement->get_result()->fetch_assoc()['total'] === 0) {
        $connection->query("ALTER TABLE `{$table}` ADD UNIQUE INDEX `{$index}` (`{$column}`)");
    }
}

function seed_shop_catalog(mysqli $connection): void
{
    $productCount = (int) $connection->query('SELECT COUNT(*) AS total FROM products')->fetch_assoc()['total'];
    if ($productCount > 0) {
        return;
    }

    $connection->begin_transaction();
    try {
        $categoryNames = ['Tulip', 'Hồng', 'Cúc', 'Mẫu đơn'];
        $categoryStatement = $connection->prepare("INSERT IGNORE INTO categories (name, slug, sort_order) VALUES (?, ?, ?)");
        foreach ($categoryNames as $index => $name) {
            $slug = shop_slug($name);
            $sortOrder = ($index + 1) * 10;
            $categoryStatement->bind_param('ssi', $name, $slug, $sortOrder);
            $categoryStatement->execute();
        }

        $categoryMap = [];
        foreach ($connection->query('SELECT id, name FROM categories') as $category) {
            $categoryMap[$category['name']] = (int) $category['id'];
        }

        $products = [
            ['Tulip Hồng - Nàng Thơ', 'Tulip', 'Hồng', 'birthday', 690000, 790000, 'Bán chạy', 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=900&q=85'],
            ['Tulip Trắng - Chạm Khẽ', 'Tulip', 'Trắng', 'wedding', 850000, 950000, 'Mới', 'https://images.unsplash.com/photo-1523438885200-e635ba2c371e?auto=format&fit=crop&w=900&q=85'],
            ['Tulip Pastel - Mây Hồng', 'Tulip', 'Pastel', 'love', 1150000, 1290000, '-11%', 'https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=900&q=85'],
            ['Tulip Vàng - Nắng Sớm', 'Tulip', 'Vàng', 'congratulations', 620000, 720000, '-14%', 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=900&q=85'],
            ['Hồng Kem - Lời Thương', 'Hồng', 'Pastel', 'love', 780000, 850000, 'Yêu thích', 'https://images.unsplash.com/photo-1526047932273-341f2a7631f9?auto=format&fit=crop&w=900&q=85'],
            ['Tulip Tím - Secret Garden', 'Tulip', 'Tím', 'seasonal', 980000, 1090000, 'Mới', 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=900&q=85'],
            ['Tulip Hồng - Chờ Mong', 'Tulip', 'Hồng', 'birthday', 490000, 550000, '-11%', 'https://images.unsplash.com/photo-1519378058457-4c29a0a2efac?auto=format&fit=crop&w=900&q=85'],
            ['Cúc Trắng - Bình Yên', 'Cúc', 'Trắng', 'congratulations', 420000, 480000, 'Trong ngày', 'https://images.unsplash.com/photo-1508610048659-a06b669e3321?auto=format&fit=crop&w=900&q=85'],
            ['Tulip Mix - First Date', 'Tulip', 'Pastel', 'bouquet', 1290000, 1450000, 'Cao cấp', 'https://images.unsplash.com/photo-1494336934272-f726e72e4a6b?auto=format&fit=crop&w=900&q=85'],
            ['Hồng Hồng - True Love', 'Hồng', 'Hồng', 'love', 1590000, 1750000, 'Bán chạy', 'https://images.unsplash.com/photo-1518709594023-6eab9bab7b23?auto=format&fit=crop&w=900&q=85'],
            ['Tulip Trắng - Thanh Khiết', 'Tulip', 'Trắng', 'wedding', 730000, 820000, '-10%', 'https://images.unsplash.com/photo-1531058240690-006c446962d8?auto=format&fit=crop&w=900&q=85'],
            ['Mẫu Đơn - Vườn Thơ', 'Mẫu đơn', 'Hồng', 'basket', 1850000, 2100000, 'Phiên bản giới hạn', 'https://images.unsplash.com/photo-1495231916356-a86217efff12?auto=format&fit=crop&w=900&q=85'],
        ];

        $statement = $connection->prepare("INSERT INTO products
            (category_id, name, slug, sku, color, occasion, short_description, description, price, compare_price, stock_quantity, image_url, badge, status, featured)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 20, ?, ?, 'active', ?)");
        foreach ($products as $index => $product) {
            [$name, $category, $color, $occasion, $price, $comparePrice, $badge, $imageUrl] = $product;
            $categoryId = $categoryMap[$category] ?? null;
            $slug = shop_slug($name);
            $sku = 'LF-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $shortDescription = 'Bó hoa tươi thiết kế tinh tế, phù hợp làm quà tặng trong nhiều dịp.';
            $description = 'Hoa tươi được chọn lọc và thiết kế trong ngày. Sản phẩm có thể thay đổi nhẹ theo mùa nhưng vẫn giữ nguyên tông màu và phong cách.';
            $featured = $index < 4 ? 1 : 0;
            $statement->bind_param('isssssssiissi', $categoryId, $name, $slug, $sku, $color, $occasion, $shortDescription, $description, $price, $comparePrice, $imageUrl, $badge, $featured);
            $statement->execute();
        }
        $connection->commit();
    } catch (Throwable $exception) {
        $connection->rollback();
        throw $exception;
    }
}

function shop_slug(string $value): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $slug = strtolower((string) ($ascii !== false ? $ascii : $value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    return trim($slug, '-') ?: 'item-' . bin2hex(random_bytes(3));
}
