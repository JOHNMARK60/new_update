<?php
require_once __DIR__ . '/../config/auth.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        echo "<script>alert('Password does not match.'); window.location='reset_password.php?token=" . e($token) . "';</script>";
        exit();
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE reset_token = ? AND token_expires_at > NOW() LIMIT 1');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "<script>alert('Reset token is invalid or expired.'); window.location='forgot_password.php';</script>";
        exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password = ?, reset_token = NULL, token_expires_at = NULL WHERE id = ?');
    $stmt->execute([$hash, $user['id']]);

    echo "<script>alert('Password updated. Please sign in.'); window.location='login.php';</script>";
    exit();
}

$pageTitle = 'Reset Password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="auth-page">
    <main class="auth-card overflow-hidden">
        <section class="border-b border-slate-200 bg-white p-8 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-lg bg-brand text-2xl text-white">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1 class="mt-5 text-2xl font-extrabold text-ink">Set New Password</h1>
        </section>

        <form method="POST" class="grid gap-4 p-8">
            <input type="hidden" name="token" value="<?php echo e($token); ?>">
            <div class="form-group">
                <label for="password">New password</label>
                <input id="password" type="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <input id="confirm_password" type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn w-full">Update Password</button>
        </form>
    </main>
</body>
</html>
