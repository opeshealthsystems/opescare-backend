<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Widens appointment booking from a handful of flagship hospitals to a national
 * network.
 *
 * The booking chain is:
 *   care_facilities (the 897-row public registry the patient browses)
 *     -> care_facilities.facility_id
 *       -> facilities (the operational tenant a slot belongs to)
 *         -> appointment_slots
 *
 * BookableFacilitySlotsSeeder linked 17 named flagship hospitals. Everything
 * else in the registry still had facility_id = NULL, so
 * MobileFacilityController::slots() answered `{facility_id: null, data: []}`
 * and the booking wizard dead-ended on 880 of 897 facilities — a patient could
 * find their local hospital and then not book it.
 *
 * This seeder is the wide pass. Rather than a hand-written name list it selects
 * by *type and region* straight out of the real registry, so it stays correct as
 * the registry grows and never invents a facility:
 *
 *   1. every active hospital          — present in all 10 regions
 *   2. every active clinic
 *   3. up to HEALTH_CENTRES_PER_REGION health centres per region, alphabetically
 *
 * Step 3 is capped per region on purpose: 174 of the 263 health centres sit in
 * Centre and 88 in Adamaoua, so an uncapped pass would drag the bookable network
 * back into a Yaoundé cluster — the exact shape this seeder exists to fix.
 *
 * Pharmacies, laboratories, imaging and diagnostic centres are deliberately left
 * unlinked: they are not consultation venues and a booking wizard pointed at
 * them would be a lie.
 *
 * Clinic hours differ by facility type (a rural health centre does not run a
 * 30-minute specialist diary from 08:00 to 16:00), and the hospital schedule is
 * byte-identical to BookableFacilitySlotsSeeder's so the two seeders can run in
 * either order over the same 17 hospitals without producing a duplicate slot.
 *
 * Idempotent: a directory row is linked only while facility_id is NULL, and
 * slots are diffed against what is already on the calendar before insert. Run it
 * twice and the second run reports 0 linked, 0 opened.
 */
class BookableFacilityNetworkSeeder extends Seeder
{
    /** How far ahead the diary is opened. */
    private const HORIZON_DAYS = 14;

    /**
     * Per-region ceiling on health centres, so topping up past the hospital list
     * does not re-cluster the network in Centre/Adamaoua.
     */
    private const HEALTH_CENTRES_PER_REGION = 6;

    /** Registry types that can genuinely take a consultation booking. */
    private const CONSULTATION_TYPES = ['hospital', 'clinic', 'health_center'];

    /**
     * Weekday clinic hours per facility type.
     *
     * `hospital` intentionally mirrors BookableFacilitySlotsSeeder exactly
     * (08:00–16:00, 30-minute, capacity 3) — same grid, so re-running either
     * seeder over a shared hospital creates nothing new.
     *
     * @var array<string,array{start:int,end:int,step:int,capacity:int}>
     */
    private const SCHEDULES = [
        'hospital'      => ['start' => 8, 'end' => 16, 'step' => 30, 'capacity' => 3],
        'clinic'        => ['start' => 9, 'end' => 15, 'step' => 30, 'capacity' => 2],
        'health_center' => ['start' => 8, 'end' => 13, 'step' => 60, 'capacity' => 4],
    ];

    public function run(): void
    {
        // appointment_slots.provider_id is NOT NULL, so a clinician is required
        // before a single slot can be written. Rotating a small pool across
        // facilities keeps the diary from reading as one doctor holding every
        // clinic in the country.
        $providerIds = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('roles.name', ['doctor', 'specialist', 'multi_doctor'])
            ->orderBy('users.id')
            ->pluck('users.id')
            ->all();

        if ($providerIds === []) {
            $this->command?->warn(
                'BookableFacilityNetworkSeeder: no clinician users found — '
                . 'appointment_slots.provider_id is NOT NULL, so no slots can be opened. '
                . 'Seed users/roles first.'
            );

            return;
        }

        $candidates = $this->candidates();
        $linked = 0;
        $slotsCreated = 0;

        // Operational facilities already spoken for by a directory row. Two
        // registry entries share the name "CSI MENGUEME"; without this, the
        // second would be pointed at the first one's tenant and their diaries
        // would silently merge.
        $claimed = DB::table('care_facilities')
            ->whereNotNull('facility_id')
            ->pluck('facility_id')
            ->flip();

        foreach ($candidates as $index => $directory) {
            $facilityId = $directory->facility_id;

            if (! $facilityId) {
                $facilityId = $this->linkFacility($directory, $claimed);
                $claimed[$facilityId] = true;
                $linked++;
            }

            $slotsCreated += $this->openSlots(
                $facilityId,
                self::SCHEDULES[$directory->facility_type],
                // Deterministic rotation: the same facility always gets the same
                // clinician, so re-running never rewrites who owns a diary.
                $providerIds[$index % count($providerIds)],
            );
        }

        $bookable = DB::table('care_facilities')->whereNotNull('facility_id')->count();

        $this->command?->info(
            "BookableFacilityNetworkSeeder: {$linked} facility(ies) newly linked, "
            . "{$slotsCreated} slot(s) opened. "
            . "Bookable directory entries: {$bookable}. "
            . 'appointment_slots: ' . DB::table('appointment_slots')->count() . '.'
        );
    }

