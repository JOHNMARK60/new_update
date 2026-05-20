<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class AdminNotification
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: Database::getConnection();
    }

    public function createClosingNotification(int $closingId, string $cashierName, string $closingDate, float $expectedCash, float $actualCash): void
    {
        if ($closingId <= 0) {
            return;
        }

        $exists = $this->pdo->prepare(
            "SELECT id FROM admin_notifications
             WHERE related_type = 'closing_report' AND related_id = :related_id
             LIMIT 1"
        );
        $exists->execute(['related_id' => $closingId]);

        if ($exists->fetch()) {
            return;
        }

        $difference = round($actualCash - $expectedCash, 2);
        $title = 'Cashier closing submitted';

        if ($difference < 0) {
            $body = sprintf(
                '%s closed %s. Short PHP %s. Please explain missing cash before next shift.',
                $cashierName,
                date('M d, Y', strtotime($closingDate)),
                number_format(abs($difference), 2)
            );
        } else {
            $body = sprintf(
                '%s closed %s. Expected %s, actual %s, difference %s.',
                $cashierName,
                date('M d, Y', strtotime($closingDate)),
                $this->moneyText($expectedCash),
                $this->moneyText($actualCash),
                $this->moneyText($difference)
            );
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO admin_notifications
                (type, title, body, link_url, related_type, related_id)
             VALUES
                ('cashier_closing', :title, :body, :link_url, 'closing_report', :related_id)"
        );
        $stmt->execute([
            'title' => $title,
            'body' => $body,
            'link_url' => 'closing_validation.php',
            'related_id' => $closingId,
        ]);
    }

    public function unreadCount(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM admin_notifications WHERE read_at IS NULL')->fetchColumn();
    }

    public function latest(int $limit = 6): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM admin_notifications
             ORDER BY created_at DESC, id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function markClosingNotificationsRead(): void
    {
        $this->pdo->exec("UPDATE admin_notifications SET read_at = NOW() WHERE type = 'cashier_closing' AND read_at IS NULL");
    }

    private function moneyText(float $value): string
    {
        return 'PHP ' . number_format($value, 2);
    }
}
