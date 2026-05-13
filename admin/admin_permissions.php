<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');
$pageTitle = 'Role Separation | Admin';
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
                <h1 class="page-title">Role Separation</h1>
                <p class="page-subtitle">Admin and cashier access are routed to different workspaces.</p>
            </div>
        </header>

        <section class="grid gap-5 lg:grid-cols-2">
            <article class="panel p-6">
                <div class="card-icon blue"><i class="fa-solid fa-user-shield"></i></div>
                <h2 class="mt-5 text-xl font-extrabold text-ink">Administrator</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Full access to products, cashier accounts, sales reports, and inventory logs.</p>
            </article>

            <article class="panel p-6">
                <div class="card-icon green"><i class="fa-solid fa-cash-register"></i></div>
                <h2 class="mt-5 text-xl font-extrabold text-ink">Cashier</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Access to dashboard, point of sale, product viewing, stock availability, and personal reports.</p>
            </article>
        </section>
    </main>
</body>
</html>
