<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DrugInteractionAlertResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'reconciliation_id' => $this->reconciliation_id,
            'drug_a'            => $this->drug_a,
            'drug_b'            => $this->drug_b,
            'severity'          => $this->severity,
            'description'       => $this->description,
            'is_hard_stop'      => (bool) $this->is_hard_stop,
            'acknowledged'      => (bool) $this->acknowledged,
            'acknowledged_by'   => $this->acknowledged_by,
            'acknowledged_at'   => $this->acknowledged_at?->toISOString(),
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
