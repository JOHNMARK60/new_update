<?php
$activePage = basename($_SERVER['PHP_SELF']);
$cashierName = $_SESSION['first_name'] ?? 'Cashier';

function user_nav_class($file, $activePage)
{
    $base = 'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition';
    return $base . ($activePage === $file
        ? ' is-active'
        : ' text-slate-300 hover:bg-white/10 hover:text-white');
}
?>
<aside class="sidebar app-sidebar">
    <div class="mb-8 flex items-center gap-3 px-2">
        <div class="grid h-11 w-11 place-items-center rounded-lg bg-white text-mint">
            <i class="fa-solid fa-store"></i>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-slate-400">Cashier</p>
            <h2 class="text-lg font-extrabold text-white">RetailFlow POS</h2>
        </div>
    </div>

    <nav class="space-y-2">
        <a href="user_dashboard.php" class="<?php echo user_nav_class('user_dashboard.php', $activePage); ?>">
            <i class="fa-solid fa-table-columns w-5"></i>
            Home
        </a>
        <a href="cashier_sales.php" class="<?php echo user_nav_class('cashier_sales.php', $activePage); ?>">
            <i class="fa-solid fa-cash-register w-5"></i>
            POS
        </a>
        <a href="cashier_products.php" class="<?php echo user_nav_class('cashier_products.php', $activePage); ?>">
            <i class="fa-solid fa-box w-5"></i>
            Products
        </a>
        <a href="inventory.php" class="<?php echo user_nav_class('inventory.php', $activePage); ?>">
            <i class="fa-solid fa-warehouse w-5"></i>
            Stock
        </a>
        <a href="cashier_reports.php" class="<?php echo user_nav_class('cashier_reports.php', $activePage); ?>">
            <i class="fa-solid fa-chart-pie w-5"></i>
            Reports
        </a>
    </nav>

    <div class="mt-auto rounded-lg border border-white/10 bg-white/5 p-4">
        <p class="text-xs uppercase text-slate-400">Signed in as</p>
        <p class="mt-1 font-bold text-white"><?php echo e($cashierName); ?></p>
        <a href="../auth/logout.php" class="mt-4 flex items-center gap-2 text-sm font-semibold text-red-200 hover:text-white">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</aside>
