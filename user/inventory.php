<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

$total_p = (int) $pdo->query('SELECT COUNT(id) FROM products')->fetchColumn();
$low_p = (int) $pdo->query('SELECT COUNT(id) FROM products WHERE quantity > 0 AND quantity <= low_stock_level')->fetchColumn();
$out_p = (int) $pdo->query('SELECT COUNT(id) FROM products WHERE quantity = 0')->fetchColumn();
$search = trim((string) ($_GET['search'] ?? ''));
$params = [];
$where = '';

if ($search !== '') {
    $where = 'WHERE p.name LIKE :search_name OR p.sku LIKE :search_sku OR c.name LIKE :search_category';
    $searchTerm = '%' . $search . '%';
    $params = [
        'search_name' => $searchTerm,
        'search_sku' => $searchTerm,
        'search_category' => $searchTerm,
    ];
}

$stmt = $pdo->prepare(
    "SELECT p.*, COALESCE(c.name, 'Uncategorized') AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     {$where}
     ORDER BY p.quantity ASC, p.name ASC"
);
$stmt->execute($params);
$products = $stmt->fetchAll();
$pageTitle = 'Stock | KANTO GOODS';
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
        $appHeaderTitle = 'Stock';
        $appHeaderSubtitle = 'Inventory list and low-stock alerts.';
        $appHeaderIcon = 'fa-warehouse';
        $appHeaderHome = 'user_dashboard.php';
        $appHeaderSearchPlaceholder = 'Search stock by product, category or SKU...';
        $appHeaderSearchAction = 'inventory.php';
        $appHeaderSearchName = 'search';
        $appHeaderSearchValue = $search;
        $appHeaderActions = [
            ['href' => 'cashier_sales.php', 'label' => 'Go to POS', 'icon' => 'fa-cash-register', 'class' => 'btn'],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <div class="rf-mobile-shell">
            <section class="dashboard-cards">
                <article class="dashboard-card">
                    <div class="flex items-center gap-4">
                        <div class="text-3xl text-[var(--rf-blue)]"><i class="fa-solid fa-box-archive"></i></div>
                        <p class="text-xl font-bold text-slate-700">Total Catalog</p>
                    </div>
                    <h2 class="mt-5 text-5xl font-extrabold text-black"><?php echo number_format((int) $total_p); ?></h2>
                    <p class="mt-4 font-bold text-[var(--rf-green)]"><i class="fa-solid fa-arrow-trend-up"></i> Updated today</p>
                </article>

                <article class="dashboard-card">
                    <div class="flex items-center gap-4">
                        <div class="text-3xl text-[var(--rf-orange)]"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <p class="text-xl font-bold text-slate-700">Low Stock</p>
                    </div>
                    <h2 class="mt-5 text-5xl font-extrabold text-black"><?php echo (int) $low_p; ?></h2>
                    <p class="mt-4 font-bold text-[var(--rf-orange)]">Action required</p>
                </article>

                <article class="dashboard-card">
                    <div class="flex items-center gap-4">
                        <div class="text-3xl text-red-700"><i class="fa-solid fa-circle-xmark"></i></div>
                        <p class="text-xl font-bold text-slate-700">Out of Stock</p>
                    </div>
                    <h2 class="mt-5 text-5xl font-extrabold text-black"><?php echo (int) $out_p; ?></h2>
                    <p class="mt-4 font-bold text-red-700">Needs restock</p>
                </article>
            </section>

            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-black">Stock Table</h2>
                    <p class="mt-2 text-sm font-semibold text-slate-500">
                        <?php echo number_format(count($products)); ?> visible product<?php echo count($products) === 1 ? '' : 's'; ?>.
                    </p>
                    <?php if ($search !== ''): ?>
                        <p class="mt-2 text-sm font-semibold text-slate-500">
                            Showing results for "<?php echo e($search); ?>"
                            <a href="inventory.php" class="ml-2 text-[var(--rf-green)]">Clear</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <section class="panel overflow-hidden cashier-stock-panel">
                <div class="table-responsive">
                    <table class="cashier-stock-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Low Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $row): ?>
                                <?php
                                $qty = (int) $row['quantity'];
                                $lowLevel = (int) ($row['low_stock_level'] ?? 5);
                                $sku = trim((string) ($row['sku'] ?? ''));
                                $image_path = trim((string) ($row['image_path'] ?? ''));
                                $image_url = $image_path !== '' ? app_url($image_path) : '';
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($image_url): ?>
                                            <img src="<?php echo e($image_url); ?>" alt="<?php echo e($row['name']); ?>" class="admin-thumb">
                                        <?php else: ?>
                                            <div class="admin-thumb admin-thumb-empty"><i class="fa-solid fa-box"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo e($row['name']); ?></strong>
                                        <small class="block font-mono text-xs font-bold text-slate-500">
                                            SKU: <?php echo e($sku !== '' ? $sku : 'RF-' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT)); ?>
                                        </small>
                                    </td>
                                    <td><?php echo e($row['category_name']); ?></td>
                                    <td><?php echo money($row['price']); ?></td>
                                    <td class="font-extrabold <?php echo $qty <= $lowLevel ? 'text-[var(--rf-orange)]' : 'text-ink'; ?>">
                                        <?php echo $qty; ?>
                                    </td>
                                    <td><?php echo $lowLevel; ?></td>
                                    <td>
                                        <?php if ($qty === 0): ?>
                                            <span class="stock-badge status-out">Out of Stock</span>
                                        <?php elseif ($qty <= $lowLevel): ?>
                                            <span class="stock-badge status-low">Low Stock</span>
                                        <?php else: ?>
                                            <span class="stock-badge status-ok">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($products === []): ?>
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-slate-500">No stock records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <?php
        $appFooterRole = 'cashier';
        $appFooterRoleLabel = 'Cashier';
        $appFooterLinks = [
            ['href' => 'user_dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-table-columns'],
            ['href' => 'cashier_products.php', 'label' => 'Products', 'icon' => 'fa-box'],
            ['href' => 'cashier_reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-pie'],
        ];
        include __DIR__ . '/../config/app_footer.php';
        ?>
    </main>
</body>
</html>
