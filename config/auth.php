<?php
require_once __DIR__ . '/db.php';

use App\Services\Auth;

Auth::startSession();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($value)
{
    return '&#8369;' . number_format((float) $value, 2);
}

function swal_flash(string $icon, string $title, string $text = '', array $options = []): void
{
    $_SESSION['swal'] = array_merge([
        'icon' => $icon,
        'title' => $title,
        'text' => $text,
    ], $options);
}

function swal_toast(string $icon, string $title, array $options = []): void
{
    $_SESSION['swal'] = array_merge([
        'toast' => true,
        'icon' => $icon,
        'title' => $title,
    ], $options);
}

function take_swal_flash(): ?array
{
    if (empty($_SESSION['swal']) || !is_array($_SESSION['swal'])) {
        return null;
    }

    $flash = $_SESSION['swal'];
    unset($_SESSION['swal']);

    return $flash;
}

function wants_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    return stripos($accept, 'application/json') !== false
        || strtolower($requestedWith) === 'xmlhttprequest'
        || isset($_POST['ajax']);
}

function json_response(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit();
}

function swal_response_or_redirect(array $payload, string $fallback): never
{
    if (wants_json()) {
        json_response($payload, ($payload['status'] ?? '') === 'success' ? 200 : 422);
    }

    swal_flash($payload['icon'] ?? 'info', $payload['title'] ?? 'Notice', $payload['message'] ?? $payload['text'] ?? '', [
        'redirect' => $payload['redirect'] ?? null,
    ]);
    header('Location: ' . $fallback);
    exit();
}

function app_base_path()
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if ($dir === '.' || $dir === '') {
        return '';
    }

    $section = basename($dir);

    if (in_array($section, ['admin', 'user', 'cashier', 'auth', 'assets', 'config'], true)) {
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
    header('Location: ' . ($role === 'admin' ? app_url('admin/dashboard.php') : app_url('cashier/dashboard.php')));
    exit();
}

function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        swal_flash('error', 'Access denied', 'Please sign in to continue.');
        header('Location: ' . app_url('index.php'));
        exit();
    }
}

function require_role($role)
{
    if (!isset($_SESSION['user_id'])) {
        swal_flash('error', 'Session expired', 'Please sign in again.');
        header('Location: ' . app_url('index.php'));
        exit();
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        swal_flash('error', 'Access denied', 'Access denied. You are not allowed to view this page.');
        header('Location: ' . app_url('index.php'));
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
        $stmt = App\Core\Database::getConnection()->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute(['password' => $fresh_hash, 'id' => (int) $user['id']]);
    }

    return $matches;
}
?>
