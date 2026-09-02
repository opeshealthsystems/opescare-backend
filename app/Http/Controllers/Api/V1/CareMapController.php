<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CareFacility;
use App\Models\SavedFacility;
use App\Models\MedicineReservationRequest;
use App\Http\Resources\SavedFacilityResource;
use App\Modules\CareMap\Services\CareMapSearchService;
use App\Modules\CareMap\Services\FacilityVerificationService;
use App\Modules\CareMap\Services\FacilityClaimService;
use App\Modules\CareMap\Services\FacilityReportService;
use App\Modules\CareMap\Services\FacilityFreshnessService;
use App\Modules\CareMap\Services\PharmacyStockSearchService;
use App\Modules\CareMap\Services\BloodAvailabilitySearchService;
use App\Modules\CareMap\Services\LabTestSearchService;
use App\Modules\CareMap\Services\InsuranceNetworkSearchService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CareMapController extends Controller
{
    /**
     * Row attributes that answer "when did the facility last report this?".
     *
     * `last_reported_at` is the Medicine Finder's column
     * (`medicine_pharmacy_stocks`); `last_updated_at` is the CareMap
     * availability tables' (`pharmacy_stock_availability`,
     * `blood_availability`, `lab_test_availability`). Both are listed so the
     * meta block keeps telling the truth whichever table backs a search.
     */
    private const REPORTED_AT_KEYS = ['last_reported_at', 'last_updated_at'];

    /**
     * fresh/recent age ceilings in hours, per domain.
     *
     * These mirror the thresholds FacilityFreshnessService already writes into
     * each row's `freshness_status` (pharmacy 24h/72h, blood 2h/6h) and its
     * 30-day stale rule for lab tests, so `meta.warning` can never contradict
     * the freshness stored on the very rows being returned.
     */
    private const FRESHNESS_WINDOWS = [
        'medicine' => ['fresh' => 24,  'recent' => 72],
        'blood'    => ['fresh' => 2,   'recent' => 6],
        'lab'      => ['fresh' => 168, 'recent' => 720],
    ];

    protected $searchService;
    protected $verificationService;
    protected $claimService;
    protected $reportService;
    protected $freshnessService;
    protected $pharmacySearch;
    protected $bloodSearch;
    protected $labSearch;
    protected $insuranceSearch;

    public function __construct(
        CareMapSearchService $searchService,
        FacilityVerificationService $verificationService,
        FacilityClaimService $claimService,
        FacilityReportService $reportService,
        FacilityFreshnessService $freshnessService,
        PharmacyStockSearchService $pharmacySearch,
        BloodAvailabilitySearchService $bloodSearch,
        LabTestSearchService $labSearch,
        InsuranceNetworkSearchService $insuranceSearch
    ) {
        $this->searchService = $searchService;
        $this->verificationService = $verificationService;
        $this->claimService = $claimService;
        $this->reportService = $reportService;
        $this->freshnessService = $freshnessService;
        $this->pharmacySearch = $pharmacySearch;
        $this->bloodSearch = $bloodSearch;
        $this->labSearch = $labSearch;
        $this->insuranceSearch = $insuranceSearch;
    }

    /**
     * Public search directory
     */
    public function index(Request $request)
    {
        $facilities = $this->searchService->searchNearby($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $facilities,
            'meta' => [
                'disclaimer' => __('public.availability_may_change') ?? 'Information may change. Please contact the facility before travelling or making medical decisions.',
            ]
        ]);
    }

    /**
     * Show facility details with service catalogs
     */
    public function show($id)
    {
        $facility = CareFacility::with(['services', 'hours', 'insurances', 'pharmacyStock', 'labTests', 'bloodAvailability'])
            ->findOrFail($id);

        if ($facility->listing_status !== 'active') {
            return response()->json([
                'status' => 'error',
                'code' => 'FACILITY_SUSPENDED',
                'message' => __('api.facility_listing_inactive')
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $facility,
            'meta' => [
                'disclaimer' => __('public.availability_may_change') ?? 'Information may change. Please contact the facility before travelling or making medical decisions.',
            ]
        ]);
    }

    /**
     * Search pharmacies having medicine stock
     */
    public function searchMedicine(Request $request)
    {
        $request->validate([
            'medicine' => 'required|string',
        ]);

        $lat = $request->input('latitude');
        $lon = $request->input('longitude');
        $radius = $request->input('radius', 50);

        $results = $this->pharmacySearch->searchMedicine($request->input('medicine'), $lat, $lon, $radius);

        return response()->json([
            'status' => 'success',
            'data' => $results,
            'meta' => array_merge(
                $this->availabilityFreshness($results, 'medicine'),
                ['disclaimer' => __('public.medicine_disclaimer') ?? 'Medicine availability is reported by the pharmacy and may change. Always confirm with the pharmacy.']
            )
        ]);
    }

    /**
     * Search blood availability
     */
    public function searchBlood(Request $request)
    {
        $request->validate([
            'blood_group' => 'required|string',
        ]);

        $lat = $request->input('latitude');
        $lon = $request->input('longitude');
        $radius = $request->input('radius', 50);
        $component = $request->input('component_type', 'whole_blood');

        $results = $this->bloodSearch->searchBlood($request->input('blood_group'), $component, $lat, $lon, $radius);

        return response()->json([
            'status' => 'success',
            'data' => $results,
            'meta' => array_merge(
                $this->availabilityFreshness($results, 'blood'),
                ['disclaimer' => __('public.blood_disclaimer') ?? 'Blood availability may change quickly. Contact the blood bank immediately.']
            )
        ]);
    }

    /**
     * Search labs offering LOINC/turnaround tests
     */
    public function searchTests(Request $request)
    {
        $request->validate([
            'test_name' => 'required|string',
        ]);

        $lat = $request->input('latitude');
        $lon = $request->input('longitude');
        $radius = $request->input('radius', 50);

        $results = $this->labSearch->searchTests($request->input('test_name'), $lat, $lon, $radius);

        return response()->json([
            'status' => 'success',
            'data' => $results,
            'meta' => array_merge(
                $this->availabilityFreshness($results, 'lab'),
                ['disclaimer' => 'Some tests may require a clinician’s request. Confirm requirements with the lab before booking.']
            )
        ]);
    }

    /**
     * Clean and swift emergency finder
     */
    public function searchEmergency(Request $request)
    {
        $results = $this->searchService->searchEmergency($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $results,
            'meta' => [
                'disclaimer' => __('public.emergency_disclaimer') ?? 'If this is a life-threatening emergency, contact local emergency services or go to the nearest emergency facility immediately.'
            ]
        ]);
    }

    /**
     * Save/favorite a facility listing
     */
    public function saveFacility(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.unauthenticated')
            ], 401);
        }

        $facility = CareFacility::findOrFail($id);

        $saved = SavedFacility::updateOrCreate([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
        ], [
            'label' => $request->input('label'),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => SavedFacilityResource::make($saved)
        ]);
    }

    /**
     * File incorrect info correction report
     */
    public function reportFacility(Request $request, $id)
    {
        $facility = CareFacility::findOrFail($id);
        $user = Auth::user();

        $report = $this->reportService->submitReport([
            'facility_id' => $facility->id,
            'reported_by_user_id' => $user ? $user->id : null,
            'report_type' => $request->input('report_type', 'wrong_phone'),
            'description' => $request->input('description'),
            'evidence_path' => $request->input('evidence_path'),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $report,
            'message' => __('api.caremap_report_submitted')
        ]);
    }

    /**
     * Claim listing ownership profile.
     *
     * `$id` is a `care_facilities.id` — this endpoint is reached from the public
     * listing page. It used to be handed to submitClaim(), which writes it to
     * `facility_claims.facility_id`, a column whose foreign key points at
     * `facilities`. That could never have inserted for the 1,395 listings with
     * no operational tenant, and `facility_claims` holds 0 rows. It now goes
     * through the directory-claim path, which anchors on `care_facility_id`.
     *
     * The claim is created in `submitted` and confers nothing. Approval is a
     * decision an administrator makes at /admin/care-map/review.
     */
    public function claimFacility(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.unauthenticated')
            ], 401);
        }

        try {
            $claim = $this->claimService->submitDirectoryClaim($id, $user->id, [
                'claim_reason'   => $request->input('claim_reason', 'Listing management'),
                'claimant_name'  => $request->input('claimant_name', $user->name),
                'claimant_role'  => $request->input('claimant_role'),
                'claimant_email' => $request->input('claimant_email', $user->email),
                'claimant_phone' => $request->input('claimant_phone'),
            ]);
            return response()->json([
                'status' => 'success',
                'data' => $claim,
                'message' => __('api.caremap_claim_submitted')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => $e->getMessage(),
                'message' => __('api.caremap_claim_pending')
            ], 400);
        }
    }

    /**
     * Admin: verify facility listing status
     */
    public function adminVerifyFacility(Request $request, $id)
    {
        $adminClientId = $request->attributes->get('integration_client_id');
        $admin = Auth::user();

        if (!$adminClientId && !$admin) {
            return response()->json(['status' => 'error', 'message' => __('api.admin_access_required'), 'code' => 'ADMIN_ACCESS_REQUIRED'], 403);
        }

        $actorId = $adminClientId ?? $admin->id;

        $facility = $this->verificationService->verifyFacility($id, $actorId, $request->input('status', 'license_verified'));

        return response()->json([
            'status' => 'success',
            'data' => $facility,
            'message' => __('api.facility_listing_verified')
        ]);
    }

    /**
     * Admin: suspend active listing
     */
    public function adminSuspendFacility(Request $request, $id)
    {
        $adminClientId = $request->attributes->get('integration_client_id');
        $admin = Auth::user();

        if (!$adminClientId && !$admin) {
            return response()->json(['status' => 'error', 'message' => __('api.admin_access_required'), 'code' => 'ADMIN_ACCESS_REQUIRED'], 403);
        }

        $actorId = $adminClientId ?? $admin->id;

        $facility = $this->verificationService->suspendFacility($id, $actorId);

        return response()->json([
            'status' => 'success',
            'data' => $facility,
            'message' => __('api.facility_listing_suspended')
        ]);
    }

    /**
     * Partner pharmacy stock sync -- NOT a stock ingest. Refuses, deliberately.
     *
     * This endpoint stored no stock. It validated nothing, wrote no rows, and
     * then stamped `last_availability_update_at = now()` before answering
     * "success". That timestamp is what the directory renders as freshness, so
     * a partner calling this made a listing *look* freshly reported while its
     * stock stayed empty or stale -- the one claim this platform must never
     * fabricate, on the surface a patient uses to decide whether to travel.
     *
     * The ownership check was also inert. `$facility->partner_id &&` short
     * circuits whenever `partner_id` is NULL, and on 2026-09-02 that was ALL
     * 1,863 rows -- so any authenticated user could stamp any facility in the
     * country as freshly updated.
     *
     * Real partner ingest lives at `POST /api/v1/connect/inventory/pharmacy`
     * (`Connect\\InventoryController::syncPharmacyStock`), which resolves drug
     * codes, persists rows, stamps provenance, and is idempotent. Rather than
     * grow a second, weaker ingest path here, this one now says what it is.
     */
    public function partnerStockSync(Request $request, $id)
    {
        $facility = CareFacility::findOrFail($id);
        $user = Auth::user();

        // Fail closed: an unowned facility is not an open one.
        if ($facility->partner_id === null || $facility->partner_id !== $user?->id) {
            return response()->json([
                'status'  => 'error',
                'message' => __('api.unauthorized_facility_owner'),
            ], 403);
        }

        return response()->json([
            'status'     => 'error',
            'error_code' => 'STOCK_SYNC_NOT_IMPLEMENTED_HERE',
            'message'    => __('api.pharmacy_stock_sync_moved'),
        ], 501);
    }

    /**
     * Web: Render Public Interactive Directory Map
     */
    public function publicDirectory(Request $request)
    {
        $facilities = $this->searchService->searchNearby($request->all());
        
        return view('care_map.directory', [
            'facilities' => $facilities,
            'locale' => session('locale', 'en'),
        ]);
    }

    /**
     * Web: Render Facility Clinical Profile
     */
    public function publicProfile($id)
    {
        $facility = CareFacility::with(['services', 'hours', 'insurances', 'pharmacyStock', 'labTests', 'bloodAvailability'])
            ->findOrFail($id);

        return view('care_map.profile', [
            'facility' => $facility,
            'locale' => session('locale', 'en'),
        ]);
    }

    /**
     * Web: Render Simplified Red Emergency Panel
     */
    public function publicEmergency(Request $request)
    {
        $facilities = $this->searchService->searchEmergency($request->all());

        return view('care_map.emergency', [
            'facilities' => $facilities,
            'locale' => session('locale', 'en'),
        ]);
    }

    /**
     * Web: Render Admin Care Map Moderation Desk
     */
    public function adminGovernance()
    {
        $pendingClaims = \App\Models\FacilityClaim::with(['careFacility', 'facility', 'claimant'])
            ->open()
            ->get();
        $reports = \App\Models\FacilityReport::with(['facility', 'reporter'])->where('status', 'new')->get();
        $staleStock = \App\Models\PharmacyStockAvailability::where('freshness_status', 'stale')->with('facility')->get();

        return view('care_map.admin_dashboard', [
            'pendingClaims' => $pendingClaims,
            'reports' => $reports,
            'staleStock' => $staleStock,
            'locale' => session('locale', 'en'),
        ]);
    }

    /**
     * Freshness meta for an availability result set — derived, never asserted.
     *
     * Every value below comes from the rows actually being returned. Two rules
     * this must never break:
     *   - An EMPTY result set yields `no_data`. The API must not make a
     *     freshness claim about availability it did not find.
     *   - A row with no report timestamp counts as stale, never fresh — the
     *     same reading FacilityFreshnessService and BloodAvailabilityProjector
     *     give a missing `last_updated_at`. Silence is not evidence.
     *
     * All values are machine-readable codes/ISO-8601 instants, not prose, so
     * callers (mobile app, partners, AI agents) can act on them in any locale.
     *
     * @param  string  $domain  key into self::FRESHNESS_WINDOWS
     * @return array{warning:string,last_reported_at:?string,oldest_reported_at:?string,results_count:int,freshness_window_hours:array{fresh:int,recent:int}}
     */
    private function availabilityFreshness(mixed $results, string $domain): array
    {
        $windows = self::FRESHNESS_WINDOWS[$domain];
        $count = $this->countResults($results);

        if ($count === 0) {
            return [
                'warning' => 'no_data',
                'last_reported_at' => null,
                'oldest_reported_at' => null,
                'results_count' => 0,
                'freshness_window_hours' => $windows,
            ];
        }

        $newest = null;
        $oldest = null;

        foreach ($this->collectReportedAt($results) as $stamp) {
            if ($newest === null || $stamp->greaterThan($newest)) {
                $newest = $stamp;
            }
            if ($oldest === null || $stamp->lessThan($oldest)) {
                $oldest = $stamp;
            }
        }

        return [
            'warning' => $this->freshnessBucket($newest, $windows),
            'last_reported_at' => $newest?->toIso8601String(),
            'oldest_reported_at' => $oldest?->toIso8601String(),
            'results_count' => $count,
            'freshness_window_hours' => $windows,
        ];
    }

    /**
     * Bucket the newest report time in the result set: fresh | recent | stale.
     */
    private function freshnessBucket(?CarbonInterface $reportedAt, array $windows): string
    {
        if ($reportedAt === null) {
            return 'stale';
        }

        // Absolute age: a timestamp in the future is clock skew or bad data and
        // must not be rewarded with a stronger claim than a real one.
        $ageHours = $reportedAt->diffInHours(now(), true);

        return match (true) {
            $ageHours <= $windows['fresh'] => 'fresh',
            $ageHours <= $windows['recent'] => 'recent',
            default => 'stale',
        };
    }

    /**
     * Count the rows a search actually returned.
     */
    private function countResults(mixed $results): int
    {
        if ($results === null) {
            return 0;
        }

        if (is_countable($results)) {
            return count($results);
        }

        if ($results instanceof Arrayable) {
            return count($results->toArray());
        }

        return 1;
    }

    /**
     * Harvest every report timestamp carried by a result set.
     *
     * The search services return facility rows with the matching availability
     * record attached (`matched_stock` / `matched_blood` / `matched_test`), so
     * the timestamp sits one level down. This walks the returned structure —
     * models, relations, collections, arrays — instead of hard-coding that
     * shape, so the meta block stays honest if the query behind an endpoint is
     * re-pointed at a different availability table.
     *
     * @return CarbonInterface[]
     */
    private function collectReportedAt(mixed $node, int $depth = 0): array
    {
        if ($node === null || is_scalar($node) || $depth > 4) {
            return [];
        }

        $stamps = [];

        foreach (self::REPORTED_AT_KEYS as $key) {
            $value = $this->readKey($node, $key);
            $parsed = $value === null ? null : $this->toCarbon($value);

            if ($parsed !== null) {
                $stamps[] = $parsed;
            }
        }

        foreach ($this->childNodes($node) as $child) {
            if ($child === null || is_scalar($child)) {
                continue;
            }

            $stamps = array_merge($stamps, $this->collectReportedAt($child, $depth + 1));
        }

        return $stamps;
    }

    /**
     * Read one key off a model / array / object without triggering a lazy load.
     */
    private function readKey(mixed $node, string $key): mixed
    {
        if ($node instanceof Model) {
            return array_key_exists($key, $node->getAttributes())
                ? $node->getAttribute($key)
                : null;
        }

        if (is_array($node)) {
            return $node[$key] ?? null;
        }

        if ($node instanceof \ArrayAccess) {
            return $node->offsetExists($key) ? $node[$key] : null;
        }

        if (is_object($node)) {
            return $node->{$key} ?? null;
        }

        return null;
    }

    /**
     * Nested values worth descending into. Uses already-loaded attributes and
     * relations only — never queries the database.
     */
    private function childNodes(mixed $node): array
    {
        if ($node instanceof Model) {
            return array_merge(
                array_values($node->getAttributes()),
                array_values($node->getRelations())
            );
        }

        if ($node instanceof \Traversable) {
            return iterator_to_array($node, false);
        }

        if (is_array($node)) {
            return array_values($node);
        }

        if ($node instanceof Arrayable) {
            return array_values($node->toArray());
        }

        if (is_object($node)) {
            return array_values(get_object_vars($node));
        }

        return [];
    }

    /**
     * Normalise a stored timestamp to Carbon; anything unparseable is no
     * timestamp at all, which the caller reads as stale rather than fresh.
     */
    private function toCarbon(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}

