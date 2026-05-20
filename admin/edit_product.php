<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

use App\Repositories\ProductRepository;

$productRepository = new ProductRepository($pdo);
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

function product_category_from_request(): string
{
    $category = trim((string) ($_POST['category'] ?? 'General'));

    if ($category === '__new__') {
        $category = trim((string) ($_POST['new_category'] ?? ''));
    }

    return $category !== '' ? $category : 'General';
}

if ($id <= 0) {
    header('Location: admin_inventory.php');
    exit();
}

if (isset($_POST['update_product'])) {
    try {
        $current = $productRepository->find($id);
        $productRepository->updateProduct($id, [
            'name' => trim($_POST['name'] ?? ''),
            'price' => (float) ($_POST['price'] ?? 0),
            'quantity' => (int) ($_POST['stock'] ?? 0),
            'image_path' => $current['image_path'] ?? null,
            'category_id' => $productRepository->ensureCategory(product_category_from_request()),
            'supplier_id' => $productRepository->ensureSupplier((string) ($_POST['supplier'] ?? '')),
            'low_stock_level' => (int) ($_POST['low_stock_level'] ?? 5),
            'expiration_date' => $_POST['expiration_date'] ?? null,
            'sku' => trim($_POST['sku'] ?? ''),
            'created_by' => (int) $_SESSION['user_id'],
        ]);

        swal_flash('success', 'Product Updated', 'Product updated successfully.');
        header('Location: admin_inventory.php');
        exit();
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
        swal_flash('error', 'Failed to Save Product', 'Something went wrong. Please try again.');
    }
}

$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS category_name, s.name AS supplier_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN suppliers s ON s.id = p.supplier_id
     WHERE p.id = :id
     LIMIT 1'
);
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: admin_inventory.php');
    exit();
}

$categories = $productRepository->categories();
$pageTitle = 'Edit Product | Admin';
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
        $appHeaderTitle = 'Edit Product';
        $appHeaderSubtitle = $product['name'];
        $appHeaderIcon = 'fa-pen-to-square';
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

        <?php if (!empty($errorMessage)): ?>
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                <?php echo e($errorMessage); ?>
            </div>
        <?php endif; ?>

        <section class="panel max-w-2xl p-6">
            <form method="POST" class="grid gap-5">
                <input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>">

                <div class="form-group">
                    <label for="name">Product name</label>
                    <input id="name" type="text" name="name" value="<?php echo e($product['name']); ?>" required>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="sku">SKU</label>
                        <input id="sku" type="text" name="sku" value="<?php echo e($product['sku'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" data-category-select required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo e($category['name']); ?>" <?php echo ($product['category_name'] ?? 'General') === $category['name'] ? 'selected' : ''; ?>>
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

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="price">Unit price</label>
                        <input id="price" type="number" step="0.01" min="0" name="price" value="<?php echo e($product['price']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="stock">Stock quantity</label>
                        <input id="stock" type="number" min="0" name="stock" value="<?php echo e($product['quantity']); ?>" required>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="low_stock_level">Low stock level</label>
                        <input id="low_stock_level" type="number" min="0" name="low_stock_level" value="<?php echo (int) ($product['low_stock_level'] ?? 5); ?>">
                    </div>

                    <div class="form-group">
                        <label for="expiration_date">Expiration date</label>
                        <input id="expiration_date" type="date" name="expiration_date" value="<?php echo e($product['expiration_date'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="supplier">Supplier</label>
                    <input id="supplier" type="text" name="supplier" value="<?php echo e($product['supplier_name'] ?? ''); ?>">
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="update_product" class="btn">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Changes
                    </button>
                    <a href="admin_inventory.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
