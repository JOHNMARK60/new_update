<?php
use App\Services\AdminNotification;

$activePage = basename($_SERVER['PHP_SELF']);
$adminNotificationService = new AdminNotification($pdo);
$adminNotificationCount = $adminNotificationService->unreadCount();
$adminNotifications = $adminNotificationService->latest();

function admin_nav_class($file, $activePage)
{
    $base = 'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition';
    return $base . ($activePage === $file
        ? ' is-active'
        : ' text-slate-300 hover:bg-white/10 hover:text-white');
}
?>
<aside class="sidebar app-sidebar admin-sidebar">
    <div class="sidebar-brand-row mb-8 flex items-center gap-3 px-2">
        <div class="sidebar-logo grid h-11 w-11 place-items-center rounded-lg bg-white text-brand">
            <i class="fa-solid fa-bag-shopping"></i>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-slate-400">Admin</p>
            <h2 class="text-lg font-extrabold text-white">KANTO GOODS</h2>
        </div>
        <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-expanded="false" aria-label="Toggle admin navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <nav class="space-y-2" data-sidebar-nav>
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
        <a href="closing_validation.php" class="<?php echo admin_nav_class('closing_validation.php', $activePage); ?>">
            <i class="fa-solid fa-lock w-5"></i>
            Closing
        </a>
        <a href="admin_permissions.php" class="<?php echo admin_nav_class('admin_permissions.php', $activePage); ?>">
            <i class="fa-solid fa-shield-halved w-5"></i>
            Roles
        </a>
    </nav>

</aside>
