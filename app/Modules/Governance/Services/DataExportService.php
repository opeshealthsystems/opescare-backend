<?php

namespace App\Modules\Governance\Services;

use App\Models\DataExportRequest;
use App\Models\Encounter;
use App\Models\ImagingOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Validation\UnauthorizedException;

class DataExportService
{
    public function requestExport(
        ?string $patientId,
        string $userId,
        string $exportType,
        array $scope
    ): DataExportRequest {
        $request = new DataExportRequest();
        $request->patient_id = $patientId;
        $request->requested_by_user_id = $userId;
        $request->export_type = $exportType;
        $request->scope_json = $scope;
        $request->status = 'pending';
        $request->save();

        // Audit log request
        AccessLogService::log(
            $patientId,
            $userId,
            'User',
            null,
            null,
            'patient_request',
            'data_export',
            'DataExportRequest',
            $request->id,
            'create'
        );

        return $request;
    }

    public function approveExport(string $requestId, string $approverId): DataExportRequest
    {
        $request = DataExportRequest::findOrFail($requestId);
        $request->status = 'approved';
        $request->approved_by = $approverId;
        $request->file_path = "/exports/secure-patient-summary-{$request->id}.csv";
        $request->expires_at = Carbon::now()->addHours(24);
        $request->save();

        // Audit log approval
        AccessLogService::log(
            $request->patient_id,
            $approverId,
            'User',
            null,
            null,
            'facility_operations',
            'data_export',
            'DataExportRequest',
            $request->id,
            'approve'
        );

        return $request;
    }

    /**
     * Returns the actual assembled export payload (not just a stored path) so
     * the patient-facing download genuinely delivers their data, per Cameroon
     * Law No. 2010/012 (patient's right to a copy of their own record).
     *
     * @return array<string, mixed>
     */
    public function downloadExport(string $requestId, string $userId): array
    {
        $request = DataExportRequest::findOrFail($requestId);

        if ($request->status !== 'approved') {
            throw new UnauthorizedException("Export is not approved.");
        }

        if ($request->expires_at && Carbon::now()->greaterThan($request->expires_at)) {
            $request->status = 'expired';
            $request->save();
            throw new UnauthorizedException("Export download link has expired.");
        }

        $content = $this->buildExportContent($request);

        $request->status = 'downloaded';
        $request->save();

        // Audit log download
        AccessLogService::log(
            $request->patient_id,
            $userId,
            'User',
            null,
            null,
            'patient_request',
            'data_export',
            'DataExportRequest',
            $request->id,
            'download'
        );

        return [
            'id'           => $request->id,
            'export_type'  => $request->export_type,
            'status'       => $request->status,
            'generated_at' => Carbon::now()->toISOString(),
            'expires_at'   => $request->expires_at?->toISOString(),
            'content'      => $content,
        ];
    }

    /**
     * Assembles the actual patient-record payload for an approved export
     * request, scoped strictly to that patient and the requested type(s).
     *
     * @return array<string, mixed>
     */
    private function buildExportContent(DataExportRequest $request): array
    {
        $patientId = $request->patient_id;
        $patient = $patientId ? Patient::find($patientId) : null;

        $sectionsWanted = $request->export_type === 'full_record'
            ? ['encounters', 'prescriptions', 'lab_results', 'imaging']
            : [$request->export_type];

        $sections = [];
        foreach ($sectionsWanted as $section) {
            $sections[$section] = $patientId ? $this->fetchExportSection($section, $patientId) : [];
        }

        return [
            'patient' => $patient ? [
                'health_id'     => $patient->health_id ?? null,
                'display_name'  => trim(($patient->first_name ?? '').' '.($patient->last_name ?? '')),
                'date_of_birth' => optional($patient->date_of_birth)->toDateString(),
                'sex'           => $patient->sex ?? null,
            ] : null,
            'sections' => $sections,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchExportSection(string $section, string $patientId): array
    {
        return match ($section) {
            'encounters' => Encounter::where('patient_id', $patientId)
                ->orderByDesc('started_at')
                ->limit(500)
                ->get(['id', 'encounter_type', 'status', 'reason_for_encounter', 'started_at', 'ended_at'])
                ->toArray(),
            'prescriptions' => Prescription::where('patient_id', $patientId)
                ->orderByDesc('prescribed_at')
                ->limit(500)
                ->get(['id', 'status', 'prescribed_at', 'dispensed_at', 'expires_at', 'notes'])
                ->toArray(),
            'lab_results' => LabResult::where('patient_id', $patientId)
                ->orderByDesc('resulted_at')
                ->limit(500)
                ->get(['id', 'parameter_name', 'value', 'unit', 'reference_range', 'flag', 'resulted_at'])
                ->toArray(),
            'imaging' => ImagingOrder::where('patient_id', $patientId)
                ->orderByDesc('ordered_at')
                ->limit(500)
                ->get(['id', 'modality', 'body_part', 'status', 'ordered_at', 'completed_at'])
                ->toArray(),
            default => [],
        };
    }
}
