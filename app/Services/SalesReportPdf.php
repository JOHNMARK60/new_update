<?php

namespace App\Services;

class SalesReportPdf
{
    public function render(array $items, string $from, string $to, float $total, string $preparedBy): string
    {
        $lines = ['KANTO GOODS', 'Sales Report', "Period: {$from} to {$to}", "Prepared by: {$preparedBy}", '', 'Product                              Qty          Amount'];
        foreach (array_slice($items, 0, 36) as $item) {
            $lines[] = sprintf('%-35s %5d %15s', substr((string) $item['product_name'], 0, 35), $item['quantity'], number_format((float) $item['total'], 2));
        }
        $lines[] = '';
        $lines[] = 'Total Sales: PHP '.number_format($total, 2);
        $content = "BT\n/F1 11 Tf\n45 800 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -19 Td\n";
            }
            $content .= '('.$this->escape($line).") Tj\n";
        }
        $content .= 'ET';
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
            5 => '<< /Length '.strlen($content).">>\nstream\n{$content}\nendstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($id = 1; $id <= 5; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], preg_replace('/[^\x20-\x7E]/', '', $value));
    }
}
