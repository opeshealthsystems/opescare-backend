<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A professional exists before a facility admits them.
 *
 * `staff_profiles.facility_id` was NOT NULL, which encoded the assumption that
 * every clinical record is created BY a facility, FOR someone it already
 * employs. Two paths in this app break that assumption, and both were failing
 * with SQLSTATE 23502:
 *
 *  - `ProfessionalRegistrationController::store()` — self-registration at
 *    /signup/professional never sets a facility, because at that moment there
 *    isn't one.
 *  - `ProfessionalProfileController::resolveProfile()` — lazily creates the
 *    staff record on first visit to /professional/profile, seeded from
 *    `users.primary_facility_id`, which is null for exactly the self-registered
 *    clinician that page exists to serve. The result was a 500 on a person's
 *    own profile page.
 *
 * The alternative — inventing a non-null facility so the insert succeeds —
 * would be worse than a null. A staff row pointing at an arbitrary facility is
 * a false claim of employment, and `MobileProviderController` reads exactly
 * this column to decide who is listed and bookable at a facility; it would have
 * published the person as that facility's clinician.
 *
 * NULL is the honest value, and every existing reader already handles it:
 * `MobileProviderAuthController::resolveFacilityId()` falls back and then
 * refuses the session with a 409, and the two `where('facility_id', …)` lookups
 * in `MobileProviderController` simply never match, so an unattached clinician
 * is a 404 rather than a bookable provider. `facility_join_requests` is how the
 * column later gets a value.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Raw DDL rather than ->nullable()->change(): a Blueprint change()
        // restates the whole column, and restating this one would have to
        // repeat its foreign key and risk dropping it. DROP NOT NULL touches
        // exactly the one property being changed.
        DB::statement('ALTER TABLE staff_profiles ALTER COLUMN facility_id DROP NOT NULL');
    }

    public function down(): void
    {
        // Reversing is only possible while no unattached professional exists.
        // Failing loudly beats silently deleting or re-pointing someone's
        // clinical staff record to satisfy a constraint.
        $unattached = DB::table('staff_profiles')->whereNull('facility_id')->count();

        if ($unattached > 0) {
            throw new \RuntimeException(
                "Cannot restore NOT NULL on staff_profiles.facility_id: {$unattached} "
                . 'staff profile(s) are not attached to a facility. Attach or remove '
                . 'them first — this migration will not choose a facility for them.'
            );
        }

        DB::statement('ALTER TABLE staff_profiles ALTER COLUMN facility_id SET NOT NULL');
    }
};
