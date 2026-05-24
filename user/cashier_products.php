<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

function cashier_products_stock_status(array $product): array
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

function cashier_products_group_by_category(array $products): array
{
    usort($products, static function (array $first, array $second): int {
        $other = 'Other Products / Uncategorized';
        $firstCategory = trim((string) ($first['category_name'] ?? ''));
        $secondCategory = trim((string) ($second['category_name'] ?? ''));
        $firstCategory = $firstCategory !== '' ? $firstCategory : $other;
        $secondCategory = $secondCategory !== '' ? $secondCategory : $other;

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
        $category = trim((string) ($product['category_name'] ?? ''));
        $category = $category !== '' ? $category : 'Other Products / Uncategorized';
        $status = cashier_products_stock_status($product);

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
$productGroups = cashier_products_group_by_category($products);
$categories = array_keys($productGroups);
$pageTitle = 'Products | KANTO GOODS';
$index = 0;
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
        $appHeaderTitle = 'Products';
        $appHeaderSubtitle = 'Browse prices, stock status, and available catalog items.';
        $appHeaderIcon = 'fa-box';
        $appHeaderHome = 'user_dashboard.php';
        $appHeaderSearchPlaceholder = 'Search products by name, category or SKU...';
        $appHeaderSearchAction = 'cashier_products.php';
        $appHeaderSearchName = 'search';
        $appHeaderSearchValue = $search;
        $appHeaderActions = [
            ['href' => 'cashier_sales.php', 'label' => 'Go to POS', 'icon' => 'fa-cash-register', 'class' => 'btn'],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <div class="rf-mobile-shell product-catalog-shell inventory-browser" data-inventory-browser>
            <div class="inventory-browser-header">
                <div>
                    <p class="inventory-eyebrow">Catalog by category</p>
                    <h2>Products</h2>
                    <p>
                        <?php echo number_format(count($products)); ?> visible product<?php echo count($products) === 1 ? '' : 's'; ?> arranged by category.
                        <?php if ($search !== ''): ?>
                            Showing results for "<?php echo e($search); ?>"
                            <a href="cashier_products.php">Clear</a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="rf-filter-row product-category-row inventory-filter-panel" data-inventory-controls aria-label="Product categories">
                <button type="button" class="rf-chip rf-chip-active inventory-filter-chip is-active" data-inventory-filter data-filter-type="all" data-filter-value="">
                    All Categories
                </button>
                <?php foreach ($categories as $category): ?>
                    <button type="button" class="rf-chip inventory-filter-chip" data-inventory-filter data-filter-type="category" data-filter-value="<?php echo e($category); ?>">
                        <?php echo e($category); ?>
                    </button>
                <?php endforeach; ?>
                <button type="button" class="rf-chip inventory-filter-chip" data-inventory-filter data-filter-type="status" data-filter-value="low">Low Stock</button>
                <button type="button" class="rf-chip inventory-filter-chip" data-inventory-filter data-filter-type="status" data-filter-value="out">Out of Stock</button>
            </div>

            <div class="inventory-category-list" data-inventory-list>
                <?php foreach ($productGroups as $category => $group): ?>
                    <section class="inventory-category-section product-category-section" data-category-section data-category="<?php echo e($category); ?>">
                        <header class="inventory-category-head">
                            <div>
                                <p>Category</p>
                                <h3><?php echo e($category); ?></h3>
                            </div>
                            <div class="inventory-category-metrics">
                                <span><strong><?php echo number_format(count($group['products'])); ?></strong> products</span>
                                <span class="stock-badge status-low"><?php echo (int) $group['low']; ?> low stock</span>
                                <span class="stock-badge status-out"><?php echo (int) $group['out']; ?> out</span>
                            </div>
                        </header>

                        <div class="rf-product-grid">
                            <?php foreach ($group['products'] as $row): ?>
                                <?php
                                $qty = (int) $row['quantity'];
                                $sku = trim((string) ($row['sku'] ?? ''));
                                $alt = ($index % 4) + 1;
                                $icon = ['fa-bottle-water', 'fa-basket-shopping', 'fa-box', 'fa-book'][$index % 4];
                                $image_path = trim((string) ($row['image_path'] ?? ''));
                                $image_url = $image_path !== '' ? app_url($image_path) : '';
                                $status = cashier_products_stock_status($row);
                                $searchText = strtolower($row['name'] . ' ' . $category . ' ' . $sku);
                                $index++;
                                ?>
                                <article
                                    class="rf-product-card product-catalog-card inventory-product-row"
                                    data-product-row
                                    data-name="<?php echo e(strtolower($row['name'])); ?>"
                                    data-category="<?php echo e($category); ?>"
                                    data-status="<?php echo e($status['key']); ?>"
                                    data-search="<?php echo e($searchText); ?>">
                                    <div class="rf-product-image alt-<?php echo $alt; ?><?php echo $image_url ? ' has-photo' : ''; ?>">
                                        <?php if ($image_url): ?>
                                            <img src="<?php echo e($image_url); ?>" alt="<?php echo e($row['name']); ?>" class="rf-product-photo">
                                        <?php else: ?>
                                            <i class="fa-solid <?php echo $icon; ?>"></i>
                                        <?php endif; ?>
                                    </div>

                                    <div class="absolute right-4 top-4">
                                        <span class="stock-badge <?php echo e($status['class']); ?>"><?php echo e($status['label']); ?></span>
                                    </div>

                                    <div class="rf-product-body">
                                        <h2 class="rf-product-title"><?php echo e($row['name']); ?></h2>
                                        <p class="rf-product-meta"><?php echo e($category); ?><?php echo $sku !== '' ? ' - SKU: ' . e($sku) : ''; ?></p>

                                        <div class="rf-product-bottom">
                                            <div>
                                                <p class="rf-label">Price</p>
                                                <p class="rf-value rf-value-blue"><?php echo money($row['price']); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="rf-label">Stock</p>
                                                <p class="rf-value"><?php echo $qty; ?> Units</p>
                                            </div>
                                        </div>

                                        <a href="cashier_sales.php" class="product-card-pos-link">
                                            <i class="fa-solid fa-cash-register"></i>
                                            Add in POS
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <div class="product-empty-state inventory-empty-state <?php echo $productGroups === [] ? '' : 'hidden'; ?>" data-inventory-empty>
                <i class="fa-solid fa-box-open"></i>
                <strong>No products found</strong>
                <span>Try another search keyword, category, or stock filter.</span>
            </div>
        </div>

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
</body>
</html>
