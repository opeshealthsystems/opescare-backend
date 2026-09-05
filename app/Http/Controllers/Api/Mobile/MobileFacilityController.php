<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AppointmentSlot;
use App\Models\CareFacility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Mobile Patient API — the public facility directory.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  THE TWO FACILITY REGISTERS — read this before changing anything here
 * ══════════════════════════════════════════════════════════════════════════
 *
 * This product keeps facilities in two tables, and they are NOT duplicates of
 * each other. The relationship is superset → subset, and the schema says so
 * itself:
 *
 *   `care_facilities`  — the PUBLIC DIRECTORY. Everything a patient can look
 *       up: name, type, address, coordinates, phone, opening hours, published
 *       services, insurance acceptance, pharmacy stock, lab menu. Populated by
 *       imports of the national facility register (see
 *       2026_09_02_090000_add_import_provenance_to_care_facilities — it carries
 *       `source_system` / `source_ref`), by facility claims, and by admin
 *       review. A row here means "this place exists and a patient can find
 *       it". It does NOT mean the place uses OpesCare.
 *
 *   `facilities`       — the OPERATIONAL TENANT. Created when an organisation
 *       actually signs up. It is what staff accounts, appointment calendars,
 *       prescriptions, consent grants, provider tokens and every clinical
 *       write are scoped to. A row here means "care can happen here, inside
 *       this system".
 *
 * The link is one nullable column, and the create migration states its purpose
 * in so many words:
 *
 *     // 2026_05_25_000000_create_care_map_tables.php
 *     $table->uuid('facility_id')->nullable(); // link to internal facilities
 *                                              // table for slot lookup
 *
 * So: a directory listing is a listing. A directory listing WITH a
 * `facility_id` is a listing whose operator has signed up — the only kind that
 * can publish a clinician, open a slot, or receive a booking. There is no
 * second, richer join anywhere; the two `add_facility_id_to_care_facilities`
 * migrations are both defensive no-ops over this same column.
 *
 * ── The measured gap (dev database, 2026-09-05) ───────────────────────────
 *   care_facilities (all active)          2 728 rows
 *   facilities                              482 rows
 *   care_facilities WITH facility_id        468 rows   ← 17.2 % of the directory
 *   ...of those, with an open future slot   121
 *   ...of those, publishing a clinician       0        (staff_profiles holds
 *                                                       one row in total, and
 *                                                       it is administrative)
 *
 * That is why "Find Provider" felt broken: it listed 2 728 rows in *no order
 * at all* and the overwhelming majority answered "not connected". The endpoint
 * gave the client no way to ask the only question that mattered.
 *
 * ── What this controller now does about it ────────────────────────────────
 * It answers that question in the response, without inventing a single link:
 *
 *   • every row carries `connected` (does it have a `facility_id`?),
 *     `facility_id`, `provider_count` and `open_slot_count` — all counted,
 *     never guessed;
 *   • `?connected=1` / `?bookable=1` / `?with_providers=1` filter down to the
 *     rows that can actually do something;
 *   • the DEFAULT order is connected-first, so the 468 usable listings sit at
 *     the top of page 1 instead of being scattered through 137 pages;
 *   • the `registry` block reports the honest totals, so the UI can say
 *     "468 of 2 728 connected" rather than implying all 2 728 work.
 *
 * Nothing here fabricates connectivity. An unlinked listing still says it is
 * unlinked; it is simply no longer the first thing a patient trips over.
 */
class MobileFacilityController extends Controller
{
    /** Directory page size. Unchanged — clients paginate on it. */
    private const PER_PAGE = 20;

    /** Register-wide totals change on import/claim, not per request. */
    private const REGISTRY_CACHE_KEY = 'mobile:facility-registry:totals:v1';
    private const REGISTRY_CACHE_TTL = 300;

    /**
     * Accent folding for directory search, as two Postgres `translate()`
     * arguments and the same map applied to the needle in PHP.
     *
     * The national register spells cities the way they are spelled: 793 of
     * 2 728 listings are in "Yaoundé", 61 in "Ngaoundéré". A patient on a
     * phone keyboard types "Yaounde", and ILIKE '%Yaounde%' matched none of
     * them — the single most-populated city in the directory returned nothing.
     * `translate()` needs no extension (unaccent does) and no index is lost,
     * because a leading-wildcard ILIKE never used one.
     */
    private const ACCENTED = 'àáâãäåçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ';
    private const FOLDED   = 'aaaaaaceeeeiiiinooooouuuuyyAAAAAACEEEEIIIINOOOOOUUUUY';

