<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
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

$pageTitle = 'Product Details | Admin';
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
        $appHeaderTitle = 'Product Details';
        $appHeaderSubtitle = $product['name'];
        $appHeaderIcon = 'fa-box';
        $appHeaderHome = 'admin_dashboard.php';
        $appHeaderActions = [
            [
                'href' => 'admin_inventory.php',
                'label' => 'Back',
                'icon' => 'fa-arrow-left',
                'class' => 'btn btn-secondary',
            ],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <section class="grid gap-5 lg:grid-cols-3">
            <article class="panel p-6">
                <p class="text-sm font-bold text-slate-500">Product</p>
                <h2 class="mt-2 text-2xl font-extrabold text-ink"><?php echo e($product['name']); ?></h2>
            </article>
            <article class="panel p-6">
                <p class="text-sm font-bold text-slate-500">Price</p>
                <h2 class="mt-2 text-2xl font-extrabold text-brand"><?php echo money($product['price']); ?></h2>
            </article>
            <article class="panel p-6">
                <p class="text-sm font-bold text-slate-500">Quantity</p>
                <h2 class="mt-2 text-2xl font-extrabold text-ink"><?php echo (int) $product['quantity']; ?></h2>
            </article>
        </section>

        <section class="panel mt-5 p-6">
            <h2 class="text-lg font-extrabold text-ink">Inventory Details</h2>
            <dl class="modal-detail-grid mt-5">
                <div><dt>SKU</dt><dd><?php echo e($product['sku'] ?? 'N/A'); ?></dd></div>
                <div><dt>Category</dt><dd><?php echo e($product['category_name'] ?? 'General'); ?></dd></div>
                <div><dt>Supplier</dt><dd><?php echo e($product['supplier_name'] ?? 'N/A'); ?></dd></div>
                <div><dt>Low Stock Level</dt><dd><?php echo (int) ($product['low_stock_level'] ?? 5); ?></dd></div>
                <div><dt>Expiration Date</dt><dd><?php echo e($product['expiration_date'] ?? 'N/A'); ?></dd></div>
                <div><dt>Date Added</dt><dd><?php echo e(date('M d, Y', strtotime($product['created_at']))); ?></dd></div>
            </dl>
        </section>

        <a href="edit_product.php?id=<?php echo (int) $product['id']; ?>" class="btn mt-6">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Product
        </a>
    </main>
</body>
</html>
