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
        'email' => 'demo.specialist@opescare.test', 'roles' => ['doctor'],
        'job_title' => 'Cardiologist', 'department' => 'Cardiology',
    ];

    public const NURSE = [
        'id' => '00000000-0000-0000-0000-200000000010', 'name' => 'Nurse Fatou Traoré',
        'email' => 'demo.nurse@opescare.test', 'roles' => ['nurse'],
        'job_title' => 'Registered Nurse', 'department' => 'Outpatients',
    ];

    public const LAB_TECH = [
        'id' => '00000000-0000-0000-0000-200000000013', 'name' => 'Lab Tech Boubacar Keïta',
        'email' => 'demo.labtech@opescare.test', 'roles' => ['lab_technician', 'nurse'],
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
     * Create the members that are missing. Never touches an existing account.
     */
    public static function ensure(?Command $command = null): void
    {
        $facilityId = DemoFacilityResolver::primaryHospital();

        foreach ([self::DOCTOR, self::SPECIALIST, self::NURSE, self::LAB_TECH] as $member) {
            if (self::id($member)) {
                continue;
            }

            $roleId = null;
            foreach ($member['roles'] as $roleName) {
                $roleId = DB::table('roles')->where('name', $roleName)->value('id');
                if ($roleId) {
                    break;
                }
            }

            if (! $roleId) {
                $command?->warn("  DemoCareTeam: no role for {$member['name']} (" . implode('/', $member['roles']) . ') — skipped.');
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

            self::writeStaffProfile($id, $member);

            self::$cache[$member['email']] = $id;
            $command?->info("  DemoCareTeam: created {$member['name']} <{$member['email']}>");
        }
    }

    /**
     * The specialist's title is read from staff_profiles by the care-pathway
     * seeder ("Dr. Ibrahim Sow (Cardiologist)"); write only the columns this
     * estate's table actually has.
     */
    private static function writeStaffProfile(string $userId, array $member): void
    {
        if (! Schema::hasTable('staff_profiles')) {
            return;
        }

        $columns = Schema::getColumnListing('staff_profiles');
        $row = array_intersect_key([
            'job_title'  => $member['job_title'],
            'department' => $member['department'],
            'created_at' => now(),
            'updated_at' => now(),
        ], array_flip($columns));

        if (in_array('id', $columns, true) && ! DB::table('staff_profiles')->where('user_id', $userId)->exists()) {
            $row['id'] = (string) Str::orderedUuid();
        }

        DB::table('staff_profiles')->updateOrInsert(['user_id' => $userId], $row);
    }

    /** Tests and long-running processes: drop the memoised ids. */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
