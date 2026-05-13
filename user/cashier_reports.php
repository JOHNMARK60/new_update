<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

$user_id = (int) $_SESSION['user_id'];
$today_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_price), 0) AS total FROM sales WHERE user_id = $user_id AND DATE(sale_date) = CURDATE()"))['total'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_price), 0) AS total FROM sales WHERE user_id = $user_id"))['total'];

$chart_query = mysqli_query($conn, "SELECT p.name, SUM(s.quantity) AS qty FROM sales s LEFT JOIN products p ON s.product_id = p.id WHERE s.user_id = $user_id GROUP BY p.name ORDER BY qty DESC LIMIT 5");
$product_names = [];
$product_quantities = [];

while ($row = mysqli_fetch_assoc($chart_query)) {
    $product_names[] = $row['name'] ?? 'Deleted product';
    $product_quantities[] = (int) $row['qty'];
}

$transactions = mysqli_query($conn, "SELECT s.*, p.name FROM sales s LEFT JOIN products p ON s.product_id = p.id WHERE s.user_id = $user_id ORDER BY s.sale_date DESC LIMIT 10");
$pageTitle = 'Sales Reports | Cashier';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="app-shell">
    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <main class="main-content">
        <header class="page-topbar">
            <div>
                <h1 class="page-title">Sales Reports</h1>
                <p class="page-subtitle">Performance overview for <strong><?php echo e($_SESSION['first_name']); ?></strong>.</p>
            </div>
        </header>

        <section class="dashboard-cards">
            <article class="dashboard-card">
                <p class="text-sm font-semibold text-slate-500">Sales Today</p>
                <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo money($today_sales); ?></h2>
            </article>
            <article class="dashboard-card">
                <p class="text-sm font-semibold text-slate-500">Total Revenue</p>
                <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo money($total_revenue); ?></h2>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <article class="panel overflow-hidden">
                <div class="border-b border-slate-200 p-6">
                    <h2 class="text-xl font-extrabold text-ink">Recent Transactions</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions && mysqli_num_rows($transactions) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($transactions)): ?>
                                    <tr>
                                        <td><?php echo (int) $row['id']; ?></td>
                                        <td><strong><?php echo e($row['name'] ?? 'Deleted product'); ?></strong></td>
                                        <td><?php echo money($row['total_price']); ?></td>
                                        <td><?php echo e(date('M d, Y h:i A', strtotime($row['sale_date']))); ?></td>
                                        <td><span class="status-paid">Paid</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-500">No transactions found yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="panel p-6">
                <h2 class="text-center text-xl font-extrabold text-ink">Top Products</h2>
                <div class="mt-5 min-h-72">
                    <canvas id="salesChart"></canvas>
                </div>
            </article>
        </section>
    </main>

    <script>
        const ctx = document.getElementById('salesChart');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($product_names); ?>,
                datasets: [{
                    data: <?php echo json_encode($product_quantities); ?>,
                    backgroundColor: ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>
