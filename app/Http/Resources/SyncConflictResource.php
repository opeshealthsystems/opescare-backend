<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Wire contract for App\Models\SyncConflict. */
class SyncConflictResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'offline_queue_id'    => $this->offline_queue_id,
            'conflict_type'       => $this->conflict_type,
            'status'              => $this->status,
            'resolution_strategy' => $this->resolution_strategy,
            'resolved_by'         => $this->resolved_by,
            'resolved_at'         => $this->resolved_at?->toISOString(),
            'created_at'          => $this->created_at?->toISOString(),
        ];
    }
}
