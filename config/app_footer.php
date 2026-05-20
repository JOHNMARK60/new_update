<?php
$appFooterRole = $appFooterRole ?? ($_SESSION['role'] ?? 'user');
$appFooterRoleLabel = $appFooterRoleLabel ?? ucfirst((string) $appFooterRole);
$appFooterLinks = $appFooterLinks ?? [];
?>
<footer class="app-footer">
    <div>
        <strong>KANTO GOODS</strong>
        <span>&copy; <?php echo date('Y'); ?> KANTO GOODS</span>
    </div>

    <nav aria-label="<?php echo e($appFooterRoleLabel); ?> footer navigation">
        <?php foreach ($appFooterLinks as $link): ?>
            <a href="<?php echo e($link['href'] ?? '#'); ?>">
                <?php if (!empty($link['icon'])): ?>
                    <i class="fa-solid <?php echo e($link['icon']); ?>"></i>
                <?php endif; ?>
                <?php echo e($link['label'] ?? 'Link'); ?>
            </a>
        <?php endforeach; ?>
    </nav>
</footer>
