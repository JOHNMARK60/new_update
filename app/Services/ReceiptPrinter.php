<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\PrintableReceiptInterface;

class ReceiptPrinter
{
    public function render(PrintableReceiptInterface $receipt): string
    {
        return $receipt->renderHtml();
    }
}

