<?php
declare(strict_types=1);

namespace App\Services;

class DailySalesReportPdf
{
    private const PAGE_WIDTH = 842.0;
    private const PAGE_HEIGHT = 595.0;
    private const MARGIN = 32.0;
    private const ROWS_PER_PAGE = 14;

    /** @var string[] */
    private array $pages = [];

    public function render(array $report, string $departmentIncharge, string $anchorDate): string
    {
        $this->pages = [];

        $items = array_values($report['items'] ?? []);
        $chunks = array_chunk($items, self::ROWS_PER_PAGE);

        if ($chunks === []) {
            $chunks = [[]];
        }

        foreach ($chunks as $pageIndex => $chunk) {
            $this->addReportPage(
                $report,
                $chunk,
                $departmentIncharge,
                $anchorDate,
                $pageIndex,
                count($chunks)
            );
        }

        return $this->buildPdf();
    }

    private function addReportPage(array $report, array $items, string $departmentIncharge, string $anchorDate, int $pageIndex, int $pageCount): void
    {
        $summary = $report['summary'] ?? [];
        $page = '';

        $this->rect($page, 0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, [248, 250, 252], null);
        $this->rect($page, self::MARGIN, 24, 778, 52, [7, 43, 100], [7, 43, 100], 0.8);
        $this->text($page, 'KANTO GOODS', 421, 39, 24, 'F2', [237, 255, 36], 'C');
        $this->text($page, 'Daily Sales Report', 421, 64, 8, 'F2', [255, 255, 255], 'C');

        $metaY = 84.0;
        $metaHeight = 86.0;
        $leftW = 528.0;
        $logoW = 250.0;
        $labelW = 150.0;
        $rowH = $metaHeight / 4;

        $this->rect($page, self::MARGIN, $metaY, $leftW, $metaHeight, [255, 255, 255], [0, 0, 0], 0.7);
        $this->rect($page, self::MARGIN + $leftW, $metaY, $logoW, $metaHeight, [255, 255, 255], [0, 0, 0], 0.7);

        $meta = [
            ['Department Name:', 'Sales Department'],
            ['Department Incharge:', $departmentIncharge],
            ['Date:', $this->dateLabel($report, $anchorDate)],
            ['Total Sales PHP', $this->money((float) ($summary['total_sales'] ?? 0))],
        ];

        foreach ($meta as $index => [$label, $value]) {
            $y = $metaY + ($index * $rowH);
            $this->line($page, self::MARGIN, $y + $rowH, self::MARGIN + $leftW, $y + $rowH, [209, 213, 219], 0.45);
            $this->line($page, self::MARGIN + $labelW, $y, self::MARGIN + $labelW, $y + $rowH, [0, 0, 0], 0.5);
            $this->text($page, $label, self::MARGIN + $labelW - 8, $y + 7, 9, 'F2', [17, 24, 39], 'R');
            $this->text($page, $value, self::MARGIN + $labelW + 8, $y + 7, 9, 'F2', [17, 24, 39], 'L', $leftW - $labelW - 16);
        }

        $logoX = self::MARGIN + $leftW + 105;
        $this->rect($page, $logoX, $metaY + 14, 42, 42, [5, 122, 48], [5, 122, 48], 0.7);
        $this->text($page, 'KG', $logoX + 21, $metaY + 27, 15, 'F2', [255, 255, 255], 'C');
        $this->text($page, 'KANTO GOODS', self::MARGIN + $leftW + ($logoW / 2), $metaY + 62, 11, 'F2', [17, 24, 39], 'C');

        $this->rect($page, self::MARGIN, 178, 778, 36, [223, 237, 247], [0, 0, 0], 0.7);
        $this->text($page, 'Daily Sales Report Template', 421, 188, 17, 'F2', [0, 0, 0], 'C');

        $tableX = self::MARGIN;
        $tableY = 214.0;
        $tableW = 778.0;
        $headerH = 24.0;
        $rowH = 20.0;
        $columns = [
            ['SR NO.', 70.0, 'C'],
            ['ITEM', 336.0, 'L'],
            ['QTY', 72.0, 'C'],
            ['Department', 160.0, 'L'],
            ['Amount', 140.0, 'R'],
        ];

        $this->rect($page, $tableX, $tableY, $tableW, $headerH, [223, 237, 247], [0, 0, 0], 0.7);

        $x = $tableX;
        foreach ($columns as [$title, $width, $align]) {
            $this->line($page, $x, $tableY, $x, $tableY + $headerH, [0, 0, 0], 0.55);
            $this->text($page, (string) $title, $x + 6, $tableY + 8, 9, 'F2', [0, 0, 0], $align === 'R' ? 'R' : 'L', $width - 12);
            $x += (float) $width;
        }
        $this->line($page, $tableX + $tableW, $tableY, $tableX + $tableW, $tableY + $headerH, [0, 0, 0], 0.55);

        $runningIndex = ($pageIndex * self::ROWS_PER_PAGE) + 1;
        $currentY = $tableY + $headerH;

        for ($i = 0; $i < self::ROWS_PER_PAGE; $i++) {
            $item = $items[$i] ?? null;
            $this->rect($page, $tableX, $currentY, $tableW, $rowH, [255, 255, 255], [0, 0, 0], 0.45);

            $values = $item ? [
                (string) ($runningIndex + $i),
                (string) ($item['product_name'] ?? ''),
                (string) ((int) ($item['quantity_sold'] ?? 0)),
                (string) ($item['category_name'] ?? 'Uncategorized'),
                $this->money((float) ($item['total_amount'] ?? 0)),
            ] : ['', '', '', '', ''];

            $x = $tableX;
            foreach ($columns as $columnIndex => [, $width, $align]) {
                $this->line($page, $x, $currentY, $x, $currentY + $rowH, [0, 0, 0], 0.4);
                $textX = $align === 'C' ? $x + ((float) $width / 2) : ($align === 'R' ? $x + (float) $width - 6 : $x + 6);
                $this->text($page, $values[$columnIndex], $textX, $currentY + 6, 8, $columnIndex === 1 ? 'F2' : 'F1', [17, 24, 39], (string) $align, (float) $width - 12);
                $x += (float) $width;
            }
            $this->line($page, $tableX + $tableW, $currentY, $tableX + $tableW, $currentY + $rowH, [0, 0, 0], 0.4);
            $currentY += $rowH;
        }

        $this->rect($page, $tableX, $currentY, $tableW, 24, [223, 237, 247], [0, 0, 0], 0.7);
        $this->text($page, 'Total', $tableX + 8, $currentY + 8, 9, 'F2', [0, 0, 0], 'L');
        $this->text($page, (string) ((int) ($summary['total_items_sold'] ?? 0)), $tableX + 70 + 336 + 36, $currentY + 8, 9, 'F2', [0, 0, 0], 'C');
        $this->text($page, $this->money((float) ($summary['total_sales'] ?? 0)), $tableX + $tableW - 8, $currentY + 8, 9, 'F2', [0, 0, 0], 'R');

        $footerY = 552.0;
        $this->line($page, self::MARGIN, $footerY, self::MARGIN + 180, $footerY, [100, 116, 139], 0.4);
        $this->line($page, self::MARGIN + 240, $footerY, self::MARGIN + 420, $footerY, [100, 116, 139], 0.4);
        $this->text($page, 'Prepared by', self::MARGIN, $footerY + 6, 7, 'F1', [71, 85, 105], 'L');
        $this->text($page, 'Reviewed by', self::MARGIN + 240, $footerY + 6, 7, 'F1', [71, 85, 105], 'L');
        $this->text($page, 'Generated ' . date('M d, Y h:i A'), self::PAGE_WIDTH - self::MARGIN, $footerY + 6, 7, 'F1', [71, 85, 105], 'R');
        $this->text($page, 'Page ' . ($pageIndex + 1) . ' of ' . $pageCount, self::PAGE_WIDTH - self::MARGIN, $footerY + 17, 7, 'F1', [71, 85, 105], 'R');

        $this->pages[] = $page;
    }

