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
            $q->whereHas('patient', fn($p) => $p->where('full_name', 'like', "%{$search}%"));
        }

        $prescriptions = $q->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('portals.pharmacy.prescriptions', compact('prescriptions'));
    }

    // ------------------------------------------------------------------
    // Dispense (mark a prescription as dispensed)
    // ------------------------------------------------------------------

    /**
     * Dispensing goes through PrescriptionService so that the item lines, the
     * dispenser, and the audit event are all recorded — and so a repeat POST is
     * refused rather than silently re-stamping a prescription that has already
     * been handed to the patient.
     */
    public function dispense(Request $req, string $id, \App\Services\Clinical\PrescriptionService $prescriptions)
    {
        $facilityId = $this->facilityId();

        $rx = Prescription::where('facility_id', $facilityId)->findOrFail($id);

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
     * Legacy path (`/portals/pharmacy/inventory`).
     *
     * Repointed at `medicine_pharmacy_stocks`. It used to read
     * `pharmacy_inventories`, which no patient query has ever touched — a
     * pharmacist updating stock here changed nothing anybody could see.
     * `pharmacy_inventories` is untouched and still backs the dashboard
     * counters and the controlled-substances register (expiry, recall and
     * quarantine live there and have no equivalent on the public listing).
     */
    public function inventory(Request $req)
    {
        return $this->stock($req);
    }

    /**
     * Reported public availability for this pharmacy's directory listing.
     *
     * This is the table `MedicineFinderService` queries, so what is edited
     * here is exactly what a patient searching nearby pharmacies sees.
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
