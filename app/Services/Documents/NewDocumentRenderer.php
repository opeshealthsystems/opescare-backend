<?php

namespace App\Services\Documents;

use App\Models\Patient;
use App\Services\Patient\DocumentVerificationService;
use App\Support\QrSvg;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the documents specified in docs/specs/documents/20-32 on the
 * OpesCare Secure sheet: verification row first (the QR must be on the page),
 * render, seal the sha256 of the exact bytes, stream with no-store.
 *
 * Nothing is ever written to disk. A document without a patient (a blood
 * request filed for nobody yet) is rendered without a verification row,
 * because verification rows are per patient.
 */
class NewDocumentRenderer
{
    public function __construct(private readonly DocumentVerificationService $verifications)
    {
    }

    /** @param array<string, mixed> $data */
    public function secure(string $view, ?Patient $patient, string $documentType, array $data, string $kind, ?string $issuedBy = null): Response
    {
        $issuedAt     = Carbon::now();
        $verification = null;
        $verifyQr     = null;

        if ($patient) {
            $issued       = $this->verifications->issue($patient, $documentType, $issuedBy);
            $verification = $issued['record'];
            $verifyQr     = QrSvg::pngDataUri($issued['verify_url']);
        }

        $bytes = Pdf::loadView($view, $data + compact('patient', 'issuedAt', 'verification', 'verifyQr'))
            ->setPaper('a4', 'portrait')
            ->setOptions($this->options(), true)
            ->output();

        if ($verification) {
            $this->verifications->seal($verification, $bytes);
        }

        return $this->stream($bytes, $this->filename($kind, $patient, $verification?->reference ?? ($data['reference'] ?? null)));
    }

    /**
     * A plain document on custom paper (labels): no sheet, no verification row.
     *
     * @param array<string, mixed> $data
     * @param array{0: float, 1: float, 2: float, 3: float} $paper points
     */
    public function plain(string $view, array $data, array $paper, string $kind, ?Patient $patient = null): Response
    {
        $bytes = Pdf::loadView($view, $data)
            ->setPaper($paper)
            ->setOptions($this->options(), true)
            ->output();

        return $this->stream($bytes, $this->filename($kind, $patient, $data['reference'] ?? null));
    }

    /** Whole francs, space-grouped: 12 500 FCFA. XAF has no minor unit. */
    public static function xaf(mixed $amount): string
    {
        return number_format((int) round((float) $amount), 0, ',', ' ') . ' FCFA';
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        // `true` on setOptions() merges — without it the chroot is lost.
        return [
            'defaultFont'             => 'DejaVu Sans',
            'isHtml5ParserEnabled'    => true,
            'isFontSubsettingEnabled' => true,
            'dpi'                     => 96,
        ];
    }

    private function filename(string $kind, ?Patient $patient, ?string $reference): string
    {
        $parts = array_filter([$kind, $patient?->health_id, $reference]);

        return Str::slug(implode('-', $parts)) . '.pdf';
    }

    private function stream(string $bytes, string $filename): Response
    {
        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, private',
            'Pragma'              => 'no-cache',
        ]);
    }
}
