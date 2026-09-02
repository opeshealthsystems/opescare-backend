<?php

namespace App\Modules\Inventory\Services;

use App\Models\PharmacyInventory;
use Illuminate\Support\Collection;

class PharmacyInventoryService
{
    public function list(string $facilityId, array $filters = []): Collection
    {
        $query = PharmacyInventory::where('facility_id', $facilityId)
            ->orderBy('medicine_name');

        if (!empty($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }
        if (!empty($filters['form'])) {
            $query->where('form', $filters['form']);
        }
        if (isset($filters['is_expired']) && $filters['is_expired'] !== '') {
            $query->where('is_expired', (bool) $filters['is_expired']);
        }
        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(function ($sub) use ($q) {
                $sub->where('medicine_name', 'like', "%{$q}%")
                    ->orWhere('generic_name', 'like', "%{$q}%")
                    ->orWhere('strength', 'like', "%{$q}%");
            });
        }

        return $query->get();
    }

    public function addItem(string $facilityId, array $data): PharmacyInventory
    {
        $data['facility_id']      = $facilityId;
        $data['stock_status']     = $this->deriveStatus($data['available_quantity'] ?? 0);
        $data['last_stock_update'] = now();
        return PharmacyInventory::create($data);
    }

    public function adjustQuantity(string $itemId, int $delta, string $direction = 'add'): PharmacyInventory
    {
        $item = PharmacyInventory::findOrFail($itemId);

        if ($direction === 'add') {
            $item->available_quantity = max(0, $item->available_quantity + $delta);
        } else {
            $item->available_quantity = max(0, $item->available_quantity - $delta);
        }

        $item->stock_status      = $this->deriveStatus($item->available_quantity);
        $item->last_stock_update = now();
        $item->save();
        return $item;
    }

    public function setFlags(string $itemId, array $flags): PharmacyInventory
    {
        $allowed = ['is_expired', 'is_recalled', 'is_quarantined'];
        $update  = array_intersect_key($flags, array_flip($allowed));
        $item    = PharmacyInventory::findOrFail($itemId);
        $item->update($update);
        return $item;
    }

    public function removeItem(string $itemId): void
    {
        PharmacyInventory::findOrFail($itemId)->delete();
    }

    public function summary(string $facilityId): array
    {
        $items = PharmacyInventory::where('facility_id', $facilityId)->get();
        return [
            'total'       => $items->count(),
            'in_stock'    => $items->where('stock_status', 'in_stock')->count(),
            'low_stock'   => $items->where('stock_status', 'low_stock')->count(),
            'out_of_stock'=> $items->where('stock_status', 'out_of_stock')->count(),
            'expired'     => $items->where('is_expired', true)->count(),
            'recalled'    => $items->where('is_recalled', true)->count(),
            'quarantined' => $items->where('is_quarantined', true)->count(),
        ];
    }

    private function deriveStatus(int $qty): string
    {
        if ($qty <= 0)  return 'out_of_stock';
        if ($qty <= 10) return 'low_stock';
        return 'in_stock';
    }
}
