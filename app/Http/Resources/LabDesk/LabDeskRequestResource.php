<?php

namespace App\Http\Resources\LabDesk;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * A patient's request for a test, as the laboratory that received it sees it
 * — `GET /api/mobile/lab/requests` and the answer to approve / decline.
 *
 * `priority` is the patient's advisory urgency folded to routine | urgent |
 * stat; `status` is the request's own vocabulary (submitted | approved |
 * declined | cancelled). `lab_order_id` is set once approval raised an order.
 */
class LabDeskRequestResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'test_name'       => $this->test_name,
            'test_code'       => $this->test_code,
            'priority'        => $this->priority(),
            'status'          => $this->status,
            'submitted_at'    => $this->submitted_at?->toIso8601String(),
            'clinical_notes'  => $this->clinical_notes,
            'reviewed_at'     => $this->reviewed_at?->toIso8601String(),
            'reviewed_by_name' => $this->relationLoaded('reviewer') ? $this->reviewer?->name : null,
            'decision_reason' => $this->decision_reason,
            'lab_order_id'    => $this->lab_order_id,
            'patient'         => $this->patient !== null ? LabDeskPatientResource::make($this->patient)->brief() : null,
        ];
    }

    private function priority(): string
    {
        return match (strtolower(trim((string) $this->resource->priority))) {
            'stat', 'immediate', 'emergency' => 'stat',
            'urgent', 'priority'             => 'urgent',
            default                          => 'routine',
        };
    }
}
