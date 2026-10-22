<?php

namespace App\Services\Patient;

use App\Models\Patient;
use App\Support\QrSvg;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class MedicalRecordExportService
{
    private string $diskName = 'local';
    private string $exportDir = 'exports/medical-records';

    public function __construct(private readonly DocumentVerificationService $verifications)
    {
    }

    /**
     * Render the medical record PDF — in memory, never stored.
     *
     * Options: include_vitals, include_diagnoses, include_medications,
     *          include_labs, include_immunizations (all default true).
     *
     * "Documents are never stored" (see PatientDocumentService): this used to
     * Storage::put() every export "for audit/debugging". The audit trail is
     * the document_verifications row (reference, sha256 of the exact bytes,
     * who asked) plus the access log; a second copy of the whole record on
     * disk was a liability, not an audit. Spec: docs/specs/documents/08-record-export.md.
     *
     * @return array{0: string, 1: string}  [PDF bytes, download filename]
     */
    public function render(string $patientId, array $options = [], ?string $issuedByUserId = null): array
    {
        $defaults = [
            'include_vitals'        => true,
            'include_diagnoses'     => true,
            'include_medications'   => true,
            'include_labs'          => true,
            'include_immunizations' => true,
        ];
        $options = array_merge($defaults, $options);

        $patient = Patient::with([
            'allergies',
            'diagnoses'     => fn ($q) => $q->where('status', 'active'),
            // OPEN_STATUSES, not 'active' alone. A script the patient has only
            // half collected is still a medicine they are on; dropping it from
            // their own record export would hide a live prescription.
            'prescriptions' => fn ($q) => $q->whereIn('status', \App\Models\Prescription::OPEN_STATUSES)->orderByDesc('prescribed_at'),
            'prescriptions.items',
            'prescriptions.facility',
            'vitals'        => fn ($q) => $q->orderByDesc('recorded_at')->limit(3),
            'labResults'    => fn ($q) => $q->orderByDesc('created_at')->limit(10),
            'immunizations',
        ])->findOrFail($patientId);

        // The record sits on the OpesCare Secure sheet (pdf/layouts/secure):
        // background art, seal, header band and footer repeat on every page.
        // Every sheet carries a QR code that resolves to /verify/doc/{token},
        // so the verification row has to exist BEFORE the page is rendered;
        // the file's fingerprint is sealed onto it once the bytes are final.
        $issuedAt     = Carbon::now();
        $issued       = $this->verifications->issue($patient, DocumentVerificationService::TYPE_MEDICAL_RECORD, $issuedByUserId);
        $verification = $issued['record'];
        $verifyQr     = QrSvg::pngDataUri($issued['verify_url']);

        // `true` merges with the package defaults. Without it setOptions()
        // replaces the whole Options object, the chroot falls back to
        // vendor/dompdf and every sheet image under public/ is refused.
        $bytes = \App\Support\SecureSheet::render('exports.medical-record', compact('patient', 'options', 'issuedAt', 'verification', 'verifyQr'));
        $this->verifications->seal($verification, $bytes);

        // Names the record by Health ID and document reference, never by name.
        $filename = sprintf(
            'medical-record-%s-%s.pdf',
            \Illuminate\Support\Str::slug((string) ($patient->health_id ?: 'patient')),
            \Illuminate\Support\Str::slug((string) $verification->reference)
        );

        return [$bytes, $filename];
    }

    /** FHIR code-system URIs used by the export (HL7-registered). */
    public const FHIR_SYSTEM_ICD10    = 'http://hl7.org/fhir/sid/icd-10';
    public const FHIR_SYSTEM_SNOMED   = 'http://snomed.info/sct';
    public const FHIR_SYSTEM_LOINC    = 'http://loinc.org';
    public const FHIR_SYSTEM_HEALTHID = 'https://opescare.com/fhir/sid/health-id';

    /**
     * Generate a FHIR R4 (4.0.1) Bundle of type "collection" for the patient.
     *
     * Validated against the official HL7 validator, see
     * docs/certification/FHIR_EXPORT_VALIDATION.md. Rules:
     *  - every entry has fullUrl urn:uuid:{id}; every reference points at a
     *    fullUrl inside the bundle;
     *  - nothing is invented: a field we do not hold is omitted, never filled
     *    with a placeholder ("Unknown", "Prescription", created_at as onset);
     *  - a prescription line is one MedicationRequest (the medicine lives on
     *    the item, not on the prescription's free-text notes).
     */
    public function generateFhirBundle(string $patientId): array
    {
        $patient = Patient::with([
            'allergies',
            'diagnoses',
            'prescriptions.items',
        ])->findOrFail($patientId);

        $subject = ['reference' => 'urn:uuid:' . $patient->id];
        $entries = [];

        $name = array_filter([
            'use'    => 'official',
            'family' => $patient->last_name ?: null,
            'given'  => $patient->first_name ? [$patient->first_name] : null,
        ]);

        $entries[] = $this->fhirEntry(array_filter([
            'resourceType' => 'Patient',
            'id'           => (string) $patient->id,
            'identifier'   => $patient->health_id ? [[
                'system' => self::FHIR_SYSTEM_HEALTHID,
                'value'  => (string) $patient->health_id,
            ]] : null,
            'name'         => count($name) > 1 ? [$name] : null,
            'gender'       => match (strtolower((string) $patient->sex)) {
                'male', 'm'   => 'male',
                'female', 'f' => 'female',
                'other'       => 'other',
                default       => 'unknown',
            },
            'birthDate'    => $patient->date_of_birth?->toDateString(),
        ], fn ($v) => $v !== null));

        foreach ($patient->allergies ?? [] as $allergy) {
            if (! filled($allergy->substance)) {
                continue; // an allergy to nothing named is not exportable
            }
            $status = match (strtolower((string) $allergy->status)) {
                'inactive' => 'inactive',
                'resolved' => 'resolved',
                default    => 'active',
            };
            $entries[] = $this->fhirEntry(array_filter([
                'resourceType'   => 'AllergyIntolerance',
                'id'             => (string) $allergy->id,
                'clinicalStatus' => ['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical',
                    'code'   => $status,
                ]]],
                'code'           => ['text' => (string) $allergy->substance],
                'patient'        => $subject,
                'recordedDate'   => $allergy->created_at?->toIso8601String(),
            ], fn ($v) => $v !== null));
        }

        foreach ($patient->diagnoses ?? [] as $diagnosis) {
            $coding = [];
            if (filled($diagnosis->code)) {
                $sys = strtoupper(str_replace(['-', ' ', '_'], '', (string) $diagnosis->code_system));
                $uri = match (true) {
                    str_starts_with($sys, 'ICD10')          => self::FHIR_SYSTEM_ICD10,
                    $sys === 'SNOMED', $sys === 'SNOMEDCT'  => self::FHIR_SYSTEM_SNOMED,
                    $sys === 'LOINC'                        => self::FHIR_SYSTEM_LOINC,
                    default                                 => null,
                };
                if ($uri) {
                    $coding[] = array_filter(['system' => $uri, 'code' => (string) $diagnosis->code, 'display' => $diagnosis->display_name ?: null]);
                }
            }
            if (filled($diagnosis->snomed_code)) {
                $coding[] = array_filter(['system' => self::FHIR_SYSTEM_SNOMED, 'code' => (string) $diagnosis->snomed_code, 'display' => $diagnosis->snomed_display ?: null]);
            }
            $code = array_filter(['coding' => $coding ?: null, 'text' => $diagnosis->display_name ?: null]);
            if ($code === []) {
                continue; // no code and no name: nothing to say
            }

            $status   = strtolower((string) $diagnosis->status);
            $clinical = match ($status) {
                'resolved'         => 'resolved',
                'inactive'         => 'inactive',
                'remission'        => 'remission',
                'entered_in_error' => null, // con-5: no clinicalStatus when entered-in-error
                default            => 'active', // active, chronic, confirmed
            };
            $verification = match ($status) {
                'entered_in_error' => 'entered-in-error',
                'provisional'      => 'provisional',
                'differential'     => 'differential',
                default            => 'confirmed',
            };

            $entries[] = $this->fhirEntry(array_filter([
                'resourceType'       => 'Condition',
                'id'                 => (string) $diagnosis->id,
                'clinicalStatus'     => $clinical ? ['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                    'code'   => $clinical,
                ]]] : null,
                'verificationStatus' => ['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                    'code'   => $verification,
                ]]],
                'category'           => [['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                    'code'   => 'encounter-diagnosis',
                ]]]],
                'code'               => $code,
                'subject'            => $subject,
                // When it was recorded. We do not hold an onset date.
                'recordedDate'       => $diagnosis->created_at?->toIso8601String(),
            ], fn ($v) => $v !== null));
        }

        foreach ($patient->prescriptions ?? [] as $rx) {
            $status = match ((string) $rx->status) {
                'active', 'partially_dispensed' => 'active',
                'dispensed'                     => 'completed',
                'voided', 'cancelled'           => 'cancelled',
                'entered_in_error'              => 'entered-in-error',
                'expired', 'amended'            => 'stopped',
                default                         => 'unknown',
            };
            foreach ($rx->items ?? [] as $item) {
                if (! filled($item->drug_name)) {
                    continue;
                }
                $medText = trim(implode(' ', array_filter([$item->drug_name, $item->strength, $item->dosage_form])));
                $dosage  = trim(implode(', ', array_filter([
                    $item->dose,
                    $item->frequency,
                    $item->route,
                    $item->duration_days ? $item->duration_days . ' days' : null,
                ])));

                $entries[] = $this->fhirEntry(array_filter([
                    'resourceType'              => 'MedicationRequest',
                    'id'                        => (string) $item->id,
                    'groupIdentifier'           => $rx->reference ? ['value' => (string) $rx->reference] : null,
                    'status'                    => $status,
                    'intent'                    => 'order',
                    'medicationCodeableConcept' => ['text' => $medText],
                    'subject'                   => $subject,
                    'authoredOn'                => $rx->prescribed_at?->toIso8601String(),
                    'note'                      => filled($rx->notes) ? [['text' => (string) $rx->notes]] : null,
                    'dosageInstruction'         => $dosage !== '' ? [['text' => $dosage]] : null,
                    'dispenseRequest'           => is_numeric($item->quantity) && $item->quantity > 0
                        ? ['quantity' => ['value' => 0 + $item->quantity]] : null,
                ], fn ($v) => $v !== null));
            }
        }

        // Partner-reported readings (fhir_partner_*): exported with their
        // provenance — meta.source names the sending system, meta.security
        // marks them partner-asserted, and an extension names the sending
        // facility — so no reader of this bundle can take them for readings
        // taken at a facility.
        foreach ($this->partnerEntries($patient->id, $subject) as $entry) {
            $entries[] = $entry;
        }

        return [
            'resourceType' => 'Bundle',
            'id'           => \Illuminate\Support\Str::uuid()->toString(),
            'meta'         => ['lastUpdated' => Carbon::now()->toIso8601String()],
            'type'         => 'collection',
            'timestamp'    => Carbon::now()->toIso8601String(),
            'entry'        => $entries,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function partnerEntries(string $patientId, array $subject): array
    {
        $rows = app(\App\Services\Chart\PartnerReadingsService::class)
            ->forPatient($patientId, 1, \App\Services\Chart\PartnerReadingsService::MAX_PER_PAGE)['items'];

        $out = [];
        foreach ($rows as $r) {
            $meta = [
                'source'   => 'urn:opescare:partner:' . $r['sending_system']['name'],
                'security' => [[
                    'system'  => 'https://opescare.com/fhir/CodeSystem/provenance',
                    'code'    => 'partner_reported',
                    'display' => 'Reported by partner system; not measured at a facility on this platform.',
                ]],
                'extension' => [[
                    'url'         => 'https://opescare.com/fhir/StructureDefinition/sending-facility',
                    'valueString' => (string) ($r['sending_facility']['name'] ?? $r['sending_facility']['id']),
                ]],
            ];
            $coding = [[
                'system'  => $r['code_system'] === 'CVX' ? 'http://hl7.org/fhir/sid/cvx' : self::FHIR_SYSTEM_LOINC,
                'code'    => (string) $r['code'],
                'display' => $r['display'],
            ]];

            $resource = match ($r['type']) {
                'immunization' => [
                    'resourceType'       => 'Immunization',
                    'id'                 => $r['id'],
                    'meta'               => $meta,
                    'status'             => $r['status'],
                    'vaccineCode'        => ['coding' => $coding, 'text' => $r['display']],
                    'code'               => ['text' => $r['display']],
                    'patient'            => $subject,
                    'occurrenceDateTime' => $r['effective_at'],
                ],
                'diagnostic_report' => array_filter([
                    'resourceType'      => 'DiagnosticReport',
                    'id'                => $r['id'],
                    'meta'              => $meta,
                    'status'            => $r['status'],
                    'code'              => ['coding' => $coding, 'text' => $r['display']],
                    'subject'           => $subject,
                    'effectiveDateTime' => $r['effective_at'],
                    'conclusion'        => $r['conclusion'],
                ], fn ($v) => $v !== null),
                default => array_filter([
                    'resourceType'      => 'Observation',
                    'id'                => $r['id'],
                    'meta'              => $meta,
                    'status'            => $r['status'],
                    'code'              => ['coding' => $coding, 'text' => $r['display']],
                    'subject'           => $subject,
                    'effectiveDateTime' => $r['effective_at'],
                    'valueQuantity'     => $r['value'] !== null ? array_filter(['value' => $r['value'], 'unit' => $r['unit']], fn ($v) => $v !== null) : null,
                    'valueString'       => $r['value'] === null ? $r['value_text'] : null,
                ], fn ($v) => $v !== null),
            };
            $entry = $this->fhirEntry($resource);
            if ($r['type'] === 'immunization') {
                // `code` is not an Immunization element; it only fed the narrative.
                unset($entry['resource']['code']);
            }
            $out[] = $entry;
        }

        return $out;
    }

    /**
     * Wrap a resource as a bundle entry with fullUrl urn:uuid:{id}, adding a
     * generated narrative (dom-6) built only from what the resource already says.
     */
    private function fhirEntry(array $resource): array
    {
        $summary = match ($resource['resourceType']) {
            'Patient'           => trim(($resource['name'][0]['given'][0] ?? '') . ' ' . ($resource['name'][0]['family'] ?? ''))
                                   ?: ($resource['identifier'][0]['value'] ?? 'Patient'),
            'MedicationRequest' => $resource['medicationCodeableConcept']['text'] ?? '',
            default             => $resource['code']['text'] ?? ($resource['code']['coding'][0]['display'] ?? ($resource['code']['coding'][0]['code'] ?? '')),
        };
        $summary = $resource['resourceType'] . ': ' . $summary;

        $resource = array_slice($resource, 0, 2, true)
            + ['text' => [
                'status' => 'generated',
                'div'    => '<div xmlns="http://www.w3.org/1999/xhtml">' . htmlspecialchars($summary, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</div>',
            ]]
            + array_slice($resource, 2, null, true);

        return ['fullUrl' => 'urn:uuid:' . $resource['id'], 'resource' => $resource];
    }

    /**
     * Delete export files older than N hours. Returns count of files deleted.
     *
     * Nothing writes here any more; this remains only to purge files left by
     * the old store-on-export behaviour.
     */
    public function cleanupExports(int $hoursOld = 24): int
    {
        $files   = Storage::disk($this->diskName)->files($this->exportDir);
        $cutoff  = Carbon::now()->subHours($hoursOld)->timestamp;
        $deleted = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk($this->diskName)->lastModified($file);
            if ($lastModified <= $cutoff) {
                Storage::disk($this->diskName)->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
