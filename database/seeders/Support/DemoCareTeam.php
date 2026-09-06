<?php

namespace Database\Seeders\Support;

use App\Models\FacilityRoleAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The demo patient's care team — the clinicians every story row hangs off
 * (diagnoses.provider_id and allergy_records.provider_id are NOT NULL).
 *
 * The story seeders were written against reserved user ids
 * (`00000000-0000-0000-0000-2000…`). An estate seeded by FinderDemoSeeder has
 * the same PEOPLE under different ids (Dr. Amara Diallo already exists on the
 * Finder as demo.doctor@opescare.test), so members are resolved by EMAIL
 * first and by reserved id second, and only created when neither exists.
 *
 * Created members are demo accounts like every other seeded login: is_demo,
 * password "demo1234", active, attached to the primary hospital.
 *
 * ── Role names are the canonical `roles.name` values (RolesSeeder) ─────────
 *
 * The lab tech used to list `lab_technician` first — a role seeded nowhere —
 * so ensure() fell through to the second choice, `nurse`, and wrote a NURSE
 * onto both users.role_id and the facility_role_assignments row. Because
 * EnsurePortalAccess and the capability gate both judge the facility-scoped
 * role, demo.labtech signed in as a nurse: nurse sidebar, nurse capabilities,
 * no lab surfaces. The specialist had the same shape of problem (`doctor`
 * instead of `specialist`, so the badge read "Doctor").
 *
 * ensure() therefore also REALIGNS an existing demo member whose stored role
 * is no longer one of `roles` — both rows, because fixing users.role_id alone
 * leaves the assignment row (the one the portal gate actually reads) on the
 * wrong role. Only is_demo accounts are ever touched.
 */
final class DemoCareTeam
{
    public const DOCTOR = [
        'id' => '00000000-0000-0000-0000-200000000001', 'name' => 'Dr. Amara Diallo',
        'email' => 'demo.doctor@opescare.test', 'roles' => ['doctor'],
        'job_title' => 'General Practitioner', 'department' => 'General Medicine',
    ];

    public const SPECIALIST = [
        'id' => '00000000-0000-0000-0000-200000000011', 'name' => 'Dr. Ibrahim Sow',
        'email' => 'demo.specialist@opescare.test', 'roles' => ['specialist', 'doctor'],
        'job_title' => 'Cardiologist', 'department' => 'Cardiology',
    ];

    public const NURSE = [
        'id' => '00000000-0000-0000-0000-200000000010', 'name' => 'Nurse Fatou Traoré',
        'email' => 'demo.nurse@opescare.test', 'roles' => ['nurse'],
        'job_title' => 'Registered Nurse', 'department' => 'Outpatients',
    ];

    public const LAB_TECH = [
        'id' => '00000000-0000-0000-0000-200000000013', 'name' => 'Lab Tech Boubacar Keïta',
        'email' => 'demo.labtech@opescare.test', 'roles' => ['labtech', 'lab_scientist'],
        'job_title' => 'Laboratory Technician', 'department' => 'Laboratory',
    ];

    public const PASSWORD = 'demo1234';

    /** @var array<string,string|null> memoised ids by email */
    private static array $cache = [];

    public static function doctor(): ?string
    {
        return self::id(self::DOCTOR);
    }

    public static function specialist(): ?string
    {
        return self::id(self::SPECIALIST);
    }

    public static function nurse(): ?string
    {
        return self::id(self::NURSE);
    }

    public static function labTech(): ?string
    {
        return self::id(self::LAB_TECH);
    }

    /** The user id of a member, or null when this estate does not have them. */
    public static function id(array $member): ?string
    {
        $email = $member['email'];

        if (array_key_exists($email, self::$cache)) {
            return self::$cache[$email];
        }

        $id = DB::table('users')->where('email', $email)->value('id')
            ?? DB::table('users')->where('id', $member['id'])->value('id');

        return self::$cache[$email] = $id ? (string) $id : null;
    }

