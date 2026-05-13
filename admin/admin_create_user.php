<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = ($_POST['role'] ?? 'cashier') === 'admin' ? 'admin' : 'cashier';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        echo "<script>alert('Password does not match.'); window.location='admin_create_user.php';</script>";
        exit();
    }

    $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($exists) {
        echo "<script>alert('Email already exists.'); window.location='admin_create_user.php';</script>";
        exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO users (first_name, last_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'ssssss', $first_name, $last_name, $email, $phone, $hash, $role);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: admin_users.php');
    exit();
}

$pageTitle = 'Add Cashier | Admin';
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
                <h1 class="page-title">Add Cashier</h1>
                <p class="page-subtitle">Create a cashier account from the admin workspace.</p>
            </div>
            <a href="admin_users.php" class="btn btn-secondary">Back</a>
        </header>

        <section class="panel max-w-3xl p-6">
            <form method="POST" class="grid gap-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="first_name">First name</label>
                        <input id="first_name" type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last name</label>
                        <input id="last_name" type="text" name="last_name" required>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input id="email" type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone number</label>
                        <input id="phone" type="text" name="phone" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="cashier">User</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm password</label>
                        <input id="confirm_password" type="password" name="confirm_password" required>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fa-solid fa-user-plus"></i>
                    Create Cashier
                </button>
            </form>
        </section>
    </main>
</body>
</html>
