<?php
$_POST['role'] = 'admin';

if (isset($_POST['username']) && !isset($_POST['email'])) {
    $_POST['email'] = $_POST['username'];
}

require __DIR__ . '/login_process.php';
