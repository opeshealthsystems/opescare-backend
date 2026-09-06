<?php

namespace App\Http\Resources\LabDesk;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * One row of the laboratory worklist — the wire contract of the lab desk's
 * `GET /api/mobile/lab/orders`.
 *
 * `status` is the desk's four-state vocabulary (ordered | collected |
 * in_progress | resulted, plus cancelled) and `urgency` the app's three
 * (routine | urgent | stat), both folded from the free-text columns by the
 * model so the app never meets a spelling it does not know.
 *
 * Expects the order loaded with `patient`, `orderedBy` and `results_count`
 * (LabDeskService::baseQuery / detail). Falls back to a query rather than a
 * wrong number when a caller did not.
 */
class LabDeskOrderResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'test_name'           => $this->test_name,
            'test_code'           => $this->blank($this->test_code),
            'urgency'             => $this->deskUrgency(),
            'status'              => $this->deskStatus(),
            'ordered_at'          => $this->ordered_at?->toIso8601String(),
            'collected_at'        => $this->collected_at?->toIso8601String(),
            'resulted_at'         => $this->resulted_at?->toIso8601String(),
            'clinical_indication' => $this->blank($this->clinical_indication),
            'ordered_by_name'     => $this->relationLoaded('orderedBy') ? $this->orderedBy?->name : null,
            'results_count'       => $this->resultsCount(),
            'patient'             => $this->patient !== null ? LabDeskPatientResource::make($this->patient) : null,
        ];
    }

    protected function resultsCount(): int
    {
        if ($this->resource->getAttribute('results_count') !== null) {
            return (int) $this->resource->getAttribute('results_count');
        }

        return $this->relationLoaded('results')
            ? $this->results->count()
            : (int) $this->results()->count();
    }

    protected function blank(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
