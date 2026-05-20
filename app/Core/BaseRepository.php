<?php
declare(strict_types=1);

namespace App\Core;

use App\Contracts\RepositoryInterface;
use PDO;

abstract class BaseRepository implements RepositoryInterface
{
    protected PDO $pdo;
    protected string $table;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: Database::getConnection();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY id DESC");

        return $stmt->fetchAll();
    }
}

