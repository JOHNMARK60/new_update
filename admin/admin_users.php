<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

function role_label($role)
{
    return $role === 'admin' ? 'Administrator' : 'Cashier';
}

function email_exists(PDO $pdo, $email, $except_id = 0)
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :except_id LIMIT 1');
    $stmt->execute(['email' => $email, 'except_id' => (int) $except_id]);
    $exists = $stmt->fetch();

    return (bool) $exists;
}

function role_id_for(PDO $pdo, string $role): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = :role LIMIT 1');
    $stmt->execute(['role' => $role]);
    $roleId = $stmt->fetchColumn();

    return $roleId !== false ? (int) $roleId : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['user_action'] ?? '';

    if ($action === 'add') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = ($_POST['role'] ?? 'cashier') === 'admin' ? 'admin' : 'cashier';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($password !== $confirm) {
            swal_flash('warning', 'Passwords do not match.', 'Please re-enter the cashier password.');
            header('Location: admin_users.php');
            exit();
        }

        if (email_exists($pdo, $email)) {
            swal_flash('error', 'Username or email already exists.', 'Use a different cashier email address.');
            header('Location: admin_users.php');
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
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = ($_POST['role'] ?? 'cashier') === 'admin' ? 'admin' : 'cashier';
        $new_password = $_POST['new_password'] ?? '';

        if (email_exists($pdo, $email, $id)) {
            swal_flash('error', 'Username or email already exists.', 'Use a different cashier email address.');
            header('Location: admin_users.php');
            exit();
        }

        if ($id === (int) $_SESSION['user_id']) {
            $role = 'admin';
        }

        $roleId = role_id_for($pdo, $role);

        if ($new_password !== '') {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, role = :role, role_id = :role_id, password = :password
                 WHERE id = :id'
            );
            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'role' => $role,
                'role_id' => $roleId,
                'password' => $hash,
                'id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, role = :role, role_id = :role_id
                 WHERE id = :id'
            );
            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'role' => $role,
                'role_id' => $roleId,
                'id' => $id,
            ]);
        }
        swal_flash('success', 'Cashier Account Updated', 'Cashier account updated successfully.');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id !== (int) $_SESSION['user_id']) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);
            swal_flash('success', 'Cashier Account Deleted', 'Cashier account has been removed.');
        }
    }

    header('Location: admin_users.php');
    exit();
}

$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, id ASC")->fetchAll();

$pageTitle = 'User Management | Admin';
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
        $appHeaderTitle = 'Users';
        $appHeaderSubtitle = 'Manage administrator and cashier accounts.';
        $appHeaderIcon = 'fa-users-gear';
        $appHeaderHome = 'admin_dashboard.php';
        $appHeaderActions = [
            [
                'tag' => 'button',
                'label' => 'Add Account',
                'icon' => 'fa-user-plus',
                'class' => 'btn',
                'attributes' => ['data-modal-open' => 'addUserModal'],
            ],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <section class="panel overflow-hidden">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-base font-bold text-ink">System Accounts</h2>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $row): ?>
                            <?php
                            $user_id = (int) $row['id'];
                            $is_self = $user_id === (int) $_SESSION['user_id'];
                            ?>
                            <tr>
                                <td><?php echo $user_id; ?></td>
                                <td><strong><?php echo e($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
                                <td><?php echo e($row['email']); ?></td>
                                <td><?php echo e($row['phone']); ?></td>
                                <td><span class="role-badge"><?php echo e(role_label($row['role'])); ?></span></td>
                                <td>
                                    <div class="action-icons">
                                        <button type="button" class="view-btn" title="View account" data-modal-open="viewUser<?php echo $user_id; ?>">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button type="button" class="edit-btn" title="Edit account" data-modal-open="editUser<?php echo $user_id; ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <?php if (!$is_self): ?>
                                            <button type="button" class="delete-btn" title="Delete account" data-modal-open="deleteUser<?php echo $user_id; ?>">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="addUserModal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Add Account</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="user_action" value="add">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>First name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last name</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone">
                    </div>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="cashier">Cashier</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($users as $row): ?>
        <?php
        $user_id = (int) $row['id'];
        $is_self = $user_id === (int) $_SESSION['user_id'];
        ?>
        <div class="modal-overlay" id="viewUser<?php echo $user_id; ?>" aria-hidden="true">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Account Details</h3>
                    <button type="button" class="modal-close" data-modal-close>&times;</button>
                </div>
                <div class="modal-body">
                    <dl class="modal-detail-grid">
                        <div><dt>Name</dt><dd><?php echo e($row['first_name'] . ' ' . $row['last_name']); ?></dd></div>
                        <div><dt>Role</dt><dd><?php echo e(role_label($row['role'])); ?></dd></div>
                        <div><dt>Email</dt><dd><?php echo e($row['email']); ?></dd></div>
                        <div><dt>Phone</dt><dd><?php echo e($row['phone']); ?></dd></div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="editUser<?php echo $user_id; ?>" aria-hidden="true">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Edit Account</h3>
                    <button type="button" class="modal-close" data-modal-close>&times;</button>
                </div>
                <form method="POST" class="modal-body">
                    <input type="hidden" name="user_action" value="update">
                    <input type="hidden" name="id" value="<?php echo $user_id; ?>">

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>First name</label>
                            <input type="text" name="first_name" value="<?php echo e($row['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last name</label>
                            <input type="text" name="last_name" value="<?php echo e($row['last_name']); ?>" required>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo e($row['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo e($row['phone']); ?>">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" <?php echo $is_self ? 'disabled' : ''; ?>>
                                <option value="admin" <?php echo $row['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                <option value="cashier" <?php echo $row['role'] !== 'admin' ? 'selected' : ''; ?>>Cashier</option>
                            </select>
                            <?php if ($is_self): ?>
                                <input type="hidden" name="role" value="admin">
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>New password</label>
                            <input type="password" name="new_password" placeholder="Leave blank to keep current">
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                        <button type="submit" class="btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!$is_self): ?>
            <div class="modal-overlay" id="deleteUser<?php echo $user_id; ?>" aria-hidden="true">
                <div class="modal-card modal-card-sm">
                    <div class="modal-header">
                        <h3>Delete Account</h3>
                        <button type="button" class="modal-close" data-modal-close>&times;</button>
                    </div>
                    <form method="POST" class="modal-body">
                        <input type="hidden" name="user_action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $user_id; ?>">
                        <p class="text-slate-600">Delete <strong><?php echo e($row['first_name'] . ' ' . $row['last_name']); ?></strong>?</p>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                            <button
                                type="submit"
                                class="btn btn-danger"
                                data-swal-confirm="Delete or deactivate cashier?"
                                data-swal-text="This action cannot be undone."
                                data-swal-confirm-text="Yes, delete">
                                Delete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="<?php echo e(app_url('assets/script.js')); ?>"></script>
</body>
</html>