    /**
     * GET /api/mobile/facilities
     *
     * Query:
     *   ?type=hospital|clinic|pharmacy|...   directory facility_type
     *   ?city=Yaounde                        substring, case-insensitive
     *   ?q=search_term                       name / description / city
     *   ?connected=1|0                       1 = only listings linked to an
     *                                        operational facility; 0 = only
     *                                        directory-only listings
     *   ?bookable=1                          linked AND has an open future slot
     *   ?with_providers=1                    linked AND publishes >= 1 clinician
     *   ?sort=connected|name                 default `connected`
     *   ?page=1
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type'           => 'nullable|string|max:64',
            'city'           => 'nullable|string|max:120',
            'q'              => 'nullable|string|max:120',
            'connected'      => 'nullable|boolean',
            'bookable'       => 'nullable|boolean',
            'with_providers' => 'nullable|boolean',
            'sort'           => 'nullable|in:connected,name',
        ]);

        $query = CareFacility::where('listing_status', 'active');

        if ($type = $request->query('type')) {
            $query->where('facility_type', $type);
        }

        // Case- and accent-insensitive. Both mattered: this is Postgres, so
        // the old LIKE matched "Yaounde" but not "yaounde", and neither form
        // matched the 793 listings actually spelled "Yaoundé". Facility names
        // are public directory data — this is not, and must never become, a
        // patient search (see the enumeration rule in the app CLAUDE.md).
        if ($city = $request->query('city')) {
            $this->whereLoosely($query, 'city', $city);
        }

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $this->whereLoosely($sub, 'facility_name', $q);
                $this->whereLoosely($sub, 'description', $q, 'or');
                $this->whereLoosely($sub, 'city', $q, 'or');
            });
        }

        // ── Connectivity filters ─────────────────────────────────────────
        if ($request->filled('connected')) {
            $request->boolean('connected')
                ? $query->whereNotNull('facility_id')
                : $query->whereNull('facility_id');
        }

        if ($request->boolean('bookable')) {
            $query->whereNotNull('facility_id')
                ->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                    ->from('appointment_slots')
                    ->whereColumn('appointment_slots.facility_id', 'care_facilities.facility_id')
                    ->where('appointment_slots.status', 'open')
                    ->where('appointment_slots.starts_at', '>=', now())
                    ->whereColumn('appointment_slots.booked_count', '<', 'appointment_slots.capacity'));
        }

        if ($request->boolean('with_providers')) {
            $query->whereNotNull('facility_id')
                ->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                    ->from('staff_profiles')
                    ->whereColumn('staff_profiles.facility_id', 'care_facilities.facility_id')
                    ->where('staff_profiles.status', 'active')
                    ->where('staff_profiles.staff_category', 'clinical')
                    ->whereNotNull('staff_profiles.user_id'));
        }

        // ── Ordering ─────────────────────────────────────────────────────
        // Default: usable listings first. Before this the endpoint had NO
        // order at all, so Postgres returned whatever the heap gave it and
        // page 1 was 20 arbitrary rows out of 2 728 — statistically 17 of them
        // dead ends. `false` sorts before `true` in Postgres, so
        // "(facility_id IS NULL) ASC" puts the linked rows first.
        if ($request->query('sort') === 'name') {
            $query->orderBy('facility_name');
        } else {
            $query->orderByRaw('(facility_id IS NULL) ASC')
                ->orderBy('facility_name');
        }

        $facilities = $query->select([
            'id', 'facility_id', 'facility_name', 'facility_type', 'ownership_type',
            'city', 'region', 'address', 'latitude', 'longitude',
            'phone_primary', 'phone_secondary', 'email', 'website',
            'integration_status', 'listing_status',
        ])->paginate(self::PER_PAGE);

        /** @var array<int, CareFacility> $rows */
        $rows = $facilities->items();

