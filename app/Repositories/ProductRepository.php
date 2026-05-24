<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;
use InvalidArgumentException;

class ProductRepository extends BaseRepository
{
    protected string $table = 'products';

    public function available(): array
    {
        $stmt = $this->pdo->query(
            "SELECT p.*, c.name AS category_name, s.name AS supplier_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.quantity > 0
             ORDER BY CASE WHEN c.name IS NULL OR c.name = '' THEN 1 ELSE 0 END ASC,
                      c.name ASC,
                      p.name ASC"
        );

        return $stmt->fetchAll();
    }

    public function allWithMeta(?string $search = null): array
    {
        $params = [];
        $where = '';

        if ($search !== null && trim($search) !== '') {
            $where = 'WHERE p.name LIKE :search_name OR c.name LIKE :search_category OR s.name LIKE :search_supplier OR p.sku LIKE :search_sku';
            $searchTerm = '%' . trim($search) . '%';
            $params = [
                'search_name' => $searchTerm,
                'search_category' => $searchTerm,
                'search_supplier' => $searchTerm,
                'search_sku' => $searchTerm,
            ];
        }

        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name AS category_name, s.name AS supplier_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             {$where}
             ORDER BY CASE WHEN c.name IS NULL OR c.name = '' THEN 1 ELSE 0 END ASC,
                      c.name ASC,
                      p.name ASC,
                      p.id ASC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $this->validateProductData($data);

        $stmt = $this->pdo->prepare(
            'INSERT INTO products
                (name, price, quantity, image_path, category_id, supplier_id, low_stock_level, expiration_date, sku)
             VALUES
                (:name, :price, :quantity, :image_path, :category_id, :supplier_id, :low_stock_level, :expiration_date, :sku)'
        );
        $stmt->execute($this->payload($data));

        $id = (int) $this->pdo->lastInsertId();
        $this->logMovement($id, 'Product added', (int) $data['quantity'], null, (int) $data['quantity'], 'product', $id, $data['created_by'] ?? null);

        return $id;
    }

    public function updateProduct(int $id, array $data): void
    {
        $this->validateProductData($data);
        $current = $this->find($id);

        if (!$current) {
            throw new InvalidArgumentException('Product not found.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE products
             SET name = :name,
                 price = :price,
                 quantity = :quantity,
                 image_path = :image_path,
                 category_id = :category_id,
                 supplier_id = :supplier_id,
                 low_stock_level = :low_stock_level,
                 expiration_date = :expiration_date,
                 sku = :sku
             WHERE id = :id'
        );
        $payload = $this->payload($data);
        $payload['id'] = $id;
        $stmt->execute($payload);

        $stockBefore = (int) $current['quantity'];
        $stockAfter = (int) $data['quantity'];
        $this->logMovement($id, 'Product updated', $stockAfter - $stockBefore, $stockBefore, $stockAfter, 'product', $id, $data['created_by'] ?? null);
    }

    public function deleteProduct(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function lowStock(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM products
             WHERE quantity > 0 AND quantity <= low_stock_level
             ORDER BY quantity ASC, name ASC'
        );

        return $stmt->fetchAll();
    }

    public function categories(): array
    {
        return $this->pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
    }

    public function suppliers(): array
    {
        return $this->pdo->query('SELECT * FROM suppliers ORDER BY name ASC')->fetchAll();
    }

    public function ensureCategory(string $name): ?int
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $stmt = $this->pdo->prepare('INSERT IGNORE INTO categories (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);

        $stmt = $this->pdo->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $name]);

        return (int) $stmt->fetchColumn();
    }

    public function ensureSupplier(string $name): ?int
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $stmt = $this->pdo->prepare('INSERT IGNORE INTO suppliers (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);

        $stmt = $this->pdo->prepare('SELECT id FROM suppliers WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $name]);

        return (int) $stmt->fetchColumn();
    }

    public function logMovement(
        int $productId,
        string $action,
        int $quantityChange,
        ?int $stockBefore,
        ?int $stockAfter,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO inventory_logs
                (product_id, action, quantity_change, stock_before, stock_after, reference_type, reference_id, created_by)
             VALUES
                (:product_id, :action, :quantity_change, :stock_before, :stock_after, :reference_type, :reference_id, :created_by)'
        );
        $stmt->execute([
            'product_id' => $productId,
            'action' => $action,
            'quantity_change' => $quantityChange,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy,
        ]);
    }

    private function validateProductData(array $data): void
    {
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new InvalidArgumentException('Product name is required.');
        }

        if ((float) ($data['price'] ?? 0) < 0) {
            throw new InvalidArgumentException('Product price cannot be negative.');
        }

        if ((int) ($data['quantity'] ?? 0) < 0) {
            throw new InvalidArgumentException('Stock quantity cannot be negative.');
        }
    }

    private function payload(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'price' => round((float) $data['price'], 2),
            'quantity' => max(0, (int) $data['quantity']),
            'image_path' => $data['image_path'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'low_stock_level' => max(0, (int) ($data['low_stock_level'] ?? 5)),
            'expiration_date' => ($data['expiration_date'] ?? '') !== '' ? $data['expiration_date'] : null,
            'sku' => ($data['sku'] ?? '') !== '' ? trim((string) $data['sku']) : null,
        ];
    }
}