    private function buildPdf(): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $kids = [];
        $objectId = 5;

        foreach ($this->pages as $content) {
            $contentId = $objectId++;
            $pageId = $objectId++;
            $objects[$contentId] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $kids[] = $pageId . ' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        $maxId = max(array_keys($objects));

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $maxId; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }

        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function dateLabel(array $report, string $anchorDate): string
    {
        $from = (string) ($report['date_from'] ?? $anchorDate);
        $to = (string) ($report['date_to'] ?? $from);
        $fromText = date('M d, Y', strtotime($from) ?: time());

        if ($from === $to) {
            return $fromText;
        }

        return $fromText . ' - ' . date('M d, Y', strtotime($to) ?: time());
    }

    private function money(float $value): string
    {
        return 'PHP ' . number_format($value, 2);
    }

    private function rect(string &$page, float $x, float $topY, float $width, float $height, ?array $fill = null, ?array $stroke = null, float $lineWidth = 0.5): void
    {
        if ($fill !== null) {
            $page .= $this->color($fill, 'rg') . "\n";
        }

        if ($stroke !== null) {
            $page .= $this->color($stroke, 'RG') . "\n" . $this->num($lineWidth) . " w\n";
        }

        $page .= $this->num($x) . ' ' . $this->num(self::PAGE_HEIGHT - $topY - $height) . ' ' . $this->num($width) . ' ' . $this->num($height) . " re " . ($fill !== null && $stroke !== null ? 'B' : ($fill !== null ? 'f' : 'S')) . "\n";
    }

