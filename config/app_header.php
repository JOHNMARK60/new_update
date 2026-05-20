<?php
$appHeaderRole = $appHeaderRole ?? ($_SESSION['role'] ?? 'user');
$appHeaderRoleLabel = $appHeaderRoleLabel ?? ucfirst((string) $appHeaderRole);
$appHeaderKicker = $appHeaderKicker ?? ($appHeaderRole === 'admin' ? 'Admin Console' : 'Store operations');
$appHeaderTitle = $appHeaderTitle ?? ($pageTitle ?? 'Dashboard');
$appHeaderSubtitle = $appHeaderSubtitle ?? '';
$appHeaderIcon = $appHeaderIcon ?? 'fa-table-columns';
$appHeaderActions = $appHeaderActions ?? [];
$appHeaderHome = $appHeaderHome ?? ($appHeaderRole === 'admin' ? '../admin/admin_dashboard.php' : '../user/user_dashboard.php');
$appHeaderName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?: $appHeaderRoleLabel;
$appHeaderBrandTitle = $appHeaderBrandTitle ?? ($appHeaderRole === 'admin' ? 'KANTO GOODS' : 'KANTO GOODS');
$appHeaderBrandSubtitle = $appHeaderBrandSubtitle ?? $appHeaderKicker;
$appHeaderBrandIcon = $appHeaderBrandIcon ?? ($appHeaderRole === 'admin' ? 'fa-briefcase' : 'fa-store');
$appHeaderGreeting = $appHeaderGreeting ?? '';
$appHeaderProfileLabel = $appHeaderProfileLabel ?? ($appHeaderRole === 'admin' ? 'Administrator' : $appHeaderRoleLabel);
$appHeaderProfileIcon = $appHeaderProfileIcon ?? ($appHeaderRole === 'admin' ? 'fa-user-shield' : 'fa-user');
$appHeaderSearchPlaceholder = $appHeaderSearchPlaceholder ?? 'Search...';
$appHeaderSearchAction = $appHeaderSearchAction ?? '';
$appHeaderSearchMethod = strtoupper((string) ($appHeaderSearchMethod ?? 'GET'));
$appHeaderSearchName = $appHeaderSearchName ?? 'search';
$appHeaderSearchValue = $appHeaderSearchValue ?? '';
$appHeaderShowSearch = $appHeaderShowSearch ?? true;
$appHeaderShowNotifications = $appHeaderShowNotifications ?? ($appHeaderRole === 'admin');
$appHeaderNotificationCount = $appHeaderNotificationCount ?? ($adminNotificationCount ?? 0);
$appHeaderNotifications = $appHeaderNotifications ?? ($adminNotifications ?? []);

