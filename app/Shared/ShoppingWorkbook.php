<?php

declare(strict_types=1);

namespace Premisely\Shared;

use RuntimeException;
use ZipArchive;

/** Minimal .xlsx for the printable shopping list. */
final class ShoppingWorkbook
{
    /**
     * @param list<array{category:string,color:string,items:list<array{product:string,brand:string,quantity:string}>}> $groups
     */
    public static function render(string $title, array $groups): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('No se puede armar el Excel en este servidor.');
        }

        $headers = ['Categoría (rubro)', 'Producto', 'Marca', 'Cantidad a comprar', '¿Comprado?'];
        $widths = [24.0, 22.0, 16.0, 22.0, 14.0];
        foreach ($groups as $group) {
            $widths[0] = max($widths[0], min(46.0, mb_strlen($group['category']) + 3));
            foreach ($group['items'] as $item) {
                $values = [$item['product'], $item['brand'], $item['quantity']];
                foreach ($values as $i => $value) {
                    $widths[$i + 1] = max($widths[$i + 1], min(46.0, mb_strlen($value) + 3));
                }
            }
        }

        $sheet = self::sheet($title, $headers, $groups, $widths);
        $files = [
            '[Content_Types].xml' => self::contentTypes(),
            '_rels/.rels' => self::rootRels(),
            'xl/workbook.xml' => self::workbook(),
            'xl/_rels/workbook.xml.rels' => self::workbookRels(),
            'xl/styles.xml' => self::styles($groups),
            'xl/worksheets/sheet1.xml' => $sheet,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new RuntimeException('No se pudo crear el archivo.');
        }
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new RuntimeException('No se pudo crear el archivo.');
        }
        foreach ($files as $name => $xml) {
            $zip->addFromString($name, $xml);
        }
        $zip->close();
        $binary = file_get_contents($tmp);
        @unlink($tmp);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('No se pudo crear el archivo.');
        }

        return $binary;
    }

    /**
     * @param list<string> $headers
     * @param list<array{category:string,color:string,items:list<array{product:string,brand:string,quantity:string}>}> $groups
     * @param list<float> $widths
     */
    private static function sheet(string $title, array $headers, array $groups, array $widths): string
    {
        $cols = '';
        foreach ($widths as $i => $width) {
            $n = $i + 1;
            $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . number_format($width, 2, '.', '') . '" customWidth="1"/>';
        }

        $styleOf = [];
        foreach (self::colors($groups) as $i => $hex) {
            $styleOf[$hex] = 6 + $i;
        }

        $rowsXml = '<row r="1" ht="32" customHeight="1">' . self::cell('A', 1, $title, 1) . '</row>';
        $headerStyles = [2, 3, 3, 3, 3];
        $headerCells = '';
        foreach ($headers as $i => $header) {
            $headerCells .= self::cell(self::column($i), 2, $header, $headerStyles[$i]);
        }
        $rowsXml .= '<row r="2" ht="22" customHeight="1">' . $headerCells . '</row>';

        $merges = ['A1:E1'];
        $r = 3;
        foreach ($groups as $group) {
            $start = $r;
            $hex = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $group['color']) ?? '');
            $style = $styleOf[$hex] ?? 4;
            $count = count($group['items']);
            foreach ($group['items'] as $i => $item) {
                $line = '';
                if ($i === 0) {
                    $line .= self::cell('A', $r, $group['category'], $style);
                }
                $line .= self::cell('B', $r, $item['product'], 4);
                $line .= self::cell('C', $r, $item['brand'], 4);
                $line .= self::cell('D', $r, $item['quantity'], 4);
                $line .= '<c r="E' . $r . '" s="5"/>';
                $rowsXml .= '<row r="' . $r . '">' . $line . '</row>';
                $r++;
            }
            if ($count > 1) {
                $merges[] = 'A' . $start . ':A' . ($r - 1);
            }
        }
        $last = max(2, $r - 1);
        $mergeXml = '';
        foreach ($merges as $ref) {
            $mergeXml .= '<mergeCell ref="' . $ref . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<dimension ref="A1:E' . $last . '"/>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '<mergeCells count="' . count($merges) . '">' . $mergeXml . '</mergeCells>'
            . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '<pageSetup paperSize="9" orientation="portrait" fitToWidth="1" fitToHeight="0"/>'
            . '</worksheet>';
    }

    /** @param list<array{category:string,color:string,items:list<array{product:string,brand:string,quantity:string}>}> $groups @return list<string> */
    private static function colors(array $groups): array
    {
        $colors = [];
        foreach ($groups as $group) {
            $hex = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $group['color']) ?? '');
            if (strlen($hex) === 6 && !in_array($hex, $colors, true)) {
                $colors[] = $hex;
            }
        }

        return $colors;
    }

    private static function column(int $index): string
    {
        return chr(65 + $index);
    }

    private static function cell(string $column, int $row, string $value, int $style): string
    {
        return '<c r="' . $column . $row . '" t="inlineStr" s="' . $style . '"><is><t xml:space="preserve">'
            . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            . '</t></is></c>';
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Lista de compras" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    /** @param list<array{category:string,color:string,items:list<array{product:string,brand:string,quantity:string}>}> $groups */
    private static function styles(array $groups): string
    {
        $colors = self::colors($groups);
        $fills = '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFC5EDE8"/><bgColor rgb="FFC5EDE8"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8EEF2"/><bgColor rgb="FFE8EEF2"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0E6E6A"/><bgColor rgb="FF0E6E6A"/></patternFill></fill>';
        $xfs = '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>';
        foreach ($colors as $i => $hex) {
            $fillId = 5 + $i;
            $fills .= '<fill><patternFill patternType="solid"><fgColor rgb="FF' . $hex . '"/><bgColor rgb="FF' . $hex . '"/></patternFill></fill>';
            $xfs .= '<xf numFmtId="0" fontId="3" fillId="' . $fillId . '" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="16"/><color rgb="FF12343A"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF172033"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="' . (5 + count($colors)) . '">' . $fills . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFD5DEE6"/></left>'
            . '<right style="thin"><color rgb="FFD5DEE6"/></right>'
            . '<top style="thin"><color rgb="FFD5DEE6"/></top>'
            . '<bottom style="thin"><color rgb="FFD5DEE6"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . (6 + count($colors)) . '">' . $xfs . '</cellXfs>'
            . '</styleSheet>';
    }
}
