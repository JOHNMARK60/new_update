<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

use App\Repositories\SaleRepository;

function scalar_query(PDO $pdo, $sql)
{
    return $pdo->query($sql)->fetchColumn() ?: 0;
}

$total_products = (int) scalar_query($pdo, 'SELECT COUNT(*) FROM products');
$total_staff = (int) scalar_query($pdo, "SELECT COUNT(*) FROM users WHERE role = 'cashier'");
$saleRepository = new SaleRepository($pdo);
$today_sales = $saleRepository->summary([
    'date_from' => date('Y-m-d'),
    'date_to' => date('Y-m-d'),
    'status' => 'paid',
])['total_sales'];
$low_stock = (int) scalar_query($pdo, 'SELECT COUNT(*) FROM products WHERE quantity > 0 AND quantity <= low_stock_level');
$dashboardFilters = ['status' => 'paid'];
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
$pageTitle = 'Admin Dashboard | KANTO GOODS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="app-shell admin-dashboard-page">
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <main class="admin-main">
        <?php
        $appHeaderRole = 'admin';
        $appHeaderRoleLabel = 'Administrator';
        $appHeaderKicker = 'Admin control center';
        $appHeaderTitle = 'Dashboard Overview';
        $appHeaderSubtitle = 'Welcome back, ' . ($_SESSION['first_name'] ?? 'Admin') . '. Monitor inventory, cashier accounts, and sales performance.';
        $appHeaderIcon = 'fa-chart-line';
        $appHeaderHome = 'admin_dashboard.php';
        $appHeaderShowSearch = false;
        $appHeaderActions = [
            ['href' => 'admin_inventory.php', 'label' => 'Add Product', 'icon' => 'fa-plus', 'class' => 'btn'],
            ['href' => 'admin_sales_report.php', 'label' => 'View Reports', 'icon' => 'fa-file-lines', 'class' => 'btn btn-secondary'],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

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
                    <canvas id="adminDailySalesChart"></canvas>
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
                    <canvas id="adminMonthlySalesChart"></canvas>
                </div>
            </article>

            <article class="panel chart-panel chart-panel-wide top-selling-card">
                <div class="top-selling-header">
                    <h2><i class="fa-solid fa-layer-group"></i> Top Selling</h2>
                </div>
                <div class="top-selling-layout top-selling-body">
                    <div class="chart-wrap top-selling-radar top-selling-chart">
                        <canvas id="adminTopSellingChart"></canvas>
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

        <section class="panel p-6 admin-command-panel">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-ink">KANTO GOODS</h2>
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

        <?php
        $appFooterRole = 'admin';
        $appFooterRoleLabel = 'Administrator';
        $appFooterLinks = [
            ['href' => 'admin_inventory.php', 'label' => 'Inventory', 'icon' => 'fa-boxes-stacked'],
            ['href' => 'admin_users.php', 'label' => 'Users', 'icon' => 'fa-users-gear'],
            ['href' => 'admin_sales_report.php', 'label' => 'Reports', 'icon' => 'fa-file-invoice-dollar'],
        ];
        include __DIR__ . '/../config/app_footer.php';
        ?>
    </main>
    <script>
        const adminDailyLabels = <?php echo json_encode(array_column($dailySalesSeries, 'label')); ?>;
        const adminDailySales = <?php echo json_encode(array_column($dailySalesSeries, 'total_sales')); ?>;
        const adminMonthlyLabels = <?php echo json_encode(array_column($monthlySalesSeries, 'label')); ?>;
        const adminMonthlySales = <?php echo json_encode(array_column($monthlySalesSeries, 'total_sales')); ?>;
        const adminTopLabels = <?php echo json_encode($topSellingLabels); ?>;
        const adminTopQuantities = <?php echo json_encode($topSellingQuantities); ?>;
        const adminTopMax = Math.max(1, ...adminTopQuantities);
        const adminTopPercentages = adminTopQuantities.map((value) => Math.round((Number(value || 0) / adminTopMax) * 100));

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
                        borderColor: '#16c784',
                        backgroundColor: type === 'bar' ? 'rgba(22, 199, 132, .72)' : 'rgba(22, 199, 132, .14)',
                        borderWidth: 2,
                        fill: type === 'line',
                        tension: .36,
                        pointRadius: 4,
                        pointBackgroundColor: '#16c784',
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

        makeSalesChart('adminDailySalesChart', 'line', adminDailyLabels, adminDailySales, 'Daily sales');
        makeSalesChart('adminMonthlySalesChart', 'bar', adminMonthlyLabels, adminMonthlySales, 'Monthly sales');

        if (adminTopLabels.length) {
            new Chart(document.getElementById('adminTopSellingChart'), {
                type: 'bar',
                data: {
                    labels: adminTopLabels,
                    datasets: [{
                        label: 'Sold',
                        data: adminTopPercentages,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, .72)',
                        borderWidth: 2,
                        borderRadius: 8,
                        barThickness: 22
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => 'Top selling: ' + context.parsed.x + '%'
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            min: 0,
                            max: 100,
                            grid: { color: '#edf1f6' },
                            ticks: {
                                color: '#98a2b3',
                                font: { family: 'Poppins', size: 10 },
                                callback: (value) => value + '%'
                            }
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                color: '#667085',
                                font: { family: 'Poppins', size: 11, weight: '600' },
                                callback: (labelIndex) => {
                                    const label = adminTopLabels[labelIndex] || '';
                                    return String(label).length > 22 ? String(label).slice(0, 22) + '...' : label;
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
