<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\Pharmacy\PharmacyReportService;
use App\Services\Portal\PortalContextService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The pharmacy reports page, its CSV / PDF exports, and the monthly
 * controlled-substance register. Every number comes from
 * PharmacyReportService; this controller resolves the facility, validates
 * the window and chooses the shape of the answer.
 *
 * The facility is read from PortalContextService only — never a header,
 * never the query string. The role gate is `capability:pharmacy_stock` on
 * the routes. The PDF is rendered on the OpesCare Secure sheet
 * (pdf/layouts/secure) at request time and never stored.
 */
class PharmacyReportsController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly PharmacyReportService $reports,
    ) {
    }

    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();
        $params     = $this->params($request);

        $tables = [];
        foreach (PharmacyReportService::REPORTS as $key) {
            $tables[$key] = $this->reports->report($key, $facilityId, $params);
        }

        return view('portals.pharmacy.reports.index', [
            'tables'      => $tables,
            'days'        => $params['days'],
            'from'        => $params['from']->toDateString(),
            'to'          => $params['to']->toDateString(),
            'windows'     => PharmacyReportService::EXPIRY_WINDOWS,
            'previewRows' => PharmacyReportService::PREVIEW_ROWS,
            'query'       => $this->queryString($params),
        ]);
    }

    public function csv(Request $request, string $report): StreamedResponse
    {
        $this->assertReport($report);

        $facilityId = $this->facilityId();
        $params     = $this->params($request);
        $table      = $this->reports->report($report, $facilityId, $params);

        return $this->streamCsv($table['headers'], $table['rows'], $this->filename($facilityId, $report, $params, 'csv'));
    }

    /** Rows a report PDF prints before pointing at the CSV for the rest. */
    public const PDF_ROW_CAP = 500;

    public function pdf(Request $request, string $report): Response
    {
        $this->assertReport($report);

        $facilityId = $this->facilityId();
        $params     = $this->params($request);
        $table      = $this->reports->report($report, $facilityId, $params);

        // dompdf lays a table out in memory, page by page; past a few hundred
        // rows the render outgrows the request time limit. The PDF carries the
        // first PDF_ROW_CAP rows and says so; the CSV always carries them all.
        $totalRows = count($table['rows']);
        if ($totalRows > self::PDF_ROW_CAP) {
            $table['rows'] = array_slice($table['rows'], 0, self::PDF_ROW_CAP);
        }

        return $this->renderPdf('portals.pharmacy.reports.pdf', [
            'table'     => $table,
            'totalRows' => $totalRows,
            'rowCap'    => self::PDF_ROW_CAP,
            'report'   => $report,
            'facility' => Facility::withoutGlobalScopes()->find($facilityId),
            'params'   => [
                'days' => $params['days'],
                'from' => $params['from']->toDateString(),
                'to'   => $params['to']->toDateString(),
            ],
        ], $this->filename($facilityId, $report, $params, 'pdf'));
    }

    /** The month's controlled-substance register: ?month=YYYY-MM, ?format=csv|pdf (pdf by default). */
    public function controlledRegister(Request $request): Response
    {
        $facilityId = $this->facilityId();

        $data = $request->validate([
            'month'  => ['nullable', 'date_format:Y-m'],
            'format' => ['nullable', 'in:csv,pdf'],
        ]);

        $month    = isset($data['month']) ? Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth() : Carbon::today()->startOfMonth();
        $register = $this->reports->controlledRegister($facilityId, $month);
        $facility = Facility::withoutGlobalScopes()->find($facilityId);
        $slug     = Str::slug((string) ($facility?->name ?? 'pharmacy')) ?: 'pharmacy';
        $filename = sprintf('%s_controlled-register_%s', $slug, $register['month']);

        if (($data['format'] ?? 'pdf') === 'csv') {
            return $this->streamCsv($register['headers'], $register['rows'], $filename . '.csv');
        }

        return $this->renderPdf('portals.pharmacy.reports.controlled_register_pdf', [
            'register' => $register,
            'facility' => $facility,
        ], $filename . '.pdf');
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function assertReport(string $report): void
    {
        abort_unless(in_array($report, PharmacyReportService::REPORTS, true), 404);
    }

    /** @return array{days: int, from: Carbon, to: Carbon} */
    private function params(Request $request): array
    {
        $data = $request->validate([
            'days' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d'],
        ]);

        $to   = isset($data['to']) ? Carbon::parse($data['to']) : Carbon::today();
        $from = isset($data['from']) ? Carbon::parse($data['from']) : $to->copy()->subDays(29);

        return [
            'days' => PharmacyReportService::window($data['days'] ?? null),
            'from' => $from,
            'to'   => $to,
        ];
    }

    /** @param array{days: int, from: Carbon, to: Carbon} $params */
    private function queryString(array $params): array
    {
        return [
            'days' => $params['days'],
            'from' => $params['from']->toDateString(),
            'to'   => $params['to']->toDateString(),
        ];
    }

    /** @param array{days: int, from: Carbon, to: Carbon} $params */
    private function filename(string $facilityId, string $report, array $params, string $ext): string
    {
        $facility = Facility::withoutGlobalScopes()->find($facilityId);
        $slug     = Str::slug((string) ($facility?->name ?? 'pharmacy')) ?: 'pharmacy';

        $suffix = match ($report) {
            'expiry'     => $params['days'] . 'd',
            'dispensing' => $params['from']->toDateString() . '_' . $params['to']->toDateString(),
            default      => Carbon::today()->toDateString(),
        };

        return sprintf('%s_%s_%s.%s', $slug, $report, $suffix, $ext);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    private function streamCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, \App\Support\CsvSafe::row(array_map(fn ($v) => $v ?? '', $row)));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function renderPdf(string $view, array $data, string $filename): Response
    {
        // `true` merges with the package defaults; without it the chroot falls
        // back to vendor/dompdf and the sheet art under public/ is refused.
        // A facility report carries the same /verify/doc QR as a patient
        // document: the row is minted before render (the QR is on the page),
        // the fingerprint of the bytes is sealed after.
        $verifications = app(\App\Services\Patient\DocumentVerificationService::class);
        $type          = $view === 'portals.pharmacy.reports.controlled_register_pdf' ? 'controlled_register' : 'pharmacy_report';
        $issued        = $verifications->issueForFacility($this->facilityId(), $type, auth()->id() ? (string) auth()->id() : null);

        $pdf = Pdf::loadView($view, $data + [
            'issuedAt'     => Carbon::now(),
            'patient'      => null,
            'verification' => $issued['record'],
            'verifyQr'     => \App\Support\QrSvg::pngDataUri($issued['verify_url']),
            'generatedBy'  => auth()->user()?->name,
            'pageStamp'    => true,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'             => 'DejaVu Sans',
                'isHtml5ParserEnabled'    => true,
                'isFontSubsettingEnabled' => true,
                'dpi'                     => 96,
            ], true);

        $bytes = \App\Support\SecureSheet::output($pdf, true);
        $verifications->seal($issued['record'], $bytes);

        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, private',
        ]);
    }

    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('pharmacy_reports.no_facility_context'));
        }

        return $facilityId;
    }
}
