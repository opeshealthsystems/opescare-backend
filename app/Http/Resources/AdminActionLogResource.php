<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AdminActionLogResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'actor_id'      => $this->actor_id,
            'action'        => $this->action,
            'resource_type' => $this->resource_type,
            'resource_id'   => $this->resource_id,
            'before'        => $this->before,
            'after'         => $this->after,
            'ip_address'    => $this->ip_address,
            'occurred_at'   => $this->occurred_at?->toISOString(),
        ];
    }
}
