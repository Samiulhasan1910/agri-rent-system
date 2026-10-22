<?php
declare(strict_types=1);

/**
 * Minimal dependency-free PDF writer (A4, Helvetica, text/lines/rectangles/tables).
 * Used for rental history reports, admin reports and invoices.
 * Text is encoded as WinAnsi, so the Taka sign is written as "Tk".
 */
final class Pdf
{
    private const HELVETICA = [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584];
    private const HELVETICA_BOLD = [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584];

    private array $pages = [];
    private int $page = -1;
    private float $width;
    private float $height;
    private bool $bold = false;
    private float $size = 10;
    public float $y = 0;
    public float $margin = 40;
    private ?Closure $pageHeader = null;

    public function __construct(bool $landscape = false)
    {
        $this->width = $landscape ? 841.89 : 595.28;
        $this->height = $landscape ? 595.28 : 841.89;
    }

    public function width(): float
    {
        return $this->width;
    }

    public function contentWidth(): float
    {
        return $this->width - 2 * $this->margin;
    }

    /** Callback drawn at the top of every new page (e.g. table header). */
    public function onPage(?Closure $callback): void
    {
        $this->pageHeader = $callback;
    }

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->page = count($this->pages) - 1;
        $this->y = $this->margin;
        if ($this->pageHeader) {
            ($this->pageHeader)($this);
        }
    }

    public function ensureSpace(float $needed): void
    {
        if ($this->page < 0 || $this->y + $needed > $this->height - $this->margin) {
            $this->addPage();
        }
    }

    public function font(float $size, bool $bold = false): void
    {
        $this->size = $size;
        $this->bold = $bold;
    }

    public function textWidth(string $text, ?float $size = null, ?bool $bold = null): float
    {
        if ($text === '') {
            return 0.0;
        }
        $widths = ($bold ?? $this->bold) ? self::HELVETICA_BOLD : self::HELVETICA;
        $total = 0;
        foreach (str_split($this->encode($text)) as $char) {
            $code = ord($char);
            $total += ($code >= 32 && $code <= 126) ? $widths[$code - 32] : 556;
        }
        return $total * ($size ?? $this->size) / 1000;
    }

    public function fit(string $text, float $width): string
    {
        if ($this->textWidth($text) <= $width) {
            return $text;
        }
        while ($text !== '' && $this->textWidth($text . '...') > $width) {
            $text = mb_substr($text, 0, -1);
        }
        return $text . '...';
    }

    /** Draw text; $y is measured from the top of the page (baseline). */
    public function text(float $x, float $y, string $text, array $rgb = [29, 43, 34], string $align = 'L', float $boxWidth = 0): void
    {
        if ($align !== 'L' && $boxWidth > 0) {
            $w = $this->textWidth($text);
            $x += $align === 'R' ? $boxWidth - $w : ($boxWidth - $w) / 2;
        }
        $this->out(sprintf(
            'BT /%s %.2F Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET',
            $this->bold ? 'F2' : 'F1',
            $this->size,
            $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255,
            $x,
            $this->height - $y,
            $this->escape($this->encode($text))
        ));
    }

    public function rect(float $x, float $y, float $w, float $h, ?array $fill = null, ?array $stroke = null): void
    {
        $ops = '';
        if ($fill) {
            $ops .= sprintf('%.3F %.3F %.3F rg ', $fill[0] / 255, $fill[1] / 255, $fill[2] / 255);
        }
        if ($stroke) {
            $ops .= sprintf('%.3F %.3F %.3F RG 0.6 w ', $stroke[0] / 255, $stroke[1] / 255, $stroke[2] / 255);
        }
        $ops .= sprintf('%.2F %.2F %.2F %.2F re %s', $x, $this->height - $y - $h, $w, $h, $fill && $stroke ? 'B' : ($fill ? 'f' : 'S'));
        $this->out($ops);
    }

    public function line(float $x1, float $y1, float $x2, float $y2, array $rgb = [210, 222, 214]): void
    {
        $this->out(sprintf('%.3F %.3F %.3F RG 0.6 w %.2F %.2F m %.2F %.2F l S', $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255, $x1, $this->height - $y1, $x2, $this->height - $y2));
    }

    /** Standard branded document header. */
    public function header(string $title, string $subtitle = ''): void
    {
        $this->ensureSpace(80);
        $this->rect(0, 0, $this->width, 64, [22, 128, 60]);
        $this->font(20, true);
        $this->text($this->margin, 38, setting('platform_name', 'AgriRent'), [255, 255, 255]);
        $this->font(9);
        $this->text($this->margin, 52, 'Agricultural Equipment Rental and Management System', [220, 240, 226]);
        $this->font(13, true);
        $this->text($this->margin, 38, $title, [255, 255, 255], 'R', $this->contentWidth());
        if ($subtitle !== '') {
            $this->font(9);
            $this->text($this->margin, 52, $subtitle, [220, 240, 226], 'R', $this->contentWidth());
        }
        $this->y = 90;
    }

    /**
     * Render a table with automatic page breaks.
     * $columns: [['label' => 'Name', 'width' => 120, 'align' => 'L'], ...]
     */
    public function table(array $columns, array $rows, float $rowHeight = 18): void
    {
        $x0 = $this->margin;
        $drawHeader = function () use ($columns, $x0, $rowHeight): void {
            $this->rect($x0, $this->y, array_sum(array_column($columns, 'width')), $rowHeight + 2, [234, 246, 238]);
            $this->font(8.5, true);
            $x = $x0;
            foreach ($columns as $col) {
                $this->text($x + 4, $this->y + 12.5, $this->fit($col['label'], $col['width'] - 8), [20, 83, 45], $col['align'] ?? 'L', $col['width'] - 8);
                $x += $col['width'];
            }
            $this->y += $rowHeight + 2;
        };
        $this->ensureSpace($rowHeight * 3);
        $drawHeader();
        $this->font(8.5);
        foreach ($rows as $i => $row) {
            if ($this->y + $rowHeight > $this->height - $this->margin) {
                $this->addPage();
                $drawHeader();
                $this->font(8.5);
            }
            if ($i % 2 === 1) {
                $this->rect($x0, $this->y, array_sum(array_column($columns, 'width')), $rowHeight, [248, 251, 249]);
            }
            $x = $x0;
            foreach (array_values($row) as $c => $cell) {
                $col = $columns[$c];
                $this->font(8.5, !empty($col['bold']));
                $this->text($x + 4, $this->y + 12, $this->fit((string)$cell, $col['width'] - 8), [29, 43, 34], $col['align'] ?? 'L', $col['width'] - 8);
                $x += $col['width'];
            }
            $this->y += $rowHeight;
            $this->line($x0, $this->y, $x0 + array_sum(array_column($columns, 'width')), $this->y, [230, 238, 232]);
        }
    }

    public function output(): string
    {
        if ($this->page < 0) {
            $this->addPage();
        }
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];
        $next = 5;
        $total = count($this->pages);
        foreach ($this->pages as $index => $content) {
            // page footer
            $footer = sprintf('BT /F1 8 Tf 0.45 0.5 0.47 rg %.2F %.2F Td (%s) Tj ET', $this->margin, 20.0,
                $this->escape($this->encode('Generated ' . date('d M Y, h:i A') . '  |  Page ' . ($index + 1) . ' of ' . $total)));
            $content .= $footer . "\n";
            $pageId = $next++;
            $contentId = $next++;
            $kids[] = "{$pageId} 0 R";
            $objects[$pageId] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', $this->width, $this->height, $contentId);
            $objects[$contentId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }
        $xref = strlen($pdf);
        $count = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    public function download(string $filename): void
    {
        $data = $this->output();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($data));
        header('Cache-Control: no-store');
        echo $data;
        exit;
    }

    private function out(string $line): void
    {
        if ($this->page < 0) {
            $this->addPage();
        }
        $this->pages[$this->page] .= $line . "\n";
    }

    private function encode(string $text): string
    {
        $text = str_replace(['৳', '—', '–', '…', '→', '✓', '★'], ['Tk ', '-', '-', '...', '->', 'v', '*'], $text);
        $converted = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
        return is_string($converted) ? $converted : preg_replace('/[^\x20-\x7E]/', '?', $text);
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $text);
    }
}
