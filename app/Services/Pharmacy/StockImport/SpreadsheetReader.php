<?php

namespace App\Services\Pharmacy\StockImport;

use DOMDocument;
use XMLReader;
use ZipArchive;

/**
 * Reads a stock spreadsheet into a plain grid of strings — without any
 * third-party library (none is installed; see docs in the controller).
 *
 * The FORMAT is decided by the file's first bytes, never by its name, so a
 * renamed file cannot pick its own parser:
 *
 *   PK\x03\x04 + xl/workbook.xml          Excel 2007+ (.xlsx)       ZipArchive + XMLReader
 *   PK\x03\x04 + mimetype …spreadsheet    OpenDocument (.ods)       ZipArchive + XMLReader
 *   \xD0\xCF\x11\xE0                      legacy binary Excel (.xls) REFUSED — needs a library
 *   "<" (html/xml table)                  ".xls" saved by many pharmacy systems as HTML
 *   anything else that is text            CSV / TSV / semicolon / pipe, UTF-8, UTF-16 or Windows-1252
 *
 * PDF, images and Word documents are refused with a reason: reading stock out
 * of them would be OCR, and a misread "50" for "5" would be published to
 * patients as live availability.
 */
class SpreadsheetReader
{
    public const MAX_ROWS = 5000;
    public const MAX_COLS = 40;
    /** Uncompressed size a zip member may claim before it is treated as a bomb. */
    private const MAX_XML_BYTES = 60 * 1024 * 1024;

    /**
     * @return array{format: string, rows: list<list<string>>}
     *
     * @throws StockImportException
     */
    public function read(string $path): array
    {
        $head = (string) @file_get_contents($path, false, null, 0, 8);

        if ($head === '') {
            throw new StockImportException('err_empty_file');
        }

        if (str_starts_with($head, "PK\x03\x04")) {
            return $this->readZip($path);
        }
        if (str_starts_with($head, "\xD0\xCF\x11\xE0")) {
            throw new StockImportException('err_legacy_xls');
        }
        if (str_starts_with($head, '%PDF')) {
            throw new StockImportException('err_pdf');
        }
        if (str_starts_with($head, "\x89PNG") || str_starts_with($head, "\xFF\xD8\xFF") || str_starts_with($head, 'GIF8')
            || (str_starts_with($head, 'RIFF') && str_contains((string) file_get_contents($path, false, null, 8, 4), 'WEBP'))
            || str_starts_with($head, "II*\x00") || str_starts_with($head, "MM\x00*")) {
            throw new StockImportException('err_image');
        }

        $text = $this->toUtf8((string) file_get_contents($path));

        if (str_contains(substr($text, 0, 4096), "\x00")) {
            throw new StockImportException('err_unreadable');
        }

        $trim = ltrim($text);
        if (str_starts_with($trim, '<') && preg_match('/<table/i', substr($trim, 0, 200000))) {
            return ['format' => 'html', 'rows' => $this->readHtmlTable($text)];
        }

        [$rows, $delimiter] = $this->readDelimited($text);

        return ['format' => $delimiter === "\t" ? 'tsv' : 'csv', 'rows' => $rows];
    }

    // ── Text ──────────────────────────────────────────────────────────────

    /** UTF-8 (with or without BOM), UTF-16 with BOM, else Windows-1252 — the encoding Excel on Windows saves "CSV" in. */
    public function toUtf8(string $raw): string
    {
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            return substr($raw, 3);
        }
        if (str_starts_with($raw, "\xFF\xFE")) {
            return (string) mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');
        }
        if (str_starts_with($raw, "\xFE\xFF")) {
            return (string) mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16BE');
        }
        if (mb_check_encoding($raw, 'UTF-8')) {
            return $raw;
        }

