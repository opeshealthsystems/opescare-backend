<?php

namespace App\Http\Resources\Desk;

use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * A clinician posted at the desk's facility — someone an appointment can be
 * routed to. `role` is the role held at THIS facility (FrontDeskService stamps
 * it as `desk_role`), so a nurse here who is a doctor elsewhere is listed as
 * a nurse.
 *
 * @property User $resource
 */
class DeskProviderResource extends ApiResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->resource->id,
            'name' => $this->resource->name,
            'role' => $this->resource->getAttribute('desk_role') ?? $this->resource->role?->name,
        ];
    }
}
