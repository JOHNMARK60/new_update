<?php
/**
 * Database bootstrap.
 *
 * Opening any page now creates the database, required tables, and starter data
 * when they do not exist yet. Keep the credentials simple for Laragon/XAMPP,
 * but allow environment overrides for deployment.
 */
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'cashieringinventorysystem';

$conn = mysqli_connect($db_host, $db_user, $db_pass);

if (!$conn) {
    die('Database server connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

$safe_db_name = str_replace('`', '``', $db_name);
mysqli_query(
    $conn,
    "CREATE DATABASE IF NOT EXISTS `$safe_db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
);
mysqli_select_db($conn, $db_name);

function column_exists($conn, $table, $column)
{
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);

    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = '$table'
         AND COLUMN_NAME = '$column'"
    );

    $row = mysqli_fetch_assoc($result);

    return (int) $row['total'] > 0;
}

function ensure_column($conn, $table, $column, $definition)
{
    if (!column_exists($conn, $table, $column)) {
        mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

mysqli_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        phone VARCHAR(30) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'cashier',
        reset_token VARCHAR(128) DEFAULT NULL,
        token_expires_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

mysqli_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        quantity INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

mysqli_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NULL,
        quantity INT NOT NULL DEFAULT 1,
        total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        user_id INT NULL,
        sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sales_product_id (product_id),
        INDEX idx_sales_user_id (user_id),
        CONSTRAINT fk_sales_product
            FOREIGN KEY (product_id) REFERENCES products(id)
            ON DELETE SET NULL,
        CONSTRAINT fk_sales_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

mysqli_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS inventory_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NULL,
        action VARCHAR(100) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_inventory_logs_product_id (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

ensure_column($conn, 'users', 'reset_token', 'VARCHAR(128) DEFAULT NULL');
ensure_column($conn, 'users', 'token_expires_at', 'DATETIME DEFAULT NULL');
ensure_column($conn, 'products', 'quantity', 'INT NOT NULL DEFAULT 0');
ensure_column($conn, 'products', 'image_path', 'VARCHAR(255) DEFAULT NULL');
ensure_column($conn, 'sales', 'total_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0');

if (column_exists($conn, 'products', 'stock')) {
    mysqli_query($conn, 'UPDATE products SET quantity = stock WHERE quantity = 0 AND stock > 0');
}

mysqli_query($conn, 'UPDATE sales SET total_amount = total_price WHERE total_amount = 0 AND total_price > 0');

$admin_check = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin' LIMIT 1");

if (mysqli_num_rows($admin_check) === 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (first_name, last_name, email, phone, password, role)
         VALUES (?, ?, ?, ?, ?, 'admin')"
    );
    $first_name = 'System';
    $last_name = 'Administrator';
    $email = 'admin@system.local';
    $phone = '0000000000';
    mysqli_stmt_bind_param($stmt, 'sssss', $first_name, $last_name, $email, $phone, $admin_password);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$product_check = mysqli_query($conn, "SELECT id FROM products LIMIT 1");

if (mysqli_num_rows($product_check) === 0) {
    $products = [
        ['Bottled Water', 25.00, 48],
        ['Instant Coffee', 12.00, 80],
        ['Notebook', 35.00, 24],
        ['Ballpen', 10.00, 100],
    ];

    $stmt = mysqli_prepare($conn, "INSERT INTO products (name, price, quantity) VALUES (?, ?, ?)");

    foreach ($products as $product) {
        mysqli_stmt_bind_param($stmt, 'sdi', $product[0], $product[1], $product[2]);
        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);
}

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('PDO connection failed: ' . $e->getMessage());
}
?>
