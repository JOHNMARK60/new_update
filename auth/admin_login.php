<?php
require_once __DIR__ . '/../config/auth.php';

if (isset($_SESSION['user_id'])) {
    redirect_for_role($_SESSION['role']);
}

$pageTitle = 'Admin Login | Cashiering Inventory System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="auth-page">
    <main class="auth-card overflow-hidden">
        <section class="border-b border-slate-200 bg-ink p-8 text-center text-white">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-lg bg-white text-2xl text-brand">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <h1 class="mt-5 text-2xl font-extrabold">Admin Portal</h1>
            <p class="mt-2 text-sm text-slate-300">Protected access for inventory, staff, and sales control.</p>
        </section>

        <form action="admin_login_process.php" method="POST" class="space-y-4 p-8">
            <div class="form-group">
                <label for="username">Admin email</label>
                <input id="username" type="email" name="username" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
            </div>

            <button type="submit" class="admin-btn w-full">
                <i class="fa-solid fa-lock"></i>
                Access Dashboard
            </button>
        </form>

        <div class="border-t border-slate-200 bg-slate-50 p-6 text-center text-sm">
            <a href="login.php" class="font-semibold text-slate-600 hover:text-brand">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Back to cashier login
            </a>
        </div>
    </main>
</body>
</html>
