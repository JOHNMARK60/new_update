<?php
declare(strict_types=1);

use App\Core\Database;

require_once __DIR__ . '/../app/bootstrap.php';

Database::migrate();

$pdo = Database::getConnection();
$conn = function_exists('mysqli_connect') ? Database::getLegacyMysqli() : null;

function column_exists($conn, $table, $column)
{
    return Database::columnExists(Database::getConnection(), (string) $table, (string) $column);
}

function ensure_column($conn, $table, $column, $definition)
{
    Database::ensureColumn(Database::getConnection(), (string) $table, (string) $column, (string) $definition);
}
