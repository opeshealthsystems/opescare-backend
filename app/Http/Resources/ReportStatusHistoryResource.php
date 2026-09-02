<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ReportStatusHistoryResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'changed_by' => $this->changed_by,
            'reason' => $this->reason,
            'changed_at' => $this->changed_at?->toISOString(),
        ];
    }
}
