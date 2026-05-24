<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

function cashier_inventory_category_label(array $product): string
{
    $category = trim((string) ($product['category_name'] ?? ''));

    return $category !== '' ? $category : 'Other Products / Uncategorized';
}

function cashier_inventory_stock_status(array $product): array
{
    $quantity = (int) ($product['quantity'] ?? 0);
    $lowStockLevel = (int) ($product['low_stock_level'] ?? 5);

    if ($quantity === 0) {
        return ['key' => 'out', 'label' => 'Out of Stock', 'class' => 'status-out'];
    }

    if ($quantity <= $lowStockLevel) {
        return ['key' => 'low', 'label' => 'Low Stock', 'class' => 'status-low'];
    }

    return ['key' => 'ok', 'label' => 'In Stock', 'class' => 'status-ok'];
}

function cashier_inventory_group_products(array $products): array
{
    usort($products, static function (array $first, array $second): int {
        $other = 'Other Products / Uncategorized';
        $firstCategory = cashier_inventory_category_label($first);
        $secondCategory = cashier_inventory_category_label($second);

        if ($firstCategory === $other && $secondCategory !== $other) {
            return 1;
        }

        if ($secondCategory === $other && $firstCategory !== $other) {
            return -1;
        }

        $categoryCompare = strcasecmp($firstCategory, $secondCategory);

        if ($categoryCompare !== 0) {
            return $categoryCompare;
        }

        $nameCompare = strcasecmp((string) ($first['name'] ?? ''), (string) ($second['name'] ?? ''));

        if ($nameCompare !== 0) {
            return $nameCompare;
        }

        return (int) ($first['id'] ?? 0) <=> (int) ($second['id'] ?? 0);
    });

    $groups = [];

    foreach ($products as $product) {
        $category = cashier_inventory_category_label($product);
        $status = cashier_inventory_stock_status($product);

        if (!isset($groups[$category])) {
            $groups[$category] = [
                'products' => [],
                'low' => 0,
                'out' => 0,
            ];
        }

        $groups[$category]['products'][] = $product;

        if ($status['key'] === 'low') {
            $groups[$category]['low']++;
        }

        if ($status['key'] === 'out') {
            $groups[$category]['out']++;
        }
    }

    return $groups;
}

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
    "SELECT p.*, COALESCE(NULLIF(c.name, ''), 'Other Products / Uncategorized') AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     {$where}
     ORDER BY CASE WHEN c.name IS NULL OR c.name = '' THEN 1 ELSE 0 END ASC,
              c.name ASC,
              p.name ASC,
              p.id ASC"
);
$stmt->execute($params);
$products = $stmt->fetchAll();
$productGroups = cashier_inventory_group_products($products);
$categoryFilters = array_keys($productGroups);
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

            <section class="inventory-browser cashier-inventory-browser" data-inventory-browser>
                <div class="inventory-browser-header">
                    <div>
                        <p class="inventory-eyebrow">Stock by category</p>
                        <h2>Stock Table</h2>
                        <p>
                            <?php echo number_format(count($products)); ?> visible product<?php echo count($products) === 1 ? '' : 's'; ?> arranged by category.
                            <?php if ($search !== ''): ?>
                                Showing results for "<?php echo e($search); ?>"
                                <a href="inventory.php">Clear</a>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="inventory-filter-panel" data-inventory-controls aria-label="Stock filters">
                    <button type="button" class="inventory-filter-chip is-active" data-inventory-filter data-filter-type="all" data-filter-value="">
                        <i class="fa-solid fa-layer-group"></i>
                        All Categories
                    </button>
                    <?php foreach ($categoryFilters as $categoryName): ?>
                        <button type="button" class="inventory-filter-chip" data-inventory-filter data-filter-type="category" data-filter-value="<?php echo e($categoryName); ?>">
                            <?php echo e($categoryName); ?>
                        </button>
                    <?php endforeach; ?>
                    <button type="button" class="inventory-filter-chip" data-inventory-filter data-filter-type="status" data-filter-value="low">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Low Stock
                    </button>
                    <button type="button" class="inventory-filter-chip" data-inventory-filter data-filter-type="status" data-filter-value="out">
                        <i class="fa-solid fa-circle-xmark"></i>
                        Out of Stock
                    </button>
                </div>

                <div class="inventory-category-list" data-inventory-list>
                    <?php foreach ($productGroups as $categoryName => $group): ?>
                        <section class="inventory-category-section" data-category-section data-category="<?php echo e($categoryName); ?>">
                            <header class="inventory-category-head">
                                <div>
                                    <p>Category</p>
                                    <h3><?php echo e($categoryName); ?></h3>
                                </div>
                                <div class="inventory-category-metrics">
                                    <span><strong><?php echo number_format(count($group['products'])); ?></strong> products</span>
                                    <span class="stock-badge status-low"><?php echo (int) $group['low']; ?> low stock</span>
                                    <span class="stock-badge status-out"><?php echo (int) $group['out']; ?> out</span>
                                </div>
                            </header>

                            <div class="table-responsive">
                                <table class="cashier-stock-table inventory-category-table">
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
                                        <?php foreach ($group['products'] as $row): ?>
                                            <?php
                                            $qty = (int) $row['quantity'];
                                            $lowLevel = (int) ($row['low_stock_level'] ?? 5);
                                            $sku = trim((string) ($row['sku'] ?? ''));
                                            $image_path = trim((string) ($row['image_path'] ?? ''));
                                            $image_url = $image_path !== '' ? app_url($image_path) : '';
                                            $status = cashier_inventory_stock_status($row);
                                            $searchText = strtolower($row['name'] . ' ' . $categoryName . ' ' . $sku);
                                            ?>
                                            <tr
                                                class="inventory-product-row"
                                                data-product-row
                                                data-category="<?php echo e($categoryName); ?>"
                                                data-status="<?php echo e($status['key']); ?>"
                                                data-search="<?php echo e($searchText); ?>">
                                                <td>
                                                    <?php if ($image_url): ?>
                                                        <img src="<?php echo e($image_url); ?>" alt="<?php echo e($row['name']); ?>" class="admin-thumb">
                                                    <?php else: ?>
                                                        <div class="admin-thumb admin-thumb-empty"><i class="fa-solid fa-box"></i></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo e($row['name']); ?></strong>
                                                    <small class="inventory-product-subtext">
                                                        SKU: <?php echo e($sku !== '' ? $sku : 'RF-' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT)); ?>
                                                    </small>
                                                </td>
                                                <td><span class="inventory-category-pill"><?php echo e($categoryName); ?></span></td>
                                                <td><?php echo money($row['price']); ?></td>
                                                <td class="font-extrabold <?php echo $qty <= $lowLevel ? 'text-[var(--rf-orange)]' : 'text-ink'; ?>">
                                                    <?php echo $qty; ?> <small class="inventory-product-subtext">Units</small>
                                                </td>
                                                <td><?php echo $lowLevel; ?></td>
                                                <td><span class="stock-badge <?php echo e($status['class']); ?>"><?php echo e($status['label']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>

                <div class="inventory-empty-state <?php echo $productGroups === [] ? '' : 'hidden'; ?>" data-inventory-empty>
                    <i class="fa-solid fa-box-open"></i>
                    <strong>No stock records found</strong>
                    <span>Try another search keyword, category, or stock filter.</span>
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
