<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CarePlanGoalResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'care_plan_id' => $this->care_plan_id,
            'goal_text'    => $this->goal_text,
            'target_date'  => $this->target_date?->toISOString(),
            'status'       => $this->status,
            'achieved_at'  => $this->achieved_at?->toISOString(),
            'notes'        => $this->notes,
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
