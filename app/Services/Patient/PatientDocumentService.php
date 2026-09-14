<?php

namespace App\Services\Patient;

use App\Models\Admission;
use App\Models\ImmunizationRecord;
use App\Models\LabOrder;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\Prescription;
use App\Support\QrSvg;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A patient's documents, rendered at the moment they ask for them.
 *
 * NOTHING HERE IS EVER SAVED. The PDF is built in memory, streamed to the
 * person who asked for it, and dropped. That is deliberate and it is the whole
 * design:
 *
 *   - A file on a disk is a copy of a medical record that outlives the reason
 *     it was made, has to be guarded, backed up, and eventually deleted, and is
 *     stale the moment the record moves on. A document generated on demand is
 *     always current, because it is the record.
 *   - The old download endpoints read `official_documents.pdf_path`, and
 *     nothing in this codebase has ever written one — so every "Download"
 *     button a patient pressed returned "no report file for this order". The
 *     fix is not to start writing files; it is to render on the way out.
 *
 * WHAT IS PERSISTED is the verification row, and only that: a document number,
 * the hash of the bytes handed over, and who asked. That is what makes the QR
 * on the sheet resolve at /verify/doc/{token}, so a clinic holding a printout
 * can check it against the register. The record of an issue is not a copy of
 * the record.
 *
 * AUTHORISATION IS NOT DONE HERE. Every caller must already have established
 * that this patient is the one entitled to the document — the portal resolves
 * the viewing patient, the mobile routes carry a signed URL naming one patient
 * and one record. This service refuses to render anything that does not belong
 * to the patient it was handed, as a second line rather than the first.
 */
class PatientDocumentService
{
    public const LABORATORY_REPORT  = 'laboratory_report';
    public const PRESCRIPTION       = 'prescription';
    public const IMMUNISATION       = 'immunisation_record';
    public const DISCHARGE_SUMMARY  = 'discharge_summary';
    /** A certificate is the document itself, not a print of a clinical record. */
    public const CERTIFICATE        = 'certificate';

    /** Document type → the Blade that draws it on the secure sheet. */
    private const VIEWS = [
        self::LABORATORY_REPORT => 'documents.laboratory-report',
        self::PRESCRIPTION      => 'documents.prescription',
        self::IMMUNISATION      => 'documents.immunisation-record',
        self::DISCHARGE_SUMMARY => 'documents.discharge-summary',
        self::CERTIFICATE       => 'documents.certificate',
    ];

    public function __construct(private readonly DocumentVerificationService $verifications)
    {
    }

    public static function types(): array
    {
        return array_keys(self::VIEWS);
    }

    /**
     * Stream one document, generated now.
     *
     * `$recordId` names the lab order, prescription or admission the document
     * is about; the whole-record documents ignore it.
     */
    public function download(
        Patient $patient,
        string $type,
        ?string $recordId = null,
        ?string $issuedByUserId = null
    ): StreamedResponse {
        [$bytes, $filename] = $this->render($patient, $type, $recordId, $issuedByUserId);

        return response()->streamDownload(
            function () use ($bytes) {
                echo $bytes;
            },
            $filename,
            [
                'Content-Type'  => 'application/pdf',
                // A medical document must not sit in a shared cache, and the
                // next request must build it from the record as it is then.
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
                'Pragma'        => 'no-cache',
            ]
        );
    }

    /**
     * The PDF bytes and the name to hand them over under.
     *
     * @return array{0: string, 1: string}
     */
    public function render(
        Patient $patient,
        string $type,
        ?string $recordId = null,
        ?string $issuedByUserId = null
    ): array {
        $view = self::VIEWS[$type] ?? null;

        abort_if($view === null, 404, 'Unknown document type.');

        $data = $this->gather($patient, $type, $recordId);

        // The sheet carries a QR resolving to /verify/doc/{token}, so the
        // verification row has to exist BEFORE the page is rendered; the
        // fingerprint of the bytes is sealed onto it once they are final.
        $issuedAt     = Carbon::now();
        $issued       = $this->verifications->issue($patient, $type, $issuedByUserId);
        $verification = $issued['record'];
        $verifyQr     = QrSvg::pngDataUri($issued['verify_url']);

        // `true` merges with the package defaults. Without it setOptions()
        // replaces the whole Options object, the chroot falls back to
        // vendor/dompdf and every sheet image under public/ is refused.
        $pdf = Pdf::loadView($view, $data + compact('patient', 'issuedAt', 'verification', 'verifyQr'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'             => 'DejaVu Sans',
                'isHtml5ParserEnabled'    => true,
                'isFontSubsettingEnabled' => true,
                'dpi'                     => 96,
            ], true);

        $bytes = $pdf->output();
        $this->verifications->seal($verification, $bytes);

        return [$bytes, $this->filename($patient, $type, $verification->reference ?? null)];
    }

