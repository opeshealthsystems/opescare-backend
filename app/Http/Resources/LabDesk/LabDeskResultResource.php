<?php

namespace App\Http\Resources\LabDesk;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * One analyte as the bench entered it. Values, units and intervals are
 * printed exactly as recorded; `flag` is the desk's H | L | N | C (see
 * LabResult::deskFlag()) or null when none was recorded.
 */
class LabDeskResultResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'parameter_name'  => $this->parameter_name,
            'value'           => $this->value,
            'unit'            => $this->unit,
            'reference_range' => $this->reference_range,
            'flag'            => $this->deskFlag(),
            'notes'           => $this->notes,
            'loinc_code'      => $this->loinc_code,
            'resulted_at'     => $this->resulted_at?->toIso8601String(),
        ];
    }
}
