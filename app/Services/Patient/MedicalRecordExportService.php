<?php

namespace App\Services\Patient;

use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class MedicalRecordExportService
{
    private string $diskName = 'local';
    private string $exportDir = 'exports/medical-records';

    /**
     * Generate a PDF medical record.
     *
     * Options: include_vitals, include_diagnoses, include_medications,
     *          include_labs, include_immunizations (all default true).
     *
     * Returns absolute path to the generated PDF file.
     */
    public function generatePdf(string $patientId, array $options = []): string
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
            'prescriptions' => fn ($q) => $q->where('status', 'active'),
            'vitals'        => fn ($q) => $q->orderByDesc('recorded_at')->limit(3),
            'labResults'    => fn ($q) => $q->orderByDesc('created_at')->limit(10),
            'immunizations',
        ])->findOrFail($patientId);

        $pdf = Pdf::loadView('exports.medical-record', compact('patient', 'options'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'sans-serif',
                'isHtml5ParserEnabled' => true,
            ]);

        // Include a random suffix so two exports for the same patient within the
        // same second don't collide and overwrite each other.
        $filename     = sprintf('medical-record-%s-%s-%s.pdf', $patientId, Carbon::now()->format('YmdHis'), \Illuminate\Support\Str::random(6));
        $relativePath = $this->exportDir . '/' . $filename;

        Storage::disk($this->diskName)->put($relativePath, $pdf->output());

        return Storage::disk($this->diskName)->path($relativePath);
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
