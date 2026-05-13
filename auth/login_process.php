<?php
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = mysqli_prepare($conn, 'SELECT * FROM users WHERE email = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user || $user['role'] === 'admin' || !password_matches_and_upgrade($conn, $user, $password)) {
    echo "<script>alert('Invalid cashier email or password.'); window.location='login.php';</script>";
    exit();
}

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['id'] = (int) $user['id'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['role'] = $user['role'];

header('Location: ' . app_url('user/user_dashboard.php'));
exit();
?>
