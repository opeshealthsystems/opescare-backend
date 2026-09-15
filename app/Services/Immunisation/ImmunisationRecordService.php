<?php

namespace App\Services\Immunisation;

use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\VaccinationSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Writing a dose onto a patient's vaccination card — ONE implementation shared
 * by the clinician app (Api\Mobile\MobileDoctorImmunizationController) and the
 * web desk (MedicalId\ImmunisationDeskController).
 *
 * Extracted verbatim from the mobile controller. Callers keep their own
 * transport: validation, the relationship gate, the audit row and the shape of
 * the refusal. The facility and clinician are always passed in from the
 * caller's auth layer, never read from a request.
 *
 * Immutable: there is no update here. A dose recorded in error is voided
 * (status `entered_in_error`, reason in `not_done_reason` — the table has no
 * void columns and none are invented) and the truth is recorded as a new dose.
 */
class ImmunisationRecordService
{
    /** What a dose may be at the moment it is written. Never born retracted. */
    public const WRITABLE_STATUSES = ['completed', 'not_done'];

    /** Routes of administration recognised by the EPI record. */
    public const ROUTES = ['IM', 'SC', 'ID', 'oral', 'intranasal'];

    /**
     * A same-day dose of the same vaccine (and dose number, when given) that
     * is not voided — the double-tap guard. Not UNIQUE in the schema because a
     * genuine repeat exists; callers let a human override it.
     */
    public function findDuplicate(string $patientId, string $vaccineCode, Carbon $administeredAt, ?int $doseNumber): ?ImmunizationRecord
    {
        return ImmunizationRecord::query()
            ->where('patient_id', $patientId)
            ->where('vaccine_code', $vaccineCode)
            ->where('status', '!=', 'entered_in_error')
            ->whereDate('administered_at', $administeredAt->toDateString())
            ->when($doseNumber !== null, fn ($q) => $q->where('dose_number', $doseNumber))
            ->first();
    }

    /**
     * @param  array<string,mixed>  $validated
     */
    public function record(
        Patient $patient,
        string $facilityId,
        string $clinicianId,
        array $validated,
        string $status,
        bool $isHistorical,
        Carbon $administeredAt,
    ): ImmunizationRecord {
        return DB::transaction(function () use ($validated, $patient, $clinicianId, $facilityId, $status, $isHistorical, $administeredAt) {
            $row = ImmunizationRecord::create([
                'patient_id'  => $patient->id,
                // From the caller's auth layer. Never from a body.
                'facility_id' => $facilityId,
                // A transcribed paper card does NOT name the clinician typing
                // it as the one who gave the vaccine. Unknown stays unknown.
                'administered_by_id' => $isHistorical ? null : $clinicianId,
                'encounter_id'       => $validated['encounter_id'] ?? null,
                'vaccine_code'       => $validated['vaccine_code'],
                'vaccine_system'     => $validated['vaccine_system'] ?? 'WHO-EPI',
                'vaccine_name'       => $validated['vaccine_name'],
                'lot_number'         => $validated['lot_number'] ?? null,
                'manufacturer'       => $validated['manufacturer'] ?? null,
                'administered_at'    => $administeredAt,
                // Absent means absent. No dose number is assumed to be 1.
                'dose_number'        => $validated['dose_number'] ?? null,
                'dose_sequence'      => $validated['dose_sequence'] ?? null,
                'route'              => $validated['route'] ?? null,
                'site'               => $validated['site'] ?? null,
                'dose_quantity'      => $validated['dose_quantity'] ?? null,
                'dose_unit'          => $validated['dose_unit'] ?? null,
                'expiry_date'        => $validated['expiry_date'] ?? null,
                'status'             => $status,
                'not_done_reason'    => $status === 'not_done' ? $validated['not_done_reason'] : null,
                // Only a dose this facility gave itself is `verified`.
                'verification_status' => $isHistorical ? 'unverified' : 'verified',
                'is_historical'       => $isHistorical,
                'source_document_id'  => $validated['source_document_id'] ?? null,
            ]);

            // Close the matching open line on the forward course — EXACT
            // vaccine_code + dose_number only.
            if ($status === 'completed' && isset($validated['dose_number'])) {
                VaccinationSchedule::query()
                    ->where('patient_id', $patient->id)
                    ->where('vaccine_code', $validated['vaccine_code'])
                    ->where('dose_number', $validated['dose_number'])
                    ->whereIn('status', ['due', 'overdue'])
                    ->update([
                        'status'                       => 'completed',
                        'completed_by_immunization_id' => $row->id,
                        'updated_at'                   => now(),
                    ]);
            }

            return $row;
        });
    }

    /** Mark a dose entered-in-error. The row stays; a closed course line reopens. */
    public function void(ImmunizationRecord $record, string $reason): void
    {
        DB::transaction(function () use ($record, $reason) {
            $record->fill([
                'status'          => 'entered_in_error',
                'not_done_reason' => $reason,
            ])->save();

            VaccinationSchedule::query()
                ->where('completed_by_immunization_id', $record->id)
                ->update([
                    'status'                       => 'due',
                    'completed_by_immunization_id' => null,
                    'updated_at'                   => now(),
                ]);
        });
    }
}
