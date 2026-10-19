<?php

namespace App\Services\Pharmacy\StockImport;

use App\Support\CsvSafe;
use ZipArchive;

/**
 * The stock template a pharmacy downloads, in the current locale: a CSV, or
 * an .xlsx with a "Stock" sheet and an "Instructions" sheet. The .xlsx is
 * written by hand (inline strings, one bold style) — no library needed.
 */
class StockTemplate
{
    /** @return list<string> */
    public function headers(): array
    {
        return array_map(fn ($f) => __('pharmacy_stock_import.col_' . $f), ColumnMapper::FIELDS);
    }

    /** @return list<list<string>> */
    public function examples(): array
    {
        return [
            ['Amoxicillin 500mg Capsule', 'Amoxicillin', '500mg', __('pharmacy_stock_import.example_form_capsule'), __('pharmacy_stock_import.example_pack_16'), '40', '2500', '1800', now()->addYear()->endOfMonth()->format('d/m/Y'), 'AMX-2026-01', '', 'J01CA04'],
            ['Paracetamol 500mg Tablet', 'Paracetamol', '500mg', __('pharmacy_stock_import.example_form_tablet'), __('pharmacy_stock_import.example_pack_20'), '120', '500', '300', now()->addMonths(18)->format('m/Y'), 'PCM-7781', '', 'N02BE01'],
        ];
    }

    /** @return list<string> */
    public function instructions(): array
    {
        return array_map(fn ($i) => __('pharmacy_stock_import.template_instruction_' . $i), range(1, 9));
    }

    public function csv(): string
    {
        $h = fopen('php://temp', 'r+');
        fwrite($h, "\xEF\xBB\xBF");
        fputcsv($h, CsvSafe::row($this->headers()), ',', '"', '');
        foreach ($this->examples() as $row) {
            fputcsv($h, CsvSafe::row($row), ',', '"', '');
        }
        rewind($h);
        $csv = (string) stream_get_contents($h);
        fclose($h);

        return $csv;
    }

    /** Writes the .xlsx to a temp file and returns its path (the caller deletes it). */
    public function xlsx(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'stktpl');
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);

        $stockName = __('pharmacy_stock_import.sheet_stock');
        $helpName  = __('pharmacy_stock_import.sheet_instructions');

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->esc(mb_substr($stockName, 0, 31)) . '" sheetId="1" r:id="rId1"/>'
            . '<sheet name="' . $this->esc(mb_substr($helpName, 0, 31)) . '" sheetId="2" r:id="rId2"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F4C81"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
            . '</styleSheet>');

        $stockRows = [$this->headers(), ...$this->examples()];
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($stockRows, array_fill(0, count(ColumnMapper::FIELDS), 20), true));

        $help = array_map(fn ($line) => [$line], [__('pharmacy_stock_import.template_instructions_title'), ...$this->instructions()]);
        $zip->addFromString('xl/worksheets/sheet2.xml', $this->sheet($help, [120], true));

        $zip->close();

        return $path;
    }

    /** @param list<list<string>> $rows @param list<int> $widths */
    private function sheet(array $rows, array $widths, bool $boldFirst): string
    {
        $cols = '';
        foreach ($widths as $i => $w) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"' . ($i >= 0 ? ' style="2"' : '') . '/>';
        }
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols><sheetData>';
        foreach ($rows as $r => $row) {
            $xml .= '<row r="' . ($r + 1) . '">';
            foreach ($row as $c => $value) {
                $ref   = $this->colName($c) . ($r + 1);
                $style = $boldFirst && $r === 0 ? ' s="1"' : ' s="2"';
                $xml  .= '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . $this->esc((string) CsvSafe::cell($value)) . '</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    private function colName(int $i): string
    {
        $s = '';
        for ($n = $i + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $s = chr(65 + ($n - 1) % 26) . $s;
        }

        return $s;
    }

    private function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
