<?php
require_once __DIR__ . '/../config/auth.php';

swal_flash('error', 'Registration Disabled', 'Cashier accounts should be created only by the administrator.');
header('Location: ' . app_url('index.php'));
exit();
