<?php

declare(strict_types=1);

namespace Premisely\Shared;

/** Minimal multi-page text PDF (WinAnsi) for weekly reports. */
final class SimplePdf
{
    /** @var list<array{type:string,text:string}> */
    private array $blocks = [];

    public function title(string $text): void
    {
        $this->blocks[] = ['type' => 'title', 'text' => $text];
    }

    public function subtitle(string $text): void
    {
        $this->blocks[] = ['type' => 'subtitle', 'text' => $text];
    }

    public function heading(string $text): void
    {
        $this->blocks[] = ['type' => 'heading', 'text' => $text];
    }

    public function line(string $text): void
    {
        $this->blocks[] = ['type' => 'line', 'text' => $text];
    }

    public function blank(): void
    {
        $this->blocks[] = ['type' => 'blank', 'text' => ''];
    }

    public function render(): string
    {
        $pages = $this->buildPages();
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $pageCount = count($pages);
        for ($i = 0; $i < $pageCount; $i++) {
            $kids[] = (3 + $i * 2) . ' 0 R';
        }
        $objects[] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';

        foreach ($pages as $i => $content) {
            $pageObj = 3 + $i * 2;
            $contentObj = $pageObj + 1;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents '
                . $contentObj . ' 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> /F2 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> >> >> >>';
            $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    /** @return list<string> */
    private function buildPages(): array
    {
        $pages = [];
        $y = 800;
        $lines = [];
        $flush = static function () use (&$pages, &$lines): void {
            $pages[] = "BT\n" . implode("\n", $lines) . "\nET";
            $lines = [];
        };

        foreach ($this->blocks as $block) {
            $needed = match ($block['type']) {
                'title' => 28,
                'subtitle' => 20,
                'heading' => 26,
                'blank' => 10,
                default => 16,
            };
            if ($y - $needed < 50) {
                $flush();
                $y = 800;
            }

            $text = self::winAnsi($block['text']);
            if ($block['type'] === 'blank') {
                $y -= 10;
                continue;
            }
            if ($block['type'] === 'title') {
                $lines[] = '/F2 16 Tf';
                $lines[] = self::tj(50, $y, $text);
                $y -= 24;
                continue;
            }
            if ($block['type'] === 'subtitle') {
                $lines[] = '/F1 11 Tf';
                $lines[] = self::tj(50, $y, $text);
                $y -= 18;
                continue;
            }
            if ($block['type'] === 'heading') {
                $y -= 8;
                $lines[] = '/F2 12 Tf';
                $lines[] = self::tj(50, $y, $text);
                $y -= 18;
                continue;
            }

            foreach (self::wrap($text, 90) as $chunk) {
                if ($y < 50) {
                    $flush();
                    $y = 800;
                }
                $lines[] = '/F1 10 Tf';
                $lines[] = self::tj(50, $y, $chunk);
                $y -= 14;
            }
        }

        if ($lines !== []) {
            $flush();
        }
        if ($pages === []) {
            $pages[] = "BT\n/F1 10 Tf\n" . self::tj(50, 800, '-') . "\nET";
        }

        return $pages;
    }

    private static function tj(float $x, float $y, string $text): string
    {
        return sprintf('1 0 0 1 %.2F %.2F Tm (%s) Tj', $x, $y, self::escape($text));
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    /** @return list<string> */
    private static function wrap(string $text, int $width): array
    {
        if ($text === '') {
            return [''];
        }
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $rows = [];
        $current = '';
        foreach ($words as $word) {
            $trial = $current === '' ? $word : $current . ' ' . $word;
            if (strlen($trial) > $width && $current !== '') {
                $rows[] = $current;
                $current = $word;
            } else {
                $current = $trial;
            }
        }
        if ($current !== '') {
            $rows[] = $current;
        }
        return $rows;
    }

    private static function winAnsi(string $text): string
    {
        $map = [
            'á' => "\xE1", 'é' => "\xE9", 'í' => "\xED", 'ó' => "\xF3", 'ú' => "\xFA",
            'Á' => "\xC1", 'É' => "\xC9", 'Í' => "\xCD", 'Ó' => "\xD3", 'Ú' => "\xDA",
            'ñ' => "\xF1", 'Ñ' => "\xD1", 'ü' => "\xFC", 'Ü' => "\xDC",
            '¿' => "\xBF", '¡' => "\xA1", '°' => "\xB0", '–' => '-', '—' => '-',
            '“' => '"', '”' => '"', '‘' => "'", '’' => "'",
        ];
        $out = strtr($text, $map);
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $out);
        return $converted !== false ? $converted : $out;
    }
}
