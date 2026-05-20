<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\AbstractModel;

class Product extends AbstractModel
{
    public function getId(): int
    {
        return (int) $this->get('id');
    }

    public function getName(): string
    {
        return (string) $this->get('name');
    }

    public function getPrice(): float
    {
        return (float) $this->get('price');
    }

    public function getQuantity(): int
    {
        return (int) $this->get('quantity');
    }

    public function getLowStockLevel(): int
    {
        return (int) $this->get('low_stock_level', 5);
    }

    public function isLowStock(): bool
    {
        $quantity = $this->getQuantity();

        return $quantity > 0 && $quantity <= $this->getLowStockLevel();
    }
}

