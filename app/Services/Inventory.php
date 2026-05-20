<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepository;

class Inventory
{
    private ProductRepository $products;

    public function __construct(?ProductRepository $products = null)
    {
        $this->products = $products ?: new ProductRepository();
    }

    public function hasEnoughStock(int $productId, int $quantity): bool
    {
        $product = $this->products->find($productId);

        return $product !== null && (int) $product['quantity'] >= $quantity;
    }

    public function lowStockAlerts(): array
    {
        return $this->products->lowStock();
    }

    public function statusLabel(array $product): string
    {
        $quantity = (int) $product['quantity'];
        $lowStockLevel = (int) ($product['low_stock_level'] ?? 5);

        if ($quantity === 0) {
            return 'Out of stock';
        }

        if ($quantity <= $lowStockLevel) {
            return 'Low stock';
        }

        return 'In stock';
    }
}

