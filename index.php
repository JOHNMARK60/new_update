<?php
require_once __DIR__ . '/config/auth.php';

$flash = take_swal_flash();

if (isset($_SESSION['user_id']) && !$flash) {
    redirect_for_role($_SESSION['role']);
}

$pageTitle = 'KANTO GOODS | Inventory and POS System';
$stylesVersion = is_file(__DIR__ . '/assets/css/style.css') ? filemtime(__DIR__ . '/assets/css/style.css') : time();
$scriptVersion = is_file(__DIR__ . '/assets/js/script.js') ? filemtime(__DIR__ . '/assets/js/script.js') : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(app_url('assets/css/style.css?v=' . $stylesVersion)); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.KANTO_SWAL_FLASH = <?php echo json_encode($flash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window.KANTO_LOGIN_ENDPOINT = <?php echo json_encode(app_url('auth/login_process.php')); ?>;
    </script>
    <script src="<?php echo e(app_url('assets/js/script.js?v=' . $scriptVersion)); ?>" defer></script>
</head>
<body class="landing-page">
    <header class="site-header">
        <a href="#home" class="brand" aria-label="KANTO GOODS home">
            <span class="brand-mark"><i class="fa-solid fa-cubes-stacked"></i></span>
            <span class="brand-copy">
                <strong>KANTO GOODS</strong>
                <small>Inventory System</small>
            </span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Open navigation" data-nav-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="site-nav" data-site-nav>
            <a class="is-active" href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="#benefits">Benefits</a>
            <a href="#contact">Contact</a>
            <button class="nav-login-btn" type="button" data-open-login>
                <i class="fa-regular fa-user"></i>
                <span>Login</span>
            </button>
        </nav>
    </header>

    <main id="home">
        <section class="hero-section">
            <div class="hero-copy">
                <p class="hero-badge">
                    <i class="fa-solid fa-cube"></i>
                    <span>Smart Inventory Solution</span>
                </p>
                <h1>Inventory <span>Management</span> System</h1>
                <p class="hero-subtitle" id="about">
                    Manage products, monitor stock levels, process sales, and generate reports in one organized platform.
                </p>

                <div class="hero-actions" id="login">
                    <button type="button" class="primary-btn" data-open-login>
                        <i class="fa-solid fa-cash-register"></i>
                        <span>Login to System</span>
                    </button>
                    <a class="secondary-btn" href="#features">
                        <i class="fa-solid fa-rotate"></i>
                        <span>View Features</span>
                    </a>
                </div>

                <div class="hero-proof-grid" aria-label="System highlights">
                    <article>
                        <i class="fa-solid fa-shield-halved"></i>
                        <strong>Secure</strong>
                        <span>Data Protection</span>
                    </article>
                    <article>
                        <i class="fa-solid fa-bolt"></i>
                        <strong>Fast &amp; Easy</strong>
                        <span>Operations</span>
                    </article>
                    <article>
                        <i class="fa-solid fa-chart-simple"></i>
                        <strong>Accurate</strong>
                        <span>Reports</span>
                    </article>
                </div>
            </div>

            <div class="hero-visual" aria-label="Inventory system workstation preview">
                <img src="<?php echo e(app_url('assets/images/landing-hero.png')); ?>" alt="POS inventory screen with barcode scanner, receipt printer, products, and stock shelves">
            </div>
        </section>

        <section class="features-section" id="features">
            <div class="section-heading section-heading-center">
                <p class="eyebrow">Features</p>
                <h2>Powerful Features for Your Business</h2>
                <p>Everything you need to manage your inventory and sales efficiently.</p>
            </div>

            <div class="features-grid">
                <article class="feature-card">
                    <span class="feature-icon"><i class="fa-solid fa-box-open"></i></span>
                    <div>
                        <h3>Product Management</h3>
                        <p>Add, update, and organize products with categories, prices, and details.</p>
                    </div>
                </article>
                <article class="feature-card">
                    <span class="feature-icon"><i class="fa-regular fa-clipboard"></i></span>
                    <div>
                        <h3>Stock Monitoring</h3>
                        <p>Track real-time stock levels and get alerts for low-stock items.</p>
                    </div>
                </article>
                <article class="feature-card">
                    <span class="feature-icon"><i class="fa-solid fa-cash-register"></i></span>
                    <div>
                        <h3>POS Cashiering</h3>
                        <p>Process transactions quickly and securely with receipt generation.</p>
                    </div>
                </article>
                <article class="feature-card">
                    <span class="feature-icon"><i class="fa-regular fa-file-lines"></i></span>
                    <div>
                        <h3>Receipt Printing</h3>
                        <p>Generate professional receipts for every transaction.</p>
                    </div>
                </article>
                <article class="feature-card">
                    <span class="feature-icon"><i class="fa-solid fa-chart-column"></i></span>
                    <div>
                        <h3>Sales Reports</h3>
                        <p>View and export daily, weekly, monthly, and yearly sales reports.</p>
                    </div>
                </article>
                <article class="feature-card">
                    <span class="feature-icon"><i class="fa-solid fa-users"></i></span>
                    <div>
                        <h3>User Management</h3>
                        <p>Manage system users, roles, and access permissions.</p>
                    </div>
                </article>
            </div>
        </section>

        <section class="benefits-section" id="benefits">
            <div class="section-heading section-heading-center">
                <p class="eyebrow">Why Use This System?</p>
                <h2>Benefits for Your Business</h2>
            </div>

            <div class="benefits-grid">
                <article class="benefit-item">
                    <i class="fa-solid fa-gauge-high"></i>
                    <h3>Increase Efficiency</h3>
                    <p>Automate inventory and sales processes to save time and effort.</p>
                </article>
                <article class="benefit-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <h3>Reduce Errors</h3>
                    <p>Minimize manual errors with accurate stock tracking and reporting.</p>
                </article>
                <article class="benefit-item">
                    <i class="fa-solid fa-chart-line"></i>
                    <h3>Better Control</h3>
                    <p>Monitor your business performance and stock anytime, anywhere.</p>
                </article>
                <article class="benefit-item">
                    <i class="fa-solid fa-wallet"></i>
                    <h3>Cost Savings</h3>
                    <p>Avoid overstocking and stockouts to improve profitability.</p>
                </article>
                <article class="benefit-item">
                    <i class="fa-solid fa-lock"></i>
                    <h3>Secure &amp; Reliable</h3>
                    <p>Your data is safe with role-based access and regular backups.</p>
                </article>
            </div>
        </section>
    </main>

    <div class="login-modal" data-login-modal aria-hidden="true">
        <div class="login-backdrop" data-close-login></div>
        <section class="login-card" role="dialog" aria-modal="true" aria-labelledby="loginTitle">
            <button type="button" class="modal-close" data-close-login aria-label="Close login">&times;</button>
            <div class="login-icon"><i class="fa-solid fa-cubes-stacked"></i></div>
            <h2 id="loginTitle">System Login</h2>
            <p>Sign in to access the cashiering and inventory system.</p>

            <form id="systemLoginForm" action="<?php echo e(app_url('auth/login_process.php')); ?>" method="POST" novalidate>
                <div class="field-group">
                    <label for="login_email">Email or username</label>
                    <input id="login_email" name="email" type="email" autocomplete="username" placeholder="name@store.com">
                </div>

                <div class="field-group">
                    <label for="login_password">Password</label>
                    <input id="login_password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password">
                </div>

                <div class="field-group">
                    <label for="login_role">Role</label>
                    <select id="login_role" name="role">
                        <option value="">Select role</option>
                        <option value="cashier">Cashier</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit" class="signin-btn">Sign In</button>
                <a class="forgot-link" href="<?php echo e(app_url('auth/forgot_password.php')); ?>">Forgot password?</a>
            </form>
        </section>
    </div>

    <footer class="site-footer" id="contact">
        <div class="footer-inner">
            <a href="#home" class="footer-brand" aria-label="KANTO GOODS home">
                <span class="footer-mark"><i class="fa-solid fa-cubes-stacked"></i></span>
                <strong>KANTO GOODS</strong>
            </a>
            <span class="footer-divider" aria-hidden="true"></span>
            <p>Smart. Simple. Reliable.</p>
            <p class="footer-copy">&copy; <?php echo date('Y'); ?> All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
