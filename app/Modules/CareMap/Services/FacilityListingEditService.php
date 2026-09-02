<?php

namespace App\Modules\CareMap\Services;

use App\Models\CareFacility;
use App\Models\CareFacilityHour;
use App\Models\CareFacilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Self-service editing of a directory listing by its approved claimant.
 *
 * Every method here takes the CareFacility as an object the caller already
 * resolved from an approved claim — none of them accepts an id, so no caller
 * can pass one in from a request. That is the whole point of the signature.
 *
 * Every write lands in `facility_update_audits` with the actor's user id and
 * `actor_type = 'facility'`. Two things depend on that and neither is optional:
 *
 *  - 903 of these rows are MINSANTE registry data. An edit to one must say who
 *    changed it and from what, or the institutional record silently becomes
 *    something nobody can account for.
 *  - OsmFacilityImporter::loadHumanEditedFields() reads exactly this table and
 *    treats `actor_type IN (user, api_partner, facility, admin)` as untouchable.
 *    A facility's own phone number therefore survives the next import run
 *    because it was audited, not because anyone remembered to protect it.
 */
class FacilityListingEditService
{
    /** Fields a claimant may edit. Notably absent: verification_status. */
    public const CONTACT_FIELDS = [
        'phone_primary',
        'phone_secondary',
        'email',
        'website',
        'description',
    ];

    public const SOURCE = 'facility_self_service';

    /**
     * Apply contact-detail edits and audit every field that actually changed.
     *
     * @param  array<string,mixed> $input
     * @return list<string>  the field names that changed
     */
    public function updateContact(CareFacility $listing, array $input, string $actorId): array
    {
        $changes = [];
        $audits  = [];

        foreach (self::CONTACT_FIELDS as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $new = $this->normalise($input[$field]);
            $old = $this->normalise($listing->{$field});

            // 'N/A' is not a phone number (see CareFacility::PHONE_PLACEHOLDER).
            // A claimant clearing that field is replacing a placeholder, and the
            // audit row should say so rather than pretend they deleted a number.
            if ($field === 'phone_primary') {
                $old = CareFacility::realValue($old);

                // The column is NOT NULL. Keep the placeholder rather than
                // writing an empty string that would read as a real, blank number.
                if ($new === null) {
                    $new = CareFacility::PHONE_PLACEHOLDER;
                }
            }

            if ($old === $new || ($old === null && $new === CareFacility::PHONE_PLACEHOLDER)) {
                continue;
            }

            $changes[$field] = $new;

            $audits[] = [
                'id'              => (string) Str::uuid(),
                'facility_id'     => $listing->id,
                'actor_id'        => $actorId,
                'actor_type'      => 'facility',
                'field_changed'   => $field,
                'old_value'       => $old,
                'new_value'       => $new,
                'source'          => self::SOURCE,
                'requires_review' => false,
                'created_at'      => now(),
            ];
        }

        if ($changes === []) {
            return [];
        }

        DB::transaction(function () use ($listing, $changes, $audits) {
            $listing->forceFill($changes + ['last_profile_update_at' => now()])->save();
            DB::table('facility_update_audits')->insert($audits);
        });

        return array_keys($changes);
    }

    /**
     * Add one service/specialty line to a listing.
     *
     * `care_facility_services` holds 11 rows for 1,863 facilities — the
     * directory currently cannot answer "who does dialysis in Bamenda". Only
     * the facility knows, so only the facility can fill it in.
     *
     * @param  array<string,mixed> $input
     */
    public function addService(CareFacility $listing, array $input, string $actorId): CareFacilityService
    {
        return DB::transaction(function () use ($listing, $input, $actorId) {
            $service = CareFacilityService::create([
                'facility_id'            => $listing->id,
                'service_name'           => $input['service_name'],
                'service_category'       => $input['service_category'],
                'specialty'              => $input['specialty'] ?? null,
                'availability_status'    => $input['availability_status'] ?? 'available',
                'appointment_required'   => (bool) ($input['appointment_required'] ?? false),
                'walk_in_allowed'        => (bool) ($input['walk_in_allowed'] ?? true),
                'telemedicine_available' => (bool) ($input['telemedicine_available'] ?? false),
                'last_updated_at'        => now(),
            ]);

            $this->audit($listing, $actorId, 'service:' . $service->id, null, $service->service_name);
            $listing->forceFill(['last_profile_update_at' => now()])->save();

            return $service;
        });
    }

    /**
     * Remove a service line — scoped to the listing, so an id belonging to
     * another facility resolves to nothing rather than to somebody else's row.
     */
    public function removeService(CareFacility $listing, string $serviceId, string $actorId): bool
    {
        $service = CareFacilityService::where('id', $serviceId)
            ->where('facility_id', $listing->id)
            ->first();

        if ($service === null) {
            return false;
        }

        DB::transaction(function () use ($listing, $service, $actorId) {
            $this->audit($listing, $actorId, 'service:' . $service->id, $service->service_name, null);
            $service->delete();
        });

        return true;
    }

    /**
     * Replace the whole opening-hours week in one write.
     *
     * Hours are read as a set (is this open on Sunday?), so editing them one
     * row at a time invites a half-saved week. `care_facility_hours` holds 0
     * rows today, so there is nothing to migrate and no partial state to
     * preserve.
     *
     * @param  array<int,array{is_closed?:mixed,is_24_hours?:mixed,opens_at?:?string,closes_at?:?string}> $week keyed 0..6
     */
    public function replaceHours(CareFacility $listing, array $week, string $actorId): int
    {
        $rows = [];

        foreach (range(0, 6) as $day) {
            $spec = $week[$day] ?? null;

            if (! is_array($spec)) {
                continue;
            }

            $is24     = (bool) ($spec['is_24_hours'] ?? false);
            $isClosed = ! $is24 && (bool) ($spec['is_closed'] ?? false);
            $opens    = $is24 || $isClosed ? null : ($spec['opens_at'] ?: null);
            $closes   = $is24 || $isClosed ? null : ($spec['closes_at'] ?: null);

            // Neither open-all-day, nor closed, nor given a time: no statement
            // was made about this day, so none is stored.
            if (! $is24 && ! $isClosed && ($opens === null || $closes === null)) {
                continue;
            }

            $rows[] = [
                'id'              => (string) Str::uuid(),
                'facility_id'     => $listing->id,
                'day_of_week'     => $day,
                'opens_at'        => $opens,
                'closes_at'       => $closes,
                'is_closed'       => $isClosed,
                'is_24_hours'     => $is24,
                'service_context' => 'General',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        DB::transaction(function () use ($listing, $rows, $actorId) {
            $before = CareFacilityHour::where('facility_id', $listing->id)->count();

            CareFacilityHour::where('facility_id', $listing->id)->delete();

            if ($rows !== []) {
                DB::table('care_facility_hours')->insert($rows);
            }

            $this->audit($listing, $actorId, 'opening_hours', (string) $before, (string) count($rows));
            $listing->forceFill(['last_profile_update_at' => now()])->save();
        });

        return count($rows);
    }

    private function audit(CareFacility $listing, string $actorId, string $field, ?string $old, ?string $new): void
    {
        DB::table('facility_update_audits')->insert([
            'id'              => (string) Str::uuid(),
            'facility_id'     => $listing->id,
            'actor_id'        => $actorId,
            'actor_type'      => 'facility',
            'field_changed'   => $field,
            'old_value'       => $old,
            'new_value'       => $new,
            'source'          => self::SOURCE,
            'requires_review' => false,
            'created_at'      => now(),
        ]);
    }

    private function normalise(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
