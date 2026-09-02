<?php

namespace App\Http\Controllers\Api\V1\Sdk;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SdkFacilityController extends Controller
{
    /**
     * Return facility profile by ID.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $facility = Facility::find($id);

        if (!$facility) {
            return response()->json(['error' => 'not_found', 'message' => __('api.facility_not_found')], 404);
        }

        return response()->json([
            'id'       => $facility->id,
            'name'     => $facility->name,
            'type'     => $facility->type ?? null,
            'address'  => $facility->address ?? null,
            'phone'    => $facility->phone ?? null,
            'email'    => $facility->email ?? null,
            'timezone' => $facility->timezone ?? 'UTC',
        ]);
    }

    /**
     * Return a compact stock-level summary for the facility.
     */
    public function stockSummary(Request $request, string $id): JsonResponse
    {
        $facility = Facility::find($id);

        if (!$facility) {
            return response()->json(['error' => 'not_found', 'message' => __('api.facility_not_found')], 404);
        }

        $items = InventoryItem::where('facility_id', $id)
            ->where('status', 'active')
            ->get(['id', 'name', 'code', 'unit', 'category', 'reorder_level']);

        $stock = $items->map(fn($item) => [
            'item_id'       => $item->id,
            'name'          => $item->name,
            'code'          => $item->code,
            'unit'          => $item->unit,
            'category'      => $item->category,
            'quantity'      => $item->totalStock($id),
            'reorder_level' => $item->reorder_level,
            'status'        => $item->totalStock($id) <= ($item->reorder_level ?? 0) ? 'low' : 'ok',
        ]);

        return response()->json([
            'facility_id'  => $id,
            'item_count'   => $stock->count(),
            'low_stock'    => $stock->where('status', 'low')->count(),
            'items'        => $stock->values(),
            'retrieved_at' => now()->toIso8601String(),
        ]);
    }
}
