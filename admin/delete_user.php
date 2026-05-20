<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'cashier'");
    $stmt->execute(['id' => $id]);
}

header('Location: admin_users.php');
exit();
?>
