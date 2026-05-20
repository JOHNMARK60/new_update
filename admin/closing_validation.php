<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

use App\Repositories\SaleRepository;
use App\Repositories\UserRepository;
use App\Services\AdminNotification;
use App\Services\ClosingValidation;

$userRepository = new UserRepository($pdo);
$saleRepository = new SaleRepository($pdo);
$closingValidation = new ClosingValidation($pdo, $saleRepository);
$adminNotification = new AdminNotification($pdo);
$adminNotification->markClosingNotificationsRead();
$cashiers = $userRepository->cashiers();
$message = '';
$errorMessage = '';
$selectedDate = $_GET['date'] ?? $_POST['closing_date'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cashierId = (int) ($_POST['cashier_id'] ?? 0);
        $cashier = $userRepository->find($cashierId);
        $cashierName = $cashier ? trim($cashier['first_name'] . ' ' . $cashier['last_name']) : '';
        $closingValidation->closeDay(
            (string) ($_POST['closing_date'] ?? date('Y-m-d')),
            $cashierId,
            $cashierName,
            (int) $_SESSION['user_id'],
            (float) ($_POST['actual_cash_amount'] ?? 0),
            (string) ($_POST['notes'] ?? '')
        );
        $message = 'Closing report saved successfully.';
        swal_flash('success', 'Daily sales closed successfully.', 'End-of-day closing report has been generated.');
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
        swal_flash('error', 'Closing failed.', $e->getMessage());
    }
}

$openPerformance = $saleRepository->cashierPerformance([
    'date_from' => $selectedDate,
    'date_to' => $selectedDate,
    'status' => 'paid',
    'closing_status' => 'open',
]);
$closings = $closingValidation->closings([
    'date_from' => $_GET['date_from'] ?? date('Y-m-01'),
    'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
    'cashier_id' => (int) ($_GET['cashier_id'] ?? 0),
]);
$pageTitle = 'Closing Validation | Admin';
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
        $appHeaderTitle = 'End-of-Day Closing';
        $appHeaderSubtitle = 'Validate actual cash against transaction-based sales.';
        $appHeaderIcon = 'fa-lock';
        $appHeaderHome = 'admin_dashboard.php';
        $appHeaderShowSearch = false;
        $appHeaderActions = [
            [
                'href' => 'admin_sales_report.php',
                'label' => 'Sales Reports',
                'icon' => 'fa-file-invoice-dollar',
                'class' => 'btn btn-secondary',
            ],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <?php if ($message !== ''): ?>
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"><?php echo e($message); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?php echo e($errorMessage); ?></div>
        <?php endif; ?>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
            <article class="panel overflow-hidden">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="text-base font-bold text-ink">Open Sales for <?php echo e(date('M d, Y', strtotime($selectedDate))); ?></h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Cashier</th>
                                <th>Transactions</th>
                                <th>Items Sold</th>
                                <th>Expected Cash</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($openPerformance as $row): ?>
                                <tr>
                                    <td><strong><?php echo e($row['cashier_name']); ?></strong></td>
                                    <td><?php echo (int) $row['total_transactions']; ?></td>
                                    <td><?php echo (int) $row['total_items_sold']; ?></td>
                                    <td class="font-bold text-mint"><?php echo money($row['total_sales']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($openPerformance === []): ?>
                                <tr><td colspan="4" class="py-10 text-center text-slate-500">No paid sales for this date.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <form method="POST" class="panel p-6">
                <h2 class="text-lg font-extrabold text-ink">Close Cashier Day</h2>
                <div class="mt-5 grid gap-4">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="closing_date" value="<?php echo e($selectedDate); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Cashier</label>
                        <select name="cashier_id" required>
                            <option value="">Select cashier...</option>
                            <?php foreach ($cashiers as $cashier): ?>
                                <option value="<?php echo (int) $cashier['id']; ?>">
                                    <?php echo e($cashier['first_name'] . ' ' . $cashier['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Actual Cash Amount</label>
                        <input type="number" step="0.01" min="0" name="actual_cash_amount" required>
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
        </section>

        <section class="panel mt-5 overflow-hidden">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-base font-bold text-ink">Closing Reports</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Cashier</th>
                            <th>Transactions</th>
                            <th>Items</th>
                            <th>Total Sales</th>
                            <th>Expected</th>
                            <th>Actual</th>
                            <th>Difference</th>
                            <th>Feedback</th>
                            <th>Closed By</th>
                            <th>Closing Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($closings as $closing): ?>
                            <tr>
                                <td><?php echo e(date('M d, Y', strtotime($closing['closing_date']))); ?></td>
                                <td><strong><?php echo e($closing['cashier_name']); ?></strong></td>
                                <td><?php echo (int) $closing['total_transactions']; ?></td>
                                <td><?php echo (int) $closing['total_items_sold']; ?></td>
                                <td><?php echo money($closing['total_sales']); ?></td>
                                <td><?php echo money($closing['expected_cash_amount']); ?></td>
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
                                <td><?php echo e(trim($closing['closed_by_name'] ?? '') ?: 'N/A'); ?></td>
                                <td><?php echo e(date('M d, Y h:i A', strtotime($closing['closing_time']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($closings === []): ?>
                            <tr><td colspan="11" class="py-10 text-center text-slate-500">No closing reports found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
