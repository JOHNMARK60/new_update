<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

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
     ORDER BY p.name ASC"
);
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = array_values(array_unique(array_map(static fn (array $product): string => (string) $product['category_name'], $products)));
sort($categories, SORT_NATURAL | SORT_FLAG_CASE);
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

        <div class="rf-mobile-shell product-catalog-shell">
            <div class="rf-filter-row product-category-row" id="productCategoryFilters" aria-label="Product categories">
                <button type="button" class="rf-chip rf-chip-active" data-category="">All Items</button>
                <?php foreach ($categories as $category): ?>
                    <button type="button" class="rf-chip" data-category="<?php echo e($category); ?>"><?php echo e($category); ?></button>
                <?php endforeach; ?>
            </div>

            <section class="rf-product-grid" id="productCatalogGrid">
                <?php foreach ($products as $row): ?>
                    <?php
                    $qty = (int) $row['quantity'];
                    $category = (string) ($row['category_name'] ?? 'Uncategorized');
                    $sku = trim((string) ($row['sku'] ?? ''));
                    $alt = ($index % 4) + 1;
                    $icon = ['fa-bottle-water', 'fa-headphones', 'fa-clock', 'fa-camera'][$index % 4];
                    $image_path = trim((string) ($row['image_path'] ?? ''));
                    $image_url = $image_path !== '' ? app_url($image_path) : '';
                    $index++;
                    ?>
                    <article
                        class="rf-product-card product-catalog-card"
                        data-name="<?php echo e(strtolower($row['name'])); ?>"
                        data-category="<?php echo e($category); ?>"
                        data-search="<?php echo e(strtolower($row['name'] . ' ' . $category . ' ' . $sku)); ?>">
                        <div class="rf-product-image alt-<?php echo $alt; ?><?php echo $image_url ? ' has-photo' : ''; ?>">
                            <?php if ($image_url): ?>
                                <img src="<?php echo e($image_url); ?>" alt="<?php echo e($row['name']); ?>" class="rf-product-photo">
                            <?php else: ?>
                                <i class="fa-solid <?php echo $icon; ?>"></i>
                            <?php endif; ?>
                        </div>

                        <div class="absolute right-4 top-4">
                            <?php if ($qty === 0): ?>
                                <span class="stock-badge status-out">Out of Stock</span>
                            <?php elseif ($qty <= (int) ($row['low_stock_level'] ?? 5)): ?>
                                <span class="stock-badge status-low">Low Stock</span>
                            <?php else: ?>
                                <span class="stock-badge status-ok">In Stock</span>
                            <?php endif; ?>
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
            </section>

            <div class="product-empty-state hidden" id="productEmptyState">
                <i class="fa-solid fa-box-open"></i>
                <strong>No products found</strong>
                <span>Try another search keyword or category.</span>
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
    <script>
        const mainProductSearch = document.querySelector('.app-header-search input');
        const productCards = Array.from(document.querySelectorAll('[data-search]'));
        const productFilters = document.getElementById('productCategoryFilters');
        const productEmptyState = document.getElementById('productEmptyState');
        let activeProductCategory = '';

        function filterProducts() {
            const query = String(mainProductSearch?.value || '').trim().toLowerCase();
            let visibleCount = 0;

            productCards.forEach((card) => {
                const matchesCategory = activeProductCategory === '' || card.dataset.category === activeProductCategory;
                const matchesSearch = query === '' || String(card.dataset.search || '').includes(query);
                const isVisible = matchesCategory && matchesSearch;

                card.classList.toggle('hidden', !isVisible);

                if (isVisible) {
                    visibleCount++;
                }
            });

            productEmptyState.classList.toggle('hidden', visibleCount > 0);
        }

        mainProductSearch?.addEventListener('input', filterProducts);
        filterProducts();

        productFilters?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-category]');

            if (!button) {
                return;
            }

            activeProductCategory = button.dataset.category || '';
            productFilters.querySelectorAll('[data-category]').forEach((item) => {
                item.classList.toggle('rf-chip-active', item === button);
            });
            filterProducts();
        });
    </script>
</body>
</html>
