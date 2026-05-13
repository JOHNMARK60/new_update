<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: admin_inventory.php');
    exit();
}

if (isset($_POST['update_product'])) {
    $name = trim($_POST['name'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $quantity = (int) ($_POST['stock'] ?? 0);

    $stmt = mysqli_prepare($conn, 'UPDATE products SET name = ?, price = ?, quantity = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'sdii', $name, $price, $quantity, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $action = 'Product updated';
    $stmt = mysqli_prepare($conn, 'INSERT INTO inventory_logs (product_id, action) VALUES (?, ?)');
    mysqli_stmt_bind_param($stmt, 'is', $id, $action);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: admin_inventory.php');
    exit();
}

$stmt = mysqli_prepare($conn, 'SELECT * FROM products WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) {
    header('Location: admin_inventory.php');
    exit();
}

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
        <header class="page-topbar">
            <div>
                <h1 class="page-title">Edit Product</h1>
                <p class="page-subtitle"><?php echo e($product['name']); ?></p>
            </div>
        </header>

        <section class="panel max-w-2xl p-6">
            <form method="POST" class="grid gap-5">
                <input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>">

                <div class="form-group">
                    <label for="name">Product name</label>
                    <input id="name" type="text" name="name" value="<?php echo e($product['name']); ?>" required>
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
