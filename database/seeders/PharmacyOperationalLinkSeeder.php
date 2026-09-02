<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Gives real pharmacies an operational tenant so they can report their own stock.
 *
 * THE GAP THIS CLOSES
 * -------------------
 * The medicine finder reads `medicine_pharmacy_stocks`, keyed on
 * `care_facilities` — the public directory. A pharmacist reports stock through
 * PharmacyStockReportService, which resolves their listing through
 * `care_facilities.facility_id`, the link to the operational `facilities` row
 * their login belongs to.
 *
 * That link did not exist. Of 385 pharmacy listings exactly one carried a
 * facility_id, and that one had no coordinates — and the finder filters on
 * `latitude IS NOT NULL`, so it was invisible anyway. The count of pharmacies
 * that could both be found by a patient AND report their own stock was ZERO.
 * The write path shipped complete and unreachable, which is why all 23,264
 * stock rows are `source_system='demo_seed'` rather than anything a pharmacy
 * actually said.
 *
 * WHAT IT DOES NOT DO
 * -------------------
 * It invents nothing. Names and coordinates are the real OpenStreetMap and
 * MINSANTE records already in the directory; this only creates the operational
 * row and sets the link. Listings without coordinates are deliberately skipped
 * rather than geocoded by guesswork — a pharmacy at a made-up location is worse
 * than one that is missing, because a patient would travel to it.
 *
 * Creating the tenant does not create a login. A real pharmacy still needs a
 * user attached to that facility before anyone can sign in and report; this
 * removes the structural blocker, not the onboarding work.
 *
 * Idempotent: reuses a free same-named facilities row, and re-running relinks
 * nothing that is already linked.
 */
class PharmacyOperationalLinkSeeder extends Seeder
{
    public function run(): void
    {
        // Only geocoded, actively listed pharmacies — the exact set
        // MedicineFinderService is willing to return.
        $listings = DB::table('care_facilities')
            ->where('facility_type', 'pharmacy')
            ->whereNull('facility_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('listing_status', 'active')
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type', 'city', 'region']);

        if ($listings->isEmpty()) {
            $this->command?->info('PharmacyOperationalLinkSeeder: nothing to link.');

            return;
        }

        // Facility ids already claimed by some other listing, so two directory
        // rows can never end up sharing one operational tenant.
        $claimed = DB::table('care_facilities')
            ->whereNotNull('facility_id')
            ->pluck('facility_id')
            ->filter()
            ->flip();

        $linked = 0;
        $reused = 0;

        foreach ($listings as $listing) {
            $existing = Facility::withoutDemoIsolation()
                ->where('name', $listing->facility_name)
                ->whereNotIn('id', $claimed->keys()->all() ?: ['00000000-0000-0000-0000-000000000000'])
                ->first();

            if ($existing) {
                $reused++;
            }

            $facility = $existing ?? Facility::create([
                'name'   => $listing->facility_name,
                'type'   => $listing->facility_type,
                'status' => 'active',
            ]);

            DB::table('care_facilities')
                ->where('id', $listing->id)
                ->update(['facility_id' => $facility->id, 'updated_at' => now()]);

            $claimed->put((string) $facility->id, true);
            $linked++;
        }

        $this->command?->info(sprintf(
            'PharmacyOperationalLinkSeeder: %d pharmacy(ies) linked (%d reused an existing tenant). '
            . 'They can now be found by patients and report their own stock.',
            $linked,
            $reused
        ));
    }
}
