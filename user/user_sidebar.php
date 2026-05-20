<?php
$activePage = basename($_SERVER['PHP_SELF']);

function user_nav_class($file, $activePage)
{
    $base = 'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition';
    return $base . ($activePage === $file
        ? ' is-active'
        : ' text-slate-300 hover:bg-white/10 hover:text-white');
}
?>
<aside class="sidebar app-sidebar cashier-sidebar">
    <div class="sidebar-brand-row mb-8 flex items-center gap-3 px-2">
        <div class="sidebar-logo grid h-11 w-11 place-items-center rounded-lg bg-white text-mint">
            <i class="fa-solid fa-bag-shopping"></i>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-slate-400">Cashier</p>
            <h2 class="text-lg font-extrabold text-white">KANTO GOODS</h2>
        </div>
        <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-expanded="false" aria-label="Toggle cashier navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <nav class="space-y-2" data-sidebar-nav>
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
        <a href="cashier_closing.php" class="<?php echo user_nav_class('cashier_closing.php', $activePage); ?>">
            <i class="fa-solid fa-lock w-5"></i>
            Closing
        </a>
    </nav>

</aside>
