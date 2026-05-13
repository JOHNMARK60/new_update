<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: admin_users.php');
    exit();
}

if (isset($_POST['update_user'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if ($new_password !== '') {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, password = ? WHERE id = ? AND role = "cashier"'
        );
        mysqli_stmt_bind_param($stmt, 'sssssi', $first_name, $last_name, $email, $phone, $hashed_password, $id);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ? AND role = "cashier"'
        );
        mysqli_stmt_bind_param($stmt, 'ssssi', $first_name, $last_name, $email, $phone, $id);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: admin_users.php');
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? AND role = 'cashier' LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    header('Location: admin_users.php');
    exit();
}

$pageTitle = 'Edit Cashier | Admin';
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
                <h1 class="page-title">Edit Cashier</h1>
                <p class="page-subtitle"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></p>
            </div>
        </header>

        <section class="panel max-w-3xl p-6">
            <form method="POST" class="grid gap-5">
                <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="first_name">First name</label>
                        <input id="first_name" type="text" name="first_name" value="<?php echo e($user['first_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last name</label>
                        <input id="last_name" type="text" name="last_name" value="<?php echo e($user['last_name']); ?>" required>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input id="email" type="email" name="email" value="<?php echo e($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone number</label>
                        <input id="phone" type="text" name="phone" value="<?php echo e($user['phone']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">New password</label>
                    <input id="new_password" type="password" name="new_password" placeholder="Leave blank to keep current password">
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="update_user" class="btn">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Changes
                    </button>
                    <a href="admin_users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
