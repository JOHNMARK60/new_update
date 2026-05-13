<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$query = "SELECT s.id, p.name AS product_name, s.quantity, s.total_price, s.sale_date, u.first_name
          FROM sales s
          LEFT JOIN products p ON s.product_id = p.id
          LEFT JOIN users u ON s.user_id = u.id
          ORDER BY s.sale_date DESC";
$result = mysqli_query($conn, $query);
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
        <header class="page-topbar">
            <div>
                <h1 class="page-title">Sales Report</h1>
                <p class="page-subtitle">All cashier transactions, newest first.</p>
            </div>
            <span class="badge bg-white text-ink shadow-sm">
                <i class="fa-solid fa-file-invoice-dollar text-brand"></i>
                Transactions
            </span>
        </header>

        <section class="panel overflow-hidden">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Cashier</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo (int) $row['id']; ?></td>
                                    <td><strong><?php echo e($row['product_name'] ?? 'Deleted product'); ?></strong></td>
                                    <td><?php echo (int) $row['quantity']; ?></td>
                                    <td class="font-bold text-mint"><?php echo money($row['total_price']); ?></td>
                                    <td><span class="role-badge"><?php echo e($row['first_name'] ?? 'N/A'); ?></span></td>
                                    <td><?php echo e(date('M d, Y h:i A', strtotime($row['sale_date']))); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-500">No transactions found yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
