<?php

namespace App\Http\Controllers\MedicalId;

use App\Enums\PharmacyStockStatus;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\Patient;
use App\Models\PharmacyInventory;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PharmacyPortalController extends Controller
{
    public function __construct(
        private readonly PharmacyStockReportService $stockReports,
    ) {
    }

    /**
     * INN / keyword list for identifying controlled substances. OpesCare has no
     * dedicated drug-schedule column yet, so the controlled-substances register
     * matches these standard controlled-drug names (opioids, benzodiazepines,
     * barbiturates, stimulants) against the medicine / generic name.
     */
    public const CONTROLLED_KEYWORDS = [
        'morphine', 'pethidine', 'fentanyl', 'codeine', 'tramadol', 'oxycodone',
        'hydromorphone', 'methadone', 'buprenorphine', 'pentazocine', 'nalbuphine',
        'tapentadol', 'diazepam', 'lorazepam', 'midazolam', 'clonazepam', 'alprazolam',
        'bromazepam', 'phenobarbital', 'pentobarbital', 'secobarbital', 'ketamine',
        'pregabalin', 'zolpidem', 'amphetamine', 'methylphenidate',
    ];

    private function facilityId(): ?string
    {
        return session('active_facility_id')
            ?? auth()->user()?->primary_facility_id
            ?? Facility::value('id');
    }

    /**
     * The facility a stock report may be written against.
     *
     * Deliberately narrower than facilityId(): the `Facility::value('id')`
     * fallback there resolves to "whatever facility happens to be first in the
     * table", which is harmless for an empty read but would let a user with no
     * facility context publish stock in a stranger's name.
     */
    private function writableFacilityId(): ?string
    {
        return session('active_facility_id')
            ?? auth()->user()?->primary_facility_id;
    }

    // ------------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------------

    public function dashboard()
    {
        $facilityId = $this->facilityId();

        $stats = [
            'pending_rx'     => Prescription::where('facility_id', $facilityId)
                                    ->whereIn('status', ['active', 'partially_dispensed'])
                                    ->count(),
            'dispensed_today' => Prescription::where('facility_id', $facilityId)
                                    ->where('status', 'dispensed')
                                    ->whereDate('dispensed_at', today())
                                    ->count(),
            'total_drugs'    => PharmacyInventory::where('facility_id', $facilityId)->count(),
            'low_stock'      => PharmacyInventory::where('facility_id', $facilityId)
                                    ->where('stock_status', 'low_stock')
                                    ->count(),
            'expired'        => PharmacyInventory::where('facility_id', $facilityId)
                                    ->where('is_expired', true)
                                    ->count(),
            'out_of_stock'   => PharmacyInventory::where('facility_id', $facilityId)
                                    ->where('stock_status', 'out_of_stock')
                                    ->count(),
        ];

        $pendingRx = Prescription::with(['patient', 'items'])
            ->where('facility_id', $facilityId)
            ->whereIn('status', ['active', 'partially_dispensed'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $alerts = PharmacyInventory::where('facility_id', $facilityId)
            ->where(function ($q) {
                $q->where('is_expired', true)
                  ->orWhere('stock_status', 'out_of_stock')
                  ->orWhere('stock_status', 'low_stock');
            })
            ->orderByRaw("CASE stock_status WHEN 'out_of_stock' THEN 0 WHEN 'low_stock' THEN 1 ELSE 2 END")
            ->limit(6)
            ->get();

        return view('portals.pharmacy.dashboard', compact('stats', 'pendingRx', 'alerts'));
    }

    // ------------------------------------------------------------------
    // Prescription Queue
    // ------------------------------------------------------------------

    public function prescriptions(Request $req)
    {
        $facilityId = $this->facilityId();

        $q = Prescription::with(['patient', 'items'])
            ->where('facility_id', $facilityId);

        if ($status = $req->input('status')) {
            $q->where('status', $status);
        } else {
            $q->whereIn('status', ['active', 'partially_dispensed']);
        }

        if ($search = $req->input('search')) {
            /*
             * EXACT HEALTH ID, NEVER A NAME SEARCH.
             *
             * This read `where('full_name', 'like', "%{$search}%")`, which was
             * wrong twice over.
             *
             * It crashed. `patients` carries first_name / middle_name /
             * last_name and no `full_name` at all, so every use of the search
             * box on this screen was a 500 — SQLSTATE 42703, undefined column.
             *
             * And had that column existed, the result would have been worse
             * than the crash: a LIKE across patient names is precisely the
             * enumeration search this platform forbids. Type "a" at a pharmacy
             * counter and you walk the patient register. The bug was the only
             * thing preventing it.
             *
             * `resolveHealthId()` is what the rest of the platform uses — this
             * same controller already calls it at the dispensing counter — and
             * it matches one patient exactly, following an alias and following
             * a merge. Finding nothing is the correct answer to a partial
             * Health ID.
             */
            $patient = \App\Models\Patient::resolveHealthId($search);

            // No match must return NO prescriptions, not all of them. Omitting
            // the clause when the lookup fails would turn a failed search into
            // "show me every prescription in this facility".
            $q->where('patient_id', $patient?->id ?? '00000000-0000-0000-0000-000000000000');
        }

        $prescriptions = $q->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('portals.pharmacy.prescriptions', compact('prescriptions'));
    }

    // ------------------------------------------------------------------
    // Dispense (mark a prescription as dispensed)
    // ------------------------------------------------------------------

    /**
     * The alphabet a prescription reference is drawn from: Crockford's base32,
     * which omits I, L, O and U precisely so that a handle read down a phone or
     * copied off a photocopy cannot turn a 1 into an I or a 0 into an O.
     *
     * Mirrors the alphabet the `prescriptions.reference` backfill migration uses;
     * kept here as the read-side contract for what a valid reference looks like.
     */
    private const REFERENCE_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Canonicalise whatever the pharmacist typed into `RX-XXXX-XXXX`, or null
     * when it cannot be a prescription reference at all.
     *
     * The input side is deliberately generous, because this string is retyped
     * from a printed sheet, a fax, a photocopy or a phone screen: case is
     * ignored, spaces and hyphens are dropped wherever they fall, and the `RX`
     * prefix is optional. `rx4f2a9c31`, `RX 4F2A 9C31`, `4f2a-9c31` and
     * `RX-4F2A-9C31` are all the same handle.
     *
     * The alphabet is where it stops being generous — exactly eight Crockford
     * characters — and that is also what makes the one-box lookup unambiguous:
     * a Health ID is `CM-HID-XXXX-XXXX-XXXX`, seventeen characters once the
     * hyphens are stripped, so it can never be mistaken for a reference.
     *
     * The prefix-stripped reading is tried first and the whole string second, so
     * a reference that legitimately begins `RX-RX..` still resolves whether it is
     * typed with its prefix or without.
     */
    private function normaliseReference(string $raw): ?string
    {
        $compact = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $raw));

        $candidates = [];

        if (str_starts_with($compact, 'RX')) {
            $candidates[] = substr($compact, 2);
        }

        $candidates[] = $compact;

        foreach ($candidates as $candidate) {
            if (strlen($candidate) === 8 && strspn($candidate, self::REFERENCE_ALPHABET) === 8) {
                return 'RX-' . substr($candidate, 0, 4) . '-' . substr($candidate, 4, 4);
            }
        }

        return null;
    }

    /**
     * Counter lookup: the patient presents their Health ID **or** a printed
     * script, and the pharmacy sees what it may dispense — from ANY facility.
     *
     * One box, two kinds of handle. At a busy counter the pharmacist has
     * whichever the patient happens to be carrying: a phone showing the Health
     * ID, or a sheet of paper with a prescription number on it. Making them pick
     * a mode first buys nothing, because the two are trivially told apart — see
     * normaliseReference() — so the box just works out which was typed.
     *
     * Exact matches in both directions, never a prefix or LIKE search. Patient
     * enumeration is forbidden platform-wide, and a reference must resolve to one
     * prescription or to none: a partial `RX-4F2A` that returned a list would turn
     * a guess into a fishing expedition across other people's scripts.
     *
     * A reference deliberately ignores the issuing facility. That is the whole
     * point of it — a script written at a hospital is filled at an outside
     * pharmacy, and the dispense action is already gated on dispensability rather
     * than on who wrote it.
     */
    public function prescriptionLookup(Request $req)
    {
        // `q` is the single box. `health_id` is still honoured so an existing
        // bookmark or a link into this screen keeps resolving exactly as before.
        $query = trim((string) ($req->filled('q') ? $req->input('q') : $req->input('health_id', '')));

        $reference = $query === '' ? null : $this->normaliseReference($query);
        $mode      = $reference !== null ? 'reference' : 'health_id';
        $patient   = null;
        $results   = collect();

        if ($query !== '' && $reference !== null) {
            $prescription = Prescription::with([
                    'items',
                    'facility:id,name',
                    'patient',
                    'prescriber:id,name',
                ])
                ->where('reference', $reference)
                ->first();

            if ($prescription) {
                // The patient row can come back null — merged away, or filtered
                // by the demo-isolation scope. The screen still has to render the
                // script, so identity is shown defensively rather than assumed.
                $patient = $prescription->patient;
                $results = collect([$prescription]);
            }
        } elseif ($query !== '') {
            // Follows a merge: the card at the counter may carry a retired ID.
            $patient = \App\Models\Patient::resolveHealthId($query);

            if ($patient) {
                $results = Prescription::with(['items', 'facility:id,name', 'prescriber:id,name'])
                    ->where('patient_id', $patient->id)
                    ->whereIn('status', ['active', 'partially_dispensed'])
                    ->orderByDesc('prescribed_at')
                    ->get();
            }
        }

        return view('portals.pharmacy.prescription_lookup', [
            'query'     => $query,
            // Kept so the old variable name still resolves for anything that
            // reads it, and so the Health-ID path is unchanged from the view's side.
            'healthId'  => $mode === 'health_id' ? $query : '',
            'mode'      => $mode,
            'reference' => $reference,
            'patient'   => $patient,
            'results'   => $results,
            'searched'  => $query !== '',
        ]);
    }

    /**
     * Dispensing goes through PrescriptionService so that the item lines, the
     * dispenser, and the audit event are all recorded — and so a repeat POST is
     * refused rather than silently re-stamping a prescription that has already
     * been handed to the patient.
     *
     * The service also re-checks dispensability, which is what makes the screen's
     * hidden Dispense button a courtesy rather than the control: a voided,
     * cancelled, entered-in-error, amended, already-dispensed or lapsed script is
     * refused here even if the POST is replayed by hand.
     */
    public function dispense(Request $req, string $id, \App\Services\Clinical\PrescriptionService $prescriptions)
    {
        $facilityId = $this->facilityId();

        // A prescription is written at one facility and filled at another — the
        // patient carries it. Scoping this to the pharmacy's OWN facility made an
        // outside prescription impossible to dispense, which is the normal case.
        // The gate is that the pharmacist reached this id at the counter, via the
        // patient's exact Health ID or the reference printed on the script they
        // are holding (see prescriptionLookup) — ids are not guessable.
        $rx = Prescription::findOrFail($id);

        try {
            $prescriptions->dispense($rx, auth()->id(), ['notes' => $req->input('dispense_notes')]);
        } catch (\Throwable $e) {
            return redirect()->route('portals.pharmacy.prescriptions')
                ->with('error', __('flash.prescription_not_dispensable'));
        }

        return redirect()->route('portals.pharmacy.prescriptions')
            ->with('success', __('flash.prescription_dispensed'));
    }

    // ------------------------------------------------------------------
    // Public medicine availability — the rows the patient finder reads
    // ------------------------------------------------------------------

    /**
     * Reported public availability for this pharmacy's directory listing.
     *
     * This is the table `MedicineFinderService` queries, so what is edited
     * here is exactly what a patient searching nearby pharmacies sees.
     *
     * History worth keeping: this screen used to read `pharmacy_inventories`,
     * which no patient query has ever touched — a pharmacist updating stock
     * changed nothing anybody could see. It was repointed at
     * `medicine_pharmacy_stocks`. `pharmacy_inventories` is untouched and
     * still backs the dashboard counters and the controlled-substances
     * register (expiry, recall and quarantine live there and have no
     * equivalent on the public listing).
     *
     * A legacy `inventory()` alias for the old `/portals/pharmacy/inventory`
     * path was removed: it had no route and no caller, and a second entry
     * point to the same screen is one more thing to keep guarded for nobody.
     */
    public function stock(Request $req)
    {
        $listing = $this->stockReports->listingFor($this->writableFacilityId());
        $issues  = $this->stockReports->listingIssues($listing);

        $search = trim((string) $req->input('search'));
        $status = (string) $req->input('stock_status');

        $stocks   = null;
        $catalog  = collect();
        $coverage = ['total' => 0, 'reported' => 0, 'seeded' => 0];

        if ($listing) {
            $q = MedicinePharmacyStock::query()
                ->where('care_facility_id', $listing->id)
                ->with('medicine');

            if ($search !== '') {
                $q->whereHas('medicine', function ($m) use ($search) {
                    $m->where('name', 'ilike', "%{$search}%")
                      ->orWhere('generic_name', 'ilike', "%{$search}%")
                      ->orWhere('brand_name', 'ilike', "%{$search}%");
                });
            }

            if ($status !== '' && in_array($status, PharmacyStockStatus::values(), true)) {
                $q->where('stock_status', $status);
            }

            $stocks = $q
                ->orderBy(Medicine::select('name')
                    ->whereColumn('medicines.id', 'medicine_pharmacy_stocks.medicine_id'))
                ->paginate(30)
                ->withQueryString();

            // Catalog entries this pharmacy has never reported on — the
            // "add a medicine" picker. Capped so a 419-row catalog cannot
            // grow into an unbounded <select>.
            $catalog = Medicine::query()
                ->active()
                ->whereNotIn('id', MedicinePharmacyStock::query()
                    ->where('care_facility_id', $listing->id)
                    ->select('medicine_id'))
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'strength', 'form', 'default_pack_size', 'currency']);

            $coverage = $this->stockReports->coverage($listing);
        }

        return view('portals.pharmacy.inventory', [
            'listing'      => $listing,
            'issues'       => $issues,
            'stocks'       => $stocks,
            'catalog'      => $catalog,
            'coverage'     => $coverage,
            'statuses'     => PharmacyStockStatus::cases(),
            'portalSource' => PharmacyStockReportService::SOURCE_PORTAL,
        ]);
    }

    /**
     * Report one medicine's availability into `medicine_pharmacy_stocks`.
     *
     * Insert vs. update, the `last_reported_at` stamp, the `source_system`
     * marker and the overwrite audit all live in PharmacyStockReportService —
     * this method only resolves who is writing and validates what they sent.
     */
    public function reportStock(Request $req)
    {
        $listing = $this->stockReports->listingFor($this->writableFacilityId());

        if (! $listing) {
            return back()->with('error', __('public.pharmacy_portal.stock_err_unlinked'));
        }

        $validated = $req->validate([
            'medicine_id'         => ['required', 'uuid'],
            'stock_status'        => ['required', Rule::in(PharmacyStockStatus::values())],
            'packs_available'     => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'pack_size'           => ['nullable', 'string', 'max:60'],
            'unit_price'          => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'reservation_enabled' => ['nullable', 'boolean'],
        ]);

        $medicine = Medicine::query()->active()->find($validated['medicine_id']);

        if (! $medicine) {
            return back()->with('error', __('public.pharmacy_portal.stock_err_medicine'));
        }

        $this->stockReports->report($listing, $medicine, [
            'stock_status'        => $validated['stock_status'],
            'packs_available'     => $validated['packs_available'] ?? null,
            'pack_size'           => $validated['pack_size'] ?? null,
            'unit_price'          => $validated['unit_price'] ?? null,
            'reservation_enabled' => $req->boolean('reservation_enabled'),
        ], auth()->id());

        $this->stockReports->touchListingFreshness($listing);

        // `stock_status` in the body is the NEW status being reported; the list
        // filter travels separately so the pharmacist lands back where they were.
        return redirect()
            ->route('portals.pharmacy.stock', array_filter([
                'search'       => $req->input('search'),
                'stock_status' => $req->input('stock_status_filter'),
            ]))
            ->with('success', __('public.pharmacy_portal.stock_flash_reported', [
                'medicine' => $medicine->name,
            ]));
    }

    // ------------------------------------------------------------------
    // Controlled Substances Log
    // ------------------------------------------------------------------

    public function controlled()
    {
        $facilityId = $this->facilityId();

        $keywords = self::CONTROLLED_KEYWORDS;

        $controlled = PharmacyInventory::where('facility_id', $facilityId)
            ->where('is_recalled', false)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('generic_name', 'like', "%{$kw}%")
                      ->orWhere('medicine_name', 'like', "%{$kw}%");
                }
            })
            ->orderBy('medicine_name')
            ->limit(100)
            ->get();

        $recentRx = Prescription::with(['patient', 'items'])
            ->where('facility_id', $facilityId)
            ->whereHas('items', function ($q) use ($keywords) {
                $q->where(function ($iq) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $iq->orWhere('drug_name', 'like', "%{$kw}%");
                    }
                });
            })
            ->orderByDesc('dispensed_at')
            ->limit(20)
            ->get();

        return view('portals.pharmacy.controlled', compact('controlled', 'recentRx'));
    }
}