    private function line(string &$page, float $x1, float $topY1, float $x2, float $topY2, array $color, float $lineWidth = 0.5): void
    {
        $page .= $this->color($color, 'RG') . "\n" . $this->num($lineWidth) . " w\n";
        $page .= $this->num($x1) . ' ' . $this->num(self::PAGE_HEIGHT - $topY1) . ' m ' . $this->num($x2) . ' ' . $this->num(self::PAGE_HEIGHT - $topY2) . " l S\n";
    }

    private function text(
        string &$page,
        string $text,
        float $x,
        float $topY,
        float $size,
        string $font,
        array $color,
        string $align = 'L',
        ?float $maxWidth = null
    ): void {
        $text = $this->fit($text, $size, $maxWidth);
        $width = $this->textWidth($text, $size);

        if ($align === 'C') {
            $x -= $width / 2;
        } elseif ($align === 'R') {
            $x -= $width;
        }

        $page .= "BT\n";
        $page .= '/' . $font . ' ' . $this->num($size) . " Tf\n";
        $page .= $this->color($color, 'rg') . "\n";
        $page .= $this->num($x) . ' ' . $this->num(self::PAGE_HEIGHT - $topY - $size) . " Td\n";
        $page .= '(' . $this->escapeText($text) . ") Tj\n";
        $page .= "ET\n";
    }

    private function fit(string $text, float $size, ?float $maxWidth): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if ($maxWidth === null || $this->textWidth($text, $size) <= $maxWidth) {
            return $text;
        }

        while ($text !== '' && $this->textWidth($text . '...', $size) > $maxWidth) {
            $text = substr($text, 0, -1);
        }

        return rtrim($text) . '...';
    }

    private function textWidth(string $text, float $size): float
    {
        return strlen($this->toWinAnsi($text)) * $size * 0.5;
    }

    private function escapeText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->toWinAnsi($text));
    }

    private function toWinAnsi(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '', $text) ?? '' : $converted;
    }

    private function color(array $rgb, string $operator): string
    {
        return $this->num(((float) $rgb[0]) / 255) . ' ' . $this->num(((float) $rgb[1]) / 255) . ' ' . $this->num(((float) $rgb[2]) / 255) . ' ' . $operator;
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(sprintf('%.3F', $value), '0'), '.');
    }
}
