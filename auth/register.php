<?php
require_once __DIR__ . '/../config/auth.php';

swal_flash('info', 'Admin Approval Required', 'Public registration is disabled. Cashier accounts are created only by the administrator.');
header('Location: ' . app_url('index.php'));
exit();
