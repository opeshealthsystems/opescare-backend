<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Modules\Inventory\Services\PharmacyInventoryService;
use App\Modules\Inventory\Services\BloodInventoryService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryPortalController extends Controller
{
    /**
     * Provenance stamped on the public `blood_availability` row a staff entry
     * publishes. Without it the Blood Finder withholds the row as
     * unattributed — see App\Models\BloodAvailability::scopeReportedByRealSource().
     */
    private const BLOOD_SOURCE = 'portal';

    public function __construct(
        private PharmacyInventoryService $pharmacyService,
        private BloodInventoryService    $bloodService,
        private PortalContextService     $context,
    ) {}

    // ── Helpers ──────────────────────────────────────────────

    private function demoFacilityId(): string
    {
        return Facility::value('id') ?? '';
    }

    /**
     * The facility a blood-bank write belongs to.
     *
     * The blood screen is the only reachable writer of the public Blood Finder
     * signal, so "which fridge is this?" has to be answered from the signed-in
     * user's session — the resolution RequireFacilityContext already
     * guarantees for every route in this group — and not from
     * `Facility::value('id')`, which is whichever row Postgres hands back
     * first. Under that helper a clerk in Bamenda published Douala's stock.
     *
     * The single-facility fallback is honoured only when there really is
     * exactly one facility — the condition that made it safe. Production has
     * 345, and RequireFacilityContext is bypassed for platform-admin roles, so
     * an unguarded fallback would let an admin publish an arbitrary hospital's
     * blood stock to the public finder without ever choosing it. With more
     * than one facility and no resolved context there is no safe guess, so
     * this fails closed instead.
     */
    private function bloodFacilityId(): string
    {
        $resolved = $this->context->facilityId();

        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        abort_unless(
            Facility::count() === 1,
            409,
            'No facility is selected for this session, so there is no way to tell '
            . 'which blood bank this entry belongs to. Select a facility first.'
        );

        return $this->demoFacilityId();
    }

    // ── Pharmacy Inventory ───────────────────────────────────

    public function pharmacy(Request $request): View
    {
        $facilityId = $this->demoFacilityId();
        $items      = $this->pharmacyService->list($facilityId, $request->only([
            'stock_status', 'form', 'is_expired', 'search',
        ]));
        $summary    = $this->pharmacyService->summary($facilityId);
        $forms      = $items->pluck('form')->filter()->unique()->sort()->values();

        return view('portals.staff.inventory.pharmacy', compact('items', 'summary', 'forms'));
    }

    public function pharmacyStore(Request $request): RedirectResponse
    {
        $request->validate([
            'medicine_name' => 'required|string|max:200',
            'generic_name'  => 'required|string|max:200',
            'form'          => 'required|string|max:80',
            'strength'      => 'required|string|max:80',
            'available_quantity' => 'required|integer|min:0',
        ]);

        $this->pharmacyService->addItem($this->demoFacilityId(), $request->except(['_token']));

        return redirect()->route('portals.staff.inventory.pharmacy')
            ->with('success', __('flash.inventory_item_added'));
    }

    public function pharmacyRestock(Request $request, string $id): RedirectResponse
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        $this->pharmacyService->adjustQuantity($id, (int) $request->input('quantity'), 'add');
        return redirect()->route('portals.staff.inventory.pharmacy')
            ->with('success', __('flash.inventory_restocked'));
    }

    public function pharmacyDispense(Request $request, string $id): RedirectResponse
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        try {
            $this->pharmacyService->adjustQuantity($id, (int) $request->input('quantity'), 'subtract');
            return redirect()->route('portals.staff.inventory.pharmacy')
                ->with('success', __('flash.inventory_dispensed'));
        } catch (\Throwable $e) {
            return redirect()->route('portals.staff.inventory.pharmacy')
                ->with('error', $e->getMessage());
        }
    }

    public function pharmacyFlag(Request $request, string $id): RedirectResponse
    {
        $this->pharmacyService->setFlags($id, $request->only([
            'is_expired', 'is_recalled', 'is_quarantined',
        ]));
        return redirect()->route('portals.staff.inventory.pharmacy')
            ->with('success', __('flash.inventory_item_flags_updated'));
    }

    public function pharmacyDelete(string $id): RedirectResponse
    {
        $this->pharmacyService->removeItem($id);
        return redirect()->route('portals.staff.inventory.pharmacy')
            ->with('success', __('flash.inventory_item_removed'));
    }

    // ── Blood Inventory ───────────────────────────────────────

    public function blood(Request $request): View
    {
        $facilityId = $this->bloodFacilityId();
        $items      = $this->bloodService->list($facilityId, $request->only(['blood_group', 'component']));
        $summary    = $this->bloodService->summary($facilityId);

        // Does anything a patient can search actually point at this facility?
        // BloodAvailabilityProjector publishes onto `care_facilities` rows
        // linked back by `facility_id`, and returns 0 — silently, and
        // correctly — when there are none. Without this the screen accepts
        // stock all day and the Blood Finder never changes, which is the exact
        // failure this whole path was built to end.
        $publishesToFinder = CareFacility::query()
            ->where('facility_id', $facilityId)
            ->exists();

        return view('portals.staff.inventory.blood', compact('items', 'summary', 'publishesToFinder'));
    }

    public function bloodUpsert(Request $request): RedirectResponse
    {
        $request->validate([
            'blood_group'    => 'required|string|max:10',
            'component'      => 'required|string|max:80',
            'available_units'=> 'required|integer|min:0',
        ]);

        $this->bloodService->upsertUnit(
            $this->bloodFacilityId(),
            $request->except(['_token']),
            self::BLOOD_SOURCE,
        );

        return redirect()->route('portals.staff.inventory.blood')
            ->with('success', __('flash.blood_inventory_updated'));
    }

    public function bloodAdjust(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'units'     => 'required|integer|min:1',
            'direction' => 'required|in:add,subtract',
        ]);

        // Pinned to the acting user's facility: the route carries only an item
        // id, so without it one blood bank could adjust another's shelf and
        // republish it to the public finder.
        $this->bloodService->adjustUnits(
            $id,
            (int) $request->input('units'),
            $request->input('direction'),
            $this->bloodFacilityId(),
            self::BLOOD_SOURCE,
        );

        return redirect()->route('portals.staff.inventory.blood')
            ->with('success', __('flash.blood_stock_adjusted'));
    }

    public function bloodFlag(Request $request, string $id): RedirectResponse
    {
        $this->bloodService->setFlags(
            $id,
            $request->only(['is_expired', 'is_quarantined', 'is_unsafe']),
            $this->bloodFacilityId(),
            self::BLOOD_SOURCE,
        );

        return redirect()->route('portals.staff.inventory.blood')
            ->with('success', __('flash.blood_unit_flags_updated'));
    }
}
