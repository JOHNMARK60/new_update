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
$today_sales = $todaySummary['total_sales'];
$items_sold = $todaySummary['total_items_sold'];
$low_stock = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE quantity > 0 AND quantity <= low_stock_level')->fetchColumn();
$products_available = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE quantity > 0')->fetchColumn();
$dashboardFilters = ['cashier_id' => $user_id, 'status' => 'paid'];
$dailySalesSeries = $saleRepository->salesByDay($dashboardFilters, 7);
$monthlySalesSeries = $saleRepository->salesByMonth($dashboardFilters, 6);
$topSellingItems = array_slice($saleRepository->itemSummary($dashboardFilters), 0, 7);
$topSellingFallback = $topSellingItems === [];

if ($topSellingFallback) {
    $topSellingItems = [
        ['product_name' => 'Sunday', 'quantity_sold' => 48],
        ['product_name' => 'Monday', 'quantity_sold' => 100],
        ['product_name' => 'Tuesday', 'quantity_sold' => 40],
        ['product_name' => 'Wednesday', 'quantity_sold' => 68],
        ['product_name' => 'Thursday', 'quantity_sold' => 56],
        ['product_name' => 'Friday', 'quantity_sold' => 74],
        ['product_name' => 'Saturday', 'quantity_sold' => 92],
    ];
}

