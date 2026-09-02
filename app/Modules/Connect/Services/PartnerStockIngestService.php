<?php

namespace App\Modules\Connect\Services;

use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\FacilityUpdateAudit;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Modules\Inventory\Services\BloodInventoryService;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The partner (HIS / ERP / bridge) write path behind
 * POST /api/v1/connect/inventory/{pharmacy,blood}-stock/sync.
 *
 * Why this exists
 * ---------------
 * Both sync endpoints validated their payload, rejected unsafe items, emitted
 * an audit event and answered `{"status":"synced"}` while writing to no table
 * at all. A partner integrating against the published Connect API — the one
 * `/docs/api` and every SDK point at — got a success response and their stock
 * vanished. That silent success is the single largest reason the Medicine
 * Finder and Blood Finder have no real coverage.
 *
 * This service is the missing write. It deliberately does NOT invent a parallel
 * storage path: it goes through the same tables, the same keys and the same
 * provenance rules the portal already uses.
 *
 *  - Pharmacy → `medicine_pharmacy_stocks`, keyed (medicine_id, care_facility_id),
 *    exactly the semantics of PharmacyStockReportService::report(). That service
 *    hardcodes `source_system = 'portal'` (it is the pharmacist's own statement),
 *    so a partner write cannot call it without lying about where the data came
 *    from. The insert/update branching, the row lock, the `last_reported_at`
 *    stamp and the takeover audit are mirrored here with an honest source.
 *  - Blood → BloodInventoryService::upsertUnit(), which re-runs
 *    BloodAvailabilityProjector so the patient-facing `blood_availability` band
 *    is republished from the operational record on every sync.
 *
 * Provenance
 * ----------
 * Both MedicinePharmacyStock and BloodAvailability withhold seeded AND
 * unattributed rows from public search (`scopeReportedByRealSource()`), so an
 * unstamped write would persist and still never reach a patient. Every row this
 * service touches is stamped `partner_api:<client_id>` — a real source, so it
 * publishes, and one that names the partner rather than impersonating the
 * pharmacy portal or the facility's own blood bank.
 *
 * Reporting
 * ---------
 * Nothing is ever dropped in silence. Every submitted line comes back either
 * counted (created or updated) or listed in `rejected` with a machine reason
 * and a translated message — silent success is the bug this replaces.
 */
class PartnerStockIngestService
{
    /**
     * Provenance prefix stamped on every row a partner sync writes.
     *
     * Namespaced with the calling `integration_client_id` so a bad feed can be
     * traced to, and later withdrawn from, one partner — the same
     * `<kind>:<id>` shape DuplicateMergeController uses for `api:unknown`.
     * Never a value in SYNTHETIC_SOURCE_SYSTEMS: this IS a real report about a
     * real shelf, and marking it synthetic would hide it from the patients the
     * ingest exists to serve. Equally never 'portal' — no pharmacist typed it.
     */
    public const SOURCE_PREFIX = 'partner_api';

    /** `facility_update_audits.source` value for partner-originated stock. */
    public const AUDIT_SOURCE = 'partner_stock_sync';

    /** `facility_update_audits.actor_type` — the column's documented vocabulary. */
    private const ACTOR_TYPE = 'api_partner';

    /** Rejection reasons reported back per item. */
    public const REASON_UNKNOWN_DRUG_CODE   = 'unknown_drug_code';
    public const REASON_AMBIGUOUS_DRUG_CODE = 'ambiguous_drug_code';
    public const REASON_LISTING_UNLINKED    = 'pharmacy_listing_unlinked';

    /** Warning codes: the write succeeded, but patients still will not see it. */
    public const WARNING_BLOOD_LISTING_UNLINKED = 'blood_listing_unlinked';

    /**
     * Packs → reported status when a partner does not send `stock_status`.
     *
     * Same thresholds as PharmacyInventoryService::deriveStatus(), so the
     * dispensing ledger and the public finder do not disagree about what "low"
     * means at the same facility.
     */
    private const LOW_STOCK_CEILING = 10;

    public function __construct(
        private readonly PharmacyStockReportService $stockReports,
        private readonly BloodInventoryService $bloodInventory,
    ) {
    }

    /**
     * The `source_system` value stamped for one calling integration client.
     *
     * Falls back to the bare prefix when the middleware could not name the
     * client: still a real, honest source, just a less specific one.
     */
    public function sourceFor(?string $clientId): string
    {
        $clientId = trim((string) $clientId);

        if ($clientId === '' || $clientId === 'unknown_client') {
            return self::SOURCE_PREFIX;
        }

        return self::SOURCE_PREFIX . ':' . $clientId;
    }

    /**
     * Persist a partner's pharmacy stock report.
     *
     * @param  string       $facilityId  Tenant `facilities.id`, resolved by the
     *                                   bearer middleware. NEVER from the body.
     * @param  list<array{drug_code:string,quantity:int,expiry_date?:string|null,stock_status?:string|null,pack_size?:string|null,unit_price?:float|null}>  $items
     * @return array{created:int,updated:int,rejected:list<array{index:int,drug_code:string,reason:string,message:string}>,warnings:list<string>,source_system:string}
     */
    public function ingestPharmacyStock(string $facilityId, ?string $clientId, array $items): array
    {
        $source = $this->sourceFor($clientId);

        // The public listing this tenant owns, resolved ONLY through the
        // explicit care_facilities.facility_id link. Reusing the portal's
        // resolver keeps partner writes inside the same non-probabilistic
        // identity rule: no name matching, ever.
        $listing = $this->stockReports->listingFor($facilityId);

        if ($listing === null) {
            // Storing would be pointless and misleading: medicine stock hangs
            // off the public listing, and there is none. Say so per item
            // rather than answering "synced".
            return [
                'created'       => 0,
                'updated'       => 0,
                'rejected'      => $this->rejectAll($items, self::REASON_LISTING_UNLINKED, __('api.partner_stock_listing_unlinked')),
                'warnings'      => [PharmacyStockReportService::ISSUE_UNLINKED],
                'source_system' => $source,
            ];
        }

        [$byIndex, $rejectedByIndex] = $this->resolveDrugCodes($items);

        $created  = 0;
        $updated  = 0;
        $rejected = [];

        foreach ($items as $index => $item) {
            $code = (string) ($item['drug_code'] ?? '');

            if (isset($rejectedByIndex[$index])) {
                $reason     = $rejectedByIndex[$index];
                $rejected[] = [
                    'index'     => (int) $index,
                    'drug_code' => $code,
                    'reason'    => $reason,
                    'message'   => $reason === self::REASON_AMBIGUOUS_DRUG_CODE
                        ? __('api.partner_stock_ambiguous_drug')
                        : __('api.partner_stock_unknown_drug'),
                ];

                continue;
            }

            $this->storeStock($listing, $byIndex[$index], $item, $source)
                ? $created++
                : $updated++;
        }

        if ($created > 0 || $updated > 0) {
            // One touch for the whole batch, not one per medicine — the reason
            // this is a separate call on the portal service.
            $this->stockReports->touchListingFreshness($listing);
        }

        return [
            'created'       => $created,
            'updated'       => $updated,
            'rejected'      => $rejected,
            // Stock can be stored correctly and still reach nobody: the finder
            // requires an active listing WITH coordinates. The pharmacist is
            // told this in the portal; the partner has to be told too.
            'warnings'      => $this->stockReports->listingIssues($listing),
            'source_system' => $source,
        ];
    }

    /**
     * Persist a partner's blood-bank stock report.
     *
     * Every item reaching here has already passed the controller's screening
     * gate, so none is unsafe.
     *
     * @param  list<array{blood_group:string,component_code:string,units:int}>  $items
     * @return array{created:int,updated:int,rejected:list<array{index:int,drug_code:string,reason:string,message:string}>,warnings:list<string>,source_system:string}
     */
    public function ingestBloodStock(string $facilityId, ?string $clientId, array $items): array
    {
        $source  = $this->sourceFor($clientId);
        $created = 0;
        $updated = 0;

        foreach ($items as $item) {
            // Through the service, never a raw write: upsertUnit() re-publishes
            // the patient-facing blood_availability row via
            // BloodAvailabilityProjector, threading $source onto it so the
            // Blood Finder's provenance gate lets the row through.
            $row = $this->bloodInventory->upsertUnit($facilityId, [
                'blood_group'     => $item['blood_group'],
                'component'       => $item['component_code'],
                'available_units' => $item['units'],
                'is_unsafe'       => false,
            ], $source);

            $row->wasRecentlyCreated ? $created++ : $updated++;
        }

        $warnings = [];

        // The projector publishes onto care_facilities rows linked to this
        // tenant. With no linked listing the operational record is stored and
        // correct, but no patient can search it — worth saying out loud.
        if (! CareFacility::query()->where('facility_id', $facilityId)->exists()) {
            $warnings[] = self::WARNING_BLOOD_LISTING_UNLINKED;
        }

        return [
            'created'       => $created,
            'updated'       => $updated,
            'rejected'      => [],
            'warnings'      => $warnings,
            'source_system' => $source,
        ];
    }

    /**
     * Map every submitted `drug_code` onto a catalogue Medicine.
     *
     * A code is either a `medicines.id` UUID or a WHO ATC code. ATC is NOT
     * unique in this catalogue — 18 codes currently sit on more than one row
     * (strength variants of the same molecule) — so an ATC hit on several
     * medicines is reported as ambiguous rather than resolved by picking one.
     * Guessing here would file a pharmacy's ibuprofen 400mg stock against the
     * 200mg catalogue row and publish it to patients as fact.
     *
     * Resolved in two queries regardless of batch size.
     *
     * @param  list<array<string,mixed>>  $items
     * @return array{0: array<int,Medicine>, 1: array<int,string>}
     */
    private function resolveDrugCodes(array $items): array
    {
        $codes = [];
        foreach ($items as $item) {
            $code = trim((string) ($item['drug_code'] ?? ''));
            if ($code !== '') {
                $codes[$code] = true;
            }
        }
        $codes = array_keys($codes);

        $uuids    = array_values(array_filter($codes, static fn (string $c) => Str::isUuid($c)));
        $atcCodes = array_values(array_filter($codes, static fn (string $c) => ! Str::isUuid($c)));

        /** @var array<string,Medicine> $byId */
        $byId = $uuids === []
            ? []
            : Medicine::query()->active()->whereIn('id', $uuids)->get()->keyBy('id')->all();

        /** @var array<string,list<Medicine>> $byAtc */
        $byAtc = [];
        if ($atcCodes !== []) {
            $lowered      = array_map(static fn (string $c) => mb_strtolower($c), $atcCodes);
            $placeholders = implode(',', array_fill(0, count($lowered), '?'));

            $matches = Medicine::query()
                ->active()
                ->whereRaw("LOWER(atc_code) IN ($placeholders)", $lowered)
                ->get();

            foreach ($matches as $medicine) {
                $byAtc[mb_strtolower((string) $medicine->atc_code)][] = $medicine;
            }
        }

        $resolved = [];
        $rejected = [];

        foreach ($items as $index => $item) {
            $code = trim((string) ($item['drug_code'] ?? ''));

            if ($code !== '' && isset($byId[$code])) {
                $resolved[$index] = $byId[$code];
                continue;
            }

            $candidates = $byAtc[mb_strtolower($code)] ?? [];

            $rejected[$index] = match (count($candidates)) {
                0       => self::REASON_UNKNOWN_DRUG_CODE,
                1       => null,
                default => self::REASON_AMBIGUOUS_DRUG_CODE,
            };

            if ($rejected[$index] === null) {
                unset($rejected[$index]);
                $resolved[$index] = $candidates[0];
            }
        }

        return [$resolved, $rejected];
    }

    /**
     * Write one medicine's reported availability at one pharmacy listing.
     *
     * Mirrors PharmacyStockReportService::report() exactly — same unique key,
     * same row lock, same insert/update split, same freshness stamp — with the
     * partner source in place of 'portal'.
     *
     * @param  array<string,mixed>  $item
     * @return bool  true when the row was created, false when updated in place
     */
    private function storeStock(CareFacility $listing, Medicine $medicine, array $item, string $source): bool
    {
        $quantity = isset($item['quantity']) ? (int) $item['quantity'] : null;
        $status   = $this->deriveStatus($quantity, $item['stock_status'] ?? null);
        $now      = Carbon::now();

        $attributes = [
            'stock_status'    => $status->value,
            'packs_available' => $quantity,
            'pack_size'       => $item['pack_size'] ?? null,
            'unit_price'      => isset($item['unit_price']) ? (float) $item['unit_price'] : null,
            // A partner feed cannot honour a counter reservation on its own —
            // that is a workflow the pharmacy opts into through the portal.
            'reservation_enabled' => false,
            'source_system'       => $source,
            'last_reported_at'    => $now,
        ];

        // Only genuinely-held stock refreshes "last physically on the shelf".
        if ($status->isAvailable()) {
            $attributes['last_stocked_at'] = $now;
        }

        return DB::transaction(function () use ($listing, $medicine, $attributes, $source, $now) {
            // Lock (medicine_id, care_facility_id) so a retry racing the
            // original cannot have both take the insert branch and collide on
            // the unique constraint. This lock plus that constraint is what
            // makes a repeated sync an update rather than a duplicate.
            $existing = MedicinePharmacyStock::query()
                ->where('medicine_id', $medicine->id)
                ->where('care_facility_id', $listing->id)
                ->lockForUpdate()
                ->first();

            if ($existing === null) {
                $stock = new MedicinePharmacyStock($attributes + [
                    'medicine_id'      => $medicine->id,
                    'care_facility_id' => $listing->id,
                    'currency'         => $medicine->currency ?: 'XAF',
                ]);
                $stock->save();

                $this->audit(
                    $listing,
                    'medicine_pharmacy_stock.created',
                    null,
                    $this->describe($medicine, $attributes),
                    $now,
                );

                return true;
            }

            // `id`, `medicine_id` and `care_facility_id` stay out of
            // $attributes: the primary key must not move, or every
            // medicine_reservations.stock_id pointing here is orphaned.
            $previousSource = $existing->source_system;
            $before         = $this->describe($medicine, [
                'stock_status'     => $existing->stock_status?->value,
                'packs_available'  => $existing->packs_available,
                'pack_size'        => $existing->pack_size,
                'unit_price'       => $existing->unit_price,
                'source_system'    => $previousSource,
            ]);

            // Never silently take a row over from another source. A partner
            // overwriting seeded fiction is the whole point; a partner
            // overwriting a pharmacist's own portal report is worth a human
            // look — the same rule PharmacyStockReportService applies in
            // reverse.
            if ($previousSource !== null && $previousSource !== $source) {
                $synthetic = in_array($previousSource, MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS, true);

                $this->audit(
                    $listing,
                    'medicine_pharmacy_stock.source_system',
                    $previousSource,
                    $source,
                    $now,
                    requiresReview: ! $synthetic,
                );
            }

            $existing->fill($attributes);
            $existing->save();

            $this->audit(
                $listing,
                'medicine_pharmacy_stock.updated',
                $before,
                $this->describe($medicine, $attributes),
                $now,
            );

            return false;
        });
    }

    /**
     * Reported quantity → published status.
     *
     * A partner that knows its own status may state it; otherwise the pack
     * count decides. Zero packs is `out_of_stock`, never `unknown`: the partner
     * told us something definite and the finder must not round it up.
     */
    private function deriveStatus(?int $quantity, ?string $explicit): PharmacyStockStatus
    {
        if ($explicit !== null && $explicit !== '') {
            // Backed enum, never a string comparison. The controller has
            // already constrained this to PharmacyStockStatus::values().
            return PharmacyStockStatus::from($explicit);
        }

        if ($quantity === null) {
            return PharmacyStockStatus::Unknown;
        }

        return match (true) {
            $quantity <= 0                     => PharmacyStockStatus::OutOfStock,
            $quantity <= self::LOW_STOCK_CEILING => PharmacyStockStatus::LowStock,
            default                            => PharmacyStockStatus::InStock,
        };
    }

    /**
     * @param  list<array<string,mixed>>  $items
     * @return list<array{index:int,drug_code:string,reason:string,message:string}>
     */
    private function rejectAll(array $items, string $reason, string $message): array
    {
        $rejected = [];

        foreach ($items as $index => $item) {
            $rejected[] = [
                'index'     => (int) $index,
                'drug_code' => (string) ($item['drug_code'] ?? ''),
                'reason'    => $reason,
                'message'   => $message,
            ];
        }

        return $rejected;
    }

    /** @param array<string,mixed> $values */
    private function describe(Medicine $medicine, array $values): string
    {
        return json_encode([
            'medicine_id'     => $medicine->id,
            'medicine'        => $medicine->name,
            'stock_status'    => $values['stock_status'] ?? null,
            'packs_available' => $values['packs_available'] ?? null,
            'pack_size'       => $values['pack_size'] ?? null,
            'unit_price'      => $values['unit_price'] ?? null,
            'source_system'   => $values['source_system'] ?? null,
        ], JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function audit(
        CareFacility $listing,
        string $field,
        ?string $old,
        ?string $new,
        Carbon $at,
        bool $requiresReview = false,
    ): void {
        FacilityUpdateAudit::create([
            'facility_id' => $listing->id,
            // `actor_id` is a uuid column and an integration client_id is not a
            // UUID, so the client is named in `source` instead — the audit
            // still answers "which partner wrote this".
            'actor_id'        => null,
            'actor_type'      => self::ACTOR_TYPE,
            'field_changed'   => $field,
            'old_value'       => $old,
            'new_value'       => $new,
            'source'          => self::AUDIT_SOURCE,
            'requires_review' => $requiresReview,
            'created_at'      => $at,
        ]);
    }
}
