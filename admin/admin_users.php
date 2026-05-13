<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

function role_label($role)
{
    return $role === 'admin' ? 'Administrator' : 'User';
}

function email_exists($conn, $email, $except_id = 0)
{
    $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'si', $email, $except_id);
    mysqli_stmt_execute($stmt);
    $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return (bool) $exists;
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
            echo "<script>alert('Password does not match.'); window.location='admin_users.php';</script>";
            exit();
        }

        if (email_exists($conn, $email)) {
            echo "<script>alert('Email already exists.'); window.location='admin_users.php';</script>";
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
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = ($_POST['role'] ?? 'cashier') === 'admin' ? 'admin' : 'cashier';
        $new_password = $_POST['new_password'] ?? '';

        if (email_exists($conn, $email, $id)) {
            echo "<script>alert('Email already exists.'); window.location='admin_users.php';</script>";
            exit();
        }

        if ($id === (int) $_SESSION['user_id']) {
            $role = 'admin';
        }

        if ($new_password !== '') {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare(
                $conn,
                'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?'
            );
            mysqli_stmt_bind_param($stmt, 'ssssssi', $first_name, $last_name, $email, $phone, $role, $hash, $id);
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ? WHERE id = ?'
            );
            mysqli_stmt_bind_param($stmt, 'sssssi', $first_name, $last_name, $email, $phone, $role, $id);
        }

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id !== (int) $_SESSION['user_id']) {
            $stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    header('Location: admin_users.php');
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC, id ASC");
$users = [];

while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

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
        <header class="page-topbar">
            <div>
                <h1 class="page-title">Users</h1>
                <p class="page-subtitle">Manage administrator and user accounts.</p>
            </div>
            <button type="button" class="btn" data-modal-open="addUserModal">
                <i class="fa-solid fa-user-plus"></i>
                Add Account
            </button>
        </header>

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
                        <option value="cashier">User</option>
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
                                <option value="cashier" <?php echo $row['role'] !== 'admin' ? 'selected' : ''; ?>>User</option>
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
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="<?php echo e(app_url('assets/script.js')); ?>"></script>
</body>
</html>
