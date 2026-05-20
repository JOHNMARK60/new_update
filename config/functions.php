<?php
require_once __DIR__ . '/db.php';

function formatMoney($number)
{
    return '&#8369;' . number_format((float) $number, 2);
}

function checkStock($conn, $product_id)
{
    $stmt = App\Core\Database::getConnection()->prepare('SELECT quantity FROM products WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $product_id]);
    $row = $stmt->fetch();
    return $row ? (int) $row['quantity'] : 0;
}
?>
