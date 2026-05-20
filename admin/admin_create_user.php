<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

function role_id_for(PDO $pdo, string $role): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = :role LIMIT 1');
    $stmt->execute(['role' => $role]);
    $roleId = $stmt->fetchColumn();

    return $roleId !== false ? (int) $roleId : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = ($_POST['role'] ?? 'cashier') === 'admin' ? 'admin' : 'cashier';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        swal_flash('warning', 'Passwords do not match.', 'Please re-enter the cashier password.');
        header('Location: admin_create_user.php');
        exit();
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $exists = $stmt->fetch();

    if ($exists) {
        swal_flash('error', 'Username or email already exists.', 'Use a different cashier email address.');
        header('Location: admin_create_user.php');
        exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $roleId = role_id_for($pdo, $role);
    $stmt = $pdo->prepare(
        'INSERT INTO users (first_name, last_name, email, phone, password, role, role_id)
         VALUES (:first_name, :last_name, :email, :phone, :password, :role, :role_id)'
    );
    $stmt->execute([
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone,
        'password' => $hash,
        'role' => $role,
        'role_id' => $roleId,
    ]);

    swal_flash('success', 'Cashier Account Created', 'Cashier account created successfully.');
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
        <?php
        $appHeaderRole = 'admin';
        $appHeaderRoleLabel = 'Administrator';
        $appHeaderTitle = 'Add Cashier';
        $appHeaderSubtitle = 'Create a cashier account from the admin workspace.';
        $appHeaderIcon = 'fa-user-plus';
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
                        <option value="cashier">Cashier</option>
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
