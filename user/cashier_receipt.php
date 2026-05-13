<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cashier_sales.php');
    exit();
}

$product_id = (int) ($_POST['product_id'] ?? 0);
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));
$cash = (float) ($_POST['cash'] ?? 0);
$user_id = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, 'SELECT * FROM products WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product || (int) $product['quantity'] < $quantity) {
    echo "<script>alert('Selected product is out of stock or has insufficient quantity.'); window.location='cashier_sales.php';</script>";
    exit();
}

$price = (float) $product['price'];
$total_price = $price * $quantity;
$change = $cash - $total_price;

if ($change < 0) {
    echo "<script>alert('Cash tendered is not enough for this transaction.'); window.location='cashier_sales.php';</script>";
    exit();
}

$receipt_no = 'INV-' . date('Ymd') . '-' . random_int(1000, 9999);
$date = date('M d, Y');
$time = date('h:i A');
$cashier = $_SESSION['first_name'];

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO sales (product_id, quantity, total_price, total_amount, user_id, sale_date)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    mysqli_stmt_bind_param($stmt, 'iiddi', $product_id, $quantity, $total_price, $total_price, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'UPDATE products SET quantity = quantity - ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $quantity, $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $action = 'Sold ' . $quantity . ' item(s)';
    $stmt = mysqli_prepare($conn, 'INSERT INTO inventory_logs (product_id, action) VALUES (?, ?)');
    mysqli_stmt_bind_param($stmt, 'is', $product_id, $action);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    echo "<script>alert('Transaction failed. Please try again.'); window.location='cashier_sales.php';</script>";
    exit();
}

$pageTitle = 'Official Receipt';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="bg-slate-100 p-4 sm:p-8">
    <button onclick="window.print()" class="btn no-print fixed right-5 top-5 z-10">
        <i class="fa-solid fa-print"></i>
        Print
    </button>

    <main class="mx-auto max-w-3xl overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl">
        <section class="border-b border-dashed border-slate-300 p-8 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-lg bg-brand text-2xl text-white">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <h1 class="mt-4 text-3xl font-extrabold text-ink">RetailFlow POS</h1>
            <p class="mt-1 text-sm font-semibold uppercase text-slate-500">Official Sales Receipt</p>
        </section>

        <section class="grid gap-4 border-b border-dashed border-slate-300 p-6 sm:grid-cols-2">
            <div class="space-y-2 text-sm">
                <p class="flex justify-between"><span class="text-slate-500">Date</span><strong><?php echo e($date); ?></strong></p>
                <p class="flex justify-between"><span class="text-slate-500">Time</span><strong><?php echo e($time); ?></strong></p>
            </div>
            <div class="space-y-2 text-sm">
                <p class="flex justify-between"><span class="text-slate-500">Receipt</span><strong><?php echo e($receipt_no); ?></strong></p>
                <p class="flex justify-between"><span class="text-slate-500">Cashier</span><strong><?php echo e($cashier); ?></strong></p>
            </div>
        </section>

        <section class="p-6">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php echo e($product['name']); ?></strong></td>
                            <td><?php echo (int) $quantity; ?></td>
                            <td><?php echo money($price); ?></td>
                            <td><?php echo money($total_price); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="ml-auto mt-6 max-w-sm space-y-3 text-sm">
                <p class="flex justify-between"><span class="text-slate-500">Total</span><strong><?php echo money($total_price); ?></strong></p>
                <p class="flex justify-between"><span class="text-slate-500">Cash</span><strong><?php echo money($cash); ?></strong></p>
                <p class="flex justify-between text-lg"><span class="font-bold text-ink">Change</span><strong class="text-mint"><?php echo money($change); ?></strong></p>
            </div>
        </section>

        <section class="border-t border-slate-200 bg-slate-50 p-6 text-center">
            <h2 class="text-xl font-extrabold text-ink">Thank you!</h2>
            <p class="mt-1 text-sm text-slate-500">This receipt was generated by the cashiering inventory system.</p>
            <a href="cashier_sales.php" class="btn btn-secondary no-print mt-5">New Transaction</a>
        </section>
    </main>
</body>
</html>
