<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

header('Location: ' . app_url('admin/admin_dashboard.php'));
exit();
