<?php

namespace App\Http\Resources\LabDesk;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * The lab desk's home screen — `GET /api/mobile/lab/home`. Wraps the array
 * LabDeskService::home() computes; the keys below ARE the contract.
 */
class LabDeskHomeResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $home   = (array) $this->resource;
        $counts = (array) ($home['counts'] ?? []);

        return [
            'facility' => [
                'id'   => $home['facility']['id'] ?? null,
                'name' => $home['facility']['name'] ?? null,
            ],
            'counts'   => [
                'awaiting_collection' => (int) ($counts['awaiting_collection'] ?? 0),
                'awaiting_results'    => (int) ($counts['awaiting_results'] ?? 0),
                'in_progress'         => (int) ($counts['in_progress'] ?? 0),
                'resulted_today'      => (int) ($counts['resulted_today'] ?? 0),
                'urgent'              => (int) ($counts['urgent'] ?? 0),
                'requests_pending'    => (int) ($counts['requests_pending'] ?? 0),
            ],
        ];
    }
}
