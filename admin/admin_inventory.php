<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

use App\Repositories\ProductRepository;

$productRepository = new ProductRepository($pdo);
$errorMessage = '';

function product_category_from_request(): string
{
    $category = trim((string) ($_POST['category'] ?? 'General'));

    if ($category === '__new__') {
        $category = trim((string) ($_POST['new_category'] ?? ''));
    }

    return $category !== '' ? $category : 'General';
}

function upload_product_image($existing = null)
{
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }

    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return $existing;
    }

    $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed, true)) {
        return $existing;
    }

    $upload_dir = __DIR__ . '/../assets/uploads/products';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0775, true);
    }

    $filename = 'product_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $target = $upload_dir . '/' . $filename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        return $existing;
    }

    return 'assets/uploads/products/' . $filename;
}

function inventory_category_label(array $product): string
{
    $category = trim((string) ($product['category_name'] ?? ''));

    return $category !== '' ? $category : 'Other Products / Uncategorized';
}

function inventory_sort_category_names(array &$categories): void
{
    $other = 'Other Products / Uncategorized';
    usort($categories, static function (string $first, string $second) use ($other): int {
        if ($first === $other && $second !== $other) {
            return 1;
        }

        if ($second === $other && $first !== $other) {
            return -1;
        }

        return strcasecmp($first, $second);
    });
}

function inventory_stock_status(array $product): array
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

