<?php

namespace Database\Seeders;

use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\MedicineReservation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Pharmacy\PharmacyDeskService;
use Database\Seeders\Support\DemoFacilityResolver;
use Database\Seeders\Support\DemoStoryIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Puts work on the demo pharmacist's counter so the pharmacy desk (web and
 * app) opens populated the moment demo.pharmacist@opescare.test signs in:
 *
 *   - ONE of demo.reviewer's active prescriptions routed to the facility the
 *     pharmacist is posted at (incoming on the bench);
 *   - TWO medicine reservations at that pharmacy's listing — a pending pickup
 *     for demo.reviewer and an accepted delivery for demo.guardian;
 *   - ONE stock line marked low, so the home tile and the `?low=1` list are
 *     not empty.
 *
 * Idempotent: every row carries a stable id derived from the patient
 * (DemoStoryIds::derived) or is looked up before it is written, so a re-run
 * refreshes rather than piles up. is_demo only — a real person's record is
 * never touched, and the seeder says so and stops rather than guessing.
 *
 * "The facility the pharmacist is posted at" is resolved at run time — the
 * active facility_role_assignments row first, primary_facility_id second —
 * because two seeders disagree about it (FinderDemoSeeder posts them at a
 * pharmacy tenant, DemoFacilityAssignmentsSeeder at the first demo facility)
 * and the only thing that matters is where the login actually lands.
 *
 * NOT registered in DatabaseSeeder — run it explicitly, AFTER
 * DemoPatientStorySeeder (which writes the prescriptions this one routes):
 *     php artisan db:seed --class=DemoPharmacyDeskSeeder
 */
class DemoPharmacyDeskSeeder extends Seeder
{
    public const PHARMACIST_EMAIL = 'demo.pharmacist@opescare.test';
    public const REVIEWER_EMAIL   = 'demo.reviewer@opescare.test';
    public const GUARDIAN_EMAIL   = 'demo.guardian@opescare.test';

    /** Story keys — folded with the patient id into the row ids. */
    private const KEY_PICKUP   = 'demo-pharmacy-desk:reservation:pickup';
    private const KEY_DELIVERY = 'demo-pharmacy-desk:reservation:delivery';

    /** Catalogue names to stock when the listing has nothing reservable. */
    private const FALLBACK_MEDICINES = ['Paracetamol', 'Amoxicillin', 'Metformin'];

    public function run(): void
    {
        DemoFacilityResolver::flush();

        $pharmacist = User::withoutGlobalScopes()->where('email', self::PHARMACIST_EMAIL)->first();

        if (! $pharmacist || ! $pharmacist->is_demo) {
            $this->command?->warn('DemoPharmacyDeskSeeder: ' . self::PHARMACIST_EMAIL . ' is missing or not a demo account — nothing seeded.');

            return;
        }

        $facility = $this->postingOf($pharmacist);

        if (! $facility) {
            $this->command?->warn('DemoPharmacyDeskSeeder: the demo pharmacist is not posted at any facility — nothing seeded.');

            return;
        }

        $this->command?->info("── Pharmacy desk at {$facility->name} ({$facility->id})");

        $listing = $this->listingFor($facility);

        $reviewer = $this->demoPatient(self::REVIEWER_EMAIL);
        $guardian = $this->demoPatient(self::GUARDIAN_EMAIL);

        if ($reviewer) {
            $this->routePrescription($reviewer, $facility);
        } else {
            $this->command?->warn('  skip prescription: ' . self::REVIEWER_EMAIL . ' has no demo patient record (run DemoPatientStorySeeder first)');
        }

        if (! $listing) {
            $this->command?->warn("  skip reservations + stock: {$facility->name} has no care_facilities listing (care_facilities.facility_id) — link it in the directory first");

            return;
        }

        $stock = $this->ensureStock($listing, (bool) $facility->is_demo);

        if ($stock->isEmpty()) {
            $this->command?->warn('  skip reservations: no catalogue medicine to stock');

            return;
        }

        if ($reviewer) {
            $this->reservation($reviewer, $listing, $stock->first(), self::KEY_PICKUP, [
                'fulfilment_mode' => MedicineReservation::FULFILMENT_PICKUP,
                'status'          => MedicineReservation::STATUS_PENDING,
                'quantity'        => 2,
                'patient_note'    => 'I will collect after work, around 17:30.',
            ]);
        }

        if ($guardian) {
            $this->reservation($guardian, $listing, $stock->get(1) ?? $stock->first(), self::KEY_DELIVERY, [
                'fulfilment_mode'  => MedicineReservation::FULFILMENT_DELIVERY,
                'delivery_address' => 'Quartier Bastos, rue 1.771, Yaoundé — blue gate opposite the school',
                'delivery_phone'   => '+237 6 77 00 11 22',
                'status'           => MedicineReservation::STATUS_ACCEPTED,
                'accepted_at'      => now()->subMinutes(40),
                'confirmed_at'     => now()->subMinutes(40),
                'quantity'         => 1,
                'patient_note'     => 'For my mother. Please call before delivering.',
            ]);
        } else {
            $this->command?->warn('  skip delivery reservation: ' . self::GUARDIAN_EMAIL . ' has no demo patient record (run DemoFamilySeeder first)');
        }

        $this->markLow($stock->last(), (bool) $facility->is_demo);
    }

