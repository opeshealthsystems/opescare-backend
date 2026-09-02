<?php

namespace Database\Seeders;

use App\Enums\BloodComponentType;
use App\Enums\BloodGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Blood stock for the Blood Finder.
 *
 * This seeder was an empty stub, so `blood_availability` held zero rows: the
 * Blood Finder rendered every blood group as "None reported", every search
 * returned nothing, and creating a request always answered
 * 409 BLOOD_NOT_AVAILABLE. The screen existed but the feature could not work.
 *
 * The facilities here are REAL — the regional and central hospitals that
 * genuinely operate blood banks, matched by name against the registry. The
 * stock levels are illustrative operational data, exactly like the pharmacy
 * stock in PharmacyCatalogSeeder: no public source publishes live Cameroonian
 * blood inventory, and inventing one would be worse than useless in an
 * emergency screen. What matters is that a patient searching for O- in Douala
 * is pointed at a hospital that actually exists and can actually be phoned.
 *
 * Deliberately partial: not every hospital stocks every group, and the rarer
 * groups (AB-, B-, O-) are scarce or absent in places. A blood finder where
 * everything is always available teaches the patient nothing and would hide
 * the empty-state and compatible-group fallbacks the screen is built around.
 *
 * Idempotent — keyed on (facility, group, component).
 *
 * NOT PUBLIC. Every row is stamped `source_system = 'demo_seed'`, and the
 * Blood Finder withholds synthetic rows
 * (App\Models\BloodAvailability::scopeReportedByRealSource()). These rows exist
 * to make the staff and demo screens render and to measure real coverage
 * against — they are never shown to a patient searching for blood. Real
 * availability arrives through the staff blood screen or the partner ingest and
 * is published by BloodAvailabilityProjector with a real source.
 */
class DemoBloodInventorySeeder extends Seeder
{
    /**
     * Real hospitals that operate a blood bank, and how well stocked each is.
     *
     * 'wide'  — a major transfusion centre: every group, most components.
     * 'core'  — the common groups only (O+, A+, B+, AB+, O-).
     * 'thin'  — a district-level bank: a couple of common groups, low units.
     */
    private const BANKS = [
        'Hôpital Central de Yaoundé'      => 'wide',
        'CHU de Yaoundé'                  => 'wide',
        'Hôpital Général de Douala'       => 'wide',
        'Hôpital Laquintinie de Douala'   => 'wide',
        'Hôpital Général de Yaoundé'      => 'core',
        'Hôpital Régional de Bamenda'     => 'core',
        'Hôpital Régional de Bafoussam'   => 'core',
        'Hôpital Régional de Garoua'      => 'core',
        'Hôpital Régional de Maroua'      => 'thin',
        'Hôpital Régional de Ngaoundéré'  => 'thin',
        'Hôpital Régional de Bertoua'     => 'thin',
        'Hôpital Régional de Buéa'        => 'core',
    ];

    private const COMMON = ['O+', 'A+', 'B+', 'AB+', 'O-'];

    public function run(): void
    {
        $created = 0;
        $skippedMissing = 0;

        foreach (self::BANKS as $facilityName => $tier) {
            $facility = DB::table('care_facilities')
                ->where('facility_name', $facilityName)
                ->where('country_code', 'CM')
                ->first(['id', 'phone_primary']);

            if (! $facility) {
                $skippedMissing++;
                continue;
            }

            foreach (BloodGroup::cases() as $group) {
                foreach ($this->componentsFor($tier) as $component) {
                    [$status, $range, $freshness] = $this->stockFor($tier, $group->value, $component->value);

                    if ($status === null) {
                        continue;   // this bank genuinely does not hold it
                    }

                    $existing = DB::table('blood_availability')
                        ->where('facility_id', $facility->id)
                        ->where('blood_group', $group->value)
                        ->where('component_type', $component->value)
                        ->first(['id', 'source_system']);

                    if ($existing) {
                        // Claim rows this seeder wrote before provenance
                        // existed. They are withheld from patients either way
                        // (unattributed is withheld too), but leaving them
                        // blank hides WHY the Blood Finder ignores them.
                        //
                        // Only NULL is claimed: a row a real blood bank has
                        // since published carries its own source, and
                        // relabelling that as demo data would take a true
                        // report away from patients.
                        if ($existing->source_system === null) {
                            DB::table('blood_availability')
                                ->where('id', $existing->id)
                                ->update(['source_system' => 'demo_seed', 'updated_at' => now()]);
                        }

                        continue;
                    }

                    DB::table('blood_availability')->insert([
                        'id'                    => (string) Str::uuid(),
                        'facility_id'           => $facility->id,
                        'blood_group'           => $group->value,
                        'component_type'        => $component->value,
                        'units_available_range' => $range,
                        'availability_status'   => $status,
                        'freshness_status'      => $freshness,
                        // Real number where the registry has one, so "Call"
                        // actually dials somewhere.
                        'emergency_contact'     => $facility->phone_primary !== 'N/A' ? $facility->phone_primary : null,
                        // SYNTHETIC. Every stock level in this seeder was
                        // invented — see the class docblock. The stamp is what
                        // keeps these rows out of the public Blood Finder:
                        // App\Models\BloodAvailability::scopeReportedByRealSource()
                        // withholds it, so a patient is never sent to a blood
                        // bank on the strength of seed data. The rows stay for
                        // demos, screenshots and coverage measurement.
                        'source_system'         => 'demo_seed',
                        'last_updated_at'       => now()->subMinutes(random_int(10, 240)),
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ]);

                    $created++;
                }
            }
        }

        $this->command?->info(sprintf(
            'DemoBloodInventorySeeder: %d availability row(s) across %d bank(s), %d facility(ies) not in the registry. Total: %d.',
            $created,
            count(self::BANKS) - $skippedMissing,
            $skippedMissing,
            DB::table('blood_availability')->count(),
        ));
    }

    /** @return list<BloodComponentType> */
    private function componentsFor(string $tier): array
    {
        return match ($tier) {
            'wide'  => BloodComponentType::cases(),
            'core'  => [BloodComponentType::WholeBlood, BloodComponentType::RedCells],
            default => [BloodComponentType::WholeBlood],
        };
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: string} status, units range, freshness
     */
    private function stockFor(string $tier, string $group, string $component): array
    {
        $isCommon = in_array($group, self::COMMON, true);

        // Deterministic, so re-running never shuffles the picture.
        $seed = crc32($tier . $group . $component) % 100;

        if ($tier === 'thin') {
            if (! $isCommon) {
                return [null, null, 'unknown'];         // small bank, rare group: not held
            }
            return $seed < 60
                ? ['available', '1-5', 'fresh']
                : ['low', '1-5', 'aging'];
        }

        if ($tier === 'core' && ! $isCommon) {
            return [null, null, 'unknown'];
        }

        if ($isCommon) {
            return $seed < 70
                ? ['available', '20+', 'fresh']
                : ['available', '6-20', 'fresh'];
        }

        // Wide bank, rarer group — genuinely scarce.
        return $seed < 45
            ? ['available', '1-5', 'fresh']
            : ['low', '1-5', 'aging'];
    }
}
