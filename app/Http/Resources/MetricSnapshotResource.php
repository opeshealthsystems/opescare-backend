<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MetricSnapshotResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'metric_definition_id' => $this->metric_definition_id,
            'facility_id'          => $this->facility_id,
            'period_date'          => $this->period_date?->toISOString(),
            'period_granularity'   => $this->period_granularity,
            'value'                => $this->value,
            'previous_value'       => $this->previous_value,
            'change_pct'           => $this->change_pct,
            'status'               => $this->status,
            'sample_count'         => $this->sample_count,
            'breakdown'            => $this->breakdown,
            'computed_at'          => $this->computed_at?->toISOString(),
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}
