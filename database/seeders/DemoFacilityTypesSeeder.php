<?php

namespace Database\Seeders;

use App\Models\CareFacility;
use App\Models\CareFacilityHour;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One demo tenant, one public listing and the operator logins for EVERY
 * facility type the directory knows — so a reviewer can open the settings,
 * team and desk pages of a dental practice or a maternity exactly as they can
 * for the demo hospital and pharmacy. Called at the end of FinderDemoSeeder.
 *
 * Two registers, two vocabularies:
 *   - `facilities` (tenants) `type` is a free string; the estate uses
 *     hospital | clinic | health_center | laboratory | pharmacy. It is read for
 *     display only (GlobalSearch, FacilitySettingsService falls back to it when
 *     there is no listing), never switched on.
 *   - `care_facilities` (public directory) `facility_type` is what the finder
 *     filters on: clinic, pharmacy, hospital, health_center, laboratory, dental,
 *     imaging_center, eye_clinic, specialist, diagnostic_center, maternity …
 * Where the tenant vocabulary has a word (five types above) the two coincide,
 * so the tenant `type` is simply the directory value for every type here —
 * dental / imaging_center / eye_clinic / diagnostic_center / maternity extend
 * the tenant vocabulary rather than being squashed into "clinic", which would
 * make FacilitySettingsService describe a dentist as a clinic when the listing
 * is missing. `care_facilities` has NO is_demo column: demo listings are
 * recognisable by source_system = 'demo' and their name.
 *
 * Every login follows FinderDemoSeeder's contract: password demo1234,
 * is_demo = true, primary_facility_id AND an active FacilityRoleAssignment at
 * that tenant (the pairing RequireFacilityContext + roleAtFacility need), and
 * any other active posting ended so the account never meets /select-facility.
 *
 * Idempotent: tenants by name, listings by name, users by email, hours are
 * only written when the listing has none (so an operator's edits survive).
 * A listing that already carries a facility_id is never taken over.
 */
class DemoFacilityTypesSeeder extends Seeder
{
    private const PASSWORD = 'demo1234';

    /** Written to care_facilities.source_system on rows this seeder creates. */
    public const SOURCE_SYSTEM = 'demo';

    /**
     * facility_type => [tenant name, city, region, lat, lng, phone, logins[email => [role, label]]].
     * Tenant `type` = the same value (see class doc). Roles that do not exist
     * in RolesSeeder are skipped with a warning, never invented.
     */
    public static function catalogue(): array
    {
        return [
            'hospital' => [
                'name'   => 'Hôpital Central de Yaoundé',
                'city'   => 'Yaoundé', 'region' => 'Centre', 'lat' => 3.8667, 'lng' => 11.5167,
                'phone'  => '+237222234012',
                'logins' => [
                    'demo.hospital.admin@opescare.test' => ['hospital_admin', 'Hospital Admin'],
                ],
            ],
            'clinic' => [
                'name'   => 'Clinique Demo Bonapriso',
                'city'   => 'Douala', 'region' => 'Littoral', 'lat' => 4.0389, 'lng' => 9.7028,
                'phone'  => '+237233420101',
                'logins' => [
                    'demo.clinic.admin@opescare.test'     => ['clinic_admin', 'Clinic Admin'],
                    'demo.clinic.doctor@opescare.test'    => ['doctor', 'Clinic Doctor'],
                    'demo.clinic.reception@opescare.test' => ['receptionist', 'Clinic Reception'],
                    'demo.clinic.nurse@opescare.test'     => ['nurse', 'Clinic Nurse'],
                ],
            ],
            'health_center' => [
                'name'   => 'Centre de Santé Demo Nkolbisson',
                'city'   => 'Yaoundé', 'region' => 'Centre', 'lat' => 3.8712, 'lng' => 11.4531,
                'phone'  => '+237222310202',
                'logins' => [
                    'demo.healthcenter.admin@opescare.test'   => ['facility_admin', 'Health Center Admin'],
                    'demo.healthcenter.nurse@opescare.test'   => ['nurse', 'Health Center Nurse'],
                    'demo.healthcenter.midwife@opescare.test' => ['midwife', 'Health Center Midwife'],
                ],
            ],
            'laboratory' => [
                'name'   => 'Laboratoire Demo Akwa',
                'city'   => 'Douala', 'region' => 'Littoral', 'lat' => 4.0511, 'lng' => 9.6969,
                'phone'  => '+237233430303',
                'logins' => [
                    'demo.lab.manager@opescare.test'    => ['lab_manager', 'Lab Manager'],
                    'demo.lab.scientist@opescare.test'  => ['lab_scientist', 'Lab Scientist'],
                    'demo.lab.collection@opescare.test' => ['sample_collection', 'Lab Sample Collection'],
                ],
            ],
            'pharmacy' => [
                'name'   => 'Pharmacy ESSEC',
                'city'   => 'Douala', 'region' => 'Littoral', 'lat' => 4.0483, 'lng' => 9.7001,
                'phone'  => '+237233440404',
                'logins' => [
                    'demo.pharmacy.stock@opescare.test' => ['medicine_stock', 'Pharmacy Stock'],
                ],
            ],
            'dental' => [
                'name'   => 'Cabinet Dentaire Demo Bastos',
                'city'   => 'Yaoundé', 'region' => 'Centre', 'lat' => 3.8905, 'lng' => 11.5124,
                'phone'  => '+237222450505',
                'logins' => [
                    'demo.dental.admin@opescare.test'  => ['facility_admin', 'Dental Admin'],
                    'demo.dental.doctor@opescare.test' => ['doctor', 'Dental Doctor'],
                ],
            ],
            'imaging_center' => [
                'name'   => "Centre d'Imagerie Demo Bonanjo",
                'city'   => 'Douala', 'region' => 'Littoral', 'lat' => 4.0442, 'lng' => 9.6931,
                'phone'  => '+237233460606',
                'logins' => [
                    'demo.imaging.admin@opescare.test' => ['facility_admin', 'Imaging Admin'],
                    // imaging_tech exists since 2026-10-13 (RolesSeeder); labtech
                    // is the fallback on a deployment that lacks it.
                    'demo.imaging.tech@opescare.test'  => ['imaging_tech|labtech', 'Imaging Technician'],
                ],
            ],
            'eye_clinic' => [
                'name'   => 'Clinique Ophtalmologique Demo',
                'city'   => 'Yaoundé', 'region' => 'Centre', 'lat' => 3.8580, 'lng' => 11.5210,
                'phone'  => '+237222470707',
                'logins' => [
                    'demo.eye.admin@opescare.test'  => ['clinic_admin', 'Eye Clinic Admin'],
                    // `specialist` exists (dashboard profile specialist_doctor);
                    // falls back to doctor if a deployment lacks it.
                    'demo.eye.doctor@opescare.test' => ['specialist|doctor', 'Eye Specialist'],
                ],
            ],
            'diagnostic_center' => [
                'name'   => 'Centre de Diagnostic Demo',
                'city'   => 'Douala', 'region' => 'Littoral', 'lat' => 4.0605, 'lng' => 9.7412,
                'phone'  => '+237233480808',
                'logins' => [
                    'demo.diagnostic.admin@opescare.test' => ['facility_admin', 'Diagnostic Admin'],
                    'demo.diagnostic.lab@opescare.test'   => ['lab_manager', 'Diagnostic Lab Manager'],
                ],
            ],
            // Blood bank (2026-10-20, DX-5): its own roles, and a linked
            // listing so stock entered here can reach the Blood Finder. No
            // stock is seeded — an empty finder is correct.
            'blood_bank' => [
                'name'   => 'Banque de Sang Demo Messa',
                'city'   => 'Yaoundé', 'region' => 'Centre', 'lat' => 3.8790, 'lng' => 11.5050,
                'phone'  => '+237222501010',
                'logins' => [
                    'demo.bloodbank.manager@opescare.test' => ['blood_bank_manager|facility_admin', 'Blood Bank Manager'],
                    'demo.bloodbank.officer@opescare.test' => ['blood_bank_officer|labtech', 'Blood Bank Officer'],
                ],
            ],
            'maternity' => [
                'name'   => 'Maternité Demo Essos',
                'city'   => 'Yaoundé', 'region' => 'Centre', 'lat' => 3.8801, 'lng' => 11.5402,
                'phone'  => '+237222490909',
                'logins' => [
                    'demo.maternity.admin@opescare.test'   => ['facility_admin', 'Maternity Admin'],
                    'demo.maternity.midwife@opescare.test' => ['midwife', 'Maternity Midwife'],
                ],
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::catalogue() as $type => $spec) {
            $tenant  = $this->tenant($type, $spec);
            $listing = $this->listing($tenant, $type, $spec);
            $this->hours($listing);

            foreach ($spec['logins'] as $email => [$roleSpec, $label]) {
                $this->login($email, $roleSpec, "Demo — {$label}", $tenant);
            }

            $this->command?->info("  {$type}: {$tenant->name} ↔ listing {$listing->id}");
        }
    }

    /** The tenant row, by name. The hospital and pharmacy already exist. */
    private function tenant(string $type, array $spec): Facility
    {
        $tenant = Facility::withoutGlobalScopes()->where('name', $spec['name'])->first();

        if ($tenant) {
            return $tenant;
        }

        // is_demo is not fillable on Facility (mass assignment would drop it
        // silently and the IsDemoRecord trait would then stamp config('demo.enabled')).
        $tenant = new Facility(['name' => $spec['name'], 'type' => $type, 'status' => 'active']);
        $tenant->is_demo = true;
        $tenant->save();

        return $tenant;
    }

    /**
     * The public listing linked to the tenant. Order of preference:
     *  1. a listing already linked to this tenant (any name) — nothing to do;
     *  2. an UNLINKED listing of this type whose name matches (the hospital
     *     case: the imported "Hôpital Central de Yaoundé" row) — link it;
     *  3. create a demo listing.
     * A listing linked to another tenant is never taken over.
     */
    private function listing(Facility $tenant, string $type, array $spec): CareFacility
    {
        $linked = CareFacility::where('facility_id', $tenant->id)->orderBy('created_at')->first();
        if ($linked) {
            return $linked;
        }

        $unlinked = CareFacility::whereNull('facility_id')
            ->where('facility_type', $type)
            ->where('facility_name', $spec['name'])
            ->orderBy('created_at')
            ->first();

        if ($unlinked) {
            $unlinked->forceFill(['facility_id' => $tenant->id])->save();

            return $unlinked;
        }

        return CareFacility::create([
            'facility_id'         => $tenant->id,
            'facility_name'       => $spec['name'],
            'facility_type'       => $type,
            'ownership_type'      => 'private',
            'verification_status' => 'self_reported',
            'listing_status'      => 'active',
            'country_code'        => 'CM',
            'region'              => $spec['region'],
            'city'                => $spec['city'],
            'address'             => 'Demo — ' . $spec['city'],
            'latitude'            => $spec['lat'],
            'longitude'           => $spec['lng'],
            'phone_primary'       => $spec['phone'],
            'source_system'       => self::SOURCE_SYSTEM,
            'source_attribution'  => 'OpesCare demo data',
        ]);
    }

    /**
     * A working week (Mon–Fri 08:00–17:00, Sat 08:00–13:00, Sun closed) in the
     * same table and shape FacilityListingEditService::replaceHours() writes,
     * so the settings page opens populated. Only when the listing has none.
     */
    private function hours(CareFacility $listing): void
    {
        if (CareFacilityHour::where('facility_id', $listing->id)->exists()) {
            return;
        }

        $rows = [];
        foreach (range(0, 6) as $day) {
            $closed   = $day === 0;
            $saturday = $day === 6;
            $rows[] = [
                'id'              => (string) Str::uuid(),
                'facility_id'     => $listing->id,
                'day_of_week'     => $day,
                'opens_at'        => $closed ? null : '08:00',
                'closes_at'       => $closed ? null : ($saturday ? '13:00' : '17:00'),
                'is_closed'       => $closed,
                'is_24_hours'     => false,
                'service_context' => 'General',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        DB::table('care_facility_hours')->insert($rows);
    }

    /** "specialist|doctor" = first role that exists wins. */
    private function login(string $email, string $roleSpec, string $name, Facility $tenant): void
    {
        $role = null;
        foreach (explode('|', $roleSpec) as $candidate) {
            if ($role = Role::where('name', $candidate)->first()) {
                break;
            }
        }

        if (! $role) {
            $this->command?->warn("  skip {$email}: no role among {$roleSpec}");

            return;
        }

        $user = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => $email],
            [
                'name'                => $name,
                'password'            => Hash::make(self::PASSWORD),
                'status'              => 'active',
                'is_demo'             => true,
                'role_id'             => $role->id,
                'primary_facility_id' => $tenant->id,
                'email_verified_at'   => now(),
            ]
        );

        FacilityRoleAssignment::updateOrCreate(
            ['user_id' => $user->id, 'facility_id' => $tenant->id, 'role_id' => $role->id],
            ['is_active' => true, 'expires_at' => null, 'assigned_at' => now()]
        );

        // One posting per demo account — see FinderDemoSeeder.
        FacilityRoleAssignment::query()
            ->where('user_id', $user->id)
            ->where('facility_id', '!=', $tenant->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->command?->info("    {$email}  ({$role->name})");
    }
}
