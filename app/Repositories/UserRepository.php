<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    public function cashiers(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM users WHERE role = 'cashier' ORDER BY first_name ASC, last_name ASC");

        return $stmt->fetchAll();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => trim($email)]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}