    // ── Who and where ─────────────────────────────────────────────────────

    private function postingOf(User $pharmacist): ?Facility
    {
        $facilityId = null;

        if (Schema::hasTable('facility_role_assignments')) {
            $facilityId = FacilityRoleAssignment::query()
                ->where('user_id', $pharmacist->id)
                ->active()
                ->orderBy('assigned_at')
                ->value('facility_id');
        }

        $facilityId ??= $pharmacist->primary_facility_id;

        return $facilityId
            ? Facility::withoutGlobalScope('isolate_demo')->find($facilityId)
            : null;
    }

    /**
     * The directory listing reservations and stock hang off. Resolved through
     * the explicit link only — a listing is never guessed from a name.
     */
    private function listingFor(Facility $facility): ?CareFacility
    {
        return CareFacility::query()
            ->where('facility_id', $facility->id)
            ->orderByRaw("CASE WHEN facility_type = 'pharmacy' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN listing_status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->first();
    }

    private function demoPatient(string $email): ?Patient
    {
        $patient = Patient::withoutGlobalScopes()->where('email', $email)->first();

        if ($patient && ! $patient->is_demo) {
            $this->command?->warn("  refuse {$email}: not a demo record — the desk story is never written onto a real person");

            return null;
        }

        return $patient;
    }

    // ── The bench ─────────────────────────────────────────────────────────

    /**
     * Route one open, unexpired, un-routed prescription of the patient's to
     * the pharmacist's facility. One already routed here is left as it is —
     * that IS the idempotent outcome.
     */
    private function routePrescription(Patient $patient, Facility $facility): void
    {
        /** @var PharmacyDeskService $desk */
        $desk     = app(PharmacyDeskService::class);
        $routeIds = $desk->routeIds($facility->id);

        $open = Prescription::withoutGlobalScopes()
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['active', 'partially_dispensed'])
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByDesc('prescribed_at')
            ->get();

        $already = $open->first(fn (Prescription $rx) => $rx->pharmacy_route_id !== null
            && in_array((string) $rx->pharmacy_route_id, $routeIds, true));

        if ($already) {
            $this->command?->info("  prescription {$already->reference} already routed here — kept");

            return;
        }

        $candidate = $open->first(fn (Prescription $rx) => $rx->pharmacy_route_id === null);

        if (! $candidate) {
            $this->command?->warn("  skip prescription: {$patient->health_id} has no open, un-routed prescription to route");

            return;
        }

        $routed = $desk->routeToFacility($candidate, $facility);

        $this->command?->info("  prescription {$routed->reference} routed to {$facility->name} (incoming)");
    }

    // ── Reservations ──────────────────────────────────────────────────────

    /**
     * Lines the listing can reserve against. When there are none, stock the
     * fallback catalogue medicines as `demo_seed` rows — honest provenance:
     * the finder withholds them from patients, the desk still sees them.
     *
     * @return \Illuminate\Support\Collection<int, MedicinePharmacyStock>
     */
    private function ensureStock(CareFacility $listing, bool $demoFacility): \Illuminate\Support\Collection
    {
        $existing = MedicinePharmacyStock::query()
            ->where('care_facility_id', $listing->id)
            ->where('reservation_enabled', true)
            ->whereIn('stock_status', [PharmacyStockStatus::InStock->value, PharmacyStockStatus::LowStock->value])
            ->with('medicine')
            ->orderBy('created_at')
            ->limit(3)
            ->get();

        if ($existing->count() >= 2) {
            return $existing;
        }

        foreach (self::FALLBACK_MEDICINES as $generic) {
            $medicine = Medicine::query()->active()
                ->whereRaw('LOWER(generic_name) = LOWER(?)', [$generic])
                ->orderBy('name')
                ->first();

            if (! $medicine) {
                continue;
            }

            $row = MedicinePharmacyStock::query()->firstOrNew([
                'medicine_id'      => $medicine->id,
                'care_facility_id' => $listing->id,
            ]);

            if ($row->exists && ! $this->mayRewrite($row, $demoFacility)) {
                continue; // a reported line belongs to the pharmacy, not the demo
            }

            $row->fill([
                'stock_status'        => PharmacyStockStatus::InStock->value,
                'packs_available'     => $row->packs_available ?? 40,
                'pack_size'           => $row->pack_size ?? $medicine->default_pack_size,
                'unit_price'          => $row->unit_price ?? ($medicine->price_min ?? 1500),
                'currency'            => $row->currency ?: ($medicine->currency ?: 'XAF'),
                'reservation_enabled' => true,
                'source_system'       => $row->source_system ?? 'demo_seed',
                'last_stocked_at'     => now(),
                'last_reported_at'    => now(),
            ])->save();
        }

        return MedicinePharmacyStock::query()
            ->where('care_facility_id', $listing->id)
            ->where('reservation_enabled', true)
            ->whereIn('stock_status', [PharmacyStockStatus::InStock->value, PharmacyStockStatus::LowStock->value])
            ->with('medicine')
            ->orderBy('created_at')
            ->limit(3)
            ->get();
    }

