<?php
function formatMoney($number)
{
    return '&#8369; ' . number_format((float) $number, 2);
}

function checkStock($conn, $product_id)
{
    $product_id = (int) $product_id;
    $result = mysqli_query($conn, "SELECT quantity FROM products WHERE id = $product_id LIMIT 1");
    $row = mysqli_fetch_assoc($result);
    return $row ? (int) $row['quantity'] : 0;
}
?>
