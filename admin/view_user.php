<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'cashier' LIMIT 1");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: admin_users.php');
    exit();
}

$pageTitle = 'Cashier Details | Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="app-shell">
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <main class="admin-main">
        <?php
        $appHeaderRole = 'admin';
        $appHeaderRoleLabel = 'Administrator';
        $appHeaderTitle = 'Cashier Details';
        $appHeaderSubtitle = $user['first_name'] . ' ' . $user['last_name'];
        $appHeaderIcon = 'fa-id-card';
        $appHeaderHome = 'admin_dashboard.php';
        $appHeaderActions = [
            [
                'href' => 'admin_users.php',
                'label' => 'Back',
                'icon' => 'fa-arrow-left',
                'class' => 'btn btn-secondary',
            ],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <section class="panel max-w-2xl p-6">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-bold text-slate-500">Full name</dt>
                    <dd class="mt-1 text-lg font-extrabold text-ink"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-bold text-slate-500">Role</dt>
                    <dd class="mt-1"><span class="role-badge"><?php echo e(strtoupper($user['role'])); ?></span></dd>
                </div>
                <div>
                    <dt class="text-sm font-bold text-slate-500">Email</dt>
                    <dd class="mt-1 text-ink"><?php echo e($user['email']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-bold text-slate-500">Phone</dt>
                    <dd class="mt-1 text-ink"><?php echo e($user['phone']); ?></dd>
                </div>
            </dl>

            <a href="edit_user.php?id=<?php echo (int) $user['id']; ?>" class="btn mt-6">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit Cashier
            </a>
        </section>
    </main>
</body>
</html>
