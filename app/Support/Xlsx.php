<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Minimal, dependency-free .xlsx writer (Office Open XML) built on PHP's
 * ZipArchive. One worksheet; every cell is written as an inline string so we
 * never touch a shared-strings table or number/date typing. Good enough for a
 * data export and it opens cleanly in Excel / LibreOffice with no warnings.
 */
class Xlsx
{
    /** Build a single-sheet workbook and return it as a download response. */
    public static function download(string $filename, array $header, iterable $rows, string $sheet = 'Data'): BinaryFileResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('xl/workbook.xml', self::workbook($sheet));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($header, $rows));

        $zip->close();

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private static function sheet(array $header, iterable $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $r = 1;
        $xml .= self::row($r++, $header, true);
        foreach ($rows as $row) {
            $xml .= self::row($r++, array_values((array) $row), false);
        }

        return $xml . '</sheetData></worksheet>';
    }

    private static function row(int $r, array $cells, bool $head): string
    {
        $out = "<row r=\"{$r}\">";
        $col = 0;
        foreach ($cells as $val) {
            $ref = self::colName($col++) . $r;
            $style = $head ? ' s="1"' : '';
            $out .= "<c r=\"{$ref}\" t=\"inlineStr\"{$style}><is><t xml:space=\"preserve\">"
                . self::esc((string) ($val ?? '')) . '</t></is></c>';
        }

        return $out . '</row>';
    }

    /** 0 → A, 25 → Z, 26 → AA … */
    private static function colName(int $i): string
    {
        $name = '';
        $i++;
        while ($i > 0) {
            $name = chr(65 + ($i - 1) % 26) . $name;
            $i = intdiv($i - 1, 26);
        }

        return $name;
    }

    private static function esc(string $v): string
    {
        // Drop control characters Excel rejects, then XML-escape.
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v) ?? '';

        return htmlspecialchars($v, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf/><xf fontId="1" applyFont="1"/></cellXfs>'
            . '</styleSheet>';
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

    private static function workbook(string $sheet): string
    {
        $name = self::esc(substr($sheet, 0, 31)) ?: 'Data';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . "<sheets><sheet name=\"{$name}\" sheetId=\"1\" r:id=\"rId1\"/></sheets></workbook>";
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';
    }
}
