<?php

namespace Tests\Unit;

use App\Services\SalesReportPdf;
use PHPUnit\Framework\TestCase;

class SalesReportPdfTest extends TestCase
{
    public function test_it_generates_a_pdf_document(): void
    {
        $document = (new SalesReportPdf)->render([
            ['product_name' => 'Sample Product', 'quantity' => 2, 'total' => 199.50],
        ], '2026-09-01', '2026-09-01', 199.50, 'System Administrator');

        $this->assertStringStartsWith('%PDF-1.4', $document);
        $this->assertStringContainsString('Sample Product', $document);
        $this->assertStringEndsWith('%%EOF', $document);
    }
}