    /** @param  array<string, mixed>  $attributes */
    private function reservation(Patient $patient, CareFacility $listing, MedicinePharmacyStock $stock, string $key, array $attributes): void
    {
        $id = DemoStoryIds::derived($patient->id, $key);

        $quantity  = (int) ($attributes['quantity'] ?? 1);
        $unitPrice = $stock->unit_price;

        $row = MedicineReservation::query()->firstOrNew(['id' => $id]);

        $row->fill(array_merge([
            'reference'        => $row->reference ?? $this->reference($id),
            'patient_id'       => $patient->id,
            'medicine_id'      => $stock->medicine_id,
            'care_facility_id' => $listing->id,
            'stock_id'         => $stock->id,
            'prescription_id'  => null,
            'quantity'         => $quantity,
            'pack_size'        => $stock->pack_size ?? $stock->medicine?->default_pack_size,
            'unit_price'       => $unitPrice,
            'total_price'      => $unitPrice === null ? null : round($unitPrice * $quantity, 2),
            'currency'         => $stock->currency ?: 'XAF',
            'delivery_status'  => null,
            'cancelled_at'     => null,
            'cancelled_reason' => null,
            'ready_at'         => null,
            'dispatched_at'    => null,
            'delivered_at'     => null,
            'collected_at'     => null,
            'expires_at'       => now()->addHours(20),
        ], $attributes));

        if (! $row->exists) {
            $row->id = $id;
            $row->created_at = now()->subHours(2);
        }

        $row->save();

        $this->command?->info(sprintf(
            '  reservation %s  %-8s %-9s %s × %d  (%s)',
            $row->reference,
            $row->fulfilment_mode,
            $row->deskStatus(),
            $stock->medicine?->name ?? $stock->medicine_id,
            $quantity,
            $patient->health_id,
        ));
    }

    /**
     * A stable counter reference for a fixed id — the same alphabet
     * MedicineReservationService uses (no 0/O, 1/I), so the pharmacist can
     * read it off a phone without ambiguity.
     */
    private function reference(string $id): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $hex      = preg_replace('/[^0-9a-f]/i', '', $id);
        $code     = '';

        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[hexdec(substr($hex, $i * 4, 4) ?: '0') % 32];
        }

        return 'OC-RX-' . $code;
    }

    // ── Stock ─────────────────────────────────────────────────────────────

    /**
     * A stock line may be rewritten by the demo only when it is synthetic
     * (seeded) or belongs to a demo facility. A line a real pharmacy reported
     * through the portal or a feed is theirs: the seeder may reserve against
     * it, never restate its quantity or freshness.
     */
    private function mayRewrite(MedicinePharmacyStock $row, bool $demoFacility): bool
    {
        return $demoFacility || in_array($row->source_system, MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS, true);
    }

    private function markLow(?MedicinePharmacyStock $stock, bool $demoFacility): void
    {
        if (! $stock) {
            return;
        }

        if (! $this->mayRewrite($stock, $demoFacility)) {
            $this->command?->warn('  stock: skipped marking low - the line was reported by the pharmacy, not seeded');
            return;
        }

        $stock->fill([
            'stock_status'     => PharmacyStockStatus::LowStock->value,
            'packs_available'  => 3,
            'reorder_level'    => 10,
            'last_reported_at' => now(),
        ])->save();

        $this->command?->info('  stock ' . ($stock->medicine?->name ?? $stock->medicine_id) . ' marked low (3 packs, reorder at 10)');
    }
}