if ($appHeaderShowNotifications && $appHeaderRole === 'admin' && $appHeaderNotifications === [] && isset($pdo)) {
    try {
        $notificationService = new \App\Services\AdminNotification($pdo);
        $appHeaderNotificationCount = $notificationService->unreadCount();
        $appHeaderNotifications = $notificationService->latest();
    } catch (Throwable $e) {
        $appHeaderNotificationCount = 0;
        $appHeaderNotifications = [];
    }
}
?>
<header class="app-page-header">
    <div class="app-header-bar">
        <div class="app-header-left">
            <a href="<?php echo e($appHeaderHome); ?>" class="app-header-brand" aria-label="KANTO GOODS dashboard">
                <span class="app-header-brand-mark"><i class="fa-solid <?php echo e($appHeaderBrandIcon); ?>"></i></span>
                <span>
                    <strong><?php echo e($appHeaderBrandTitle); ?></strong>
                    <small><?php echo e($appHeaderBrandSubtitle); ?></small>
                </span>
            </a>

        </div>

        <div class="app-header-tools">
            <?php if ($appHeaderShowSearch): ?>
                <?php if ($appHeaderSearchAction !== ''): ?>
                    <form class="app-header-search" method="<?php echo e($appHeaderSearchMethod); ?>" action="<?php echo e($appHeaderSearchAction); ?>" role="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            type="search"
                            name="<?php echo e($appHeaderSearchName); ?>"
                            value="<?php echo e($appHeaderSearchValue); ?>"
                            placeholder="<?php echo e($appHeaderSearchPlaceholder); ?>">
                    </form>
                <?php else: ?>
                    <label class="app-header-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" placeholder="<?php echo e($appHeaderSearchPlaceholder); ?>">
                    </label>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($appHeaderShowNotifications): ?>
                <details class="app-header-notifications">
                    <summary aria-label="Admin notifications">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ((int) $appHeaderNotificationCount > 0): ?>
                            <span><?php echo (int) $appHeaderNotificationCount > 9 ? '9+' : (int) $appHeaderNotificationCount; ?></span>
                        <?php endif; ?>
                    </summary>
                    <div class="app-header-notification-menu">
                        <div class="admin-notification-head">
                            <strong>Notifications</strong>
                            <small><?php echo (int) $appHeaderNotificationCount; ?> unread</small>
                        </div>
                        <?php foreach ($appHeaderNotifications as $notification): ?>
                            <a href="<?php echo e($notification['link_url'] ?: 'closing_validation.php'); ?>" class="<?php echo empty($notification['read_at']) ? 'is-unread' : ''; ?>">
                                <i class="fa-solid fa-lock"></i>
                                <span>
                                    <strong><?php echo e($notification['title']); ?></strong>
                                    <small><?php echo e($notification['body']); ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($appHeaderNotifications === []): ?>
                            <p>No closing notifications yet.</p>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endif; ?>

            <div class="profile-dropdown header-profile-dropdown" data-profile-dropdown>
                <button type="button" class="profile-trigger header-profile-trigger" data-profile-toggle aria-expanded="false">
                    <span class="profile-avatar"><i class="fa-solid <?php echo e($appHeaderProfileIcon); ?>"></i></span>
                    <span>
                        <strong><?php echo e($appHeaderName); ?></strong>
                        <small><?php echo e($appHeaderProfileLabel); ?></small>
                    </span>
                    <i class="fa-solid fa-chevron-down profile-chevron"></i>
                </button>

                <div class="profile-menu" data-profile-menu>
                    <div class="profile-menu-footer">
                        <a href="<?php echo e(app_url('auth/logout.php')); ?>" data-logout>Sign out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-header-body">
        <div class="app-header-title">
            <span class="app-header-icon"><i class="fa-solid <?php echo e($appHeaderIcon); ?>"></i></span>
            <div>
                <?php if ($appHeaderGreeting !== ''): ?>
                    <p><?php echo e($appHeaderGreeting); ?></p>
                <?php endif; ?>
                <h1 class="page-title"><?php echo e($appHeaderTitle); ?></h1>
                <?php if ($appHeaderSubtitle !== ''): ?>
                    <span class="page-subtitle"><?php echo e($appHeaderSubtitle); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($appHeaderActions !== []): ?>
            <div class="app-header-actions">
                <?php foreach ($appHeaderActions as $action): ?>
                    <?php
                    $actionAttributes = '';
                    foreach (($action['attributes'] ?? []) as $attribute => $value) {
                        $actionAttributes .= ' ' . e($attribute) . '="' . e($value) . '"';
                    }
                    ?>
                    <?php if (($action['tag'] ?? 'a') === 'button'): ?>
                        <button type="<?php echo e($action['type'] ?? 'button'); ?>" class="<?php echo e($action['class'] ?? 'btn'); ?>"<?php echo $actionAttributes; ?>>
                            <?php if (!empty($action['icon'])): ?>
                                <i class="fa-solid <?php echo e($action['icon']); ?>"></i>
                            <?php endif; ?>
                            <?php echo e($action['label'] ?? 'Open'); ?>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo e($action['href'] ?? '#'); ?>" class="<?php echo e($action['class'] ?? 'btn'); ?>"<?php echo $actionAttributes; ?>>
                            <?php if (!empty($action['icon'])): ?>
                                <i class="fa-solid <?php echo e($action['icon']); ?>"></i>
                            <?php endif; ?>
                            <?php echo e($action['label'] ?? 'Open'); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</header>
