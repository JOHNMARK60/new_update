<?php
declare(strict_types=1);

namespace App\Models;

use App\Contracts\PaymentInterface;
use App\Core\AbstractModel;

class Payment extends AbstractModel implements PaymentInterface
{
    public function getAmount(): float
    {
        return round((float) $this->get('amount', 0), 2);
    }

    public function getTenderedAmount(): float
    {
        return round((float) $this->get('tendered_amount', 0), 2);
    }

    public function getChangeAmount(): float
    {
        return round(max($this->getTenderedAmount() - $this->getAmount(), 0), 2);
    }

    public function getMethod(): string
    {
        return (string) $this->get('payment_method', 'cash');
    }

    public function isSufficient(): bool
    {
        return $this->getTenderedAmount() >= $this->getAmount();
    }
}

