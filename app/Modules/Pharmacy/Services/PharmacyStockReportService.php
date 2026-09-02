<?php

namespace App\Modules\Pharmacy\Services;

use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\FacilityUpdateAudit;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The pharmacy-side write path for the patient Medicine Finder.
 *
 * Why this exists
 * ---------------
 * Medicine stock used to be built twice. `pharmacy_inventories` is the
 * facility's own dispensing ledger (quantities, expiries, recalls) and is
 * keyed on `facilities.id`; `medicine_pharmacy_stocks` is the *reported*
 * public availability the finder reads, keyed on `care_facilities.id`.
 * Only the second one is ever queried by a patient — and until now nothing
 * but the demo seeder ever wrote to it, which is why every finder row carried
 * `source_system = 'demo_seed'`.
 *
 * This service is the single place a logged-in pharmacy turns a stock report
 * into a row the finder can serve. It does NOT touch `pharmacy_inventories`:
 * that table keeps its own life as internal dispensing stock.
 *
 * Invariants
 * ----------
 *  - The unique key is (medicine_id, care_facility_id). Insert and update are
 *    explicitly separate branches so a primary key is never rewritten and
 *    `medicine_reservations.stock_id` is never orphaned.
 *  - Every write stamps `last_reported_at = now()`. Freshness is the finder's
 *    only trust signal; a write that does not refresh it is worse than no
 *    write at all.
 *  - A portal write stamps `source_system = 'portal'`, so real pharmacy
 *    coverage can be counted apart from seeded fiction. 'portal' is one real
 *    source among several, not the definition of one: coverage() asks
 *    `MedicinePharmacyStock::scopeReportedByRealSource()` — the same scope the
 *    public finder queries through — so partner and Bridge syncs count too.
 *  - Taking a row over from another source (`demo_seed`, a Bridge agent, a
 *    partner sync) is recorded in `facility_update_audits` before it happens.
 *    Nothing is ever silently overwritten.
 */
class PharmacyStockReportService
{
    /** `source_system` value stamped on any row a pharmacy reports through the portal. */
    public const SOURCE_PORTAL = 'portal';

    /** `facility_update_audits.source` value for portal-originated stock reports. */
    public const AUDIT_SOURCE = 'pharmacy_portal';

    /** Listing problems that make a pharmacy invisible to, or unreliable in, the finder. */
    public const ISSUE_UNLINKED       = 'unlinked';
    public const ISSUE_NO_COORDINATES = 'no_coordinates';
    public const ISSUE_NOT_LISTED     = 'not_listed';

