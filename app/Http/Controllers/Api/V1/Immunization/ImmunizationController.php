<?php

namespace App\Http\Controllers\Api\V1\Immunization;

use App\Http\Controllers\Controller;
use App\Models\AdverseReactionNote;
use App\Models\ImmunizationRecord;
use App\Models\VaccinationSchedule;
use App\Modules\Immunization\Services\ImmunizationService;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\Request;

class ImmunizationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->filled('patient_id'), 422, 'patient_id is required');

        $history = (new ImmunizationService())->getHistory($request->query('patient_id'));

        return response()->json([
            'data' => [
                'records'  => $history['records']->map(fn ($r) => $this->serializeRecord($r)),
                'schedule' => $history['schedule']->map(fn ($s) => $this->serializeSchedule($s)),
            ],
        ]);
    }

    public function store(Request $request, ImmunizationService $service, DocumentIssuanceService $issuance)
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'         => ['required', 'uuid'],
            'administered_by_id' => ['nullable', 'uuid'],
            'encounter_id'       => ['nullable', 'uuid'],
            'vaccine_code'       => ['required', 'string', 'max:50'],
            'vaccine_system'     => ['nullable', 'string', 'max:50'],
            'vaccine_name'       => ['required', 'string', 'max:200'],
            'lot_number'         => ['nullable', 'string', 'max:100'],
            'manufacturer'       => ['nullable', 'string', 'max:200'],
            'administered_at'    => ['nullable', 'date'],
            'dose_number'        => ['nullable', 'integer', 'min:1'],
            'dose_sequence'      => ['nullable', 'string', 'max:50'],
            'route'              => ['nullable', 'string', 'max:50'],
            'site'               => ['nullable', 'string', 'max:100'],
            'dose_quantity'      => ['nullable', 'numeric', 'min:0'],
            'dose_unit'          => ['nullable', 'string', 'max:20'],
            'expiry_date'        => ['nullable', 'date'],
            'status'             => ['nullable', 'in:completed,not_done'],
            'not_done_reason'    => ['nullable', 'string'],
            'is_historical'      => ['nullable', 'boolean'],
            'source_document_id' => ['nullable', 'uuid'],
        ]);

        $validated['facility_id'] = $facilityId;
        $record = $service->record($validated);

        try {
            $issuance->issueFromModel(
                'VAX',
                'Immunization Certificate — ' . $validated['vaccine_name'],
                ['record_id' => $record->id, 'patient_id' => $validated['patient_id'], 'vaccine_code' => $validated['vaccine_code'], 'vaccine_name' => $validated['vaccine_name'], 'dose_number' => $validated['dose_number'] ?? null, 'administered_at' => $validated['administered_at'] ?? now()->toDateString(), 'lot_number' => $validated['lot_number'] ?? null],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['administered_by_id'] ?? null
            );
        } catch (\Throwable) {}

        return response()->json(['data' => $this->serializeRecord($record)], 201);
    }

    public function show(ImmunizationRecord $immunization)
    {
        return response()->json([
            'data' => $this->serializeRecord($immunization->load('adverseReactions')),
        ]);
    }

    public function scheduleVaccines(Request $request, ImmunizationService $service)
    {
        $validated = $request->validate([
            'patient_id'             => ['required', 'uuid'],
            'schedules'              => ['required', 'array', 'min:1'],
            'schedules.*.vaccine_code'  => ['required', 'string'],
            'schedules.*.vaccine_name'  => ['required', 'string'],
            'schedules.*.dose_number'   => ['required', 'integer', 'min:1'],
            'schedules.*.dose_sequence' => ['nullable', 'string'],
            'schedules.*.due_date'      => ['nullable', 'date'],
            'schedules.*.earliest_date' => ['nullable', 'date'],
            'schedules.*.latest_date'   => ['nullable', 'date'],
        ]);

        $service->scheduleVaccines($validated['patient_id'], $validated['schedules']);

        return response()->json(['status' => 'scheduled']);
    }

    public function reportAdverseReaction(Request $request, ImmunizationRecord $immunization, ImmunizationService $service)
    {
        $validated = $request->validate([
            'reported_by_id'             => ['nullable', 'uuid'],
            'severity'                   => ['required', 'in:mild,moderate,severe,life_threatening'],
            'description'                => ['required', 'string'],
            'onset_timing'               => ['nullable', 'in:immediate,within_1h,within_24h,delayed'],
            'onset_at'                   => ['nullable', 'date'],
            'action_taken'               => ['nullable', 'string'],
            'outcome'                    => ['nullable', 'in:resolved,recovering,not_resolved,fatal,unknown'],
            'reported_to_authority'      => ['nullable', 'boolean'],
            'authority_report_reference' => ['nullable', 'string'],
        ]);

        $reaction = $service->recordAdverseReaction($immunization, $validated);

        return response()->json(['data' => $this->serializeReaction($reaction)], 201);
    }

    public function patientSchedule(Request $request)
    {
        abort_unless($request->filled('patient_id'), 422, 'patient_id is required');

        $schedule = VaccinationSchedule::query()
            ->where('patient_id', $request->query('patient_id'))
            ->orderBy('due_date')
            ->get();

        return response()->json(['data' => $schedule->map(fn ($s) => $this->serializeSchedule($s))]);
    }

    private function serializeRecord(ImmunizationRecord $record): array
    {
        return [
            'id'                  => $record->id,
            'patient_id'          => $record->patient_id,
            'facility_id'         => $record->facility_id,
            'vaccine_code'        => $record->vaccine_code,
            'vaccine_name'        => $record->vaccine_name,
            'lot_number'          => $record->lot_number,
            'manufacturer'        => $record->manufacturer,
            'administered_at'     => $record->administered_at?->toISOString(),
            'dose_number'         => $record->dose_number,
            'dose_sequence'       => $record->dose_sequence,
            'route'               => $record->route,
            'site'                => $record->site,
            'status'              => $record->status,
            'verification_status' => $record->verification_status,
            'is_historical'       => $record->is_historical,
            'adverse_reactions'   => $record->relationLoaded('adverseReactions')
                ? $record->adverseReactions->map(fn ($ar) => $this->serializeReaction($ar))
                : null,
        ];
    }

    private function serializeSchedule(VaccinationSchedule $schedule): array
    {
        return [
            'id'            => $schedule->id,
            'patient_id'    => $schedule->patient_id,
            'vaccine_code'  => $schedule->vaccine_code,
            'vaccine_name'  => $schedule->vaccine_name,
            'dose_number'   => $schedule->dose_number,
            'due_date'      => $schedule->due_date?->toDateString(),
            'status'        => $schedule->status,
            'is_overdue'    => $schedule->isOverdue(),
        ];
    }

    private function serializeReaction(AdverseReactionNote $reaction): array
    {
        return [
            'id'                         => $reaction->id,
            'severity'                   => $reaction->severity,
            'description'                => $reaction->description,
            'onset_timing'               => $reaction->onset_timing,
            'action_taken'               => $reaction->action_taken,
            'outcome'                    => $reaction->outcome,
            'reported_to_authority'      => $reaction->reported_to_authority,
        ];
    }
}