    /**
     * Create the members that are missing; realign the role of an existing
     * DEMO member whose stored role is no longer one of the member's roles.
     * Never changes a real person's account (is_demo = false is left alone).
     */
    public static function ensure(?Command $command = null): void
    {
        $facilityId = DemoFacilityResolver::primaryHospital();

        foreach ([self::DOCTOR, self::SPECIALIST, self::NURSE, self::LAB_TECH] as $member) {
            // `roles` is a preference list: the FIRST name this estate has
            // seeded is the member's role; the rest are fallbacks for an estate
            // that lacks it (specialist → doctor), never alternatives.
            $roleId = null;
            foreach ($member['roles'] as $roleName) {
                $roleId = DB::table('roles')->where('name', $roleName)->value('id');
                if ($roleId) {
                    $roleId = (string) $roleId;
                    break;
                }
            }

            if (! $roleId) {
                $command?->warn("  DemoCareTeam: no role for {$member['name']} (" . implode('/', $member['roles']) . ') — skipped.');
                continue;
            }

            if ($existingId = self::id($member)) {
                self::realign($existingId, $member, $roleId, $facilityId, $command);
                continue;
            }

            // Prefer the reserved id (the story seeders' constants); take a fresh
            // one only if something else already holds it.
            $id = DB::table('users')->where('id', $member['id'])->exists()
                ? (string) Str::orderedUuid()
                : $member['id'];

            DB::table('users')->insert([
                'id'                  => $id,
                'name'                => $member['name'],
                'email'               => $member['email'],
                'password'            => Hash::make(self::PASSWORD),
                'status'              => 'active',
                'is_demo'             => true,
                'role_id'             => $roleId,
                'primary_facility_id' => $facilityId,
                'email_verified_at'   => now(),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            if ($facilityId && Schema::hasTable('facility_role_assignments')) {
                FacilityRoleAssignment::updateOrCreate(
                    ['user_id' => $id, 'facility_id' => $facilityId, 'role_id' => $roleId],
                    ['is_active' => true, 'expires_at' => null, 'assigned_at' => now()]
                );
            }

            self::writeStaffProfile($id, $member, $facilityId);

            self::$cache[$member['email']] = $id;
            $command?->info("  DemoCareTeam: created {$member['name']} <{$member['email']}>");
        }
    }

    /**
     * An existing member whose role drifted (the lab tech seeded as a nurse,
     * the specialist seeded as a plain doctor): put users.role_id AND the
     * active assignment at the primary hospital on the member's role. Both
     * rows, or the portal gate — which reads the assignment — keeps seeing the
     * old role.
     */
    private static function realign(string $userId, array $member, string $roleId, ?string $facilityId, ?Command $command): void
    {
        $user = DB::table('users')->where('id', $userId)->first(['id', 'role_id', 'is_demo']);

        if (! $user || ! $user->is_demo) {
            return; // a real person's account — never ours to rewrite
        }

        $changed = [];

        if ((string) $user->role_id !== $roleId) {
            DB::table('users')->where('id', $userId)->update(['role_id' => $roleId, 'updated_at' => now()]);
            $changed[] = 'users.role_id';
        }

        if ($facilityId && Schema::hasTable('facility_role_assignments')) {
            $active = FacilityRoleAssignment::query()
                ->where('user_id', $userId)
                ->where('facility_id', $facilityId)
                ->active()
                ->get();

            $hasCorrect = $active->contains(fn ($a) => (string) $a->role_id === $roleId);
            $wrong      = $active->reject(fn ($a) => (string) $a->role_id === $roleId);

            if (! $hasCorrect) {
                // (user, facility, role) is unique — a fresh row rather than an
                // UPDATE that could collide with a dormant correct one.
                FacilityRoleAssignment::updateOrCreate(
                    ['user_id' => $userId, 'facility_id' => $facilityId, 'role_id' => $roleId],
                    ['is_active' => true, 'expires_at' => null, 'assigned_at' => now()]
                );
                $changed[] = 'facility_role_assignments (added)';
            }

            if ($wrong->isNotEmpty()) {
                FacilityRoleAssignment::query()
                    ->whereIn('id', $wrong->pluck('id')->all())
                    ->update(['is_active' => false]);
                $changed[] = 'facility_role_assignments (' . $wrong->count() . ' retired)';
            }
        }

        // The profile row used to fail silently (staff_profiles.facility_id and
        // staff_category are NOT NULL and were never supplied), so an existing
        // demo member may have none — write it now; updateOrInsert is idempotent.
        self::writeStaffProfile($userId, $member, $facilityId);

        if ($changed !== []) {
            $command?->info("  DemoCareTeam: realigned {$member['name']} <{$member['email']}> → {$member['roles'][0]} (" . implode(', ', $changed) . ')');
        }
    }

    /**
     * The specialist's title is read from staff_profiles by the care-pathway
     * seeder ("Dr. Ibrahim Sow (Cardiologist)"); write only the columns this
     * estate's table actually has.
     */
    private static function writeStaffProfile(string $userId, array $member, ?string $facilityId): void
    {
        if (! Schema::hasTable('staff_profiles')) {
            return;
        }

        // "Dr. Amara Diallo" → first "Amara", last "Diallo": the honorific is a
        // title, not a name (some estates make first/last NOT NULL).
        $words = array_values(array_filter(explode(' ', $member['name']), fn ($w) => ! in_array(rtrim($w, '.'), ['Dr', 'Nurse', 'Lab', 'Tech'], true)));
        $first = $words[0] ?? $member['name'];
        $last  = count($words) > 1 ? implode(' ', array_slice($words, 1)) : $first;

        $columns = Schema::getColumnListing('staff_profiles');
        $row = array_intersect_key([
            // Both NOT NULL on this estate (create_staff_hr_tables); every care
            // team member is clinical staff posted at the primary hospital.
            'facility_id'    => $facilityId,
            'staff_category' => 'clinical',
            'first_name'     => $first,
            'last_name'      => $last,
            'job_title'      => $member['job_title'],
            'department'     => $member['department'],
            'created_at'     => now(),
            'updated_at'     => now(),
        ], array_flip($columns));

        if (in_array('id', $columns, true) && ! DB::table('staff_profiles')->where('user_id', $userId)->exists()) {
            $row['id'] = (string) Str::orderedUuid();
        }

        try {
            // Inside DB::transaction on purpose: when the caller already holds a
            // transaction (the test suite's RefreshDatabase, a wrapping seeder)
            // this becomes a SAVEPOINT, so a failed insert is rolled back to it
            // and the outer transaction stays usable. Without it Postgres marks
            // the whole transaction aborted and every later statement fails
            // with 25P02 — the catch below would "swallow" the error and poison
            // everything after it.
            DB::transaction(function () use ($userId, $row) {
                DB::table('staff_profiles')->updateOrInsert(['user_id' => $userId], $row);
            });
        } catch (\Throwable $e) {
            // A profile is a nicety (the specialist's title on the referral);
            // the story must not fail because an estate's profile table wants
            // a column this seeder does not know.
        }
    }

    /** Tests and long-running processes: drop the memoised ids. */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
