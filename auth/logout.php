<?php
require_once __DIR__ . '/../config/auth.php';
$_SESSION = [];
session_destroy();
header('Location: ' . app_url(''));
exit();
?>
