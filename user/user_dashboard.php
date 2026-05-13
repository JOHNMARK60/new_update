<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

$user_id = (int) $_SESSION['user_id'];
$today_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_price), 0) AS total FROM sales WHERE user_id = $user_id AND DATE(sale_date) = CURDATE()"))['total'];
$items_sold = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(quantity), 0) AS total FROM sales WHERE user_id = $user_id AND DATE(sale_date) = CURDATE()"))['total'];
$low_stock = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM products WHERE quantity > 0 AND quantity <= 5'))['total'];
$products_available = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM products WHERE quantity > 0'))['total'];
$pageTitle = 'Cashier Dashboard | Cashiering Inventory System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="app-shell">
    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <main class="main-content">
        <header class="page-topbar">
            <div>
                <h1 class="page-title">Cashier Dashboard</h1>
                <p class="page-subtitle">Welcome back, <strong><?php echo e($_SESSION['first_name']); ?></strong>.</p>
            </div>
            <a href="cashier_sales.php" class="btn">
                <i class="fa-solid fa-cash-register"></i>
                Open POS
            </a>
        </header>

        <section class="dashboard-cards">
            <article class="dashboard-card flex items-center gap-4">
                <div class="card-icon blue"><i class="fa-solid fa-peso-sign"></i></div>
                <div>
                    <p class="text-sm font-semibold text-slate-500">Sales Today</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo money($today_sales); ?></h2>
                </div>
            </article>

            <article class="dashboard-card flex items-center gap-4">
                <div class="card-icon green"><i class="fa-solid fa-box"></i></div>
                <div>
                    <p class="text-sm font-semibold text-slate-500">Items Sold</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo (int) $items_sold; ?></h2>
                </div>
            </article>

            <article class="dashboard-card flex items-center gap-4">
                <div class="card-icon orange"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <p class="text-sm font-semibold text-slate-500">Low Stock</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo (int) $low_stock; ?></h2>
                </div>
            </article>

            <article class="dashboard-card flex items-center gap-4">
                <div class="card-icon purple"><i class="fa-solid fa-layer-group"></i></div>
                <div>
                    <p class="text-sm font-semibold text-slate-500">Available Products</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo (int) $products_available; ?></h2>
                </div>
            </article>
        </section>

        <section class="grid gap-5 lg:grid-cols-4">
            <a href="cashier_sales.php" class="panel p-6 transition hover:-translate-y-1 hover:shadow-lg">
                <div class="card-icon green"><i class="fa-solid fa-cash-register"></i></div>
                <h3 class="mt-5 text-lg font-extrabold text-ink">Point of Sale</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Process customer purchases and print receipts.</p>
            </a>

            <a href="cashier_products.php" class="panel p-6 transition hover:-translate-y-1 hover:shadow-lg">
                <div class="card-icon blue"><i class="fa-solid fa-barcode"></i></div>
                <h3 class="mt-5 text-lg font-extrabold text-ink">Products</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Browse prices and product availability.</p>
            </a>

            <a href="inventory.php" class="panel p-6 transition hover:-translate-y-1 hover:shadow-lg">
                <div class="card-icon orange"><i class="fa-solid fa-warehouse"></i></div>
                <h3 class="mt-5 text-lg font-extrabold text-ink">Inventory</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Check low-stock and out-of-stock items.</p>
            </a>

            <a href="cashier_reports.php" class="panel p-6 transition hover:-translate-y-1 hover:shadow-lg">
                <div class="card-icon purple"><i class="fa-solid fa-chart-pie"></i></div>
                <h3 class="mt-5 text-lg font-extrabold text-ink">Reports</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Review your latest transactions.</p>
            </a>
        </section>
    </main>
</body>
</html>
