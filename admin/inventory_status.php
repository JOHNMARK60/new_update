<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$logs = $pdo->query(
    "SELECT l.id, l.action, l.quantity_change, l.stock_before, l.stock_after, l.created_at,
            p.name AS product_name,
            CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS created_by_name
     FROM inventory_logs l
     LEFT JOIN products p ON l.product_id = p.id
     LEFT JOIN users u ON u.id = l.created_by
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
        <?php
        $appHeaderRole = 'admin';
        $appHeaderRoleLabel = 'Administrator';
        $appHeaderBrandTitle = 'KANTO GOODS';
        $appHeaderBrandSubtitle = 'Admin Console';
        $appHeaderBrandIcon = 'fa-box-open';
        $appHeaderTitle = 'Inventory Logs';
        $appHeaderSubtitle = 'Recent product movements and stock updates.';
        $appHeaderIcon = 'fa-clock-rotate-left';
        $appHeaderHome = 'admin_dashboard.php';
        $appHeaderActions = [
            [
                'href' => 'admin_inventory.php',
                'label' => 'Inventory',
                'icon' => 'fa-arrow-left',
                'class' => 'btn btn-secondary',
            ],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <section class="panel overflow-hidden">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Product</th>
                            <th>Action</th>
                            <th>Qty Change</th>
                            <th>Stock Before</th>
                            <th>Stock After</th>
                            <th>By</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $logRows = $logs->fetchAll(); ?>
                        <?php if ($logRows): ?>
                            <?php foreach ($logRows as $row): ?>
                                <tr>
                                    <td><?php echo (int) $row['id']; ?></td>
                                    <td><strong><?php echo e($row['product_name'] ?? 'Deleted product'); ?></strong></td>
                                    <td><?php echo e($row['action']); ?></td>
                                    <td><?php echo (int) ($row['quantity_change'] ?? 0); ?></td>
                                    <td><?php echo $row['stock_before'] === null ? 'N/A' : (int) $row['stock_before']; ?></td>
                                    <td><?php echo $row['stock_after'] === null ? 'N/A' : (int) $row['stock_after']; ?></td>
                                    <td><?php echo e(trim($row['created_by_name'] ?? '') ?: 'System'); ?></td>
                                    <td><?php echo e(date('M d, Y h:i A', strtotime($row['created_at']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-500">No inventory activity yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
