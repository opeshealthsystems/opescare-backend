<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryPriceChange;
use App\Models\MedicinePharmacyStock;
use App\Models\User;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use App\Services\Pharmacy\PharmacyPriceService;
use App\Services\Portal\PortalContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * The pharmacy price list: every SKU of the session facility, its selling
 * price in whole francs, the price the finder currently shows if it differs,
 * and the append-only history behind each. The rules live in
 * PharmacyPriceService; this controller resolves who is writing and
 * validates what they sent.
 *
 * The facility is read from PortalContextService only — never a header,
 * never the body, never a fallback. An item of another pharmacy is not
 * refused: it does not exist (404). No patient appears on these screens;
 * the search box matches the item's own name, which is catalogue data.
 */
class PharmacyPriceListController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly PharmacyPriceService $prices,
        private readonly PharmacyStockReportService $stockReports,
    ) {
    }

    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();
        $search     = trim((string) $request->input('q'));

        $q = $this->items($facilityId)->with(['medicine', 'priceChanges' => fn ($h) => $h->latest('created_at')->limit(1)]);

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $q->where(function (Builder $w) use ($like) {
                $w->where('name', 'ilike', $like)
                  ->orWhereHas('medicine', fn (Builder $m) => $m->where('name', 'ilike', $like)->orWhere('generic_name', 'ilike', $like));
            });
        }

        $items = $q->orderBy('name')->get();

        // The finder rows for this pharmacy, keyed by medicine.
        $listing   = $this->stockReports->listingFor($facilityId);
        $published = $listing
            ? MedicinePharmacyStock::query()
                ->where('care_facility_id', $listing->id)
                ->whereIn('medicine_id', $items->pluck('medicine_id')->filter())
                ->get()
                ->keyBy('medicine_id')
            : collect();

        $actors = User::query()
            ->whereIn('id', $items->map(fn ($i) => $i->priceChanges->first()?->changed_by)->filter()->unique())
            ->pluck('name', 'id');

        return view('portals.pharmacy.prices.index', [
            'items'      => $items,
            'published'  => $published,
            'hasListing' => $listing !== null,
            'actors'     => $actors,
            'search'     => $search,
            'unpriced'   => $items->whereNull('selling_price_xaf')->count(),
            'maxPrice'   => PharmacyPriceService::MAX_PRICE_XAF,
        ]);
    }

    public function update(Request $request, string $item): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $inventoryItem = $this->items($facilityId)->findOrFail($item);

        $data = $request->validate([
            'price_xaf' => ['required', 'integer', 'min:0', 'max:' . PharmacyPriceService::MAX_PRICE_XAF],
            'reason'    => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $this->prices->setPrice($inventoryItem, (int) $data['price_xaf'], $this->context->actorId(), $data['reason'] ?? null);
        } catch (InvalidArgumentException) {
            return back()->withInput()->withErrors(['price_xaf' => __('pharmacy_prices.err_price')]);
        }

        return redirect()->route('portals.pharmacy.prices.index', array_filter(['q' => $request->input('q')]))
            ->with('success', __('pharmacy_prices.flash_saved', ['name' => $inventoryItem->medicine?->name ?? $inventoryItem->name]));
    }

    public function history(string $item): View
    {
        $facilityId = $this->facilityId();

        $inventoryItem = $this->items($facilityId)->with('medicine')->findOrFail($item);

        $changes = InventoryPriceChange::query()
            ->where('facility_id', $facilityId)
            ->where('inventory_item_id', $inventoryItem->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(40);

        $actors = User::query()
            ->whereIn('id', $changes->getCollection()->pluck('changed_by')->filter()->unique())
            ->pluck('name', 'id');

        return view('portals.pharmacy.prices.history', [
            'item'    => $inventoryItem,
            'changes' => $changes,
            'actors'  => $actors,
        ]);
    }

    private function items(string $facilityId): Builder
    {
        return InventoryItem::query()->where('facility_id', $facilityId);
    }

    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('pharmacy_prices.no_facility_context'));
        }

        return $facilityId;
    }
}
