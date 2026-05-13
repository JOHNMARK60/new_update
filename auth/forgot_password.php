<?php
require_once __DIR__ . '/../config/auth.php';
$pageTitle = 'Forgot Password | Cashiering Inventory System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="auth-page">
    <main class="auth-card overflow-hidden">
        <section class="border-b border-slate-200 bg-white p-8 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-lg bg-amber-600 text-2xl text-white">
                <i class="fa-solid fa-key"></i>
            </div>
            <h1 class="mt-5 text-2xl font-extrabold text-ink">Password Reset</h1>
            <p class="mt-2 text-sm text-slate-500">A testing reset link will be generated for registered emails.</p>
        </section>

        <form action="reset_request_process.php" method="POST" class="space-y-4 p-8">
            <div class="form-group">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" autocomplete="email" required>
            </div>

            <button type="submit" name="btn_submit_request" class="reset-btn w-full">
                <i class="fa-solid fa-paper-plane"></i>
                Send Reset Request
            </button>
        </form>

        <div class="border-t border-slate-200 bg-slate-50 p-6 text-center text-sm">
            <a href="login.php" class="font-semibold text-slate-600 hover:text-brand">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Back to login
            </a>
        </div>
    </main>
</body>
</html>
