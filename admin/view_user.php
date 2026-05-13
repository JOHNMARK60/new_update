<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? AND role = 'cashier' LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

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
        <header class="page-topbar">
            <div>
                <h1 class="page-title">Cashier Details</h1>
                <p class="page-subtitle"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></p>
            </div>
            <a href="admin_users.php" class="btn btn-secondary">Back</a>
        </header>

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
