<?php

namespace App\Services\FrontDesk;

use App\Models\AppointmentSlot;
use App\Models\AuditEvent;
use App\Models\FacilitySchedule;
use App\Models\ProviderAvailability;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Opening hours, clinicians' posted hours, and the slots a patient can book.
 *
 * ── Why this exists ───────────────────────────────────────────────────────
 * Patient self-booking is slot-based (`POST /mobile/appointments` needs an
 * `appointment_slot_id`, offered by `GET /mobile/facilities/{id}/slots`).
 * Slots come from `appointment_slots`, which nothing in the product wrote:
 * `facility_schedules` and `provider_availabilities` had no writer at all,
 * and the only slots ever opened came from a one-off seeder. So the app's
 * booking flow was dead everywhere but the seeded hospitals.
 *
 * This service is the ONE writer for the three tables:
 *
 *   publishFacilityHours()   the facility's week   → facility_schedules
 *   publishProviderHours()   a clinician's week    → provider_availabilities
 *   generateSlots()          the next N days       → appointment_slots
 *
 * generateSlots() MATERIALISES the hours: for every day of the horizon, every
 * active posted-hours row for that weekday is cut into `slot_duration_minutes`
 * slots, clipped to the facility's opening hours for the day when it has any
 * (AppointmentService::assertAvailability enforces both, so a slot outside
 * either would be refused at booking). It is idempotent and safe to run
 * nightly (`slots:generate`, routes/console.php) and after every publish:
 *
 *   • a slot that already exists is left alone (bookings on it survive);
 *   • an open, UNBOOKED slot that the current hours no longer produce is
 *     closed (`status = closed`) so a patient is not offered a time the
 *     clinician has just withdrawn; a booked one is never touched;
 *   • a closed, unbooked slot the hours produce again is reopened.
 *
 * The reconciliation only ever looks at (facility, clinician) pairs that HAVE
 * posted hours. A facility with slots but no hours rows (the seeded
 * hospitals) is left exactly as it is: nothing here invents hours, and
 * nothing here closes what it did not open.
 *
 * ── Clock ─────────────────────────────────────────────────────────────────
 * Hours are wall-clock `HH:MM` compared the way AppointmentService compares
 * them — against `scheduled_at` formatted in the application timezone — so
 * they are read and written in that timezone, same as every other desk time.
 */
final class AvailabilityService
{
    /** How far ahead slots are materialised. */
    public const HORIZON_DAYS = 14;

    public const DEFAULT_SLOT_MINUTES = 30;

    public const MIN_SLOT_MINUTES = 5;

    public const MAX_SLOT_MINUTES = 240;

    /** ISO weekdays: 1 = Monday … 7 = Sunday (what AppointmentService compares `day_of_week` to). */
    public const DAYS = [1, 2, 3, 4, 5, 6, 7];

    // ──────────────────────────────────────────────────────────────────
    // Reading
    // ──────────────────────────────────────────────────────────────────

