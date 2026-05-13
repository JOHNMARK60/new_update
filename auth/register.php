<?php
require_once __DIR__ . '/../config/auth.php';

if (isset($_SESSION['user_id'])) {
    redirect_for_role($_SESSION['role']);
}

$pageTitle = 'Cashier Registration | Cashiering Inventory System';
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
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="mt-5 text-2xl font-extrabold text-ink">Create Cashier Account</h1>
            <p class="mt-2 text-sm text-slate-500">New accounts are created with cashier access only.</p>
        </section>

        <form action="register_process.php" method="POST" class="grid gap-4 p-8">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="form-group">
                    <label for="first_name">First name</label>
                    <input id="first_name" type="text" name="first_name" autocomplete="given-name" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last name</label>
                    <input id="last_name" type="text" name="last_name" autocomplete="family-name" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone number</label>
                <input id="phone" type="text" name="phone" autocomplete="tel" required>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" autocomplete="new-password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm password</label>
                    <input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" required>
                </div>
            </div>

            <button type="submit" name="register" class="login-btn w-full">
                <i class="fa-solid fa-user-plus"></i>
                Register Account
            </button>
        </form>

        <div class="border-t border-slate-200 bg-slate-50 p-6 text-center text-sm">
            <a href="login.php" class="font-semibold text-brand hover:underline">Already have an account? Sign in</a>
        </div>
    </main>
</body>
</html>