    /**
     * Every document this patient could ask for right now.
     *
     * Built by looking at the record rather than at a folder of files: a
     * released lab panel means a laboratory report exists, a prescription means
     * a prescription sheet exists. The list and the download therefore cannot
     * disagree — there is no case where the page offers a document that turns
     * out not to be there, which is exactly what the stored-file design used to
     * do on every row.
     *
     * @return list<array{type:string, record:string|null, title:string, reference:string|null, date:\Carbon\Carbon|null}>
     */
    public function available(Patient $patient): array
    {
        $out = [];

        // A prescription that already has an issued document is offered once,
        // as that document, not twice.
        $covered = $this->coveredRecordIds($patient);

        foreach (
            LabOrder::where('patient_id', $patient->id)
                // Every released spelling, INCLUDING `verified` — the web desk's
                // second-person validation. Before this read the model's list, a
                // validated panel vanished from the patient's documents.
                ->whereIn('status', [...LabOrder::RELEASED_STATUSES, 'reported'])
                ->orderByDesc('resulted_at')
                ->get() as $order
        ) {
            $out[] = [
                'type'      => self::LABORATORY_REPORT,
                'record'    => (string) $order->id,
                'title'     => __('document.lab_title') . ' — ' . ($order->test_name ?: ''),
                'reference' => $order->test_code,
                'date'      => $order->resulted_at ?? $order->ordered_at,
            ];
        }

        foreach (
            Prescription::where('patient_id', $patient->id)
                ->orderByDesc('prescribed_at')
                ->get() as $prescription
        ) {
            $out[] = [
                'type'      => self::PRESCRIPTION,
                'record'    => (string) $prescription->id,
                'title'     => __('document.rx_title'),
                'reference' => $prescription->reference,
                'date'      => $prescription->prescribed_at,
            ];
        }

        foreach (
            Admission::where('patient_id', $patient->id)
                ->whereNotNull('discharged_at')
                ->orderByDesc('discharged_at')
                ->get() as $admission
        ) {
            $out[] = [
                'type'      => self::DISCHARGE_SUMMARY,
                'record'    => (string) $admission->id,
                'title'     => __('document.dis_title'),
                'reference' => null,
                'date'      => $admission->discharged_at,
            ];
        }

        // One record covering every dose, so it is offered only when there is
        // at least one to carry.
        if (ImmunizationRecord::where('patient_id', $patient->id)->exists()) {
            $out[] = [
                'type'      => self::IMMUNISATION,
                'record'    => null,
                'title'     => __('document.imm_title'),
                'reference' => null,
                'date'      => ImmunizationRecord::where('patient_id', $patient->id)->max('administered_at'),
            ];
        }

        $out = array_values(array_filter(
            $out,
            fn (array $item) => ! isset($covered[$item['type'] . ':' . ($item['record'] ?? '')])
        ));

        usort($out, fn ($a, $b) => (string) ($b['date'] ?? '') <=> (string) ($a['date'] ?? ''));

        return $out;
    }

    /**
     * The record an already-issued document was made from, if we can render it.
     *
     * `official_documents` rows name their source in `payload_json` — a
     * prescription id, a lab order id — but carry no file, because nothing in
     * this codebase writes one. Rather than list them with a Download button
     * that fails, they are rendered from the same record they were issued from.
     *
     * @return array{0: string, 1: string|null}|null  [type, record id]
     */
    public function renderableFor(OfficialDocument $document): ?array
    {
        $payload = $document->payload_json;

        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        $payload = is_array($payload) ? $payload : [];

        $type = strtolower((string) $document->document_type);

        $prescription = $payload['prescription_id'] ?? null;
        $labOrder     = $payload['lab_order_id'] ?? ($payload['order_id'] ?? null);
        $admission    = $payload['admission_id'] ?? null;

        return match (true) {
            in_array($type, ['rx', 'prescription'], true) && $prescription
                => [self::PRESCRIPTION, (string) $prescription],
            in_array($type, ['lab_report', 'laboratory_report'], true) && $labOrder
                => [self::LABORATORY_REPORT, (string) $labOrder],
            $type === 'discharge_summary' && $admission
                => [self::DISCHARGE_SUMMARY, (string) $admission],
            in_array($type, ['vaccination_card', 'immunisation_record', 'immunization_record'], true)
                => [self::IMMUNISATION, null],
            // Everything else that was formally issued is printed from
            // its own row — a certificate is the document, not a view
            // of a record kept somewhere else.
            default => [self::CERTIFICATE, (string) $document->id],
        };
    }

