<?php
require_once __DIR__ . '/config/auth.php';

require_login();
redirect_for_role($_SESSION['role']);
?>
