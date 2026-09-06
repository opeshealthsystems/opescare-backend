<?php

namespace App\Http\Resources\Desk;

use App\Http\Resources\ApiResource;
use App\Models\Patient;
use App\Support\ProviderPatientSummary;
use Illuminate\Http\Request;

/**
 * A patient the desk has just registered — in the ONE shape a professional's
 * app receives when it identifies a patient (ProviderPatientSummary), so the
 * walk-in path can never drift from the lookup and scan paths.
 *
 * @property Patient $resource
 */
class DeskPatientResource extends ApiResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return ProviderPatientSummary::from($this->resource);
    }
}