    /**
     * The registry rows that should become bookable, in a stable order.
     *
     * Never invents a facility — every row comes straight out of care_facilities.
     *
     * @return list<object>
     */
    private function candidates(): array
    {
        $base = fn () => DB::table('care_facilities')
            ->where('country_code', 'CM')
            ->where('listing_status', 'active');

        // 1 + 2 — every hospital, then every clinic. Hospitals alone already
        // reach all 10 regions.
        $rows = $base()
            ->whereIn('facility_type', ['hospital', 'clinic'])
            ->orderByRaw("CASE facility_type WHEN 'hospital' THEN 0 ELSE 1 END")
            ->orderBy('region')
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type', 'region', 'facility_id'])
            ->all();

        // 3 — health centres, capped per region.
        $regions = $base()
            ->where('facility_type', 'health_center')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');

        foreach ($regions as $region) {
            $rows = array_merge($rows, $base()
                ->where('facility_type', 'health_center')
                ->where('region', $region)
                ->orderBy('facility_name')
                ->limit(self::HEALTH_CENTRES_PER_REGION)
                ->get(['id', 'facility_name', 'facility_type', 'region', 'facility_id'])
                ->all());
        }

        // Defensive: only types this seeder has a schedule for ever get through.
        return array_values(array_filter(
            $rows,
            static fn (object $r) => in_array($r->facility_type, self::CONSULTATION_TYPES, true)
                && isset(self::SCHEDULES[$r->facility_type]),
        ));
    }

    /**
     * Gives a directory row an operational tenant and links it.
     *
     * Reuses an existing same-named facilities row when one is free, so a
     * hospital that already exists operationally does not get a second tenant.
     *
     * @param  \Illuminate\Support\Collection<string,mixed>  $claimed
     */
    private function linkFacility(object $directory, $claimed): string
    {
        $existing = Facility::withoutDemoIsolation()
            ->where('name', $directory->facility_name)
            ->whereNotIn('id', $claimed->keys()->all() ?: ['00000000-0000-0000-0000-000000000000'])
            ->first();

        $facility = $existing ?? Facility::create([
            'name'   => $directory->facility_name,
            'type'   => $directory->facility_type,
            'status' => 'active',
        ]);

        DB::table('care_facilities')
            ->where('id', $directory->id)
            ->update(['facility_id' => $facility->id, 'updated_at' => now()]);

        return (string) $facility->id;
    }

    /**
     * Opens weekday slots across the horizon for one facility.
     *
     * Reads the facility's existing calendar once and bulk-inserts only what is
     * missing — a per-slot SELECT + INSERT would be ~35,000 round trips across
     * the whole network.
     *
     * @param  array{start:int,end:int,step:int,capacity:int}  $schedule
     */
    private function openSlots(string $facilityId, array $schedule, string $providerId): int
    {
        $day  = Carbon::today();
        $last = Carbon::today()->addDays(self::HORIZON_DAYS);

        // Existing starts, keyed for an in-memory diff.
        //
        // The key is the *wall clock*, deliberately, not an instant. starts_at
        // is `timestamp WITH time zone`; Laravel binds a Carbon as a naive
        // 'Y-m-d H:i:s' string, so PostgreSQL stamps it with the session zone
        // (Africa/Lagos here) while the app runs on UTC. A value written as
        // 15:30 comes back as `15:30:00+01`, so wall clock round-trips exactly
        // and instants do not — normalising either side to UTC shifts the key by
        // an hour and re-running the seeder would duplicate every slot.
        $existing = DB::table('appointment_slots')
            ->where('facility_id', $facilityId)
            ->whereBetween('starts_at', [$day, $last])
            ->pluck('starts_at')
            ->map(static fn ($s) => Carbon::parse($s)->format('Y-m-d H:i'))
            ->flip();

        $now  = now();
        $rows = [];

        for ($d = 0; $d < self::HORIZON_DAYS; $d++, $day = $day->copy()->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }

            $slot = $day->copy()->setTime($schedule['start'], 0);
            $end  = $day->copy()->setTime($schedule['end'], 0);

            for (; $slot->lt($end); $slot = $slot->copy()->addMinutes($schedule['step'])) {
                // Never open a slot in the past — the mobile client filters on
                // starts_at >= now() and would just render a dead day.
                if ($slot->isPast() || $existing->has($slot->format('Y-m-d H:i'))) {
                    continue;
                }

                $rows[] = [
                    'id'           => (string) Str::uuid(),
                    'facility_id'  => $facilityId,
                    'provider_id'  => $providerId,
                    'starts_at'    => $slot->copy(),
                    'ends_at'      => $slot->copy()->addMinutes($schedule['step']),
                    'capacity'     => $schedule['capacity'],
                    'booked_count' => 0,
                    'status'       => 'open',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('appointment_slots')->insert($chunk);
        }

        return count($rows);
    }
}
