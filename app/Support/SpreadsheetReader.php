<?php

namespace App\Support;

/**
 * Dependency-free reader for CSV and .xlsx (Office Open XML) upload files.
 * Returns a list of associative rows keyed by the (trimmed) first-row headers.
 * Handles the shared-strings table, inline strings and plain number cells that
 * Excel produces, plus column gaps.
 */
class SpreadsheetReader
{
    /**
     * @return array<int, array<string, string>> rows keyed by header
     */
    public static function rows(string $path, string $extension): array
    {
        $matrix = strtolower($extension) === 'csv'
            ? self::readCsv($path)
            : self::readXlsx($path);

        if (empty($matrix)) {
            return [];
        }

        $headers = array_map(fn ($h) => trim((string) $h), array_shift($matrix));
        $rows = [];

        foreach ($matrix as $cells) {
            $row = [];
            $blank = true;
            foreach ($headers as $i => $header) {
                if ($header === '') {
                    continue;
                }
                $val = trim((string) ($cells[$i] ?? ''));
                $row[$header] = $val;
                $blank = $blank && $val === '';
            }
            if (! $blank) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** @return array<int, array<int, string>> */
    private static function readCsv(string $path): array
    {
        $out = [];
        if (($fh = fopen($path, 'r')) === false) {
            return [];
        }
        // Strip a UTF-8 BOM from the first cell if present.
        $first = true;
        while (($cells = fgetcsv($fh)) !== false) {
            if ($first && isset($cells[0])) {
                $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cells[0]);
                $first = false;
            }
            $out[] = $cells;
        }
        fclose($fh);

        return $out;
    }

    /** @return array<int, array<int, string>> */
    private static function readXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        // Shared strings (Excel stores most text here).
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sst = @simplexml_load_string($xml);
            if ($sst !== false) {
                foreach ($sst->si as $si) {
                    $shared[] = self::siText($si);
                }
            }
        }

        // First worksheet as referenced by the workbook.
        $sheetPath = self::firstSheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();
        if ($sheetXml === false) {
            return [];
        }

        $sheet = @simplexml_load_string($sheetXml);
        if ($sheet === false) {
            return [];
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            $max = -1;
            foreach ($row->c as $c) {
                $col = self::colIndex((string) $c['r']);
                $type = (string) $c['t'];
                if ($type === 's') {
                    $value = $shared[(int) $c->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = self::siText($c->is);
                } elseif ($type === 'str') {
                    $value = (string) $c->v;
                } else {
                    $value = isset($c->v) ? (string) $c->v : '';
                }
                $cells[$col] = $value;
                $max = max($max, $col);
            }
            // Fill gaps so column positions line up with the header row.
            $line = [];
            for ($i = 0; $i <= $max; $i++) {
                $line[$i] = $cells[$i] ?? '';
            }
            $rows[] = $line;
        }

        return $rows;
    }

    /** Concatenate all text runs inside a shared-string / inline-string node. */
    private static function siText(\SimpleXMLElement $si): string
    {
        if (isset($si->t)) {
            return (string) $si->t;
        }
        $text = '';
        foreach ($si->r as $run) {
            $text .= (string) $run->t;
        }

        return $text;
    }

    /** Map a cell ref like "AB12" → zero-based column index (AB → 27). */
    private static function colIndex(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $m);
        $letters = $m[1] ?? 'A';
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }

    private static function firstSheetPath(\ZipArchive $zip): string
    {
        // Resolve the first sheet's target via the workbook rels; fall back to sheet1.
        $wb = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $book = $zip->getFromName('xl/workbook.xml');
        if ($wb !== false && $book !== false) {
            $rels = @simplexml_load_string($wb);
            $workbook = @simplexml_load_string($book);
            if ($rels !== false && $workbook !== false) {
                $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $sheet = $workbook->sheets->sheet[0] ?? null;
                if ($sheet !== null) {
                    $rid = (string) $sheet->attributes('r', true)->id;
                    foreach ($rels->Relationship as $rel) {
                        if ((string) $rel['Id'] === $rid) {
                            return 'xl/' . ltrim((string) $rel['Target'], '/');
                        }
                    }
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }
}
