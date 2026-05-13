<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($value)
{
    return '&#8369; ' . number_format((float) $value, 2);
}

function app_base_path()
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if ($dir === '.' || $dir === '') {
        return '';
    }

    $section = basename($dir);

    if (in_array($section, ['admin', 'user', 'auth', 'assets', 'config'], true)) {
        $dir = rtrim(dirname($dir), '/');
    }

    return $dir === '/' ? '' : $dir;
}

function app_url($path = '')
{
    return app_base_path() . '/' . ltrim($path, '/');
}

function redirect_for_role($role)
{
    header('Location: ' . ($role === 'admin' ? app_url('admin/admin_dashboard.php') : app_url('user/user_dashboard.php')));
    exit();
}

function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . app_url('auth/login.php'));
        exit();
    }
}

function require_role($role)
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . ($role === 'admin' ? app_url('auth/admin_login.php') : app_url('auth/login.php')));
        exit();
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header('Location: ' . ($role === 'admin' ? app_url('auth/admin_login.php') : app_url('auth/login.php')));
        exit();
    }
}

function password_matches_and_upgrade($conn, array $user, $password)
{
    $stored = (string) $user['password'];
    $matches = password_verify($password, $stored);

    if (!$matches && hash_equals($stored, (string) $password)) {
        $matches = true;
    }

    if ($matches && (hash_equals($stored, (string) $password) || password_needs_rehash($stored, PASSWORD_DEFAULT))) {
        $fresh_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'si', $fresh_hash, $user['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    return $matches;
}
?>