    /**
     * The record ids already covered by an issued document, so the same
     * prescription is not offered twice — once as the issued sheet and once as
     * the on-demand one.
     *
     * @return array<string,true>
     */
    public function coveredRecordIds(Patient $patient): array
    {
        $covered = [];

        foreach (
            OfficialDocument::where('patient_id', $patient->id)->get() as $document
        ) {
            $renderable = $this->renderableFor($document);

            if ($renderable !== null && $renderable[0] !== self::CERTIFICATE && $renderable[1] !== null) {
                $covered[$renderable[0] . ':' . $renderable[1]] = true;
            }
        }

        return $covered;
    }

    // ── What each document reads ─────────────────────────────────────────

    /**
     * The records behind one document.
     *
     * Every lookup is scoped to the patient the caller resolved. A record id
     * that belongs to somebody else is a 404 and not a 403: telling a stranger
     * that an order exists but is not theirs is already telling them something.
     */
    private function gather(Patient $patient, string $type, ?string $recordId): array
    {
        return match ($type) {
            self::LABORATORY_REPORT => ['order' => $this->labOrder($patient, $recordId)],
            self::PRESCRIPTION      => ['prescription' => $this->prescription($patient, $recordId)],
            self::DISCHARGE_SUMMARY => ['admission' => $this->admission($patient, $recordId)],
            self::IMMUNISATION      => ['doses' => $this->doses($patient)],
            self::CERTIFICATE       => $this->certificate($patient, $recordId),
            default                 => [],
        };
    }

    /**
     * One issued certificate, with the name of the facility that issued it.
     *
     * A revoked certificate still renders — and says so on its face. Refusing
     * to print it would leave the holder unable to see what was withdrawn, and
     * the sheet is honest about its state either way.
     */
    private function certificate(Patient $patient, ?string $id): array
    {
        $document = OfficialDocument::where('patient_id', $patient->id)
            ->where('id', $id)
            ->first();

        abort_if($document === null, 404, 'No such certificate.');

        // The issuing facility may be a real (non-demo) row, so the demo
        // isolation scope is lifted — a certificate issued by nobody is worse
        // than one that names its hospital.
        $facilityName = $document->facility_id
            ? (\App\Models\Facility::withoutGlobalScope('isolate_demo')->whereKey($document->facility_id)->value('name')
                ?? \App\Models\CareFacility::withoutGlobalScopes()->whereKey($document->facility_id)->value('facility_name'))
            : null;

        return ['document' => $document, 'facilityName' => $facilityName];
    }

    private function labOrder(Patient $patient, ?string $id): LabOrder
    {
        $order = LabOrder::with(['results', 'facility'])
            ->where('patient_id', $patient->id)
            ->where('id', $id)
            ->first();

        abort_if($order === null, 404, 'No such laboratory report.');

        // A result that has not been released is not a report. Handing one over
        // early puts an unreviewed value in a patient's hands, which is exactly
        // what the release step exists to prevent.
        abort_unless(
            in_array(strtolower((string) $order->status), [...LabOrder::RELEASED_STATUSES, 'reported'], true),
            404,
            'This report has not been released yet.'
        );

        return $order;
    }

    private function prescription(Patient $patient, ?string $id): Prescription
    {
        $prescription = Prescription::with(['items', 'facility'])
            ->where('patient_id', $patient->id)
            ->where('id', $id)
            ->first();

        abort_if($prescription === null, 404, 'No such prescription.');

        return $prescription;
    }

    private function admission(Patient $patient, ?string $id): Admission
    {
        $admission = Admission::where('patient_id', $patient->id)
            ->where('id', $id)
            ->whereNotNull('discharged_at')
            ->first();

        abort_if($admission === null, 404, 'No such discharge summary.');

        return $admission;
    }

    private function doses(Patient $patient)
    {
        return ImmunizationRecord::where('patient_id', $patient->id)
            ->orderBy('administered_at')
            ->get();
    }

    /** A name that says what the file is without naming the patient in it. */
    private function filename(Patient $patient, string $type, ?string $reference): string
    {
        return implode('-', array_filter([
            Str::slug(str_replace('_', '-', $type)),
            $patient->health_id ? Str::slug($patient->health_id) : null,
            $reference ? Str::slug($reference) : Carbon::now()->format('Ymd'),
        ])) . '.pdf';
    }
}
