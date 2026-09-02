<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SystemHealthSnapshotResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'status'               => $this->status,
            'checks'               => $this->checks,
            'failed_jobs_count'    => $this->failed_jobs_count,
            'webhook_failures_24h' => $this->webhook_failures_24h,
            'api_error_rate_pct'   => $this->api_error_rate_pct,
            'disk_used_pct'        => $this->disk_used_pct,
            'avg_response_ms'      => $this->avg_response_ms,
            'snapshot_at'          => $this->snapshot_at?->toISOString(),
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}
