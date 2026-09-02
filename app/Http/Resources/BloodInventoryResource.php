<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\BloodInventory row (blood_inventories).
 * Add fields here deliberately; never expose the model directly.
 */
class BloodInventoryResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'facility_id'       => $this->facility_id,
            'blood_group'       => $this->blood_group,
            'component'         => $this->component,
            'available_units'   => $this->available_units,
            'is_expired'        => (bool) $this->is_expired,
            'is_quarantined'    => (bool) $this->is_quarantined,
            'is_unsafe'         => (bool) $this->is_unsafe,
            'last_stock_update' => $this->last_stock_update?->toISOString(),
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
