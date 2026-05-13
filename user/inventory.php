<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

$total_p = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(id) AS total FROM products'))['total'];
$low_p = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(id) AS total FROM products WHERE quantity > 0 AND quantity <= 5'))['total'];
$out_p = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(id) AS total FROM products WHERE quantity = 0'))['total'];
$products = mysqli_query($conn, 'SELECT * FROM products ORDER BY quantity ASC, name ASC');
$pageTitle = 'Stock | RetailFlow POS';
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
                <h1 class="page-title">Stock</h1>
                <p class="page-subtitle">Inventory list and low-stock alerts.</p>
            </div>
        </header>

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

            <div class="rf-search">
                <i class="fa-solid fa-magnifying-glass text-2xl"></i>
                <input type="search" placeholder="Search products or SKUs...">
            </div>

            <div class="rf-filter-row">
                <span class="rf-chip rf-chip-active">All</span>
                <span class="rf-chip">Beverages</span>
                <span class="rf-chip">Snacks</span>
                <span class="rf-chip">Personal Care</span>
            </div>

            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-4xl font-extrabold text-black">Inventory List</h2>
                <button class="font-extrabold tracking-wider text-[var(--rf-blue)]">
                    <i class="fa-solid fa-arrow-down-wide-short"></i>
                    Sort
                </button>
            </div>

            <section class="rf-stock-list">
                <?php while ($row = mysqli_fetch_assoc($products)): ?>
                    <?php
                    $qty = (int) $row['quantity'];
                    $icon = ['fa-bottle-water', 'fa-cookie', 'fa-mug-saucer', 'fa-box-open'][$index % 4];
                    $image_path = trim((string) ($row['image_path'] ?? ''));
                    $image_url = $image_path !== '' ? app_url($image_path) : '';
                    $index++;
                    ?>
                    <article class="rf-stock-card">
                        <div class="rf-stock-thumb">
                            <?php if ($image_url): ?>
                                <img src="<?php echo e($image_url); ?>" alt="<?php echo e($row['name']); ?>" class="rf-stock-photo">
                            <?php else: ?>
                                <i class="fa-solid <?php echo $icon; ?>"></i>
                            <?php endif; ?>
                        </div>

                        <div class="min-w-0">
                            <h3 class="truncate text-2xl font-extrabold text-black"><?php echo e($row['name']); ?></h3>
                            <p class="mt-3 font-mono text-lg font-bold text-slate-500">SKU: RF-<?php echo str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT); ?></p>
                            <p class="mt-2 text-lg font-extrabold">
                                Qty: <span class="<?php echo $qty <= 5 ? 'text-[var(--rf-orange)]' : 'text-black'; ?>"><?php echo $qty; ?></span>
                                <span class="mx-3 text-slate-500">Price:</span><?php echo money($row['price']); ?>
                            </p>
                        </div>

                        <div>
                            <?php if ($qty === 0): ?>
                                <span class="stock-badge status-out">Out of Stock</span>
                            <?php elseif ($qty <= 5): ?>
                                <span class="stock-badge status-low">Low Stock</span>
                            <?php else: ?>
                                <span class="stock-badge status-ok">In Stock</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </section>

            <a href="cashier_sales.php" class="rf-floating-add">
                <i class="fa-solid fa-plus"></i>
            </a>
        </div>
    </main>
</body>
</html>
