<?php
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('index.php'));
    exit();
}

$email = trim((string) ($_POST['email'] ?? $_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$role = (string) ($_POST['role'] ?? 'cashier');
$role = in_array($role, ['admin', 'cashier'], true) ? $role : '';
$fallback = $role === 'admin' ? app_url('auth/admin_login.php') : ($role === 'cashier' ? app_url('auth/login.php') : app_url('index.php'));

if ($email === '') {
    swal_response_or_redirect([
        'status' => 'error',
        'icon' => 'warning',
        'title' => 'Login Required',
        'message' => 'Please enter your username or email.',
    ], $fallback);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    swal_response_or_redirect([
        'status' => 'error',
        'icon' => 'warning',
        'title' => 'Invalid Email',
        'message' => 'Please enter a valid email address.',
    ], $fallback);
}

if ($password === '') {
    swal_response_or_redirect([
        'status' => 'error',
        'icon' => 'warning',
        'title' => 'Password Required',
        'message' => 'Please enter your password.',
    ], $fallback);
}

if ($role === '') {
    swal_response_or_redirect([
        'status' => 'error',
        'icon' => 'warning',
        'title' => 'Role Required',
        'message' => 'Please select your role.',
    ], $fallback);
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_matches_and_upgrade($conn, $user, $password)) {
    swal_response_or_redirect([
        'status' => 'error',
        'icon' => 'error',
        'title' => 'Invalid Login',
        'message' => 'Invalid login credentials.',
    ], $fallback);
}

if (($user['role'] ?? '') !== $role) {
    swal_response_or_redirect([
        'status' => 'error',
        'icon' => 'error',
        'title' => 'Role Mismatch',
        'message' => 'User role does not match selected role.',
    ], $fallback);
}

if ((array_key_exists('is_active', $user) && (int) $user['is_active'] === 0)
    || (array_key_exists('status', $user) && strtolower((string) $user['status']) === 'disabled')) {
    swal_response_or_redirect([
        'status' => 'error',
        'icon' => 'error',
        'title' => 'Account Disabled',
        'message' => 'Your account is disabled. Please contact the administrator.',
    ], $fallback);
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['id'] = (int) $user['id'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

$redirect = $role === 'admin' ? app_url('admin/dashboard.php') : app_url('cashier/dashboard.php');
$message = $role === 'admin'
    ? 'Welcome Admin! Redirecting to your dashboard...'
    : 'Welcome Cashier! Redirecting to your dashboard...';

swal_response_or_redirect([
    'status' => 'success',
    'icon' => 'success',
    'title' => $role === 'admin' ? 'Welcome Admin' : 'Welcome Cashier',
    'message' => $message,
    'redirect' => $redirect,
], app_url('index.php'));
