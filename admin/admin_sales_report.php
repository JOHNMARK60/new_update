<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

use App\Reports\DailyReport;
use App\Reports\MonthlyReport;
use App\Reports\WeeklyReport;
use App\Reports\YearlyReport;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Services\DailySalesReportPdf;

function admin_csv_row($output, array $row): void
{
    fputcsv($output, $row, ',', '"', '');
}

function admin_export_daily_sales_csv(array $report, string $anchorDate): void
{
    $timestamp = strtotime($anchorDate);
    $safeDate = date('Y-m-d', $timestamp === false ? time() : $timestamp);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="daily-sales-' . $safeDate . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    $summary = $report['summary'];

    admin_csv_row($output, ['Daily Sales Report']);
    admin_csv_row($output, ['Date', $safeDate]);
    admin_csv_row($output, ['Period', $report['date_from'], $report['date_to']]);
    admin_csv_row($output, ['Generated At', date('Y-m-d H:i:s')]);
    admin_csv_row($output, []);
    admin_csv_row($output, ['Summary']);
    admin_csv_row($output, ['Transactions', (int) $summary['total_transactions']]);
    admin_csv_row($output, ['Items Sold', (int) $summary['total_items_sold']]);
    admin_csv_row($output, ['Total Sales', number_format((float) $summary['total_sales'], 2, '.', '')]);
    admin_csv_row($output, ['Void/Cancelled', (int) $summary['void_transactions']]);
    admin_csv_row($output, []);

    admin_csv_row($output, ['Items Sold']);
    admin_csv_row($output, ['Product', 'Category', 'Quantity Sold', 'Total Amount']);
    foreach ($report['items'] as $item) {
        admin_csv_row($output, [
            $item['product_name'],
            $item['category_name'],
            (int) $item['quantity_sold'],
            number_format((float) $item['total_amount'], 2, '.', ''),
        ]);
    }
    admin_csv_row($output, []);

    admin_csv_row($output, ['Payment Summary']);
    admin_csv_row($output, ['Method', 'Transactions', 'Sales Amount', 'Tendered', 'Change']);
    foreach ($report['payments'] as $payment) {
        admin_csv_row($output, [
            ucfirst((string) $payment['payment_method']),
            (int) $payment['transaction_count'],
            number_format((float) $payment['amount'], 2, '.', ''),
            number_format((float) $payment['tendered_amount'], 2, '.', ''),
            number_format((float) $payment['change_amount'], 2, '.', ''),
        ]);
    }
    admin_csv_row($output, []);

    admin_csv_row($output, ['Transactions']);
    admin_csv_row($output, ['Receipt', 'Cashier', 'Items', 'Total', 'Tendered', 'Change', 'Status', 'Closing', 'Date']);
    foreach ($report['transactions'] as $transaction) {
        admin_csv_row($output, [
            $transaction['receipt_no'] ?? str_pad((string) $transaction['id'], 6, '0', STR_PAD_LEFT),
            $transaction['cashier_name'] ?? 'N/A',
            (int) $transaction['items_sold'],
            number_format((float) $transaction['total_amount'], 2, '.', ''),
            number_format((float) $transaction['tendered_amount'], 2, '.', ''),
            number_format((float) $transaction['change_amount'], 2, '.', ''),
            ucfirst((string) ($transaction['status'] ?? 'paid')),
            ucfirst((string) ($transaction['closing_status'] ?? 'open')),
            date('Y-m-d H:i:s', strtotime((string) $transaction['sale_date'])),
        ]);
    }

    fclose($output);
    exit();
}

function admin_export_daily_sales_pdf(array $report, string $anchorDate, string $departmentIncharge): void
{
    $timestamp = strtotime($anchorDate);
    $safeDate = date('Y-m-d', $timestamp === false ? time() : $timestamp);
    $pdf = (new DailySalesReportPdf())->render($report, $departmentIncharge, $anchorDate);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="daily-sales-' . $safeDate . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $pdf;
    exit();
}

