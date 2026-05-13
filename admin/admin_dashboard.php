<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

function scalar_query($conn, $sql)
{
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row ? array_values($row)[0] : 0;
}

$total_products = (int) scalar_query($conn, 'SELECT COUNT(*) FROM products');
$total_staff = (int) scalar_query($conn, "SELECT COUNT(*) FROM users WHERE role = 'cashier'");
$today_sales = (float) scalar_query($conn, 'SELECT COALESCE(SUM(total_price), 0) FROM sales WHERE DATE(sale_date) = CURDATE()');
$low_stock = (int) scalar_query($conn, 'SELECT COUNT(*) FROM products WHERE quantity > 0 AND quantity <= 5');
$pageTitle = 'Admin Dashboard | Cashiering Inventory System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="app-shell">
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <main class="admin-main">
        <header class="page-topbar">
            <div>
                <h1 class="page-title">Dashboard Overview</h1>
                <p class="page-subtitle">Welcome back, <strong><?php echo e($_SESSION['first_name']); ?></strong>.</p>
            </div>
            <div class="badge bg-white text-ink shadow-sm">
                <i class="fa-solid fa-user-shield text-brand"></i>
                Administrator
            </div>
        </header>

        <section class="dashboard-cards">
            <article class="dashboard-card flex items-center gap-4">
                <div class="card-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div>
                    <p class="text-sm font-semibold text-slate-500">Products</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo $total_products; ?></h2>
                </div>
            </article>

            <article class="dashboard-card flex items-center gap-4">
                <div class="card-icon green"><i class="fa-solid fa-users"></i></div>
                <div>
                    <p class="text-sm font-semibold text-slate-500">Cashiers</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo $total_staff; ?></h2>
                </div>
            </article>

            <article class="dashboard-card flex items-center gap-4">
                <div class="card-icon orange"><i class="fa-solid fa-peso-sign"></i></div>
                <div>
                    <p class="text-sm font-semibold text-slate-500">Sales Today</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo money($today_sales); ?></h2>
                </div>
            </article>

            <article class="dashboard-card flex items-center gap-4">
                <div class="card-icon purple"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <p class="text-sm font-semibold text-slate-500">Low Stock</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo $low_stock; ?></h2>
                </div>
            </article>
        </section>

        <section class="panel p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-ink">Cashiering Inventory System</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Monitor stock levels, cashier accounts, and sales performance from one admin workspace.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="admin_inventory.php" class="btn"><i class="fa-solid fa-plus"></i>Add Product</a>
                    <a href="admin_sales_report.php" class="btn btn-secondary">View Reports</a>
                </div>
            </div>
        </section>

        <footer class="footer mt-8">&copy; <?php echo date('Y'); ?> Cashiering Inventory System</footer>
    </main>
</body>
</html>
