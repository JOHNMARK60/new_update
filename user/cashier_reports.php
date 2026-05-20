<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

use App\Repositories\SaleRepository;

$user_id = (int) $_SESSION['user_id'];
$saleRepository = new SaleRepository($pdo);
$todaySummary = $saleRepository->summary([
    'cashier_id' => $user_id,
    'date_from' => date('Y-m-d'),
    'date_to' => date('Y-m-d'),
    'status' => 'paid',
]);
$allSummary = $saleRepository->summary([
    'cashier_id' => $user_id,
    'status' => 'paid',
]);

$product_names = [];
$product_quantities = [];
$topItems = array_slice($saleRepository->itemSummary(['cashier_id' => $user_id, 'status' => 'paid']), 0, 5);

foreach ($topItems as $row) {
    $product_names[] = $row['product_name'] ?? 'Deleted product';
    $product_quantities[] = (int) $row['quantity_sold'];
}

$transactions = array_slice($saleRepository->transactions(['cashier_id' => $user_id]), 0, 10);
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
        <?php
        $appHeaderRole = 'cashier';
        $appHeaderRoleLabel = 'Cashier';
        $appHeaderKicker = 'Cashier workspace';
        $appHeaderTitle = 'Sales Reports';
        $appHeaderSubtitle = 'Your cashier-specific sales only. Admin can review all cashiers from Sales Reports.';
        $appHeaderIcon = 'fa-chart-pie';
        $appHeaderHome = 'user_dashboard.php';
        $appHeaderShowSearch = false;
        $appHeaderActions = [
            ['href' => 'cashier_closing.php', 'label' => 'Daily Closing', 'icon' => 'fa-lock', 'class' => 'btn btn-secondary'],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <section class="dashboard-cards">
            <article class="dashboard-card">
                <p class="text-sm font-semibold text-slate-500">Sales Today</p>
                <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo money($todaySummary['total_sales']); ?></h2>
            </article>
            <article class="dashboard-card">
                <p class="text-sm font-semibold text-slate-500">Total Revenue</p>
                <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo money($allSummary['total_sales']); ?></h2>
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
                                <th>Receipt</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Cash</th>
                                <th>Change</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions): ?>
                                <?php foreach ($transactions as $row): ?>
                                    <tr>
                                        <td><?php echo (int) $row['id']; ?></td>
                                        <td><strong><?php echo e($row['receipt_no'] ?? str_pad((string) $row['id'], 6, '0', STR_PAD_LEFT)); ?></strong></td>
                                        <td><?php echo (int) $row['items_sold']; ?></td>
                                        <td><?php echo money($row['total_amount']); ?></td>
                                        <td><?php echo money($row['tendered_amount']); ?></td>
                                        <td><?php echo money($row['change_amount']); ?></td>
                                        <td><?php echo e(date('M d, Y h:i A', strtotime($row['sale_date']))); ?></td>
                                        <td><span class="status-paid"><?php echo e(ucfirst($row['status'] ?? 'paid')); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-slate-500">No transactions found yet.</td>
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

        <?php
        $appFooterRole = 'cashier';
        $appFooterRoleLabel = 'Cashier';
        $appFooterLinks = [
            ['href' => 'user_dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-table-columns'],
            ['href' => 'cashier_sales.php', 'label' => 'POS', 'icon' => 'fa-cash-register'],
            ['href' => 'cashier_closing.php', 'label' => 'Closing', 'icon' => 'fa-lock'],
        ];
        include __DIR__ . '/../config/app_footer.php';
        ?>
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
