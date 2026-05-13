<?php
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "<script>alert('Email address is not registered.'); window.location='forgot_password.php';</script>";
    exit();
}

$token = bin2hex(random_bytes(32));
$expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$stmt = $pdo->prepare('UPDATE users SET reset_token = ?, token_expires_at = ? WHERE email = ?');
$stmt->execute([$token, $expiry, $email]);

$reset_link = app_url('auth/reset_password.php?token=' . $token);
$pageTitle = 'Reset Link Generated';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="auth-page">
    <main class="auth-card p-8 text-center">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-lg bg-mint text-2xl text-white">
            <i class="fa-solid fa-paper-plane"></i>
        </div>
        <h1 class="mt-5 text-2xl font-extrabold text-ink">Reset Request Sent</h1>
        <p class="mt-2 text-sm text-slate-500">Testing mode link:</p>
        <a href="<?php echo e($reset_link); ?>" class="mt-5 block break-all rounded-lg bg-slate-100 p-4 text-sm font-semibold text-brand">
            <?php echo e($reset_link); ?>
        </a>
        <a href="login.php" class="btn mt-6 w-full">Back to Login</a>
    </main>
</body>
</html>