function inventory_group_products(array $products): array
{
    usort($products, static function (array $first, array $second): int {
        $other = 'Other Products / Uncategorized';
        $firstCategory = inventory_category_label($first);
        $secondCategory = inventory_category_label($second);

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
        $category = inventory_category_label($product);
        $status = inventory_stock_status($product);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['product_action'] ?? '';

    try {
        if ($action === 'add_category') {
            $categoryName = trim((string) ($_POST['category_name'] ?? ''));

            if ($categoryName === '') {
                throw new InvalidArgumentException('Category name is required.');
            }

            $productRepository->ensureCategory($categoryName);
            swal_toast('success', 'Category added successfully.');
        }

        if ($action === 'add' || $action === 'update') {
            $categoryId = $productRepository->ensureCategory(product_category_from_request());
            $supplierId = $productRepository->ensureSupplier((string) ($_POST['supplier'] ?? ''));
            $image_path = upload_product_image($action === 'update' ? ($_POST['current_image'] ?? null) : null);
            $payload = [
                'name' => trim($_POST['name'] ?? ''),
                'price' => (float) ($_POST['price'] ?? 0),
                'quantity' => (int) ($_POST['stock'] ?? 0),
                'image_path' => $image_path,
                'category_id' => $categoryId,
                'supplier_id' => $supplierId,
                'low_stock_level' => (int) ($_POST['low_stock_level'] ?? 5),
                'expiration_date' => $_POST['expiration_date'] ?? null,
                'sku' => trim($_POST['sku'] ?? ''),
                'created_by' => (int) $_SESSION['user_id'],
            ];

            if ($action === 'add') {
                $productRepository->create($payload);
                swal_toast('success', 'Product added successfully.');
            } else {
                $productRepository->updateProduct((int) ($_POST['id'] ?? 0), $payload);
                swal_toast('success', 'Product updated successfully.');
            }
        }

        if ($action === 'delete') {
            $productRepository->deleteProduct((int) ($_POST['id'] ?? 0));
            swal_flash('success', 'Product Deleted', 'Product deleted successfully.');
        }

        header('Location: admin_inventory.php');
        exit();
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
        swal_flash('error', 'Failed to Save Product', 'Something went wrong. Please check the product details and try again.');
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$categories = $productRepository->categories();
$products = $productRepository->allWithMeta($search);
$lowStockCount = count($productRepository->lowStock());
$productGroups = inventory_group_products($products);
$categoryFilters = array_values(array_filter(array_map(static function (array $category): string {
    return trim((string) ($category['name'] ?? ''));
}, $categories)));

foreach (array_keys($productGroups) as $categoryName) {
    if (!in_array($categoryName, $categoryFilters, true)) {
        $categoryFilters[] = $categoryName;
    }
}

$categoryFilters = array_values(array_unique($categoryFilters));
inventory_sort_category_names($categoryFilters);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $lowStockCount > 0 && empty($_SESSION['swal'])) {
    swal_toast('warning', $lowStockCount . ' low stock item(s) need attention.');
}

$pageTitle = 'Inventory Management | Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="app-shell inventory-header-page">
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <main class="admin-main">
        <?php
        $appHeaderRole = 'admin';
        $appHeaderRoleLabel = 'Administrator';
        $appHeaderKicker = 'Admin Console';
        $appHeaderBrandTitle = 'KANTO GOODS';
        $appHeaderBrandSubtitle = 'Admin Console';
        $appHeaderBrandIcon = 'fa-box-open';
        $appHeaderTitle = 'Inventory Dashboard';
        $appHeaderSubtitle = 'Monitor products, stock levels, suppliers, and movement logs.';
        $appHeaderIcon = 'fa-boxes-stacked';
        $appHeaderHome = 'admin_dashboard.php';
        $appHeaderSearchPlaceholder = 'Search product, category, supplier or SKU...';
        $appHeaderSearchAction = 'admin_inventory.php';
        $appHeaderSearchName = 'search';
        $appHeaderSearchValue = $search;
        $appHeaderActions = [];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <section class="inventory-actions-bar">
            <div>
                <strong>Inventory Management</strong>
                <span>Manage products, stock levels, and product images.</span>
            </div>
            <div>
                <button type="button" class="btn" data-modal-open="addProductModal">
                    <i class="fa-solid fa-plus"></i>
                    Add Product
                </button>
                <button type="button" class="btn btn-secondary" data-modal-open="addCategoryModal">
                    <i class="fa-solid fa-tags"></i>
                    Add Category
                </button>
                <a href="inventory_status.php" class="btn btn-secondary">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Movement Logs
                </a>
            </div>
        </section>

        <?php if ($errorMessage !== ''): ?>
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                <?php echo e($errorMessage); ?>
            </div>
        <?php endif; ?>

        <section class="inventory-browser" data-inventory-browser>
            <div class="inventory-browser-header">
                <div>
                    <p class="inventory-eyebrow">Category view</p>
                    <h2>Product Inventory</h2>
                    <p>
                        <?php echo number_format(count($products)); ?> visible product<?php echo count($products) === 1 ? '' : 's'; ?> arranged by category.
                        <?php if ($search !== ''): ?>
                            Showing results for "<?php echo e($search); ?>"
                            <a href="admin_inventory.php">Clear</a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if ($productGroups !== []): ?>
                <div class="inventory-category-summary-grid" aria-label="Products per category">
                    <?php foreach ($productGroups as $categoryName => $group): ?>
                        <article class="inventory-category-summary-card">
                            <span><?php echo e($categoryName); ?></span>
                            <strong><?php echo number_format(count($group['products'])); ?></strong>
                            <small>
                                <?php echo (int) $group['low']; ?> low stock
                                &middot;
                                <?php echo (int) $group['out']; ?> out
                            </small>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="inventory-filter-panel" data-inventory-controls aria-label="Inventory filters">
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
                            <table class="inventory-category-table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Low Level</th>
                                        <th>Supplier</th>
                                        <th>Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['products'] as $row): ?>
                                        <?php
                                        $product_id = (int) $row['id'];
                                        $image_path = $row['image_path'] ?? '';
                                        $image_url = $image_path ? app_url($image_path) : '';
                                        $status = inventory_stock_status($row);
                                        $sku = trim((string) ($row['sku'] ?? ''));
                                        $searchText = strtolower($row['name'] . ' ' . $categoryName . ' ' . $sku . ' ' . ($row['supplier_name'] ?? ''));
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
                                            <td><?php echo $product_id; ?></td>
                                            <td>
                                                <strong><?php echo e($row['name']); ?></strong>
                                                <small class="inventory-product-subtext">
                                                    SKU: <?php echo e($sku !== '' ? $sku : 'KG-' . str_pad((string) $product_id, 4, '0', STR_PAD_LEFT)); ?>
                                                </small>
                                            </td>
                                            <td><span class="inventory-category-pill"><?php echo e($categoryName); ?></span></td>
                                            <td><?php echo money($row['price']); ?></td>
                                            <td>
                                                <strong><?php echo (int) $row['quantity']; ?></strong>
                                                <small class="inventory-product-subtext">Units</small>
                                            </td>
                                            <td><?php echo (int) ($row['low_stock_level'] ?? 5); ?></td>
                                            <td><?php echo e($row['supplier_name'] ?? 'N/A'); ?></td>
                                            <td><span class="stock-badge <?php echo e($status['class']); ?>"><?php echo e($status['label']); ?></span></td>
                                            <td>
                                                <div class="action-icons">
                                                    <button type="button" class="view-btn" title="View product" data-modal-open="viewProduct<?php echo $product_id; ?>">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="edit-btn" title="Edit product" data-modal-open="editProduct<?php echo $product_id; ?>">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button" class="delete-btn" title="Delete product" data-modal-open="deleteProduct<?php echo $product_id; ?>">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
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
                <strong>No products found</strong>
                <span>Try another search keyword, category, or stock filter.</span>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="addCategoryModal" aria-hidden="true">
        <div class="modal-card modal-card-sm">
            <div class="modal-header">
                <h3>Add Category</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="product_action" value="add_category">
                <div class="form-group">
                    <label>Category name</label>
                    <input type="text" name="category_name" placeholder="e.g. Frozen Goods" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="addProductModal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Add Product</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="modal-body">
                <input type="hidden" name="product_action" value="add">

                <div class="form-group">
                    <label>Product name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" data-category-select required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo e($category['name']); ?>" <?php echo $category['name'] === 'General' ? 'selected' : ''; ?>>
                                    <?php echo e($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="__new__">+ Add new category</option>
                        </select>
                        <div class="category-new-field hidden" data-new-category-field>
                            <input type="text" name="new_category" placeholder="Enter new category">
                        </div>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Price</label>
                        <input type="number" step="0.01" min="0" name="price" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" min="0" name="stock" required>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Low stock level</label>
                        <input type="number" min="0" name="low_stock_level" value="5">
                    </div>
                    <div class="form-group">
                        <label>Expiration date</label>
                        <input type="date" name="expiration_date">
                    </div>
                </div>
                <div class="form-group">
                    <label>Supplier</label>
                    <input type="text" name="supplier" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label>Product image</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($products as $row): ?>
        <?php
        $product_id = (int) $row['id'];
        $image_path = $row['image_path'] ?? '';
        $image_url = $image_path ? app_url($image_path) : '';
        ?>
        <div class="modal-overlay" id="viewProduct<?php echo $product_id; ?>" aria-hidden="true">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Product Details</h3>
                    <button type="button" class="modal-close" data-modal-close>&times;</button>
                </div>
                <div class="modal-body">
                    <div class="modal-preview">
                        <?php if ($image_url): ?>
                            <img src="<?php echo e($image_url); ?>" alt="<?php echo e($row['name']); ?>">
                        <?php else: ?>
                            <i class="fa-solid fa-box"></i>
                        <?php endif; ?>
                    </div>
                    <dl class="modal-detail-grid">
                        <div><dt>Name</dt><dd><?php echo e($row['name']); ?></dd></div>
                        <div><dt>SKU</dt><dd><?php echo e($row['sku'] ?? 'N/A'); ?></dd></div>
                        <div><dt>Category</dt><dd><?php echo e(inventory_category_label($row)); ?></dd></div>
                        <div><dt>Price</dt><dd><?php echo money($row['price']); ?></dd></div>
                        <div><dt>Quantity</dt><dd><?php echo (int) $row['quantity']; ?></dd></div>
                        <div><dt>Low Stock Level</dt><dd><?php echo (int) ($row['low_stock_level'] ?? 5); ?></dd></div>
                        <div><dt>Supplier</dt><dd><?php echo e($row['supplier_name'] ?? 'N/A'); ?></dd></div>
                        <div><dt>Expiration</dt><dd><?php echo e($row['expiration_date'] ?? 'N/A'); ?></dd></div>
                        <div><dt>Status</dt><dd><?php echo e(inventory_stock_status($row)['label']); ?></dd></div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="editProduct<?php echo $product_id; ?>" aria-hidden="true">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Edit Product</h3>
                    <button type="button" class="modal-close" data-modal-close>&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data" class="modal-body">
                    <input type="hidden" name="product_action" value="update">
                    <input type="hidden" name="id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="current_image" value="<?php echo e($image_path); ?>">

                    <div class="form-group">
                        <label>Product name</label>
                        <input type="text" name="name" value="<?php echo e($row['name']); ?>" required>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" name="sku" value="<?php echo e($row['sku'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" data-category-select required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo e($category['name']); ?>" <?php echo ($row['category_name'] ?? 'General') === $category['name'] ? 'selected' : ''; ?>>
                                        <?php echo e($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__new__">+ Add new category</option>
                            </select>
                            <div class="category-new-field hidden" data-new-category-field>
                                <input type="text" name="new_category" placeholder="Enter new category">
                            </div>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" step="0.01" min="0" name="price" value="<?php echo e($row['price']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" min="0" name="stock" value="<?php echo (int) $row['quantity']; ?>" required>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Low stock level</label>
                            <input type="number" min="0" name="low_stock_level" value="<?php echo (int) ($row['low_stock_level'] ?? 5); ?>">
                        </div>
                        <div class="form-group">
                            <label>Expiration date</label>
                            <input type="date" name="expiration_date" value="<?php echo e($row['expiration_date'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <input type="text" name="supplier" value="<?php echo e($row['supplier_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Product image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                        <button type="submit" class="btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="deleteProduct<?php echo $product_id; ?>" aria-hidden="true">
            <div class="modal-card modal-card-sm">
                <div class="modal-header">
                    <h3>Delete Product</h3>
                    <button type="button" class="modal-close" data-modal-close>&times;</button>
                </div>
                <form method="POST" class="modal-body">
                    <input type="hidden" name="product_action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $product_id; ?>">
                    <p class="text-slate-600">Delete <strong><?php echo e($row['name']); ?></strong>? This action cannot be undone.</p>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button
                        type="submit"
                        class="btn btn-danger"
                        data-swal-confirm="Delete this product?"
                        data-swal-text="This action cannot be undone."
                        data-swal-confirm-text="Yes, delete">
                        Delete
                    </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="<?php echo e(app_url('assets/script.js')); ?>"></script>
</body>
</html>
