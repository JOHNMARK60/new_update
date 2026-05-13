<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$logs = mysqli_query(
    $conn,
    "SELECT l.id, l.action, l.created_at, p.name AS product_name
     FROM inventory_logs l
     LEFT JOIN products p ON l.product_id = p.id
     ORDER BY l.created_at DESC, l.id DESC"
);
$pageTitle = 'Inventory Logs | Admin';
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
                <h1 class="page-title">Inventory Logs</h1>
                <p class="page-subtitle">Recent product movements and stock updates.</p>
            </div>
            <a href="admin_inventory.php" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Inventory
            </a>
        </header>

        <section class="panel overflow-hidden">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Product</th>
                            <th>Action</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs && mysqli_num_rows($logs) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($logs)): ?>
                                <tr>
                                    <td><?php echo (int) $row['id']; ?></td>
                                    <td><strong><?php echo e($row['product_name'] ?? 'Deleted product'); ?></strong></td>
                                    <td><?php echo e($row['action']); ?></td>
                                    <td><?php echo e(date('M d, Y h:i A', strtotime($row['created_at']))); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-10 text-center text-slate-500">No inventory activity yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