        // Two grouped queries for the whole page — this is what replaces the
        // client's one-request-per-visible-row clinician probe.
        $internalIds = $this->internalIdsOf($rows);
        $providers   = $this->providerCountsFor($internalIds);
        $openSlots   = $this->openSlotCountsFor($internalIds);

        return response()->json([
            'data' => array_map(function (CareFacility $f) use ($providers, $openSlots) {
                $payload = $f->toArray();
                $payload['connected']       = $f->facility_id !== null;
                $payload['provider_count']  = (int) ($providers[$f->facility_id] ?? 0);
                $payload['open_slot_count'] = (int) ($openSlots[$f->facility_id] ?? 0);

                return $payload;
            }, $rows),
            'pagination' => [
                'total'        => $facilities->total(),
                'per_page'     => $facilities->perPage(),
                'current_page' => $facilities->currentPage(),
                'last_page'    => $facilities->lastPage(),
            ],
            // Honest denominator. The client is expected to show this so a
            // patient understands "not connected" is the register's current
            // state, not a failure of their search.
            'registry' => $this->registryTotals(),
        ]);
    }

    /**
     * GET /api/mobile/facilities/{id}
     */
    public function show(string $id): JsonResponse
    {
        $facility = CareFacility::with(['services', 'hours', 'insurances'])
            ->where('listing_status', 'active')
            ->findOrFail($id);

        $internalIds   = $facility->facility_id ? [$facility->facility_id] : [];
        $providerCount = (int) ($this->providerCountsFor($internalIds)[$facility->facility_id] ?? 0);
        $openSlotCount = (int) ($this->openSlotCountsFor($internalIds)[$facility->facility_id] ?? 0);

        return response()->json([
            'data' => [
                'id'                 => $facility->id,
                'facility_name'      => $facility->facility_name,
                'facility_type'      => $facility->facility_type,
                'ownership_type'     => $facility->ownership_type,
                'city'               => $facility->city,
                'region'             => $facility->region,
                'address'            => $facility->address,
                'latitude'           => $facility->latitude,
                'longitude'          => $facility->longitude,
                'phone_primary'      => $facility->phone_primary,
                'phone_secondary'    => $facility->phone_secondary,
                'email'              => $facility->email,
                'website'            => $facility->website,
                'integration_status' => $facility->integration_status,
                'description'        => $facility->description,

                // Connectivity, stated once and read straight off the link
                // column. `facility_id` is the internal `facilities` id that
                // POST /mobile/appointments validates against — surfacing it
                // here saves the client a round trip to /slots just to learn
                // whether the place is reachable at all.
                'connected'          => $facility->facility_id !== null,
                'facility_id'        => $facility->facility_id,
                'provider_count'     => $providerCount,
                'open_slot_count'    => $openSlotCount,
                'connectivity_note'  => $this->connectivityNote(
                    $facility->facility_id !== null,
                    $providerCount,
                    $openSlotCount,
                ),

                'services'           => $facility->services->map(fn ($s) => [
                    'service_name'        => $s->service_name,
                    'service_category'    => $s->service_category,
                    'specialty'           => $s->specialty,
                    'appointment_required' => $s->appointment_required,
                    'walk_in_allowed'     => $s->walk_in_allowed,
                    'availability_status' => $s->availability_status,
                ]),
                'hours' => $facility->hours->map(fn ($h) => [
                    'day_of_week'     => $h->day_of_week,
                    'opens_at'        => $h->opens_at,
                    'closes_at'       => $h->closes_at,
                    'is_closed'       => $h->is_closed,
                    'is_24_hours'     => $h->is_24_hours,
                    'service_context' => $h->service_context,
                ]),
                'insurances' => $facility->insurances->map(fn ($i) => [
                    'insurance_name'   => $i->insurance_name,
                    'cashless_available' => $i->cashless_available,
                    'status'           => $i->status,
                ]),
            ],
        ]);
    }

    /**
     * GET /api/mobile/facilities/{careFacilityId}/slots
     *
     * Lists upcoming open appointment slots for a care facility from the
     * directory. The care_facilities record must have a linked facility_id
     * (facilities table) for slots to appear. Returns empty data if no link
     * exists — an unlinked listing has no calendar to read, and inventing one
     * would be a lie the patient only discovers at the counter.
     *
     * Query: ?date=YYYY-MM-DD  (optional, defaults to today onward)
     */
    public function slots(Request $request, string $careFacilityId): JsonResponse
    {
        $request->validate(['date' => 'nullable|date']);

        $careFacility = CareFacility::where('listing_status', 'active')
            ->findOrFail($careFacilityId);

        // No linked internal facility — return empty gracefully
        if (! $careFacility->facility_id) {
            return response()->json([
                'facility_id' => null,
                'connected'   => false,
                'data'        => [],
            ]);
        }

        $from = $request->query('date')
            ? \Carbon\Carbon::parse($request->query('date'))->startOfDay()
            : now();

        $slots = AppointmentSlot::where('facility_id', $careFacility->facility_id)
            ->where('status', 'open')
            ->where('starts_at', '>=', $from)
            ->whereRaw('booked_count < capacity')
            ->orderBy('starts_at')
            ->limit(50)
            ->get();

        return response()->json([
            // Internal `facilities` table id — the mobile client must echo this back
            // as `facility_id` when it calls POST /mobile/appointments (that endpoint
            // validates against `facilities`, not the public `care_facilities` directory
            // the patient actually browsed), so it has to be surfaced here.
            'facility_id' => $careFacility->facility_id,
            'connected'   => true,
            'data' => $slots->map(fn ($s) => [
                'id'              => $s->id,
                'starts_at'       => $s->starts_at->toIso8601String(),
                'ends_at'         => $s->ends_at->toIso8601String(),
                'available_count' => $s->capacity - $s->booked_count,
                'provider_id'     => $s->provider_id,
            ]),
        ]);
    }

    /**
     * GET /api/mobile/facility-registry/summary
     * (registered in routes/mobile_facility_registry.php)
     *
     * The state of the register, said plainly and broken down by facility
     * type. This exists so the app can lead with the truth — "468 of 2 728
     * listings are connected" — instead of letting a patient discover it one
     * dead row at a time.
     */
    public function registrySummary(): JsonResponse
    {
        $byType = Cache::remember(self::REGISTRY_CACHE_KEY . ':by-type', self::REGISTRY_CACHE_TTL, function (): array {
            return DB::table('care_facilities')
                ->where('listing_status', 'active')
                ->groupBy('facility_type')
                ->orderByDesc(DB::raw('count(*)'))
                ->get([
                    'facility_type',
                    DB::raw('count(*) as directory_total'),
                    DB::raw('count(facility_id) as connected_total'),
                ])
                ->map(fn ($r) => [
                    'facility_type'   => (string) $r->facility_type,
                    'directory_total' => (int) $r->directory_total,
                    'connected_total' => (int) $r->connected_total,
                ])
                ->all();
        });

        return response()->json([
            'data' => [
                'totals'  => $this->registryTotals(),
                'by_type' => $byType,
                'labels'  => [
                    'directory_total'      => __('facility_registry.summary.directory_total'),
                    'connected_total'      => __('facility_registry.summary.connected_total'),
                    'bookable_total'       => __('facility_registry.summary.bookable_total'),
                    'with_providers_total' => __('facility_registry.summary.with_providers_total'),
                ],
                'explainer' => __('facility_registry.summary.explainer'),
            ],
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * `WHERE <column> matches <needle>`, ignoring case AND accents.
     *
     * The needle is folded in PHP and the column in SQL, so "yaounde",
     * "Yaounde" and "Yaoundé" all find the same 793 listings. Everything is
     * bound — `$column` is a literal from this class, never request input.
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     */
    private function whereLoosely($query, string $column, string $needle, string $boolean = 'and'): void
    {
        $folded = strtr($needle, array_combine(
            mb_str_split(self::ACCENTED),
            mb_str_split(self::FOLDED),
        ));

        $query->whereRaw(
            "translate({$column}, ?, ?) ILIKE ?",
            [self::ACCENTED, self::FOLDED, '%' . $folded . '%'],
            $boolean,
        );
    }

    /**
     * The internal `facilities` ids referenced by a page of directory rows.
     *
     * @param  array<int, CareFacility>  $rows
     * @return array<int, string>
     */
    private function internalIdsOf(array $rows): array
    {
        return array_values(array_unique(array_filter(
            array_map(fn (CareFacility $f) => $f->facility_id, $rows)
        )));
    }

    /**
     * Active, patient-facing clinicians per internal facility id.
     *
     * The predicate is identical to MobileProviderController::index() — if it
     * is counted here it is listed there, so a badge can never promise a
     * roster the next screen does not show.
     *
     * @param  array<int, string>  $internalIds
     * @return array<string, int>
     */
    private function providerCountsFor(array $internalIds): array
    {
        if ($internalIds === []) {
            return [];
        }

        $rows = DB::table('staff_profiles')
            ->whereIn('facility_id', $internalIds)
            ->where('status', 'active')
            ->where('staff_category', 'clinical')
            ->whereNotNull('user_id')
            ->groupBy('facility_id')
            ->get(['facility_id', DB::raw('count(*) as total')]);

        return $rows->mapWithKeys(fn ($r) => [(string) $r->facility_id => (int) $r->total])->all();
    }

    /**
     * Open, future, not-yet-full slots per internal facility id.
     *
     * @param  array<int, string>  $internalIds
     * @return array<string, int>
     */
    private function openSlotCountsFor(array $internalIds): array
    {
        if ($internalIds === []) {
            return [];
        }

        $rows = DB::table('appointment_slots')
            ->whereIn('facility_id', $internalIds)
            ->where('status', 'open')
            ->where('starts_at', '>=', now())
            ->whereColumn('booked_count', '<', 'capacity')
            ->groupBy('facility_id')
            ->get(['facility_id', DB::raw('count(*) as total')]);

        return $rows->mapWithKeys(fn ($r) => [(string) $r->facility_id => (int) $r->total])->all();
    }

    /**
     * Register-wide totals. Cached as plain integers only — never models and
     * never Carbon (a cached object comes back __PHP_Incomplete_Class in this
     * app, see config serializable_classes).
     *
     * @return array<string, int>
     */
    private function registryTotals(): array
    {
        return Cache::remember(self::REGISTRY_CACHE_KEY, self::REGISTRY_CACHE_TTL, function (): array {
            $base = DB::table('care_facilities')->where('listing_status', 'active');

            $row = (clone $base)->first([
                DB::raw('count(*) as directory_total'),
                DB::raw('count(facility_id) as connected_total'),
            ]);

            $bookable = (clone $base)
                ->whereNotNull('facility_id')
                ->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                    ->from('appointment_slots')
                    ->whereColumn('appointment_slots.facility_id', 'care_facilities.facility_id')
                    ->where('appointment_slots.status', 'open')
                    ->where('appointment_slots.starts_at', '>=', now())
                    ->whereColumn('appointment_slots.booked_count', '<', 'appointment_slots.capacity'))
                ->count();

            $withProviders = (clone $base)
                ->whereNotNull('facility_id')
                ->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                    ->from('staff_profiles')
                    ->whereColumn('staff_profiles.facility_id', 'care_facilities.facility_id')
                    ->where('staff_profiles.status', 'active')
                    ->where('staff_profiles.staff_category', 'clinical')
                    ->whereNotNull('staff_profiles.user_id'))
                ->count();

            return [
                'directory_total'      => (int) ($row->directory_total ?? 0),
                'connected_total'      => (int) ($row->connected_total ?? 0),
                'bookable_total'       => (int) $bookable,
                'with_providers_total' => (int) $withProviders,
            ];
        });
    }

    /**
     * One translated sentence describing what this listing can actually do.
     * Never optimistic: it degrades from "roster" to "slots only" to
     * "directory only" strictly on what was counted.
     */
    private function connectivityNote(bool $connected, int $providerCount, int $openSlotCount): string
    {
        if (! $connected) {
            return __('facility_registry.roster.not_connected');
        }

        if ($providerCount > 0) {
            return trans_choice('facility_registry.roster.published', $providerCount, ['count' => $providerCount]);
        }

        if ($openSlotCount > 0) {
            return __('facility_registry.roster.slots_without_roster');
        }

        return __('facility_registry.roster.none_published');
    }
}
