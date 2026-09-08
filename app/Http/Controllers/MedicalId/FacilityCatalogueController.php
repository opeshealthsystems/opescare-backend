<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\ClinicalCodeDictionary;
use App\Models\FacilityCatalogueItem;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FacilityCatalogueController — "what we offer", the facility's own service list.
 *
 * The institutional dictionary knows every LOINC term and every ICD-10
 * category. It does not know, and cannot know, which of them any particular
 * hospital, laboratory or pharmacy actually performs. Until a facility says
 * so, the finder can only offer a patient a list of places and a list of
 * tests, and no way to cross the two. This screen is where a facility says so.
 *
 * Three rules run through everything below.
 *
 * 1. THE FACILITY IS NEVER TAKEN FROM THE REQUEST. It comes from
 *    PortalContextService::facilityId() — the session context that
 *    RequireFacilityContext established from the user's real postings — and it
 *    scopes every read and every write. There is no facility_id form field, no
 *    header, no query parameter and no "the one facility we found" fallback.
 *    An account with no facility context is refused outright rather than
 *    silently handed somebody's hospital.
 *
 * 2. A ROW BELONGING TO ANOTHER FACILITY DOES NOT EXIST. Lookups are
 *    `where('facility_id', $mine)->findOrFail()`, so guessing a uuid from
 *    another hospital's catalogue returns 404, not 403. A 403 would confirm
 *    the row is real; the existence of another facility's price list is not
 *    something this screen is willing to confirm.
 *
 * 3. REMOVAL IS DEACTIVATION, NEVER A DELETE. See below.
 *
 * ── Why "remove" does not delete ─────────────────────────────────────────
 * A catalogue row is referenced by value, not by key: an order, a quote, a
 * finder search result and an audit line all name the facility, the system and
 * the code, and none of them holds a foreign key the database could refuse to
 * break. So a hard delete breaks nothing loudly. It just turns "this hospital
 * offered this test at this price" into "it never did", quietly, and the price
 * a patient was quoted last week becomes unaccountable. Retiring the row keeps
 * the claim and its author on the record, and re-adding the service revives
 * the same row rather than starting a second, historyless one.
 *
 * ── Prices are information, never a gate ─────────────────────────────────
 * `price_xaf` is what the facility charges, in whole francs, published so a
 * patient can decide where to go. Nothing here reads it to permit or refuse
 * anything, and nothing downstream may: care is never gated on payment, and
 * CareIsNeverGatedOnPaymentTest holds that line platform-wide.
 *
 * ── No patient data ──────────────────────────────────────────────────────
 * This controller touches no patient row, so it emits no access log — the log
 * that matters is who opened whose record, and burying it under service-list
 * edits would make it useless. The dictionary browse reads WHO and LOINC
 * reference data, which its publishers publish in full and which has nothing
 * to enumerate.
 */
class FacilityCatalogueController extends Controller
{
    public function __construct(private readonly PortalContextService $ctx) {}

    /**
     * The facility this request acts on. Session-resolved, never
     * request-resolved. An account with no facility context is refused rather
     * than defaulted — there is no safe default for "whose catalogue is this".
     */
    private function facilityId(): string
    {
        $facilityId = $this->ctx->facilityId();

        abort_if($facilityId === null, 403, __('api.capability_forbidden'));

        return $facilityId;
    }

    // ── The list ─────────────────────────────────────────────────────────

    /**
     * Everything this facility offers, retired entries included.
     *
     * Retired rows are shown behind a filter rather than hidden for good: a
     * manager who cannot see that a test was retired last month will simply
     * add it again and wonder why the old price came back.
     */
    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();

        $show = $request->query('show') === 'retired' ? 'retired' : 'active';

        $items = FacilityCatalogueItem::query()
            ->where('facility_id', $facilityId)
            ->where('is_active', $show === 'active')
            ->when($request->query('system'), fn ($q, $s) => $q->where('system', $s))
            ->orderBy('system')
            ->orderBy('code')
            ->paginate(50)
            ->withQueryString();

        // One query for the page, not one per row. See dictionaryFor().
        $dictionary = FacilityCatalogueItem::dictionaryFor($items);

        // Counts for the two tabs, and the systems this facility actually uses
        // — never a hardcoded ['ICD-10', 'LOINC'], because imaging, procedures,
        // departments and specialties are being imported alongside this.
        $counts = FacilityCatalogueItem::query()
            ->where('facility_id', $facilityId)
            ->selectRaw('count(*) filter (where is_active) as active')
            ->selectRaw('count(*) filter (where not is_active) as retired')
            ->first();

        $systems = FacilityCatalogueItem::query()
            ->where('facility_id', $facilityId)
            ->select('system')->distinct()->orderBy('system')->pluck('system');

