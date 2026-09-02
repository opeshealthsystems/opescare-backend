<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\DataAccessCommitteeReview. Add fields here
 * deliberately; never expose the model directly.
 */
class DataAccessCommitteeReviewResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'research_access_request_id' => $this->research_access_request_id,
            'reviewer_id'                => $this->reviewer_id,
            'decision'                   => $this->decision,
            'comments'                   => $this->comments,
            'conditions'                 => $this->conditions,
            'reviewed_at'                => $this->reviewed_at?->toISOString(),
            'created_at'                 => $this->created_at?->toISOString(),
            'updated_at'                 => $this->updated_at?->toISOString(),
        ];
    }
}
