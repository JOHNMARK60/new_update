<?php
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['register'])) {
    header('Location: register.php');
    exit();
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($password !== $confirm) {
    echo "<script>alert('Password does not match.'); window.location='register.php';</script>";
    exit();
}

$stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$exists = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($exists) {
    echo "<script>alert('Email already exists.'); window.location='register.php';</script>";
    exit();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'cashier';

$stmt = mysqli_prepare(
    $conn,
    'INSERT INTO users (first_name, last_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)'
);
mysqli_stmt_bind_param($stmt, 'ssssss', $first_name, $last_name, $email, $phone, $hashed_password, $role);
$created = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($created) {
    echo "<script>alert('Registration successful. You can now sign in.'); window.location='login.php';</script>";
    exit();
}

echo "<script>alert('Registration failed. Please try again.'); window.location='register.php';</script>";
exit();
?>
