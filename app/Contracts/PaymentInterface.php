<?php
declare(strict_types=1);

namespace App\Contracts;

interface PaymentInterface
{
    public function getAmount(): float;

    public function getTenderedAmount(): float;

    public function getChangeAmount(): float;

    public function isSufficient(): bool;
}

