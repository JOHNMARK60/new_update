<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\AbstractModel;

class SaleItem extends AbstractModel
{
    public function getProductId(): int
    {
        return (int) $this->get('product_id');
    }

    public function getProductName(): string
    {
        return (string) $this->get('product_name');
    }

    public function getQuantity(): int
    {
        return (int) $this->get('quantity');
    }

    public function getUnitPrice(): float
    {
        return (float) $this->get('unit_price');
    }

    public function getTotalPrice(): float
    {
        return round($this->getUnitPrice() * $this->getQuantity(), 2);
    }
}

