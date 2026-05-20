<?php
declare(strict_types=1);

namespace App\Contracts;

interface PrintableReceiptInterface
{
    public function toArray(): array;

    public function renderHtml(): string;
}

