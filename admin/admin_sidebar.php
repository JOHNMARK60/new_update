<?php
$activePage = basename($_SERVER['PHP_SELF']);

function admin_nav_class($file, $activePage)
{
    $base = 'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition';
    return $base . ($activePage === $file
        ? ' is-active'
        : ' text-slate-300 hover:bg-white/10 hover:text-white');
}
?>
<aside class="sidebar app-sidebar">
    <div class="mb-8 flex items-center gap-3 px-2">
        <div class="grid h-11 w-11 place-items-center rounded-lg bg-white text-brand">
            <i class="fa-solid fa-store"></i>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-slate-400">Admin</p>
            <h2 class="text-lg font-extrabold text-white">RetailFlow POS</h2>
        </div>
    </div>

    <nav class="space-y-2">
        <a href="admin_dashboard.php" class="<?php echo admin_nav_class('admin_dashboard.php', $activePage); ?>">
            <i class="fa-solid fa-chart-line w-5"></i>
            Dashboard
        </a>
        <a href="admin_inventory.php" class="<?php echo admin_nav_class('admin_inventory.php', $activePage); ?>">
            <i class="fa-solid fa-boxes-stacked w-5"></i>
            Inventory
        </a>
        <a href="admin_users.php" class="<?php echo admin_nav_class('admin_users.php', $activePage); ?>">
            <i class="fa-solid fa-users-gear w-5"></i>
            Users
        </a>
        <a href="admin_sales_report.php" class="<?php echo admin_nav_class('admin_sales_report.php', $activePage); ?>">
            <i class="fa-solid fa-file-invoice-dollar w-5"></i>
            Sales Reports
        </a>
        <a href="admin_permissions.php" class="<?php echo admin_nav_class('admin_permissions.php', $activePage); ?>">
            <i class="fa-solid fa-shield-halved w-5"></i>
            Roles
        </a>
    </nav>

    <a href="../auth/logout.php" class="mt-auto flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold text-red-200 transition hover:bg-red-500/10 hover:text-white">
        <i class="fa-solid fa-right-from-bracket w-5"></i>
        Logout
    </a>
</aside>
