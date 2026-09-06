<?php

namespace App\Http\Resources\LabDesk;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * A published interval the bench may compare a value against. Wraps the
 * array LabDeskService::referenceRanges() builds from LabReferenceRange.
 */
class LabDeskReferenceRangeResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $row = (array) $this->resource;

        return [
            'parameter_name' => $row['parameter_name'] ?? null,
            'low'            => $row['low'] ?? null,
            'high'           => $row['high'] ?? null,
            'unit'           => $row['unit'] ?? null,
        ];
    }
}
