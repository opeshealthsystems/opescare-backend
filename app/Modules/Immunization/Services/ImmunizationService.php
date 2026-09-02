<?php

namespace App\Modules\Immunization\Services;

use App\Models\AdverseReactionNote;
use App\Models\ImmunizationRecord;
use App\Models\VaccinationSchedule;
use Illuminate\Support\Carbon;

class ImmunizationService
{
    public function record(array $data): ImmunizationRecord
    {
        $this->checkForDuplicate($data);

        $record = ImmunizationRecord::create([
            'patient_id'          => $data['patient_id'],
            'facility_id'         => $data['facility_id'],
            'administered_by_id'  => $data['administered_by_id'] ?? null,
            'encounter_id'        => $data['encounter_id'] ?? null,
            'vaccine_code'        => strtoupper($data['vaccine_code']),
            'vaccine_system'      => $data['vaccine_system'] ?? 'WHO-EPI',
            'vaccine_name'        => $data['vaccine_name'],
            'lot_number'          => $data['lot_number'] ?? null,
            'manufacturer'        => $data['manufacturer'] ?? null,
            'administered_at'     => $data['administered_at'] ?? now(),
            'dose_number'         => $data['dose_number'] ?? null,
            'dose_sequence'       => $data['dose_sequence'] ?? null,
            'route'               => $data['route'] ?? null,
            'site'                => $data['site'] ?? null,
            'dose_quantity'       => $data['dose_quantity'] ?? null,
            'dose_unit'           => $data['dose_unit'] ?? null,
            'expiry_date'         => $data['expiry_date'] ?? null,
            'status'              => $data['status'] ?? 'completed',
            'not_done_reason'     => $data['not_done_reason'] ?? null,
            'verification_status' => 'unverified',
            'is_historical'       => $data['is_historical'] ?? false,
            'source_document_id'  => $data['source_document_id'] ?? null,
        ]);

        // Mark any matching scheduled dose as completed
        if ($record->status === 'completed' && $record->dose_number) {
            VaccinationSchedule::query()
                ->where('patient_id', $record->patient_id)
                ->where('vaccine_code', $record->vaccine_code)
                ->where('dose_number', $record->dose_number)
                ->where('status', 'due')
                ->update([
                    'status'                     => 'completed',
                    'completed_by_immunization_id' => $record->id,
                ]);
        }

        return $record;
    }

    public function recordAdverseReaction(ImmunizationRecord $immunization, array $data): AdverseReactionNote
    {
        return AdverseReactionNote::create([
            'immunization_record_id'    => $immunization->id,
            'patient_id'                => $immunization->patient_id,
            'reported_by_id'            => $data['reported_by_id'] ?? null,
            'severity'                  => $data['severity'],
            'description'               => $data['description'],
            'onset_timing'              => $data['onset_timing'] ?? null,
            'onset_at'                  => $data['onset_at'] ?? null,
            'action_taken'              => $data['action_taken'] ?? null,
            'outcome'                   => $data['outcome'] ?? null,
            'reported_to_authority'     => $data['reported_to_authority'] ?? false,
            'authority_report_reference'=> $data['authority_report_reference'] ?? null,
        ]);
    }

    public function scheduleVaccines(string $patientId, array $schedules): void
    {
        foreach ($schedules as $schedule) {
            VaccinationSchedule::firstOrCreate(
                [
                    'patient_id'   => $patientId,
                    'vaccine_code' => strtoupper($schedule['vaccine_code']),
                    'dose_number'  => $schedule['dose_number'],
                ],
                [
                    'vaccine_name'  => $schedule['vaccine_name'],
                    'dose_sequence' => $schedule['dose_sequence'] ?? null,
                    'due_date'      => $schedule['due_date'] ?? null,
                    'earliest_date' => $schedule['earliest_date'] ?? null,
                    'latest_date'   => $schedule['latest_date'] ?? null,
                    'status'        => 'due',
                ]
            );
        }
    }

    public function getHistory(string $patientId): array
    {
        $records = ImmunizationRecord::query()
            ->where('patient_id', $patientId)
            ->where('status', '!=', 'entered_in_error')
            ->orderByDesc('administered_at')
            ->get();

        $schedule = VaccinationSchedule::query()
            ->where('patient_id', $patientId)
            ->whereIn('status', ['due', 'overdue'])
            ->orderBy('due_date')
            ->get();

        return [
            'records'  => $records,
            'schedule' => $schedule,
        ];
    }

    private function checkForDuplicate(array $data): void
    {
        $administeredAt = Carbon::parse($data['administered_at'] ?? now());

        $duplicate = ImmunizationRecord::query()
            ->where('patient_id', $data['patient_id'])
            ->where('vaccine_code', strtoupper($data['vaccine_code']))
            ->whereDate('administered_at', $administeredAt->toDateString())
            ->when(!empty($data['lot_number']), fn ($q) => $q->where('lot_number', $data['lot_number']))
            ->where('status', '!=', 'entered_in_error')
            ->exists();

        abort_if($duplicate, 409, 'A matching immunization record already exists for this patient on this date. Check for duplicates before recording again.');
    }
}
