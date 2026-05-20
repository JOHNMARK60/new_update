<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

use App\Repositories\SaleRepository;
use App\Services\Auth;
use App\Services\ClosingValidation;

$saleRepository = new SaleRepository($pdo);
$closingValidation = new ClosingValidation($pdo, $saleRepository);
$message = '';
$errorMessage = '';
$selectedDate = $_GET['date'] ?? $_POST['closing_date'] ?? date('Y-m-d');
$cashierId = Auth::userId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $closingValidation->closeDay(
            (string) ($_POST['closing_date'] ?? date('Y-m-d')),
            $cashierId,
            Auth::cashierName(),
            $cashierId,
            (float) ($_POST['actual_cash_amount'] ?? 0),
            (string) ($_POST['notes'] ?? '')
        );
        $message = 'Your daily closing has been saved.';
        swal_flash('success', 'Daily sales closed successfully.', 'Your closing report has been saved.');
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
        swal_flash('error', 'Closing failed.', $e->getMessage());
    }
}

$summary = $saleRepository->summary([
    'date_from' => $selectedDate,
    'date_to' => $selectedDate,
    'cashier_id' => $cashierId,
    'status' => 'paid',
    'closing_status' => 'open',
]);
$expectedCashAmount = round((float) $summary['total_sales'], 2);
$closings = $closingValidation->closings([
    'date_from' => date('Y-m-01', strtotime($selectedDate)),
    'date_to' => date('Y-m-d', strtotime($selectedDate)),
    'cashier_id' => $cashierId,
]);
$pageTitle = 'Daily Closing | Cashier';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="app-shell">
    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <main class="main-content">
        <?php
        $appHeaderRole = 'cashier';
        $appHeaderRoleLabel = 'Cashier';
        $appHeaderKicker = 'Cashier workspace';
        $appHeaderTitle = 'Daily Closing';
        $appHeaderSubtitle = Auth::cashierName() . ' | ' . date('M d, Y', strtotime($selectedDate)) . '. Actual cash is auto-filled from expected sales for this date.';
        $appHeaderIcon = 'fa-lock';
        $appHeaderHome = 'user_dashboard.php';
        $appHeaderShowSearch = false;
        $appHeaderActions = [
            ['href' => 'cashier_reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-pie', 'class' => 'btn btn-secondary'],
            ['href' => 'cashier_sales.php', 'label' => 'Open POS', 'icon' => 'fa-cash-register', 'class' => 'btn'],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <?php if ($message !== ''): ?>
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"><?php echo e($message); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?php echo e($errorMessage); ?></div>
        <?php endif; ?>

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
                <p class="text-sm font-semibold text-slate-500">Expected Cash</p>
                <h2 class="mt-1 text-3xl font-extrabold text-ink"><?php echo money($summary['total_sales']); ?></h2>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[360px_minmax(0,1fr)]">
            <form method="POST" class="panel p-6">
                <h2 class="text-lg font-extrabold text-ink">Close My Day</h2>
                <div class="mt-5 grid gap-4">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="closing_date" value="<?php echo e($selectedDate); ?>" data-closing-date required>
                    </div>
                    <div class="form-group">
                        <label>Actual Cash Amount</label>
                        <input type="number" step="0.01" min="0" name="actual_cash_amount" value="<?php echo e(number_format($expectedCashAmount, 2, '.', '')); ?>" required>
                        <small class="field-note">Auto-filled from expected sales: <?php echo money($expectedCashAmount); ?></small>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" rows="3"></textarea>
                    </div>
                    <button
                        type="submit"
                        class="btn"
                        data-swal-confirm="Close daily sales?"
                        data-swal-text="Once closed, today's sales cannot be edited unless authorized by admin."
                        data-swal-confirm-text="Yes, close sales">
                        <i class="fa-solid fa-lock"></i>
                        Save Closing
                    </button>
                </div>
            </form>

            <article class="panel overflow-hidden">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="text-base font-bold text-ink">My Closing Reports</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Sales</th>
                                <th>Actual</th>
                                <th>Difference</th>
                                <th>Feedback</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($closings as $closing): ?>
                                <tr>
                                    <td><?php echo e(date('M d, Y', strtotime($closing['closing_date']))); ?></td>
                                    <td><?php echo money($closing['total_sales']); ?></td>
                                    <td><?php echo money($closing['actual_cash_amount']); ?></td>
                                    <td class="<?php echo (float) $closing['difference_amount'] < 0 ? 'text-red-700' : 'text-mint'; ?> font-bold">
                                        <?php echo money($closing['difference_amount']); ?>
                                    </td>
                                    <td>
                                        <?php if (trim((string) ($closing['admin_feedback'] ?? '')) !== ''): ?>
                                            <span class="font-semibold text-red-700"><?php echo e($closing['admin_feedback']); ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-400">No feedback</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e(date('h:i A', strtotime($closing['closing_time']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($closings === []): ?>
                                <tr><td colspan="6" class="py-10 text-center text-slate-500">No closing reports yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <?php
        $appFooterRole = 'cashier';
        $appFooterRoleLabel = 'Cashier';
        $appFooterLinks = [
            ['href' => 'user_dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-table-columns'],
            ['href' => 'cashier_sales.php', 'label' => 'POS', 'icon' => 'fa-cash-register'],
            ['href' => 'cashier_reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-pie'],
        ];
        include __DIR__ . '/../config/app_footer.php';
        ?>
    </main>
    <script>
        document.querySelector('[data-closing-date]')?.addEventListener('change', (event) => {
            const value = event.target.value;
            if (value) {
                window.location.href = 'cashier_closing.php?date=' + encodeURIComponent(value);
            }
        });
    </script>
</body>
</html>