        return (string) mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
    }

    /** @return array{0: list<list<string>>, 1: string} */
    private function readDelimited(string $text): array
    {
        $delimiter = $this->sniffDelimiter($text);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $text);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            if ($row === [null]) {
                $rows[] = []; // keep blank lines so line numbers match the sheet
                continue;
            }
            $rows[] = $this->cleanRow($row);
            if (count($rows) > self::MAX_ROWS + 20) {
                fclose($stream);
                throw new StockImportException('err_too_many_rows', ['max' => self::MAX_ROWS]);
            }
        }
        fclose($stream);

        return [$this->trimGrid($rows), $delimiter];
    }

    /** The candidate that splits the first lines into the most, and the most consistent, columns. */
    public function sniffDelimiter(string $text): string
    {
        $lines = array_slice(array_filter(preg_split('/\r\n|\n|\r/', substr($text, 0, 20000)), fn ($l) => trim($l) !== ''), 0, 10);
        $best  = ',';
        $score = 0;

        foreach ([';', ',', "\t", '|'] as $candidate) {
            $counts = array_map(fn ($l) => count(str_getcsv($l, $candidate, '"', '')), $lines);
            if ($counts === [] || max($counts) < 2) {
                continue;
            }
            $values = array_count_values($counts);
            arsort($values);
            $mode = (int) array_key_first($values);
            $s    = $mode * 10 + reset($values);
            if ($mode > 1 && $s > $score) {
                $score = $s;
                $best  = $candidate;
            }
        }

        return $best;
    }

    /** @return list<list<string>> */
    private function readHtmlTable(string $html): array
    {
        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $rows = [];
        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $row = [];
            foreach ($tr->childNodes as $cell) {
                if (in_array(strtolower($cell->nodeName), ['td', 'th'], true)) {
                    $row[] = (string) $cell->textContent;
                }
            }
            $rows[] = $this->cleanRow($row);
            if (count($rows) > self::MAX_ROWS + 20) {
                throw new StockImportException('err_too_many_rows', ['max' => self::MAX_ROWS]);
            }
        }

        return $this->trimGrid($rows);
    }

    // ── Zip containers ────────────────────────────────────────────────────

    /** @return array{format: string, rows: list<list<string>>} */
    private function readZip(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new StockImportException('err_unreadable');
        }

        try {
            if ($zip->locateName('xl/workbook.xml') !== false) {
                return ['format' => 'xlsx', 'rows' => $this->readXlsx($zip)];
            }
            if ($zip->locateName('mimetype') !== false
                && str_contains((string) $zip->getFromName('mimetype'), 'opendocument.spreadsheet')) {
                return ['format' => 'ods', 'rows' => $this->readOds($zip)];
            }
            if ($zip->locateName('word/document.xml') !== false
                || str_contains((string) $zip->getFromName('mimetype'), 'opendocument.text')) {
                throw new StockImportException('err_word');
            }

            throw new StockImportException('err_unreadable');
        } finally {
            $zip->close();
        }
    }

    private function member(ZipArchive $zip, string $name): ?string
    {
        $stat = $zip->statName($name);
        if ($stat === false) {
            return null;
        }
        if ($stat['size'] > self::MAX_XML_BYTES) {
            throw new StockImportException('err_too_large');
        }

        $xml = $zip->getFromName($name);

        return $xml === false ? null : $xml;
    }

    private function xml(string $xml): XMLReader
    {
        // A spreadsheet never needs a DTD; refusing one closes XXE and entity bombs.
        if (preg_match('/<!DOCTYPE/i', substr($xml, 0, 4096))) {
            throw new StockImportException('err_unreadable');
        }
        $reader = XMLReader::XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        if (! $reader) {
            throw new StockImportException('err_unreadable');
        }

        return $reader;
    }

    /** @return list<list<string>> */
    private function readXlsx(ZipArchive $zip): array
    {
        // Shared strings.
        $shared = [];
        if (($xml = $this->member($zip, 'xl/sharedStrings.xml')) !== null) {
            $r = $this->xml($xml);
            $current = null;
            while ($r->read()) {
                if ($r->nodeType === XMLReader::ELEMENT && $r->localName === 'si') {
                    $current = '';
                    if ($r->isEmptyElement) {
                        $shared[] = '';
                        $current = null;
                    }
                } elseif ($r->nodeType === XMLReader::ELEMENT && $r->localName === 't' && $current !== null) {
                    $current .= $r->readString();
                } elseif ($r->nodeType === XMLReader::ELEMENT && $r->localName === 'rPh') {
                    $r->next(); // phonetic runs are not cell text
                } elseif ($r->nodeType === XMLReader::END_ELEMENT && $r->localName === 'si') {
                    $shared[] = (string) $current;
                    $current = null;
                }
            }
            $r->close();
        }

        $sheet = $this->firstSheetPath($zip);
        $xml = $this->member($zip, $sheet);
        if ($xml === null) {
            throw new StockImportException('err_unreadable');
        }

        $rows = [];
        $r = $this->xml($xml);
        $rowIndex = -1;
        $cellCol = 0;
        $cellType = null;
        $value = null;
        $inline = '';
        $nextCol = 0;

        while ($r->read()) {
            if ($r->nodeType === XMLReader::ELEMENT) {
                switch ($r->localName) {
                    case 'row':
                        $n = (int) $r->getAttribute('r');
                        $rowIndex = $n > 0 ? $n - 1 : $rowIndex + 1;
                        if ($rowIndex >= self::MAX_ROWS + 20) {
                            throw new StockImportException('err_too_many_rows', ['max' => self::MAX_ROWS]);
                        }
                        $rows[$rowIndex] = [];
                        $nextCol = 0;
                        break;
                    case 'c':
                        $ref = (string) $r->getAttribute('r');
                        $cellCol = $ref !== '' ? self::columnIndex($ref) : $nextCol;
                        $nextCol = $cellCol + 1;
                        $cellType = $r->getAttribute('t');
                        $value = null;
                        $inline = '';
                        if ($r->isEmptyElement) {
                            $cellType = null;
                        }
                        break;
                    case 'v':
                        $value = $r->readString();
                        break;
                    case 't':
                        if ($cellType === 'inlineStr') {
                            $inline .= $r->readString();
                        }
                        break;
                }
            } elseif ($r->nodeType === XMLReader::END_ELEMENT && $r->localName === 'c') {
                if ($cellCol < self::MAX_COLS && $rowIndex >= 0) {
                    $text = match ($cellType) {
                        's'         => $shared[(int) $value] ?? '',
                        'inlineStr' => $inline,
                        'b'         => $value === '1' ? 'TRUE' : 'FALSE',
                        default     => (string) $value,
                    };
                    $rows[$rowIndex][$cellCol] = $text;
                }
            }
        }
        $r->close();

        return $this->trimGrid($this->densify($rows));
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = (string) $this->member($zip, 'xl/workbook.xml');
        $rels     = (string) $this->member($zip, 'xl/_rels/workbook.xml.rels');

        if (preg_match('/<(?:\w+:)?sheet[^>]*r:id="([^"]+)"/', $workbook, $m)) {
            foreach (preg_split('/(?=<Relationship)/', $rels) as $rel) {
                if (str_contains($rel, 'Id="' . $m[1] . '"') && preg_match('/Target="([^"]+)"/', $rel, $t)) {
                    $target = ltrim($t[1], '/');

                    return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /** "AB12" → 27 (zero-based column). */
    public static function columnIndex(string $ref): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return max(0, $n - 1);
    }

    /** @return list<list<string>> */
    private function readOds(ZipArchive $zip): array
    {
        $xml = $this->member($zip, 'content.xml');
        if ($xml === null) {
            throw new StockImportException('err_unreadable');
        }

        $r = $this->xml($xml);
        $rows = [];
        $row = null;
        $rowRepeat = 1;
        $tables = 0;

        while ($r->read()) {
            if ($r->nodeType === XMLReader::ELEMENT && $r->localName === 'table' && $r->prefix === 'table') {
                $tables++;
                if ($tables > 1) {
                    break; // first sheet only
                }
            } elseif ($r->nodeType === XMLReader::ELEMENT && $r->localName === 'table-row') {
                $row = [];
                $rowRepeat = max(1, (int) ($r->getAttribute('table:number-rows-repeated') ?: 1));
                if ($r->isEmptyElement) {
                    $row = null;
                }
            } elseif ($r->nodeType === XMLReader::ELEMENT && in_array($r->localName, ['table-cell', 'covered-table-cell'], true) && $row !== null) {
                $repeat = max(1, (int) ($r->getAttribute('table:number-columns-repeated') ?: 1));
                $type   = $r->getAttribute('office:value-type');
                $text   = match ($type) {
                    'date'                         => substr((string) $r->getAttribute('office:date-value'), 0, 10),
                    'float', 'percentage', 'currency' => (string) $r->getAttribute('office:value'),
                    default                        => null,
                };
                if ($text === null) {
                    $text = $r->isEmptyElement ? '' : $this->odsCellText($r);
                } elseif (! $r->isEmptyElement) {
                    $r->next();
                }
                for ($i = 0; $i < $repeat && count($row) < self::MAX_COLS; $i++) {
                    $row[] = $text;
                }
            } elseif ($r->nodeType === XMLReader::END_ELEMENT && $r->localName === 'table-row' && $row !== null) {
                $empty = implode('', $row) === '';
                // Trailing blank rows are stored as one row repeated ~a million times.
                $times = $empty ? min($rowRepeat, 1) : min($rowRepeat, self::MAX_ROWS);
                for ($i = 0; $i < $times; $i++) {
                    $rows[] = $this->cleanRow($row);
                }
                if (count($rows) > self::MAX_ROWS + 20) {
                    throw new StockImportException('err_too_many_rows', ['max' => self::MAX_ROWS]);
                }
                $row = null;
            }
        }
        $r->close();

        return $this->trimGrid($rows);
    }

    private function odsCellText(XMLReader $r): string
    {
        $depth = $r->depth;
        $parts = [];
        while ($r->read() && ! ($r->nodeType === XMLReader::END_ELEMENT && $r->depth === $depth)) {
            if ($r->nodeType === XMLReader::ELEMENT && $r->localName === 'p') {
                $parts[] = $r->readString();
            }
        }

        return implode("\n", $parts);
    }

    // ── Shared ────────────────────────────────────────────────────────────

    /** @param array<int, mixed> $row @return list<string> */
    private function cleanRow(array $row): array
    {
        $out = [];
        foreach (array_slice($row, 0, self::MAX_COLS) as $cell) {
            $cell = str_replace("\u{00A0}", ' ', (string) $cell);
            $out[] = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $cell));
        }

        return $out;
    }

    /** @param array<int, array<int, string>> $rows @return list<list<string>> */
    private function densify(array $rows): array
    {
        if ($rows === []) {
            return [];
        }
        $out = [];
        $max = max(array_keys($rows));
        for ($i = 0; $i <= $max; $i++) {
            $row = $rows[$i] ?? [];
            $dense = [];
            $width = $row === [] ? 0 : max(array_keys($row)) + 1;
            for ($c = 0; $c < $width; $c++) {
                $dense[] = $row[$c] ?? '';
            }
            $out[] = $this->cleanRow($dense);
        }

        return $out;
    }

    /** Drop trailing empty rows and trailing empty cells. @param list<list<string>> $rows @return list<list<string>> */
    private function trimGrid(array $rows): array
    {
        foreach ($rows as $i => $row) {
            while ($row !== [] && end($row) === '') {
                array_pop($row);
            }
            $rows[$i] = $row;
        }
        while ($rows !== [] && end($rows) === []) {
            array_pop($rows);
        }

        if ($rows === []) {
            throw new StockImportException('err_empty_file');
        }

        return array_values($rows);
    }
}
