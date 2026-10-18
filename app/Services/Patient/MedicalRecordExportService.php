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

    /**
     * Generate a FHIR R4 Bundle for the patient.
     */
    public function generateFhirBundle(string $patientId): array
    {
        $patient = Patient::with([
            'allergies',
            'diagnoses',
            'prescriptions',
            'vitals',
            'labResults',
            'immunizations',
        ])->findOrFail($patientId);

        $entries = [];

        // Patient resource
        $entries[] = [
            'resource' => [
                'resourceType' => 'Patient',
                'id'           => $patient->id,
                'name'         => [[
                    'use'    => 'official',
                    'family' => $patient->last_name,
                    'given'  => [$patient->first_name],
                ]],
                'gender'       => $patient->sex ?? 'unknown',
                'birthDate'    => $patient->date_of_birth?->toDateString(),
                'identifier'   => [[
                    'system' => 'urn:opescare:patient-id',
                    'value'  => $patient->health_id ?? $patient->id,
                ]],
            ],
        ];

        // AllergyIntolerance resources
        foreach ($patient->allergies ?? [] as $allergy) {
            $entries[] = [
                'resource' => [
                    'resourceType'   => 'AllergyIntolerance',
                    'id'             => $allergy->id,
                    'patient'        => ['reference' => "Patient/{$patient->id}"],
                    'code'           => ['text' => $allergy->substance ?? $allergy->allergen ?? $allergy->name ?? 'Unknown'],
                    'clinicalStatus' => ['coding' => [['code' => 'active']]],
                ],
            ];
        }

        // Condition resources (diagnoses)
        foreach ($patient->diagnoses ?? [] as $diagnosis) {
            $entries[] = [
                'resource' => [
                    'resourceType'   => 'Condition',
                    'id'             => $diagnosis->id,
                    'subject'        => ['reference' => "Patient/{$patient->id}"],
                    'code'           => ['text' => $diagnosis->display_name ?? $diagnosis->code ?? 'Unknown'],
                    'clinicalStatus' => ['coding' => [['code' => $diagnosis->status ?? 'active']]],
                    'onsetDateTime'  => $diagnosis->created_at?->toIso8601String(),
                ],
            ];
        }

        // MedicationRequest resources
        foreach ($patient->prescriptions ?? [] as $rx) {
            $entries[] = [
                'resource' => [
                    'resourceType'             => 'MedicationRequest',
                    'id'                       => $rx->id,
                    'subject'                  => ['reference' => "Patient/{$patient->id}"],
                    'status'                   => $rx->status ?? 'active',
                    'intent'                   => 'order',
                    'medicationCodeableConcept' => ['text' => $rx->notes ?? 'Prescription'],
                    'dosageInstruction'        => [[
                        'text' => $rx->notes ?? '',
                    ]],
                ],
            ];
        }

        return [
            'resourceType' => 'Bundle',
            'id'           => \Illuminate\Support\Str::uuid()->toString(),
            'type'         => 'collection',
            'timestamp'    => Carbon::now()->toIso8601String(),
            'total'        => count($entries),
            'entry'        => $entries,
        ];
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
