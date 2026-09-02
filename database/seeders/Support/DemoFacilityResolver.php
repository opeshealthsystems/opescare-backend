<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Resolves the internal `facilities` id for a REAL Cameroonian institution.
 *
 * WHY THIS EXISTS
 * ---------------
 * Demo seeders used to write patient records against five invented `facilities`
 * rows — "Demo Central Hospital", "Demo City Clinic", "DemoCare Pharmacy" and
 * so on. Those names then rendered straight into the patient's own timeline,
 * which is exactly the "demo bullshit" a reviewer sees and rightly distrusts:
 * the app claims to hold 897 real MINSANTE institutions and then shows a visit
 * to a hospital that does not exist.
 *
 * The directory (`care_facilities`, 897 real rows) is the source of truth for
 * institution identity. `facilities` is the operational row a visit, vitals
 * reading or stock record actually hangs off, and `care_facilities.facility_id`
 * is the link between them.
 *
 * This resolver takes a real directory name and returns the operational id,
 * creating and linking the operational row only when the institution does not
 * have one yet. Hospitals and clinics were all linked by
 * BookableFacilityNetworkSeeder; laboratories and pharmacies were not, so for
 * those it fills the gap rather than inventing a parallel facility.
 *
 * The result: every demo clinical record points at an institution that exists,
 * and there is exactly ONE operational row per institution — no duplicates.
 */
class DemoFacilityResolver
{
    /** Her main hospital — both chronic conditions are managed here. */
    public const PRIMARY_HOSPITAL = 'Hôpital Central de Yaoundé';

    /** The one out-of-town episode of care in her history. */
    public const SECONDARY_HOSPITAL = 'Hôpital Laquintinie de Douala';

    /** Where her labs are run. */
    public const LABORATORY = 'Centre Pasteur du Cameroun — Yaoundé';

    /** Where she collects her Metformin and Amlodipine. */
    public const PHARMACY = 'Pharmacie Centrale de Yaoundé';

    /** A real clinic in Bastos, the Yaoundé quarter she lives in. */
    public const CLINIC = 'Polyclinique Bastos';

    /**
     * The five invented `facilities` rows demo seeders used to write against,
     * mapped to the real institution each one stood in for. Consumed by
     * RealFacilityRepairSeeder; kept here so the mapping lives next to the
     * names it resolves.
     */
    public const LEGACY_DEMO_FACILITIES = [
        '00000000-0000-0000-0000-100000000001' => self::PRIMARY_HOSPITAL,   // was "Demo Central Hospital"
        '00000000-0000-0000-0000-100000000002' => self::CLINIC,             // was "Demo City Clinic"
        '00000000-0000-0000-0000-100000000003' => self::SECONDARY_HOSPITAL, // was "Demo Specialist Hospital"
        '00000000-0000-0000-0000-100000000004' => self::PHARMACY,           // was "DemoCare Pharmacy"
        '00000000-0000-0000-0000-100000000005' => self::LABORATORY,         // was "Demo Diagnostic Laboratory"
    ];

    /** @var array<string,string|null> resolved ids, memoised per process */
    private static array $cache = [];

    /**
     * Operational `facilities` id for a real directory institution.
     *
     * Returns null when the directory row is absent, so a caller can skip
     * rather than attach a record to something that does not exist.
     */
    public static function resolve(string $directoryName): ?string
    {
        if (array_key_exists($directoryName, self::$cache)) {
            return self::$cache[$directoryName];
        }

        $row = DB::table('care_facilities')
            ->where('facility_name', $directoryName)
            ->first(['id', 'facility_id', 'facility_name', 'facility_type']);

        if (! $row) {
            return self::$cache[$directoryName] = null;
        }

        if ($row->facility_id) {
            return self::$cache[$directoryName] = (string) $row->facility_id;
        }

        // No operational row yet (true for laboratories and pharmacies, which
        // the bookable-network seeder deliberately leaves unlinked). Create one
        // carrying the institution's REAL name and link the directory to it.
        $facilityId = (string) Str::uuid();

        DB::table('facilities')->insert([
            'id'         => $facilityId,
            'name'       => $row->facility_name,
            'type'       => $row->facility_type,
            'status'     => 'active',
            // Matches how BookableFacilityNetworkSeeder flags the rows it
            // creates: the flag tracks the environment, not the institution,
            // and the IsDemoRecord global scope hides is_demo=false rows while
            // demo mode is on.
            'is_demo'    => (bool) config('demo.enabled', (bool) env('OPESCARE_DEMO_MODE', false)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('care_facilities')
            ->where('id', $row->id)
            ->update(['facility_id' => $facilityId, 'updated_at' => now()]);

        return self::$cache[$directoryName] = $facilityId;
    }

    public static function primaryHospital(): ?string
    {
        return self::resolve(self::PRIMARY_HOSPITAL);
    }

    public static function secondaryHospital(): ?string
    {
        return self::resolve(self::SECONDARY_HOSPITAL);
    }

    public static function laboratory(): ?string
    {
        return self::resolve(self::LABORATORY);
    }

    public static function pharmacy(): ?string
    {
        return self::resolve(self::PHARMACY);
    }

    public static function clinic(): ?string
    {
        return self::resolve(self::CLINIC);
    }

    /** Tests and long-running processes: drop the memoised ids. */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