$topSellingLabels = array_map(static fn ($item): string => (string) ($item['product_name'] ?? 'Deleted product'), $topSellingItems);
$topSellingQuantities = array_map(static fn ($item): int => (int) ($item['quantity_sold'] ?? 0), $topSellingItems);
$topSellingMax = $topSellingFallback ? 100 : max([1, ...$topSellingQuantities]);
$pageTitle = 'Cashier Dashboard | KANTO GOODS';
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
        $appHeaderTitle = 'Cashier Dashboard';
        $appHeaderSubtitle = 'Welcome back, ' . ($_SESSION['first_name'] ?? 'Cashier') . '. Track your sales, stock alerts, and daily POS work.';
        $appHeaderIcon = 'fa-cash-register';
        $appHeaderHome = 'user_dashboard.php';
        $appHeaderShowSearch = false;
        $appHeaderActions = [
            ['href' => 'cashier_sales.php', 'label' => 'Open POS', 'icon' => 'fa-cash-register', 'class' => 'btn'],
            ['href' => 'cashier_products.php', 'label' => 'Products', 'icon' => 'fa-box', 'class' => 'btn btn-secondary'],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

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

        <section class="chart-grid">
            <article class="panel chart-panel">
                <div class="chart-panel-header">
                    <div>
                        <p class="chart-eyebrow">Last 7 days</p>
                        <h2>Daily Sales</h2>
                    </div>
                    <i class="fa-solid fa-chart-line text-brand"></i>
                </div>
                <div class="chart-wrap">
                    <canvas id="cashierDailySalesChart"></canvas>
                </div>
            </article>

            <article class="panel chart-panel">
                <div class="chart-panel-header">
                    <div>
                        <p class="chart-eyebrow">Last 6 months</p>
                        <h2>Monthly Sales</h2>
                    </div>
                    <i class="fa-solid fa-chart-column text-brand"></i>
                </div>
                <div class="chart-wrap">
                    <canvas id="cashierMonthlySalesChart"></canvas>
                </div>
            </article>

            <article class="panel chart-panel chart-panel-wide top-selling-card">
                <div class="top-selling-header">
                    <h2><i class="fa-solid fa-layer-group"></i> Top Selling</h2>
                </div>
                <div class="top-selling-layout top-selling-body">
                    <div class="chart-wrap top-selling-radar">
                        <canvas id="cashierTopSellingChart"></canvas>
                    </div>
                    <div class="top-selling-list">
                        <?php foreach ($topSellingItems as $item): ?>
                            <?php
                                $quantitySold = (int) ($item['quantity_sold'] ?? 0);
                                $barWidth = (int) round(($quantitySold / $topSellingMax) * 100);
                            ?>
                            <div class="top-selling-row">
                                <div class="flex items-center justify-between gap-3">
                                    <strong><?php echo $barWidth; ?>%</strong>
                                    <span><?php echo e($item['product_name'] ?? 'Deleted product'); ?></span>
                                </div>
                                <div class="top-selling-meter"><span style="width: <?php echo $barWidth; ?>%"></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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

        <?php
        $appFooterRole = 'cashier';
        $appFooterRoleLabel = 'Cashier';
        $appFooterLinks = [
            ['href' => 'cashier_sales.php', 'label' => 'POS', 'icon' => 'fa-cash-register'],
            ['href' => 'cashier_products.php', 'label' => 'Products', 'icon' => 'fa-box'],
            ['href' => 'cashier_reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-pie'],
        ];
        include __DIR__ . '/../config/app_footer.php';
        ?>
    </main>
    <script>
        const cashierDailyLabels = <?php echo json_encode(array_column($dailySalesSeries, 'label')); ?>;
        const cashierDailySales = <?php echo json_encode(array_column($dailySalesSeries, 'total_sales')); ?>;
        const cashierMonthlyLabels = <?php echo json_encode(array_column($monthlySalesSeries, 'label')); ?>;
        const cashierMonthlySales = <?php echo json_encode(array_column($monthlySalesSeries, 'total_sales')); ?>;
        const cashierTopLabels = <?php echo json_encode($topSellingLabels); ?>;
        const cashierTopQuantities = <?php echo json_encode($topSellingQuantities); ?>;
        const cashierTopMax = Math.max(1, ...cashierTopQuantities);
        const cashierTopPercentages = cashierTopQuantities.map((value) => Math.round((Number(value || 0) / cashierTopMax) * 100));

        function formatSales(value) {
            return 'PHP ' + Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function makeSalesChart(id, type, labels, values, label) {
            const element = document.getElementById(id);

            if (!element) {
                return;
            }

            new Chart(element, {
                type,
                data: {
                    labels,
                    datasets: [{
                        label,
                        data: values,
                        borderColor: '#2563eb',
                        backgroundColor: type === 'bar' ? 'rgba(37, 99, 235, .72)' : 'rgba(37, 99, 235, .14)',
                        borderWidth: 2,
                        fill: type === 'line',
                        tension: .36,
                        pointRadius: 4,
                        pointBackgroundColor: '#2563eb',
                        borderRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => label + ': ' + formatSales(context.parsed.y)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#667085', font: { family: 'Poppins' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#edf1f6' },
                            ticks: {
                                color: '#667085',
                                font: { family: 'Poppins' },
                                callback: (value) => Number(value) >= 1000 ? 'PHP ' + (Number(value) / 1000) + 'k' : 'PHP ' + value
                            }
                        }
                    }
                }
            });
        }

        makeSalesChart('cashierDailySalesChart', 'line', cashierDailyLabels, cashierDailySales, 'Daily sales');
        makeSalesChart('cashierMonthlySalesChart', 'bar', cashierMonthlyLabels, cashierMonthlySales, 'Monthly sales');

        if (cashierTopLabels.length) {
            new Chart(document.getElementById('cashierTopSellingChart'), {
                type: 'radar',
                data: {
                    labels: cashierTopLabels,
                    datasets: [{
                        label: 'Top selling',
                        data: cashierTopPercentages,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, .18)',
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#ffffff',
                        pointHoverBackgroundColor: '#ffffff',
                        pointHoverBorderColor: '#2563eb',
                        pointRadius: 5,
                        pointBorderWidth: 3,
                        borderWidth: 2,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        r: {
                            beginAtZero: true,
                            min: 0,
                            max: 100,
                            grid: { color: '#e4e9f0' },
                            angleLines: { color: '#e4e9f0' },
                            pointLabels: {
                                color: '#667085',
                                font: { family: 'Poppins', size: 11 },
                                callback: (label) => String(label).length > 13 ? String(label).slice(0, 13) + '...' : label
                            },
                            ticks: {
                                stepSize: 20,
                                color: '#98a2b3',
                                backdropColor: 'transparent',
                                font: { family: 'Poppins', size: 10 },
                                callback: (value) => value === 0 ? '' : value
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
