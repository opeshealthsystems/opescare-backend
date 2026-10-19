<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\MedicineReservation;
use App\Services\Pharmacy\PharmacyDeskException;
use App\Services\Pharmacy\PharmacyDeskService;
use App\Services\Portal\PortalContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The pharmacy orders desk (web): patient medicine reservations placed at
 * this pharmacy, worked from a browser instead of the staff app.
 *
 * Thin on purpose. Reading goes through PharmacyDeskService::reservations()
 * and findReservation() — the pharmacy's listing ids come from
 * care_facilities.facility_id = the session facility — and every transition
 * goes through PharmacyDeskService::transitionReservation(), the SAME state
 * machine the mobile desk (PharmacyReservationController) calls. Nothing
 * about the lifecycle is re-implemented here, so the two surfaces can never
 * disagree on what a hold may become.
 *
 * ── SCOPE ──────────────────────────────────────────────────────────────────
 * The facility is read from PortalContextService only. A reservation placed
 * at another pharmacy is not refused: it does not exist (404), which is what
 * findReservation() throws. The role gate is `capability:dispense` on the
 * routes (403 `role_forbidden`).
 *
 * Forward-only: accept → ready → collected (pickup) or dispatch → delivered
 * (delivery); any open hold → cancelled with a reason. Nothing is deleted.
 */
class PharmacyOrdersController extends Controller
{
    /** Tab → the desk statuses it lists. Pending is the default: it needs a hand. */
    public const TABS = [
        'pending'     => [MedicineReservation::DESK_PENDING],
        'in_progress' => [MedicineReservation::DESK_ACCEPTED, MedicineReservation::DESK_READY, MedicineReservation::DESK_DISPATCHED],
        'done'        => [MedicineReservation::DESK_COLLECTED, MedicineReservation::DESK_DELIVERED, MedicineReservation::DESK_CANCELLED],
    ];

    public function __construct(
        private readonly PortalContextService $context,
        private readonly PharmacyDeskService $desk,
    ) {
    }

    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();

        $tab = (string) $request->query('tab', 'pending');
        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'pending';
        }

        $counts = [];
        foreach (self::TABS as $name => $statuses) {
            $counts[$name] = $this->rowsFor($facilityId, $statuses)->count();
        }

        return view('portals.pharmacy.orders.index', [
            'tab'          => $tab,
            'tabs'         => array_keys(self::TABS),
            'counts'       => $counts,
            'reservations' => $this->paginate($this->rowsFor($facilityId, self::TABS[$tab]), $request),
            // No linked directory listing = patients cannot find this pharmacy,
            // so an empty desk is not "no orders yet" — the view says why.
            'hasListing'   => $this->desk->listingIds($facilityId) !== [],
        ]);
    }

    public function show(string $id): View
    {
        $reservation = $this->find($id);

        return view('portals.pharmacy.orders.show', [
            'reservation' => $reservation,
            'desk'        => $reservation->deskStatus(),
            'stockRecorded' => $this->desk->stockRecordedFor($reservation),
        ]);
    }

    public function accept(string $id): RedirectResponse
    {
        return $this->transition($id, 'accept');
    }

    public function ready(string $id): RedirectResponse
    {
        return $this->transition($id, 'ready');
    }

    public function collected(string $id): RedirectResponse
    {
        return $this->transition($id, 'collected');
    }

    public function dispatch(string $id): RedirectResponse
    {
        return $this->transition($id, 'dispatch');
    }

    public function delivered(string $id): RedirectResponse
    {
        return $this->transition($id, 'delivered');
    }

    /** The reason is required: the patient is told why their hold was released. */
    public function cancel(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:300'],
        ]);

        return $this->transition($id, 'cancel', $data['reason']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * @param  list<string>  $statuses
     * @return Collection<int, MedicineReservation>
     */
    private function rowsFor(string $facilityId, array $statuses): Collection
    {
        $rows = collect();
        foreach ($statuses as $status) {
            $rows = $rows->concat($this->desk->reservations($facilityId, $status));
        }

        return $rows->sortByDesc('created_at')->values();
    }

    /**
     * The desk service returns one merged, sorted collection per tab (several
     * statuses), so the page is cut here rather than in SQL.
     */
    private function paginate(Collection $rows, Request $request, int $perPage = 25): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));

        return (new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page'],
        ))->withQueryString();
    }

    private function find(string $id): MedicineReservation
    {
        try {
            return $this->desk->findReservation($this->facilityId(), $id);
        } catch (PharmacyDeskException $e) {
            abort($e->status, $e->getMessage());
        }
    }

    /** @param  'accept'|'ready'|'collected'|'dispatch'|'delivered'|'cancel'  $action */
    private function transition(string $id, string $action, ?string $reason = null): RedirectResponse
    {
        $facilityId  = $this->facilityId();
        $reservation = $this->find($id);

        try {
            $this->desk->transitionReservation(
                $reservation,
                $action,
                $reason,
                Facility::query()->whereKey($facilityId)->value('name'),
            );
        } catch (PharmacyDeskException $e) {
            // A wrong step (409) is a message, not an error page: the desk
            // stays on the order and reads why.
            if ($e->status === 404) {
                abort(404, $e->getMessage());
            }

            return redirect()->route('portals.pharmacy.orders.show', $reservation->id)
                ->with('error', $e->getMessage());
        }

        $redirect = redirect()->route('portals.pharmacy.orders.show', $reservation->id)
            ->with('success', __('pharmacy_orders.flash_' . $action, ['reference' => $reservation->reference]));

        // The hand-over stands either way; if the ledger could not take the
        // packs off (no ledger item, too few usable packs) the counter is told
        // plainly instead of the finder silently over-promising.
        if (in_array($action, ['collected', 'delivered'], true) && $this->desk->stockRecordedFor($reservation) === 0) {
            $redirect->with('warning', __('pharmacy_orders.flash_stock_not_recorded'));
        }

        return $redirect;
    }

    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('pharmacy_orders.no_facility_context'));
        }

        return $facilityId;
    }
}
