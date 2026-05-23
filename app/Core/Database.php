<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use mysqli;

class Database
{
    private static ?PDO $pdo = null;
    private static ?mysqli $mysqli = null;
    private static ?array $settings = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = self::host();
        $dbName = self::dbName();
        $user = self::user();
        $pass = self::password();

        try {
            $server = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $safeDb = str_replace('`', '``', $dbName);
            $server->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            self::$pdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('PDO connection failed: ' . $e->getMessage());
            die(self::connectionFailureMessage());
        }

        return self::$pdo;
    }

    public static function getLegacyMysqli(): mysqli
    {
        if (self::$mysqli instanceof mysqli) {
            return self::$mysqli;
        }

        $mysqli = \mysqli_connect(self::host(), self::user(), self::password());

        if (!$mysqli) {
            error_log('Database server connection failed: ' . \mysqli_connect_error());
            die(self::connectionFailureMessage());
        }

        \mysqli_set_charset($mysqli, 'utf8mb4');
        $safeDb = str_replace('`', '``', self::dbName());
        \mysqli_query($mysqli, "CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        \mysqli_select_db($mysqli, self::dbName());

        self::$mysqli = $mysqli;

        return self::$mysqli;
    }

    public static function migrate(): void
    {
        $pdo = self::getConnection();

        $statements = [
            "CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                label VARCHAR(100) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(30) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'cashier',
                role_id INT NULL,
                reset_token VARCHAR(128) DEFAULT NULL,
                token_expires_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_users_role_id (role_id),
                CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL UNIQUE,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS suppliers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL UNIQUE,
                contact_no VARCHAR(50) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                quantity INT NOT NULL DEFAULT 0,
                image_path VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS sales (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NULL,
                quantity INT NOT NULL DEFAULT 1,
                total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                user_id INT NULL,
                sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sales_product_id (product_id),
                INDEX idx_sales_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS sale_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sale_id INT NOT NULL,
                product_id INT NULL,
                product_name VARCHAR(150) NOT NULL,
                quantity INT NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sale_items_sale_id (sale_id),
                INDEX idx_sale_items_product_id (product_id),
                CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sale_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                tendered_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                change_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                currency VARCHAR(20) NOT NULL DEFAULT 'PHP',
                payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
                payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_payments_sale_id (sale_id),
                CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS inventory_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NULL,
                action VARCHAR(100) NOT NULL,
                quantity_change INT NOT NULL DEFAULT 0,
                stock_before INT NULL,
                stock_after INT NULL,
                reference_type VARCHAR(50) DEFAULT NULL,
                reference_id INT NULL,
                created_by INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_inventory_logs_product_id (product_id),
                INDEX idx_inventory_logs_reference (reference_type, reference_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS receipts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sale_id INT NOT NULL,
                receipt_no VARCHAR(50) NOT NULL UNIQUE,
                receipt_data JSON NULL,
                printed_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_receipts_sale_id (sale_id),
                CONSTRAINT fk_receipts_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS closing_reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                closing_date DATE NOT NULL,
                cashier_id INT NULL,
                cashier_name VARCHAR(201) NOT NULL,
                total_transactions INT NOT NULL DEFAULT 0,
                total_items_sold INT NOT NULL DEFAULT 0,
                total_sales DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                total_cash_received DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                expected_cash_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                actual_cash_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                difference_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                closing_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                closed_by INT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'closed',
                notes VARCHAR(255) DEFAULT NULL,
                review_status VARCHAR(20) NOT NULL DEFAULT 'pending',
                admin_feedback VARCHAR(255) DEFAULT NULL,
                reviewed_by INT NULL,
                reviewed_at DATETIME NULL,
                UNIQUE KEY uniq_closing_cashier_date (closing_date, cashier_id),
                INDEX idx_closing_date (closing_date),
                CONSTRAINT fk_closing_cashier FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_closing_closed_by FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS admin_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(160) NOT NULL,
                body VARCHAR(255) NOT NULL,
                link_url VARCHAR(255) DEFAULT NULL,
                related_type VARCHAR(80) DEFAULT NULL,
                related_id INT DEFAULT NULL,
                read_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_notifications_read (read_at),
                INDEX idx_admin_notifications_created (created_at),
                INDEX idx_admin_notifications_related (related_type, related_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }

        $columns = [
            ['users', 'reset_token', 'VARCHAR(128) DEFAULT NULL'],
            ['users', 'token_expires_at', 'DATETIME DEFAULT NULL'],
            ['users', 'role_id', 'INT NULL'],
            ['products', 'quantity', 'INT NOT NULL DEFAULT 0'],
            ['products', 'image_path', 'VARCHAR(255) DEFAULT NULL'],
            ['products', 'category_id', 'INT NULL'],
            ['products', 'supplier_id', 'INT NULL'],
            ['products', 'low_stock_level', 'INT NOT NULL DEFAULT 5'],
            ['products', 'expiration_date', 'DATE NULL'],
            ['products', 'sku', 'VARCHAR(50) NULL'],
            ['sales', 'receipt_no', 'VARCHAR(50) NULL'],
            ['sales', 'cashier_id', 'INT NULL'],
            ['sales', 'cashier_name', 'VARCHAR(201) NULL'],
            ['sales', 'subtotal_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00'],
            ['sales', 'discount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00'],
            ['sales', 'tax', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00'],
            ['sales', 'tendered_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00'],
            ['sales', 'change_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00'],
            ['sales', 'payment_method', "VARCHAR(50) NOT NULL DEFAULT 'cash'"],
            ['sales', 'status', "VARCHAR(20) NOT NULL DEFAULT 'paid'"],
            ['sales', 'closing_status', "VARCHAR(20) NOT NULL DEFAULT 'open'"],
            ['sales', 'closed_at', 'DATETIME NULL'],
            ['inventory_logs', 'quantity_change', 'INT NOT NULL DEFAULT 0'],
            ['inventory_logs', 'stock_before', 'INT NULL'],
            ['inventory_logs', 'stock_after', 'INT NULL'],
            ['inventory_logs', 'reference_type', 'VARCHAR(50) DEFAULT NULL'],
            ['inventory_logs', 'reference_id', 'INT NULL'],
            ['inventory_logs', 'created_by', 'INT NULL'],
            ['closing_reports', 'review_status', "VARCHAR(20) NOT NULL DEFAULT 'pending'"],
            ['closing_reports', 'admin_feedback', 'VARCHAR(255) DEFAULT NULL'],
            ['closing_reports', 'reviewed_by', 'INT NULL'],
            ['closing_reports', 'reviewed_at', 'DATETIME NULL'],
        ];

        foreach ($columns as [$table, $column, $definition]) {
            self::ensureColumn($pdo, $table, $column, $definition);
        }

        foreach ([
            ['products', 'price'],
            ['sales', 'total_price'],
            ['sales', 'total_amount'],
            ['sales', 'subtotal_amount'],
            ['sales', 'discount'],
            ['sales', 'tax'],
            ['sales', 'tendered_amount'],
            ['sales', 'change_amount'],
            ['sale_items', 'unit_price'],
            ['sale_items', 'total_price'],
            ['payments', 'amount'],
            ['payments', 'tendered_amount'],
            ['payments', 'change_amount'],
        ] as [$moneyTable, $moneyColumn]) {
            self::ensureDecimalColumn($pdo, $moneyTable, $moneyColumn);
        }

        if (self::columnExists($pdo, 'products', 'stock')) {
            $pdo->exec('UPDATE products SET quantity = stock WHERE quantity = 0 AND stock > 0');
        }

        $pdo->exec("INSERT IGNORE INTO roles (name, label) VALUES ('admin', 'Administrator'), ('cashier', 'Cashier')");
        $pdo->exec("UPDATE users u
            LEFT JOIN roles r ON r.name = u.role
            SET u.role_id = r.id
            WHERE u.role_id IS NULL AND r.id IS NOT NULL");
        $pdo->exec("INSERT IGNORE INTO categories (name) VALUES
            ('General'),
            ('Beverages'),
            ('Snacks'),
            ('Personal Care'),
            ('Household'),
            ('School Supplies'),
            ('Frozen Goods')");
        $pdo->exec("UPDATE sales SET total_amount = total_price WHERE total_amount = 0 AND total_price > 0");
        $pdo->exec("UPDATE sales SET subtotal_amount = total_amount WHERE subtotal_amount = 0 AND total_amount > 0");
        $pdo->exec("UPDATE sales s LEFT JOIN users u ON u.id = s.user_id
            SET s.cashier_id = COALESCE(s.cashier_id, s.user_id),
                s.cashier_name = COALESCE(s.cashier_name, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')))
            WHERE s.cashier_id IS NULL OR s.cashier_name IS NULL");
        $pdo->exec("UPDATE closing_reports
            SET review_status = CASE
                    WHEN difference_amount < 0 THEN 'short'
                    WHEN difference_amount > 0 THEN 'over'
                    ELSE 'balanced'
                END
            WHERE review_status IS NULL OR review_status = '' OR review_status = 'pending'");
        $pdo->exec("UPDATE closing_reports
            SET admin_feedback = CONCAT('Short PHP ', FORMAT(ABS(difference_amount), 2), '. Please explain missing cash before next shift.')
            WHERE difference_amount < 0
            AND (admin_feedback IS NULL OR admin_feedback = '')");

        self::backfillLegacySaleItems($pdo);
        self::seedStarterData($pdo);
    }

    public static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = :table
             AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        if (!self::columnExists($pdo, $table, $column)) {
            $safeTable = str_replace('`', '``', $table);
            $safeColumn = str_replace('`', '``', $column);
            $pdo->exec("ALTER TABLE `{$safeTable}` ADD COLUMN `{$safeColumn}` {$definition}");
        }
    }

    private static function ensureDecimalColumn(PDO $pdo, string $table, string $column): void
    {
        $stmt = $pdo->prepare(
            'SELECT DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = :table
             AND COLUMN_NAME = :column
             LIMIT 1'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);
        $info = $stmt->fetch();

        if (
            $info
            && strtolower((string) $info['DATA_TYPE']) === 'decimal'
            && (int) $info['NUMERIC_PRECISION'] === 10
            && (int) $info['NUMERIC_SCALE'] === 2
        ) {
            return;
        }

        $safeTable = str_replace('`', '``', $table);
        $safeColumn = str_replace('`', '``', $column);
        $pdo->exec("ALTER TABLE `{$safeTable}` MODIFY `{$safeColumn}` DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    }

    private static function backfillLegacySaleItems(PDO $pdo): void
    {
        $stmt = $pdo->query(
            "SELECT s.id, s.product_id, COALESCE(p.name, 'Deleted product') AS product_name,
                    s.quantity, COALESCE(p.price, 0) AS unit_price, s.total_price
             FROM sales s
             LEFT JOIN products p ON p.id = s.product_id
             LEFT JOIN sale_items si ON si.sale_id = s.id
             WHERE si.id IS NULL AND s.product_id IS NOT NULL"
        );
        $insert = $pdo->prepare(
            'INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total_price)
             VALUES (:sale_id, :product_id, :product_name, :quantity, :unit_price, :total_price)'
        );

        foreach ($stmt->fetchAll() as $row) {
            $quantity = max(1, (int) $row['quantity']);
            $unitPrice = (float) $row['unit_price'];
            $total = (float) $row['total_price'];

            if ($unitPrice <= 0 && $quantity > 0) {
                $unitPrice = round($total / $quantity, 2);
            }

            $insert->execute([
                'sale_id' => (int) $row['id'],
                'product_id' => $row['product_id'],
                'product_name' => $row['product_name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $total,
            ]);
        }
    }

    private static function seedStarterData(PDO $pdo): void
    {
        $adminExists = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;

        if (!$adminExists) {
            $stmt = $pdo->prepare(
                "INSERT INTO users (first_name, last_name, email, phone, password, role, role_id)
                 VALUES (:first_name, :last_name, :email, :phone, :password, 'admin', (SELECT id FROM roles WHERE name = 'admin' LIMIT 1))"
            );
            $stmt->execute([
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => 'admin@system.local',
                'phone' => '0000000000',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
            ]);
        }

        $categoryRows = $pdo->query('SELECT id, name FROM categories')->fetchAll();
        $categoryIds = [];

        foreach ($categoryRows as $row) {
            $categoryIds[(string) $row['name']] = (int) $row['id'];
        }

        $sampleProducts = [
            [
                'name' => 'Bottled Water',
                'price' => 25.00,
                'quantity' => 39,
                'image_path' => 'assets/uploads/products/product_20260513121655_47904b25.jpg',
                'category' => null,
                'expiration_date' => null,
                'sku' => null,
            ],
            [
                'name' => 'Instant Coffee',
                'price' => 12.00,
                'quantity' => 68,
                'image_path' => 'assets/uploads/products/product_20260513121549_9f309a7d.jpg',
                'category' => null,
                'expiration_date' => null,
                'sku' => null,
            ],
            [
                'name' => 'Notebook',
                'price' => 35.00,
                'quantity' => 2,
                'image_path' => 'assets/uploads/products/product_20260513121509_d9f65b1f.jpg',
                'category' => null,
                'expiration_date' => null,
                'sku' => null,
            ],
            [
                'name' => 'Ballpen',
                'price' => 10.00,
                'quantity' => 86,
                'image_path' => 'assets/uploads/products/product_20260513121439_675629c5.jpg',
                'category' => null,
                'expiration_date' => null,
                'sku' => null,
            ],
            [
                'name' => 'Papers',
                'price' => 20.00,
                'quantity' => 97,
                'image_path' => 'assets/uploads/products/product_20260513121348_ec8a57ef.jpg',
                'category' => null,
                'expiration_date' => null,
                'sku' => null,
            ],
            [
                'name' => 'Kanto Iced Tea 500ml',
                'price' => 35.00,
                'quantity' => 9,
                'image_path' => 'assets/images/sample-products/beverages.svg',
                'category' => 'Beverages',
                'expiration_date' => null,
                'sku' => 'BEV-001',
            ],
            [
                'name' => 'Kanto Potato Chips 60g',
                'price' => 42.00,
                'quantity' => 10,
                'image_path' => 'assets/images/sample-products/snacks.svg',
                'category' => 'Snacks',
                'expiration_date' => null,
                'sku' => 'SNK-001',
            ],
            [
                'name' => 'Safe Guard',
                'price' => 48.00,
                'quantity' => 9,
                'image_path' => 'assets/uploads/products/product_20260517135758_a0352d5b.jpg',
                'category' => 'Personal Care',
                'expiration_date' => null,
                'sku' => 'PC-001',
            ],
            [
                'name' => 'JOY',
                'price' => 55.00,
                'quantity' => 9,
                'image_path' => 'assets/uploads/products/product_20260517140016_655203af.jpg',
                'category' => 'Household',
                'expiration_date' => null,
                'sku' => 'HH-001',
            ],
            [
                'name' => 'Classic Notebook 80 Leaves',
                'price' => 35.00,
                'quantity' => 10,
                'image_path' => 'assets/uploads/products/product_20260517140105_962ab560.jpg',
                'category' => 'School Supplies',
                'expiration_date' => null,
                'sku' => 'SS-001',
            ],
            [
                'name' => "L'Oreal Paris Elvive Color Vibrancy Protecting Shampoo and Conditioner Set, 12.6 Ounce Each",
                'price' => 80.00,
                'quantity' => 19,
                'image_path' => 'assets/uploads/products/product_20260517140249_99501261.jpg',
                'category' => 'Personal Care',
                'expiration_date' => null,
                'sku' => null,
            ],
            [
                'name' => 'Pantene',
                'price' => 150.00,
                'quantity' => 10,
                'image_path' => 'assets/uploads/products/product_20260517140400_9d9ee09d.jpg',
                'category' => 'Personal Care',
                'expiration_date' => '2027-07-17',
                'sku' => null,
            ],
            [
                'name' => 'Magnolia CS Chicken Korean Barbeque | 500g-550g',
                'price' => 180.00,
                'quantity' => 9,
                'image_path' => 'assets/uploads/products/product_20260517141445_1414e474.webp',
                'category' => 'Frozen Goods',
                'expiration_date' => '2026-06-17',
                'sku' => null,
            ],
            [
                'name' => 'Purefoods Stuffed Nuggets Bacon & Cheese | 200g',
                'price' => 119.00,
                'quantity' => 10,
                'image_path' => 'assets/uploads/products/product_20260517141556_cddbf206.webp',
                'category' => 'Frozen Goods',
                'expiration_date' => '2026-06-17',
                'sku' => null,
            ],
            [
                'name' => 'PureFoods Tender Juicy Hotdog Classic | 1kg',
                'price' => 189.00,
                'quantity' => 20,
                'image_path' => 'assets/uploads/products/product_20260517141724_f3dc7b86.webp',
                'category' => 'Frozen Goods',
                'expiration_date' => '2026-07-17',
                'sku' => null,
            ],
            [
                'name' => 'Purefoods Chicken Nuggets Fun Stuff Letters & Numbers | 200g',
                'price' => 110.00,
                'quantity' => 20,
                'image_path' => 'assets/uploads/products/product_20260517141929_309f6ac0.webp',
                'category' => 'Frozen Goods',
                'expiration_date' => '2026-06-17',
                'sku' => null,
            ],
            [
                'name' => 'Tang Powdered Juice Drink Calamansi | 25g',
                'price' => 20.00,
                'quantity' => 20,
                'image_path' => 'assets/uploads/products/product_20260517142149_0f90ae3b.webp',
                'category' => 'Beverages',
                'expiration_date' => '2028-01-17',
                'sku' => null,
            ],
            [
                'name' => 'Milo Active Go Twinpack | 48g 8pcs',
                'price' => 142.00,
                'quantity' => 50,
                'image_path' => 'assets/uploads/products/product_20260517142244_df9e14ff.webp',
                'category' => 'Beverages',
                'expiration_date' => '2028-01-17',
                'sku' => null,
            ],
            [
                'name' => 'Great Taste White Crema 3-in-1 Coffee Mix Twin Pack | 50g 5Pcs',
                'price' => 70.95,
                'quantity' => 19,
                'image_path' => 'assets/uploads/products/product_20260517142332_190fba4c.webp',
                'category' => 'Beverages',
                'expiration_date' => null,
                'sku' => null,
            ],
            [
                'name' => 'Del Monte Pineapple Juice Drink Fiber Enriched | 1L Tetra',
                'price' => 134.00,
                'quantity' => 18,
                'image_path' => 'assets/uploads/products/product_20260517142423_3ae49373.webp',
                'category' => 'Beverages',
                'expiration_date' => '2027-01-17',
                'sku' => null,
            ],
        ];

        $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE sku = :sku OR name = :name');
        $insertStmt = $pdo->prepare(
            'INSERT INTO products
                (name, price, quantity, image_path, category_id, low_stock_level, expiration_date, sku)
             VALUES
                (:name, :price, :quantity, :image_path, :category_id, 5, :expiration_date, :sku)'
        );

        foreach ($sampleProducts as $product) {
            $categoryName = $product['category'];
            $categoryId = $categoryName === null ? null : ($categoryIds[$categoryName] ?? null);

            if ($categoryName !== null && $categoryId === null) {
                continue;
            }

            $existsStmt->execute([
                'sku' => $product['sku'],
                'name' => $product['name'],
            ]);

            if ((int) $existsStmt->fetchColumn() > 0) {
                continue;
            }

            $insertStmt->execute([
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $product['quantity'],
                'image_path' => $product['image_path'],
                'category_id' => $categoryId,
                'expiration_date' => $product['expiration_date'],
                'sku' => $product['sku'],
            ]);
        }
    }

    private static function host(): string
    {
        return (string) self::settings()['host'];
    }

    private static function user(): string
    {
        return (string) self::settings()['user'];
    }

    private static function password(): string
    {
        return (string) self::settings()['password'];
    }

    private static function dbName(): string
    {
        return (string) self::settings()['name'];
    }

    private static function settings(): array
    {
        if (self::$settings !== null) {
            return self::$settings;
        }

        $settings = [
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'name' => 'cashieringinventorysystem',
        ];

        $localConfigPath = dirname(__DIR__, 2) . '/config/database.local.php';
        if (is_file($localConfigPath)) {
            $localSettings = require $localConfigPath;
            if (is_array($localSettings)) {
                foreach (array_keys($settings) as $key) {
                    if (array_key_exists($key, $localSettings)) {
                        $settings[$key] = (string) $localSettings[$key];
                    }
                }
            }
        }

        $envMap = [
            'DB_HOST' => 'host',
            'DB_USER' => 'user',
            'DB_PASS' => 'password',
            'DB_NAME' => 'name',
        ];

        foreach ($envMap as $envName => $settingName) {
            $value = getenv($envName);
            if ($value !== false) {
                $settings[$settingName] = (string) $value;
            }
        }

        self::$settings = $settings;

        return self::$settings;
    }

    private static function connectionFailureMessage(): string
    {
        return 'Database connection failed. Make sure MySQL is running and check the settings in config/database.local.php.';
    }
}