    /** @return Collection<int,FacilitySchedule> active rows, Monday first */
    public function facilityHours(string $facilityId): Collection
    {
        if (! Str::isUuid($facilityId)) {
            return new Collection();
        }

        return FacilitySchedule::query()
            ->where('facility_id', $facilityId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->get();
    }

    /** @return Collection<int,ProviderAvailability> active rows at the facility, by clinician then day */
    public function providerHours(string $facilityId, ?string $providerId = null): Collection
    {
        if (! Str::isUuid($facilityId)) {
            return new Collection();
        }

        $query = ProviderAvailability::query()
            ->where('facility_id', $facilityId)
            ->where('is_active', true)
            ->orderBy('provider_id')
            ->orderBy('day_of_week');

        if ($providerId !== null && $providerId !== '') {
            $query->where('provider_id', $providerId);
        }

        return $query->get();
    }

    /** Open, future, not-full slots at the facility inside the horizon — what a patient can book right now. */
    public function openSlotCount(string $facilityId, ?CarbonInterface $from = null): int
    {
        if (! Str::isUuid($facilityId)) {
            return 0;
        }

        [$from, $until] = $this->window($from);

        return AppointmentSlot::query()
            ->where('facility_id', $facilityId)
            ->where('status', 'open')
            ->whereBetween('starts_at', [$from, $until])
            ->whereColumn('booked_count', '<', 'capacity')
            ->count();
    }

    // ──────────────────────────────────────────────────────────────────
    // Writing hours
    // ──────────────────────────────────────────────────────────────────

    /**
     * Replace the facility's week. `$days` is the WHOLE week: a weekday not
     * listed is closed (its row is removed). Each entry:
     * `{day_of_week: 1..7, starts_at: 'HH:MM', ends_at: 'HH:MM'}`.
     *
     * @param  list<array{day_of_week:int,starts_at:string,ends_at:string}>  $days
     * @return Collection<int,FacilitySchedule>
     * @throws FrontDeskRefusal hours_invalid
     */
    public function publishFacilityHours(DeskActor $actor, array $days): Collection
    {
        $week = $this->normaliseWeek($days);

        DB::transaction(function () use ($actor, $week) {
            $before = FacilitySchedule::query()->where('facility_id', $actor->facilityId)->get()->toArray();

            FacilitySchedule::query()
                ->where('facility_id', $actor->facilityId)
                ->whereNotIn('day_of_week', array_keys($week))
                ->delete();

            foreach ($week as $day => [$opens, $closes]) {
                FacilitySchedule::updateOrCreate(
                    ['facility_id' => $actor->facilityId, 'day_of_week' => $day],
                    ['opens_at' => $opens, 'closes_at' => $closes, 'is_active' => true],
                );
            }

            $this->audit($actor, 'facility_hours', $actor->facilityId, 'publish_facility_hours', $before, FacilitySchedule::query()->where('facility_id', $actor->facilityId)->get()->toArray());
        });

        return $this->facilityHours($actor->facilityId);
    }

    /**
     * Replace one clinician's posted week at the actor's facility (the caller
     * has checked they are posted there — FrontDeskService::isClinicianAt).
     * Same shape as publishFacilityHours(), plus the slot length.
     *
     * @param  list<array{day_of_week:int,starts_at:string,ends_at:string}>  $days
     * @return Collection<int,ProviderAvailability>
     * @throws FrontDeskRefusal hours_invalid | slot_duration_invalid
     */
    public function publishProviderHours(DeskActor $actor, string $providerId, array $days, int $slotMinutes = self::DEFAULT_SLOT_MINUTES): Collection
    {
        if ($slotMinutes < self::MIN_SLOT_MINUTES || $slotMinutes > self::MAX_SLOT_MINUTES) {
            throw new FrontDeskRefusal('slot_duration_invalid', 422);
        }

        $week = $this->normaliseWeek($days);

        DB::transaction(function () use ($actor, $providerId, $week, $slotMinutes) {
            $rows = fn () => ProviderAvailability::query()
                ->where('facility_id', $actor->facilityId)
                ->where('provider_id', $providerId);

            $before = $rows()->get()->toArray();

            $rows()->whereNotIn('day_of_week', array_keys($week))->delete();

            foreach ($week as $day => [$starts, $ends]) {
                ProviderAvailability::updateOrCreate(
                    ['facility_id' => $actor->facilityId, 'provider_id' => $providerId, 'day_of_week' => $day],
                    ['starts_at' => $starts, 'ends_at' => $ends, 'slot_duration_minutes' => $slotMinutes, 'is_active' => true],
                );
            }

            $this->audit($actor, 'provider_hours', $providerId, 'publish_provider_hours', $before, $rows()->get()->toArray());
        });

        return $this->providerHours($actor->facilityId, $providerId);
    }

    // ──────────────────────────────────────────────────────────────────
    // Materialising slots
    // ──────────────────────────────────────────────────────────────────

    /**
     * Materialise `appointment_slots` for one facility over the horizon.
     *
     * @return array{created:int, closed:int, reopened:int}
     */
    public function generateSlots(string $facilityId, int $horizonDays = self::HORIZON_DAYS, ?CarbonInterface $from = null): array
    {
        $result = ['created' => 0, 'closed' => 0, 'reopened' => 0];

        if (! Str::isUuid($facilityId)) {
            return $result;
        }

        $tz    = config('app.timezone');
        $now   = Carbon::now($tz);
        $start = ($from ? Carbon::instance($from)->timezone($tz) : $now->copy())->startOfDay();
        $until = $start->copy()->addDays(max(1, $horizonDays))->subSecond();

        $facilityWeek = $this->facilityHours($facilityId)->keyBy('day_of_week');
        $facilityHasHours = $facilityWeek->isNotEmpty();
        $posted = $this->providerHours($facilityId)->groupBy('provider_id');

        foreach ($posted as $providerId => $rows) {
            $providerId = (string) $providerId;
            $byDay      = $rows->keyBy('day_of_week');

            /** @var array<string,true> $wanted starts_at (Y-m-d H:i:s) → true */
            $wanted = [];
            $inserts = [];

            for ($day = $start->copy(); $day->lte($until); $day->addDay()) {
                /** @var ProviderAvailability|null $hours */
                $hours = $byDay->get((int) $day->dayOfWeekIso);

                if (! $hours) {
                    continue;
                }

                [$open, $close] = $this->clock($day, $hours->starts_at, $hours->ends_at);

                if ($facilityHasHours) {
                    /** @var FacilitySchedule|null $facilityDay */
                    $facilityDay = $facilityWeek->get((int) $day->dayOfWeekIso);

                    if (! $facilityDay) {
                        continue; // the facility is closed that weekday
                    }

                    [$fOpen, $fClose] = $this->clock($day, $facilityDay->opens_at, $facilityDay->closes_at);
                    $open  = $open->max($fOpen);
                    $close = $close->min($fClose);
                }

                $step = max(self::MIN_SLOT_MINUTES, (int) ($hours->slot_duration_minutes ?: self::DEFAULT_SLOT_MINUTES));

                for ($slot = $open->copy(); $slot->copy()->addMinutes($step)->lte($close); $slot->addMinutes($step)) {
                    if ($slot->lt($now)) {
                        continue;
                    }

                    $wanted[$slot->format('Y-m-d H:i:s')] = $step;
                }
            }

            $existing = AppointmentSlot::query()
                ->where('facility_id', $facilityId)
                ->where('provider_id', $providerId)
                ->whereBetween('starts_at', [$start, $until])
                ->get(['id', 'starts_at', 'status', 'booked_count']);

            $have = [];

            foreach ($existing as $slot) {
                $key = Carbon::instance($slot->starts_at)->timezone($tz)->format('Y-m-d H:i:s');
                $have[$key] = true;

                if (isset($wanted[$key])) {
                    if ($slot->status !== 'open' && (int) $slot->booked_count === 0) {
                        AppointmentSlot::whereKey($slot->id)->update(['status' => 'open', 'updated_at' => now()]);
                        $result['reopened']++;
                    }

                    continue;
                }

                // The hours no longer produce this time: withdraw it from
                // patients unless someone already holds it.
                if ($slot->status === 'open' && (int) $slot->booked_count === 0) {
                    AppointmentSlot::whereKey($slot->id)->update(['status' => 'closed', 'updated_at' => now()]);
                    $result['closed']++;
                }
            }

            foreach ($wanted as $startsAt => $step) {
                if (isset($have[$startsAt])) {
                    continue;
                }

                $at = Carbon::parse($startsAt, $tz);

                $inserts[] = [
                    'id'           => (string) Str::uuid(),
                    'facility_id'  => $facilityId,
                    'provider_id'  => $providerId,
                    'starts_at'    => $at,
                    'ends_at'      => $at->copy()->addMinutes($step),
                    'capacity'     => 1,
                    'booked_count' => 0,
                    'status'       => 'open',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }

            foreach (array_chunk($inserts, 500) as $chunk) {
                DB::table('appointment_slots')->insert($chunk);
            }

            $result['created'] += count($inserts);
        }

        return $result;
    }

    /**
     * Every facility that has posted hours, in turn.
     *
     * @return array<string, array{created:int, closed:int, reopened:int}> keyed by facility id
     */
    public function generateAll(int $horizonDays = self::HORIZON_DAYS, ?CarbonInterface $from = null): array
    {
        $facilityIds = ProviderAvailability::query()
            ->where('is_active', true)
            ->distinct()
            ->orderBy('facility_id')
            ->pluck('facility_id');

        $out = [];

        foreach ($facilityIds as $facilityId) {
            $out[(string) $facilityId] = $this->generateSlots((string) $facilityId, $horizonDays, $from);
        }

        return $out;
    }

    // ──────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────

    /**
     * Validate and index a week: day → [starts 'HH:MM:SS', ends 'HH:MM:SS'].
     * A day listed twice, an unknown weekday, a malformed time or a window
     * that does not open before it closes is refused as a whole.
     *
     * @param  list<array{day_of_week:int|string,starts_at:string,ends_at:string}>  $days
     * @return array<int, array{0:string,1:string}>
     * @throws FrontDeskRefusal hours_invalid
     */
    private function normaliseWeek(array $days): array
    {
        $week = [];

        foreach ($days as $entry) {
            $day = (int) ($entry['day_of_week'] ?? 0);

            if (! in_array($day, self::DAYS, true) || isset($week[$day])) {
                throw new FrontDeskRefusal('hours_invalid', 422);
            }

            $starts = $this->time($entry['starts_at'] ?? null);
            $ends   = $this->time($entry['ends_at'] ?? null);

            if ($starts === null || $ends === null || $starts >= $ends) {
                throw new FrontDeskRefusal('hours_invalid', 422);
            }

            $week[$day] = [$starts, $ends];
        }

        ksort($week);

        return $week;
    }

    /** 'HH:MM' or 'HH:MM:SS' → 'HH:MM:SS', else null. */
    private function time(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', trim($value), $m)) {
            return null;
        }

        [$h, $i] = [(int) $m[1], (int) $m[2]];

        if ($h > 23 || $i > 59) {
            return null;
        }

        return sprintf('%02d:%02d:00', $h, $i);
    }

    /** @return array{0:Carbon,1:Carbon} the day's window as instants in the application timezone */
    private function clock(Carbon $day, string $opens, string $closes): array
    {
        $tz = config('app.timezone');

        return [
            Carbon::parse($day->toDateString() . ' ' . $opens, $tz),
            Carbon::parse($day->toDateString() . ' ' . $closes, $tz),
        ];
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function window(?CarbonInterface $from): array
    {
        $tz   = config('app.timezone');
        $from = $from ? Carbon::instance($from)->timezone($tz) : Carbon::now($tz);

        return [$from, $from->copy()->startOfDay()->addDays(self::HORIZON_DAYS)->subSecond()];
    }

    /**
     * One audit_events row per publish, in the shape FrontDeskService writes
     * for appointment transitions (no patient — this is facility configuration).
     *
     * @param  array<int,mixed>  $before
     * @param  array<int,mixed>  $after
     */
    private function audit(DeskActor $actor, string $resourceType, string $resourceId, string $action, array $before, array $after): void
    {
        AuditEvent::create([
            'actor_id'      => $actor->userId,
            'facility_id'   => $actor->facilityId,
            'action_type'   => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'source_system' => $actor->channel,
            'ip_address'    => $actor->ip,
            'reason'        => __('desk_api.reason_hours_published'),
            'before_state'  => ['rows' => $before],
            'after_state'   => ['rows' => $after],
        ]);
    }
}