        return view('portals.facility.catalogue.index', [
            'items'      => $items,
            'dictionary' => $dictionary,
            'counts'     => $counts,
            'systems'    => $systems,
            'show'       => $show,
            'system'     => $request->query('system'),
        ]);
    }

    // ── Browse the dictionary ────────────────────────────────────────────

    /**
     * Search the institutional dictionary for things to add.
     *
     * The system filter is built from what is actually loaded, so the day the
     * imaging importer lands its codes are offered here without a line of this
     * file changing. Headings — ICD-10 chapters and blocks, ATC groups — are excluded by
     * `offerable()`, NOT by `assignable()`: a facility offers a test, not a
     * chapter of the ICD, but it does offer procedures and vaccines, and
     * `assignable()` (which answers "may a clinician record this against a
     * patient") hid every one of them from this screen.
     *
     * Codes already on this facility's list are marked as such instead of
     * being filtered out, so the answer to "did I already add this?" is on the
     * screen rather than in the manager's memory.
     */
    public function browse(Request $request): View
    {
        $facilityId = $this->facilityId();

        $systems = ClinicalCodeDictionary::query()
            ->select('system')->distinct()->orderBy('system')->pluck('system');

        $system = $request->query('system');
        $system = in_array($system, $systems->all(), true) ? $system : null;

        $codes = ClinicalCodeDictionary::query()
            ->search($request->query('q'))
            ->when($system, fn ($q) => $q->where('system', $system))
            ->when($request->boolean('common'), fn ($q) => $q->where('is_common', true))
            ->offerable()
            ->orderBy('system')
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        // Which of these results this facility already carries, and whether the
        // existing row is live or retired.
        $existing = FacilityCatalogueItem::query()
            ->where('facility_id', $facilityId)
            ->whereIn('code', $codes->pluck('code')->all())
            ->get()
            ->keyBy(fn ($i) => $i->dictionaryKey());

        // A total of zero means no importer has ever run here, which is a very
        // different thing from a search that found nothing. The view must not
        // let the two look the same.
        $dictionaryTotal = ClinicalCodeDictionary::query()->count();

        return view('portals.facility.catalogue.browse', [
            'codes'           => $codes,
            'existing'        => $existing,
            'systems'         => $systems,
            'system'          => $system,
            'dictionaryTotal' => $dictionaryTotal,
        ]);
    }

    // ── Add ──────────────────────────────────────────────────────────────

    /**
     * Put a dictionary entry on this facility's list.
     *
     * The (system, code) pair is validated against the dictionary itself, not
     * against a list of strings: a facility may only offer something the
     * institutional vocabulary actually names, and `offerable()` keeps
     * headings and withdrawn codes out. Re-adding something retired revives
     * the original row — the unique key makes a second row impossible, and
     * reviving is the honest behaviour anyway.
     */
    public function store(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $data = $request->validate([
            'system'     => ['required', 'string', 'max:20'],
            'code'       => ['required', 'string', 'max:40'],
            'local_name' => ['nullable', 'string', 'max:200'],
            // Whole francs. XAF has no minor unit, so a decimal here is not a
            // rounding question, it is a category error — refuse it.
            'price_xaf'  => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'turnaround' => ['nullable', 'string', 'max:120'],
            'notes'      => ['nullable', 'string', 'max:2000'],
        ]);

        $known = ClinicalCodeDictionary::query()
            ->offerable()
            ->where('system', $data['system'])
            ->where('code', $data['code'])
            ->exists();

        if (! $known) {
            return back()->withInput()->with('error', __('catalogue.flash_unknown_code'));
        }

        $item = FacilityCatalogueItem::query()->firstOrNew([
            'facility_id' => $facilityId,
            'system'      => $data['system'],
            'code'        => $data['code'],
        ]);

        $revived = $item->exists && ! $item->is_active;

        $item->fill([
            'local_name'     => $data['local_name'] ?? $item->local_name,
            'price_xaf'      => $data['price_xaf'] ?? $item->price_xaf,
            'turnaround'     => $data['turnaround'] ?? $item->turnaround,
            'notes'          => $data['notes'] ?? $item->notes,
            'is_active'      => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
        ]);

        $item->added_by = $item->added_by ?? $this->ctx->actorId();
        $item->save();

        return back()->with('success', $revived
            ? __('catalogue.flash_restored')
            : __('catalogue.flash_added'));
    }

    // ── Edit the facility's own half ─────────────────────────────────────

    /**
     * Change the local name, the price, the turnaround or the notes.
     *
     * The system and the code are NOT editable. They are the published
     * identity of the concept; changing them would not be an edit, it would be
     * a different service wearing the first one's history and price.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $item = $this->findForThisFacility($id);

        $data = $request->validate([
            'local_name' => ['nullable', 'string', 'max:200'],
            'price_xaf'  => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'turnaround' => ['nullable', 'string', 'max:120'],
            'notes'      => ['nullable', 'string', 'max:2000'],
        ]);

        // Absent is not zero, and here it is not "unchanged" either: a manager
        // who clears the price field is saying "we no longer publish a price",
        // and the column is nullable exactly so that can be said.
        $item->fill([
            'local_name' => $data['local_name'] ?? null,
            'price_xaf'  => $data['price_xaf'] ?? null,
            'turnaround' => $data['turnaround'] ?? null,
            'notes'      => $data['notes'] ?? null,
        ])->save();

        return back()->with('success', __('catalogue.flash_updated'));
    }

    // ── Retire / restore ─────────────────────────────────────────────────

    /** Stop offering a service. Retires the row; never deletes it. */
    public function deactivate(Request $request, string $id): RedirectResponse
    {
        $item = $this->findForThisFacility($id);

        $item->fill([
            'is_active'      => false,
            'deactivated_at' => now(),
            'deactivated_by' => $this->ctx->actorId(),
        ])->save();

        return back()->with('success', __('catalogue.flash_retired'));
    }

    /** Offer it again. Same row, same history. */
    public function reactivate(Request $request, string $id): RedirectResponse
    {
        $item = $this->findForThisFacility($id);

        $item->fill([
            'is_active'      => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
        ])->save();

        return back()->with('success', __('catalogue.flash_restored'));
    }

    /**
     * One row of THIS facility's catalogue, or 404.
     *
     * The facility scope is part of the lookup, not a check after it. A row
     * belonging to another facility is not refused, it is not found — the same
     * answer a uuid that never existed gets, which is the only answer that
     * does not confirm another hospital's price list is real.
     */
    private function findForThisFacility(string $id): FacilityCatalogueItem
    {
        return FacilityCatalogueItem::query()
            ->where('facility_id', $this->facilityId())
            ->findOrFail($id);
    }
}
