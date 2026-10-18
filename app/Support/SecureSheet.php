<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;

/**
 * Renders a view on the OpesCare Secure sheet (pdf/layouts/secure) and
 * stamps "Page N of M" into the footer.
 *
 * dompdf does not resolve CSS `counter(pages)` inside fixed elements (it
 * prints 0), so the total is written onto each page's canvas after layout.
 * The layout leaves the page cell empty when `$pageStamp` is true, which this
 * helper passes. A caller that renders the layout itself still gets "Page N"
 * from CSS. Spec: docs/specs/documents/00-secure-sheet.md.
 */
class SecureSheet
{
    /** Render to PDF bytes. */
    public static function render(string $view, array $data): string
    {
        $pdf = Pdf::loadView($view, $data + ['pageStamp' => true])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'             => 'DejaVu Sans',
                'isHtml5ParserEnabled'    => true,
                'isFontSubsettingEnabled' => true,
                'dpi'                     => 96,
            ], true);

        return self::output($pdf, ! empty($data['verifyQr']));
    }

    /** Stamp page totals onto an already-configured sheet PDF and return its bytes. */
    public static function output(DomPdfWrapper $pdf, bool $hasQr): string
    {
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas  = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font    = $metrics->getFont('DejaVu Sans', 'bold');
        $size    = 7.0; // pt — the footer floor
        $mm      = 72 / 25.4;

        // Right edge of the page cell: 14mm inset + 182mm table, minus the
        // 26mm QR cell when the sheet carries one. Baseline at the footer's
        // first text line (272mm page height offset + 4.5mm table inset).
        $right = (14 + 182 - ($hasQr ? 26 : 0)) * $mm;
        $y     = (272 + 4.5) * $mm;
        $label = __('export.page') . ' {PAGE_NUM} ' . __('doc_ex_sheet.of') . ' {PAGE_COUNT}';

        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($font, $size, $right, $y, $label) {
            $text  = str_replace(['{PAGE_NUM}', '{PAGE_COUNT}'], [$pageNumber, $pageCount], $label);
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $canvas->text($right - $width, $y, $text, $font, $size, [0.059, 0.298, 0.506]);
        });

        return $dompdf->output();
    }
}
