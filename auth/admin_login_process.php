<?php
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_login.php');
    exit();
}

$email = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user || !password_matches_and_upgrade($conn, $user, $password)) {
    echo "<script>alert('Invalid administrator email or password.'); window.location='admin_login.php';</script>";
    exit();
}

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['id'] = (int) $user['id'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['role'] = 'admin';

header('Location: ' . app_url('admin/admin_dashboard.php'));
exit();
?>
