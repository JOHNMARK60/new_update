<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

header('Location: ' . app_url('user/user_dashboard.php'));
exit();
