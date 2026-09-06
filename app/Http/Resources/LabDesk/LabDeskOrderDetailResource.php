<?php

namespace App\Http\Resources\LabDesk;

use Illuminate\Http\Request;

/**
 * The order with its analytes and the published intervals the bench can use
 * — `GET /api/mobile/lab/orders/{id}` and the body every desk write answers
 * with.
 *
 * Reference ranges are computed by LabDeskService::referenceRanges() and
 * handed in with withReferenceRanges(); the resource never derives one.
 */
class LabDeskOrderDetailResource extends LabDeskOrderResource
{
    /** @var list<array<string, mixed>> */
    private array $referenceRanges = [];

    /** @param  iterable<array<string, mixed>>  $ranges */
    public function withReferenceRanges(iterable $ranges): static
    {
        $this->referenceRanges = collect($ranges)->values()->all();

        return $this;
    }

    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'notes'            => $this->blank($this->notes),
            'verified_by_name' => $this->relationLoaded('verifiedBy') ? $this->verifiedBy?->name : null,
            'results'          => LabDeskResultResource::collection($this->results),
            'reference_ranges' => LabDeskReferenceRangeResource::collection(collect($this->referenceRanges)),
        ];
    }
}
