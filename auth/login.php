<?php
require_once __DIR__ . '/../config/auth.php';

if (isset($_SESSION['user_id'])) {
    redirect_for_role($_SESSION['role']);
}

$pageTitle = 'Cashier Login | Cashiering Inventory System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="auth-page">
    <main class="auth-card overflow-hidden">
        <section class="border-b border-slate-200 bg-white p-8 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-lg bg-emerald-600 text-2xl text-white">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <h1 class="mt-5 text-2xl font-extrabold text-ink">Cashier Login</h1>
            <p class="mt-2 text-sm text-slate-500">Sign in with your cashier account.</p>
        </section>

        <form action="login_process.php" method="POST" class="space-y-4 p-8">
            <div class="form-group">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
            </div>

            <button type="submit" class="login-btn w-full">
                <i class="fa-solid fa-right-to-bracket"></i>
                Sign In
            </button>
        </form>

        <div class="grid gap-3 border-t border-slate-200 bg-slate-50 p-6 text-center text-sm">
            <a href="register.php" class="font-semibold text-brand hover:underline">Create cashier account</a>
            <a href="forgot_password.php" class="font-semibold text-slate-600 hover:text-brand">Forgot password?</a>
            <a href="admin_login.php" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 font-bold text-ink hover:bg-slate-100">
                <i class="fa-solid fa-user-shield"></i>
                Administrator Portal
            </a>
        </div>
    </main>
</body>
</html>
