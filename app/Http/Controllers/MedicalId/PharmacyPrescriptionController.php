<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\Pharmacy\PharmacyDeskException;
use App\Services\Pharmacy\PharmacyDeskService;
use App\Services\Pharmacy\PharmacyDispensingService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Prescription detail and the dispensing register of the web pharmacy desk.
 *
 * The facility is read from PortalContextService only — never a header, a
 * body or a query string — and the scope rule is PharmacyDeskService::find:
 * routed elsewhere is the same 404 as nonexistent.
 *
 * Writes stay on PharmacyPortalController::dispense; this controller reads.
 */
class PharmacyPrescriptionController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly PharmacyDeskService $desk,
        private readonly PharmacyDispensingService $dispensing,
    ) {
    }

    public function show(string $id)
    {
        $facility = $this->facility();

        try {
            $detail = $this->dispensing->detail($facility, $id);
        } catch (PharmacyDeskException $e) {
            abort($e->status === 404 ? 404 : 403);
        }

        return view('portals.pharmacy.prescriptions.show', $detail + ['facility' => $facility]);
    }

    public function dispensing(Request $req)
    {
        $facility = $this->facility();
        $filters  = $this->filters($req);

        return view('portals.pharmacy.dispensing.index', [
            'entries'     => $this->dispensing->register((string) $facility->id, $filters)->withQueryString(),
            'dispensers'  => $this->dispensing->dispensers((string) $facility->id),
            'filters'     => $filters,
            'service'     => $this->dispensing,
        ]);
    }

    public function dispensingExport(Request $req): StreamedResponse
    {
        $facility = $this->facility();
        $filters  = $this->filters($req);
        $name     = 'dispensing-register-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($facility, $filters) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                __('pharmacy_desk.csv_dispensed_at'),
                __('pharmacy_desk.csv_reference'),
                __('pharmacy_desk.csv_patient'),
                __('pharmacy_desk.csv_health_id'),
                __('pharmacy_desk.csv_drug'),
                __('pharmacy_desk.csv_dose'),
                __('pharmacy_desk.csv_quantity'),
                __('pharmacy_desk.csv_pharmacist'),
                __('pharmacy_desk.csv_controlled'),
                __('pharmacy_desk.csv_notes'),
            ]);
            foreach ($this->dispensing->registerRows((string) $facility->id, $filters) as $row) {
                fputcsv($out, \App\Support\CsvSafe::row($row));
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{from: ?string, to: ?string, pharmacist: ?string, controlled: bool} */
    private function filters(Request $req): array
    {
        $data = $req->validate([
            'from'       => ['nullable', 'date'],
            'to'         => ['nullable', 'date'],
            'pharmacist' => ['nullable', 'uuid'],
            'controlled' => ['nullable', Rule::in(['1', '0', 'on', 'true', 'false'])],
        ]);

        return [
            'from'       => $data['from'] ?? null,
            'to'         => $data['to'] ?? null,
            'pharmacist' => $data['pharmacist'] ?? null,
            'controlled' => $req->boolean('controlled'),
        ];
    }

    private function facility(): Facility
    {
        $facility = $this->desk->facility($this->context->facilityId());

        if (! $facility) {
            abort(403, __('pharmacy_desk.no_facility_context'));
        }

        return $facility;
    }
}