$reportType = $_GET['report_type'] ?? 'daily';
$anchorDate = $_GET['date'] ?? date('Y-m-d');
$filters = [
    'date' => $anchorDate,
    'cashier_id' => (int) ($_GET['cashier_id'] ?? 0),
    'product_id' => (int) ($_GET['product_id'] ?? 0),
    'category_id' => (int) ($_GET['category_id'] ?? 0),
    'status' => $_GET['status'] ?? '',
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

$reportGenerator = match ($reportType) {
    'weekly' => new WeeklyReport(),
    'monthly' => new MonthlyReport(),
    'yearly' => new YearlyReport(),
    default => new DailyReport(),
};
$report = $reportGenerator->generateReport($filters);
$summary = $report['summary'];

$productRepository = new ProductRepository($pdo);
$userRepository = new UserRepository($pdo);
$products = $productRepository->allWithMeta();
$categories = $productRepository->categories();
$cashiers = $userRepository->cashiers();
$selectedCashierId = (int) ($_GET['cashier_id'] ?? 0);
$selectedCashierName = 'All cashiers';

foreach ($cashiers as $cashier) {
    if ((int) $cashier['id'] === $selectedCashierId) {
        $selectedCashierName = trim($cashier['first_name'] . ' ' . $cashier['last_name']);
        break;
    }
}

if (($_GET['export'] ?? '') === 'daily_csv') {
    $dailyFilters = $filters;
    $dailyFilters['date'] = $anchorDate;
    $dailyReport = (new DailyReport())->generateReport($dailyFilters);
    admin_export_daily_sales_csv($dailyReport, $anchorDate);
}

if (($_GET['export'] ?? '') === 'daily_pdf') {
    $dailyFilters = $filters;
    $dailyFilters['date'] = $anchorDate;
    $dailyReport = (new DailyReport())->generateReport($dailyFilters);
    admin_export_daily_sales_pdf($dailyReport, $anchorDate, $selectedCashierName);
}

$csvExportQuery = array_merge($_GET, [
    'report_type' => 'daily',
    'date' => $anchorDate,
    'export' => 'daily_csv',
]);
$pdfExportQuery = array_merge($_GET, [
    'report_type' => 'daily',
    'date' => $anchorDate,
    'export' => 'daily_pdf',
]);

if (!empty($_GET)) {
    if ((int) $summary['total_transactions'] === 0) {
        swal_flash('info', 'No sales found for selected date.', 'Try another date range or cashier filter.');
    } else {
        swal_toast('success', 'Report generated successfully.');
    }
}

$pageTitle = 'Sales Report | Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="app-shell">
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <main class="admin-main">
        <?php
        $appHeaderRole = 'admin';
        $appHeaderRoleLabel = 'Administrator';
        $appHeaderTitle = $report['type'];
        $appHeaderSubtitle = date('M d, Y', strtotime((string) $report['date_from']))
            . ' to '
            . date('M d, Y', strtotime((string) $report['date_to']))
            . ' - Cashier: '
            . $selectedCashierName;
        $appHeaderIcon = 'fa-file-invoice-dollar';
        $appHeaderHome = 'admin_dashboard.php';
        $appHeaderShowSearch = false;
        $appHeaderActions = [
            [
                'href' => 'admin_sales_report.php?' . http_build_query($pdfExportQuery),
                'label' => 'Export Daily PDF',
                'icon' => 'fa-file-pdf',
                'class' => 'btn',
            ],
            [
                'href' => 'admin_sales_report.php?' . http_build_query($csvExportQuery),
                'label' => 'Export CSV',
                'icon' => 'fa-file-arrow-down',
                'class' => 'btn btn-secondary',
            ],
            [
                'href' => 'closing_validation.php',
                'label' => 'Closing',
                'icon' => 'fa-lock',
                'class' => 'btn btn-secondary',
            ],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <form method="GET" class="panel mb-5 grid gap-4 p-5 lg:grid-cols-5">
            <div class="form-group">
                <label>Report type</label>
                <select name="report_type">
                    <option value="daily" <?php echo $reportType === 'daily' ? 'selected' : ''; ?>>Daily</option>
                    <option value="weekly" <?php echo $reportType === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="monthly" <?php echo $reportType === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    <option value="yearly" <?php echo $reportType === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" value="<?php echo e($anchorDate); ?>">
            </div>
            <div class="form-group">
                <label>Cashier</label>
                <select name="cashier_id">
                    <option value="">All cashiers</option>
                    <?php foreach ($cashiers as $cashier): ?>
                        <option value="<?php echo (int) $cashier['id']; ?>" <?php echo (int) ($_GET['cashier_id'] ?? 0) === (int) $cashier['id'] ? 'selected' : ''; ?>>
                            <?php echo e($cashier['first_name'] . ' ' . $cashier['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Product</label>
                <select name="product_id">
                    <option value="">All products</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo (int) $product['id']; ?>" <?php echo (int) ($_GET['product_id'] ?? 0) === (int) $product['id'] ? 'selected' : ''; ?>>
                            <?php echo e($product['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category_id">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int) $category['id']; ?>" <?php echo (int) ($_GET['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : ''; ?>>
                            <?php echo e($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lg:col-span-5 flex justify-end">
                <button type="submit" class="btn">
                    <i class="fa-solid fa-filter"></i>
                    Apply Filters
                </button>
            </div>
        </form>

        <section class="dashboard-cards">
            <article class="dashboard-card">
                <p class="text-sm font-semibold text-slate-500">Transactions</p>
                <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo (int) $summary['total_transactions']; ?></h2>
            </article>
            <article class="dashboard-card">
                <p class="text-sm font-semibold text-slate-500">Items Sold</p>
                <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo (int) $summary['total_items_sold']; ?></h2>
            </article>
            <article class="dashboard-card">
                <p class="text-sm font-semibold text-slate-500">Total Sales</p>
                <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo money($summary['total_sales']); ?></h2>
            </article>
            <article class="dashboard-card">
                <p class="text-sm font-semibold text-slate-500">Void/Cancelled</p>
                <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo (int) $summary['void_transactions']; ?></h2>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <article class="panel overflow-hidden">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="text-base font-bold text-ink">Items Sold</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['items'] as $item): ?>
                                <tr>
                                    <td><strong><?php echo e($item['product_name']); ?></strong></td>
                                    <td><?php echo e($item['category_name']); ?></td>
                                    <td><?php echo (int) $item['quantity_sold']; ?></td>
                                    <td><?php echo money($item['total_amount']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($report['items'] === []): ?>
                                <tr><td colspan="4" class="py-10 text-center text-slate-500">No items sold in this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="panel overflow-hidden">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="text-base font-bold text-ink">Cashier Performance</h2>
                    <p class="mt-1 text-sm text-slate-500">Admin view of sales grouped by each cashier.</p>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Cashier</th>
                                <th>Transactions</th>
                                <th>Items</th>
                                <th>Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['cashiers'] as $cashier): ?>
                                <tr>
                                    <td><strong><?php echo e($cashier['cashier_name']); ?></strong></td>
                                    <td><?php echo (int) $cashier['total_transactions']; ?></td>
                                    <td><?php echo (int) $cashier['total_items_sold']; ?></td>
                                    <td><?php echo money($cashier['total_sales']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($report['cashiers'] === []): ?>
                                <tr><td colspan="4" class="py-10 text-center text-slate-500">No cashier sales in this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="panel mt-5 overflow-hidden">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-base font-bold text-ink">Payment Summary</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Transactions</th>
                            <th>Sales Amount</th>
                            <th>Tendered</th>
                            <th>Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['payments'] as $payment): ?>
                            <tr>
                                <td><strong><?php echo e(ucfirst($payment['payment_method'])); ?></strong></td>
                                <td><?php echo (int) $payment['transaction_count']; ?></td>
                                <td><?php echo money($payment['amount']); ?></td>
                                <td><?php echo money($payment['tendered_amount']); ?></td>
                                <td><?php echo money($payment['change_amount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($report['payments'] === []): ?>
                            <tr><td colspan="5" class="py-10 text-center text-slate-500">No payments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel mt-5 overflow-hidden">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-base font-bold text-ink">Transactions</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Cashier</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Tendered</th>
                            <th>Change</th>
                            <th>Status</th>
                            <th>Closing</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['transactions'] as $row): ?>
                            <tr>
                                <td><strong><?php echo e($row['receipt_no'] ?? str_pad((string) $row['id'], 6, '0', STR_PAD_LEFT)); ?></strong></td>
                                <td><?php echo e($row['cashier_name'] ?? 'N/A'); ?></td>
                                <td><?php echo (int) $row['items_sold']; ?></td>
                                <td class="font-bold text-mint"><?php echo money($row['total_amount']); ?></td>
                                <td><?php echo money($row['tendered_amount']); ?></td>
                                <td><?php echo money($row['change_amount']); ?></td>
                                <td><span class="status-paid"><?php echo e(ucfirst($row['status'] ?? 'paid')); ?></span></td>
                                <td><?php echo e(ucfirst($row['closing_status'] ?? 'open')); ?></td>
                                <td><?php echo e(date('M d, Y h:i A', strtotime($row['sale_date']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($report['transactions'] === []): ?>
                            <tr>
                                <td colspan="9" class="py-10 text-center text-slate-500">No transactions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
