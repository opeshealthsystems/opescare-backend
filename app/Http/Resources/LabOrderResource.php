<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\LabOrder as the clinician surface sees it —
 * the row MobileDoctorChartController::orders() lists, key for key, so an
 * order placed from the phone (MobileDoctorLabOrderController) comes back in
 * the shape the chart will show it in on the next read. The `test_*` /
 * `urgency` / `clinical_indication` keys are the same values under the
 * column's own name, for callers that want the record rather than the row.
 *
 * Add fields here deliberately; never expose the model directly.
 */
class LabOrderResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => (string) $this->id,
            'patient_id'          => $this->patient_id,
            'facility_id'         => $this->facility_id,
            // No visit on a phone-placed order; null is "not recorded".
            'encounter_id'        => null,
            'order_type'          => 'lab',
            'code'                => $this->test_code,
            'name'                => $this->test_name,
            'test_code'           => $this->test_code,
            'test_name'           => $this->test_name,
            'priority'            => $this->urgency,
            'urgency'             => $this->urgency,
            'clinical_indication' => $this->clinical_indication,
            'clinical_notes'      => $this->clinical_indication ?: $this->notes,
            'status'              => $this->status,
            // A laboratory releases values, not a narrative.
            'result_summary'      => null,
            'ordered_by'          => $this->ordered_by,
            'ordered_at'          => $this->ordered_at?->toISOString(),
            'created_at'          => $this->created_at?->toISOString(),
        ];
    }
}
