<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, 'SELECT * FROM products WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

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
        <header class="page-topbar">
            <div>
                <h1 class="page-title">Product Details</h1>
                <p class="page-subtitle"><?php echo e($product['name']); ?></p>
            </div>
            <a href="admin_inventory.php" class="btn btn-secondary">Back</a>
        </header>

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

        <a href="edit_product.php?id=<?php echo (int) $product['id']; ?>" class="btn mt-6">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Product
        </a>
    </main>
</body>
</html>
