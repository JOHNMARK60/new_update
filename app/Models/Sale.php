<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\AbstractModel;

class Sale extends AbstractModel
{
    /** @var SaleItem[] */
    private array $items = [];

    public function addItem(SaleItem $item): void
    {
        $this->items[] = $item;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getSubtotal(): float
    {
        return round(array_reduce($this->items, static fn (float $sum, SaleItem $item): float => $sum + $item->getTotalPrice(), 0.0), 2);
    }

    public function getDiscount(): float
    {
        return round((float) $this->get('discount', 0), 2);
    }

    public function getTax(): float
    {
        return round((float) $this->get('tax', 0), 2);
    }

    public function getTotal(): float
    {
        return round(max($this->getSubtotal() - $this->getDiscount() + $this->getTax(), 0), 2);
    }
}

