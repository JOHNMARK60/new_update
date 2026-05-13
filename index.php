<?php
require_once __DIR__ . '/config/auth.php';

header('Location: ' . app_url('auth/login.php'));
exit();
?>