    /**
     * The public pharmacy listing (`care_facilities`) a portal facility owns.
     *
     * Resolved only through the explicit `care_facilities.facility_id` link.
     * Matching on names would be probabilistic identity resolution, which this
     * platform does not do — an unlinked pharmacy gets a visible warning
     * instead of a guessed listing belonging to somebody else.
     */
    public function listingFor(?string $facilityId): ?CareFacility
    {
        if (! $facilityId) {
            return null;
        }

        return CareFacility::query()
            ->where('facility_type', 'pharmacy')
            ->where('facility_id', $facilityId)
            // Prefer the active listing when a facility somehow owns several.
            ->orderByRaw("CASE WHEN listing_status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->first();
    }

    /**
     * Why patients may not be seeing this pharmacy, in the order worth fixing.
     *
     * `MedicineFinderService::nearbyPharmacies()` filters on
     * `listing_status = 'active'` AND `latitude`/`longitude` NOT NULL, so a
     * pharmacy without coordinates can report perfect stock and still never
     * appear in a single search result. The pharmacist has to be told.
     *
     * @return list<string>
     */
    public function listingIssues(?CareFacility $listing): array
    {
        if (! $listing) {
            return [self::ISSUE_UNLINKED];
        }

        $issues = [];

        if ($listing->listing_status !== 'active') {
            $issues[] = self::ISSUE_NOT_LISTED;
        }

        if ($listing->latitude === null || $listing->longitude === null) {
            $issues[] = self::ISSUE_NO_COORDINATES;
        }

        return $issues;
    }

    /**
     * Record one medicine's reported availability at one pharmacy listing.
     *
     * @param  array{
     *     stock_status: PharmacyStockStatus|string,
     *     packs_available?: int|null,
     *     pack_size?: string|null,
     *     unit_price?: float|null,
     *     reservation_enabled?: bool,
     * }  $input
     */
    public function report(
        CareFacility $listing,
        Medicine $medicine,
        array $input,
        ?string $actorId = null,
    ): MedicinePharmacyStock {
        $status = $input['stock_status'] instanceof PharmacyStockStatus
            ? $input['stock_status']
            : PharmacyStockStatus::from((string) $input['stock_status']);

        $now = Carbon::now();

        $attributes = [
            'stock_status'        => $status->value,
            'packs_available'     => $input['packs_available'] ?? null,
            'pack_size'           => $input['pack_size'] ?? null,
            'unit_price'          => $input['unit_price'] ?? null,
            'reservation_enabled' => (bool) ($input['reservation_enabled'] ?? false),
            'source_system'       => self::SOURCE_PORTAL,
            'last_reported_at'    => $now,
        ];

        // Genuinely-held stock also refreshes "last time this was physically
        // on the shelf"; out_of_stock / unknown must not fake that.
        if ($status->isAvailable()) {
            $attributes['last_stocked_at'] = $now;
        }

        return DB::transaction(function () use ($listing, $medicine, $attributes, $status, $actorId, $now) {
            // Lock the (medicine_id, care_facility_id) row so two pharmacists
            // reporting the same medicine cannot both take the insert branch
            // and collide on the unique constraint.
            $existing = MedicinePharmacyStock::query()
                ->where('medicine_id', $medicine->id)
                ->where('care_facility_id', $listing->id)
                ->lockForUpdate()
                ->first();

            if ($existing === null) {
                // ── INSERT branch ────────────────────────────────────────
                // A brand-new (medicine, pharmacy) pair. Currency defaults to
                // the catalog entry's, which is XAF across the Cameroon catalog.
                $stock = new MedicinePharmacyStock($attributes + [
                    'medicine_id'      => $medicine->id,
                    'care_facility_id' => $listing->id,
                    'currency'         => $medicine->currency ?: 'XAF',
                ]);
                $stock->save();

                $this->audit(
                    $listing,
                    $actorId,
                    'medicine_pharmacy_stock.created',
                    null,
                    $this->describe($medicine, $attributes),
                    $now,
                );

                return $stock;
            }

            // ── UPDATE branch ────────────────────────────────────────────
            // `id`, `medicine_id` and `care_facility_id` are deliberately not
            // in $attributes: the primary key stays put, so every
            // `medicine_reservations.stock_id` pointing here stays valid.
            $previousSource = $existing->source_system;
            $before         = $this->describe($medicine, [
                'stock_status'        => $existing->stock_status?->value,
                'packs_available'     => $existing->packs_available,
                'pack_size'           => $existing->pack_size,
                'unit_price'          => $existing->unit_price,
                'reservation_enabled' => $existing->reservation_enabled,
                'source_system'       => $previousSource,
                'last_reported_at'    => $existing->last_reported_at,
            ]);

            // Never silently overwrite a row another source owns — seeded demo
            // data, a Bridge agent sync or a partner feed. Record the takeover
            // first, so "who last owned this row" is answerable afterwards.
            if ($previousSource !== null && $previousSource !== self::SOURCE_PORTAL) {
                $this->audit(
                    $listing,
                    $actorId,
                    'medicine_pharmacy_stock.source_system',
                    $previousSource,
                    self::SOURCE_PORTAL,
                    $now,
                    // A pharmacy overriding a partner/Bridge feed is worth a
                    // human look; overriding demo seed data is the whole point.
                    // Asked through the model's allow-list rather than a second
                    // literal list — the same reason coverage() does. This read
                    // 'demo_seed' && 'seed' inline, which would have silently
                    // stopped agreeing the day that constant gained a value.
                    requiresReview: ! in_array(
                        $previousSource,
                        MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS,
                        true,
                    ),
                );
            }

            $existing->fill($attributes);
            $existing->save();

            $this->audit(
                $listing,
                $actorId,
                'medicine_pharmacy_stock.updated',
                $before,
                $this->describe($medicine, $attributes),
                $now,
            );

            return $existing;
        });
    }

    /**
     * Stamp the listing itself as freshly updated.
     *
     * Separate from report() so a bulk import can touch it once instead of
     * once per medicine.
     */
    public function touchListingFreshness(CareFacility $listing): void
    {
        $listing->forceFill(['last_availability_update_at' => Carbon::now()])->save();
    }

    /**
     * How many of this listing's rows the public finder actually publishes,
     * vs. how many it withholds.
     *
     * "Reported" is decided by exactly ONE rule: the allow-list in
     * `MedicinePharmacyStock::scopeReportedByRealSource()`, which is the scope
     * every public surface queries through. Counting through the same scope is
     * the point — it makes the widget structurally incapable of disagreeing
     * with what a patient is shown, instead of agreeing by coincidence until
     * one of the two lists is edited.
     *
     * It used to count only `source_system = 'portal'`. A pharmacy syncing its
     * shelf through the Connect API (`partner`) or a Bridge agent was therefore
     * told its own live stock was seeded fiction, while the Medicine Finder was
     * simultaneously publishing that exact stock to patients. Provenance is
     * about whether a row is REAL, not about which door it came through.
     *
     * The complement keeps the `seeded` key: 'demo_seed'/'seed' rows, plus
     * NULL-provenance rows that nobody has claimed. The finder withholds both,
     * and both mean the same thing to a pharmacist — patients are not seeing
     * this. NULL must never land in `reported`; see the scope's docblock.
     */
    public function coverage(CareFacility $listing): array
    {
        $forListing = fn () => MedicinePharmacyStock::query()
            ->where('care_facility_id', $listing->id);

        $total    = (int) $forListing()->count();
        $reported = (int) $forListing()->reportedByRealSource()->count();

        return [
            'total'    => $total,
            'reported' => $reported,
            'seeded'   => $total - $reported,
        ];
    }

    /** @param array<string,mixed> $values */
    private function describe(Medicine $medicine, array $values): string
    {
        return json_encode([
            'medicine_id'         => $medicine->id,
            'medicine'            => $medicine->name,
            'stock_status'        => $values['stock_status'] ?? null,
            'packs_available'     => $values['packs_available'] ?? null,
            'pack_size'           => $values['pack_size'] ?? null,
            'unit_price'          => $values['unit_price'] ?? null,
            'reservation_enabled' => (bool) ($values['reservation_enabled'] ?? false),
            'source_system'       => $values['source_system'] ?? self::SOURCE_PORTAL,
        ], JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function audit(
        CareFacility $listing,
        ?string $actorId,
        string $field,
        ?string $old,
        ?string $new,
        Carbon $at,
        bool $requiresReview = false,
    ): void {
        FacilityUpdateAudit::create([
            'facility_id'     => $listing->id,
            'actor_id'        => $actorId,
            'actor_type'      => $actorId ? 'user' : 'system',
            'field_changed'   => $field,
            'old_value'       => $old,
            'new_value'       => $new,
            'source'          => self::AUDIT_SOURCE,
            'requires_review' => $requiresReview,
            'created_at'      => $at,
        ]);
    }
}
