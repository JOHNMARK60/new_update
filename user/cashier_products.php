<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

$products = mysqli_query($conn, 'SELECT * FROM products ORDER BY name ASC');
$pageTitle = 'Products | RetailFlow POS';
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
        <header class="page-topbar">
            <div>
                <h1 class="page-title">Products</h1>
                <p class="page-subtitle">Catalog view styled after RetailFlow POS.</p>
            </div>
            <a href="cashier_sales.php" class="btn">
                <i class="fa-solid fa-cash-register"></i>
                Go to POS
            </a>
        </header>

        <div class="rf-mobile-shell">
            <div class="rf-search">
                <i class="fa-solid fa-magnifying-glass text-2xl"></i>
                <input type="search" placeholder="Search products...">
                <i class="fa-solid fa-sliders"></i>
            </div>

            <div class="rf-filter-row">
                <span class="rf-chip rf-chip-active">All Items</span>
                <span class="rf-chip">Beverages</span>
                <span class="rf-chip">Electronics</span>
                <span class="rf-chip">Home</span>
            </div>

            <section class="rf-product-grid">
                <?php while ($row = mysqli_fetch_assoc($products)): ?>
                    <?php
                    $qty = (int) $row['quantity'];
                    $alt = ($index % 4) + 1;
                    $icon = ['fa-bottle-water', 'fa-headphones', 'fa-clock', 'fa-camera'][$index % 4];
                    $image_path = trim((string) ($row['image_path'] ?? ''));
                    $image_url = $image_path !== '' ? app_url($image_path) : '';
                    $index++;
                    ?>
                    <article class="rf-product-card">
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
                            <?php elseif ($qty <= 5): ?>
                                <span class="stock-badge status-low">Low Stock</span>
                            <?php else: ?>
                                <span class="stock-badge status-ok">In Stock</span>
                            <?php endif; ?>
                        </div>

                        <div class="rf-product-body">
                            <h2 class="rf-product-title"><?php echo e($row['name']); ?></h2>
                            <p class="rf-product-meta">Premium retail item available in the active catalog.</p>

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
                        </div>
                    </article>
                <?php endwhile; ?>
            </section>
        </div>
    </main>
</body>
</html>
