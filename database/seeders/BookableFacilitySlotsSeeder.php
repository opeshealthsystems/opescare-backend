<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Makes appointment booking actually reachable from the directory.
 *
 * The mobile booking flow is:
 *   care_facilities (public directory the patient browses)
 *     -> care_facilities.facility_id
 *       -> facilities (operational tenant)
 *         -> appointment_slots
 *
 * Every directory row was seeded with facility_id = NULL, so
 * MobileFacilityController::slots() short-circuited to `{facility_id: null,
 * data: []}` for all 903 facilities. The booking wizard then had no slot to
 * lock, which made its Confirm button a silent no-op — the flow looked
 * complete but could never produce an appointment.
 *
 * This gives the major real hospitals an operational Facility record, links
 * the directory row to it, and opens a rolling two weeks of slots so the flow
 * works end to end. Only genuinely bookable facility types are linked;
 * pharmacies and labs are deliberately left unlinked because they are not
 * consultation venues.
 *
 * Idempotent: re-running relinks nothing new and tops slots back up to the
 * same horizon without duplicating them.
 */
class BookableFacilitySlotsSeeder extends Seeder
{
    /** Real hospitals/clinics that should accept appointments. */
    private const BOOKABLE = [
        'Hôpital Central de Yaoundé',
        'CHU de Yaoundé',
        'Hôpital Général de Yaoundé',
        'Hôpital Gynéco-Obstétrique et Pédiatrique de Yaoundé',
        'Fondation Chantal Biya',
        'Hôpital Général de Douala',
        'Hôpital Laquintinie de Douala',
        'CHU de Douala',
        'Hôpital Régional de Bamenda',
        'Baptist Hospital Bamenda',
        'Hôpital Régional de Buéa',
        'Hôpital Régional de Bafoussam',
        'Hôpital Régional de Garoua',
        'Hôpital Régional de Maroua',
        'Hôpital Régional de Ngaoundéré',
        'Hôpital Régional de Bertoua',
        "Hôpital Régional d'Ebolowa",
    ];

    /** Weekday clinic hours, as [hour, minute] starts for 30-minute slots. */
    private const DAY_START_HOUR = 8;
    private const DAY_END_HOUR   = 16;
    private const SLOT_MINUTES   = 30;
    private const HORIZON_DAYS   = 14;
    private const CAPACITY       = 3;

    public function run(): void
    {
        $linked = 0;
        $slotsCreated = 0;

        // A real clinician to attribute slots to.
        //
        // This block used to describe provider_id as nullable and pass null
        // when no clinician existed. The column is NOT NULL, so on a freshly
        // migrated database — where DatabaseSeeder reaches this seeder before
        // anything has created users — the insert threw and aborted the entire
        // 'db:seed' run partway through. Degrade the way the sibling
        // BookableFacilityNetworkSeeder already does: say so and skip.
        $providerId = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('roles.name', ['doctor', 'specialist', 'multi_doctor'])
            ->value('users.id');

        if ($providerId === null) {
            $this->command?->warn(
                'BookableFacilitySlotsSeeder: no clinician users found — '
                . 'appointment_slots.provider_id is NOT NULL, so no slots can be opened. '
                . 'Seed users/roles first, then re-run this seeder.'
            );

            return;
        }

        foreach (self::BOOKABLE as $name) {
            $directory = DB::table('care_facilities')
                ->where('facility_name', $name)
                ->where('country_code', 'CM')
                ->first(['id', 'facility_name', 'facility_type', 'facility_id']);

            if (! $directory) {
                continue; // not in this environment's registry — skip quietly
            }

            $facilityId = $directory->facility_id;

            if (! $facilityId) {
                // Reuse an operational record of the same name if one exists,
                // so re-running never creates a second tenant for one hospital.
                $existing = Facility::withoutDemoIsolation()
                    ->where('name', $directory->facility_name)
                    ->first();

                $facility = $existing ?? Facility::create([
                    'name'   => $directory->facility_name,
                    'type'   => $directory->facility_type,
                    'status' => 'active',
                ]);

                DB::table('care_facilities')
                    ->where('id', $directory->id)
                    ->update(['facility_id' => $facility->id, 'updated_at' => now()]);

                $facilityId = $facility->id;
                $linked++;
            }

            $slotsCreated += $this->openSlots($facilityId, $providerId);
        }

        $this->command?->info(
            "BookableFacilitySlotsSeeder: {$linked} facility(ies) linked, {$slotsCreated} slot(s) opened. "
            . 'Bookable directory entries: '
            . DB::table('care_facilities')->whereNotNull('facility_id')->count() . '.'
        );
    }

    /** Opens 30-minute weekday slots across the horizon; skips ones already there. */
    private function openSlots(string $facilityId, ?string $providerId): int
    {
        $created = 0;
        $day = Carbon::today();

        for ($d = 0; $d < self::HORIZON_DAYS; $d++, $day = $day->copy()->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }

            for ($h = self::DAY_START_HOUR; $h < self::DAY_END_HOUR; $h++) {
                foreach ([0, self::SLOT_MINUTES] as $minute) {
                    $startsAt = $day->copy()->setTime($h, $minute);

                    if ($startsAt->isPast()) {
                        continue;
                    }

                    $exists = DB::table('appointment_slots')
                        ->where('facility_id', $facilityId)
                        ->where('starts_at', $startsAt)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('appointment_slots')->insert([
                        'id'           => (string) Str::uuid(),
                        'facility_id'  => $facilityId,
                        'provider_id'  => $providerId,
                        'starts_at'    => $startsAt,
                        'ends_at'      => $startsAt->copy()->addMinutes(self::SLOT_MINUTES),
                        'capacity'     => self::CAPACITY,
                        'booked_count' => 0,
                        'status'       => 'open',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);

                    $created++;
                }
            }
        }

        return $created;
    }
}
