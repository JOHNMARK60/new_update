<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['product_action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $quantity = (int) ($_POST['stock'] ?? 0);
        $image_path = upload_product_image();

        $stmt = mysqli_prepare($conn, 'INSERT INTO products (name, price, quantity, image_path) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sdis', $name, $price, $quantity, $image_path);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $product_id = mysqli_insert_id($conn);
        $log_action = 'Product added';
        $stmt = mysqli_prepare($conn, 'INSERT INTO inventory_logs (product_id, action) VALUES (?, ?)');
        mysqli_stmt_bind_param($stmt, 'is', $product_id, $log_action);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $quantity = (int) ($_POST['stock'] ?? 0);
        $image_path = upload_product_image($_POST['current_image'] ?? null);

        $stmt = mysqli_prepare($conn, 'UPDATE products SET name = ?, price = ?, quantity = ?, image_path = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'sdisi', $name, $price, $quantity, $image_path, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $log_action = 'Product updated';
        $stmt = mysqli_prepare($conn, 'INSERT INTO inventory_logs (product_id, action) VALUES (?, ?)');
        mysqli_stmt_bind_param($stmt, 'is', $id, $log_action);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = mysqli_prepare($conn, 'DELETE FROM products WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header('Location: admin_inventory.php');
    exit();
}

$result = mysqli_query($conn, 'SELECT * FROM products ORDER BY id DESC');
$products = [];

while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

$pageTitle = 'Inventory Management | Admin';
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
                <h1 class="page-title">Inventory</h1>
                <p class="page-subtitle">Manage products, stock levels, and product images.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn" data-modal-open="addProductModal">
                    <i class="fa-solid fa-plus"></i>
                    Add Product
                </button>
                <a href="inventory_status.php" class="btn btn-secondary">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Movement Logs
                </a>
            </div>
        </header>

        <section class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 p-5">
                <h2 class="text-base font-bold text-ink">Product Inventory</h2>
                <span class="badge bg-slate-100 text-slate-600">Modal CRUD</span>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $row): ?>
                            <?php
                            $product_id = (int) $row['id'];
                            $image_path = $row['image_path'] ?? '';
                            $image_url = $image_path ? app_url($image_path) : '';
                            ?>
                            <tr>
                                <td>
                                    <?php if ($image_url): ?>
                                        <img src="<?php echo e($image_url); ?>" alt="<?php echo e($row['name']); ?>" class="admin-thumb">
                                    <?php else: ?>
                                        <div class="admin-thumb admin-thumb-empty"><i class="fa-solid fa-box"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product_id; ?></td>
                                <td><strong><?php echo e($row['name']); ?></strong></td>
                                <td><?php echo money($row['price']); ?></td>
                                <td>
                                    <?php if ((int) $row['quantity'] === 0): ?>
                                        <span class="stock-badge status-out">Out of stock</span>
                                    <?php elseif ((int) $row['quantity'] <= 5): ?>
                                        <span class="stock-badge status-low"><?php echo (int) $row['quantity']; ?> low</span>
                                    <?php else: ?>
                                        <span class="stock-badge status-ok"><?php echo (int) $row['quantity']; ?> available</span>
                                    <?php endif; ?>
                                </td>
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
    </main>

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
                        <label>Price</label>
                        <input type="number" step="0.01" min="0" name="price" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" min="0" name="stock" required>
                    </div>
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
                        <div><dt>Price</dt><dd><?php echo money($row['price']); ?></dd></div>
                        <div><dt>Quantity</dt><dd><?php echo (int) $row['quantity']; ?></dd></div>
                        <div><dt>Status</dt><dd><?php echo (int) $row['quantity'] > 0 ? 'Available' : 'Out of stock'; ?></dd></div>
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
                            <label>Price</label>
                            <input type="number" step="0.01" min="0" name="price" value="<?php echo e($row['price']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" min="0" name="stock" value="<?php echo (int) $row['quantity']; ?>" required>
                        </div>
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
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="<?php echo e(app_url('assets/script.js')); ?>"></script>
</body>
</html>
