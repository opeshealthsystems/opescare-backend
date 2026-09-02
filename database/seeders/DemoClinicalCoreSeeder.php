<?php

namespace Database\Seeders;

use App\Enums\BloodGroup;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The clinical foundation of the ONE demo patient's record.
 *
 * Everything else in the demo dataset hangs off this: prescriptions treat these
 * conditions, labs monitor them, the allergy list is what makes a medication
 * screen safe to show. Without it the mobile app renders an em dash for blood
 * group, "0 allergies / 0 conditions" on home and profile, and a Records
 * timeline with nothing in it.
 *
 * Her story (DEMO_DATA_BRIEF.md), told in relative time so it never goes stale:
 *   -3 years   Type 2 diabetes diagnosed at Hôpital Central de Yaoundé.
 *   -22 months One episode of care at Hôpital Laquintinie de Douala.
 *   -18 months Hypertension diagnosed. Peanut allergy recorded the same day.
 *   -9/-6/-3 months, -18 days   Quarterly reviews, the last one recent.
 *
 * Deliberate boundaries:
 *   - Writes clinical rows for exactly ONE patient (self::PATIENT). It refuses
 *     to run if that patient, her clinician, or the two named hospitals are
 *     missing, so it can never attach an invented history to a real person.
 *   - Facilities are resolved BY NAME at run time against `facilities`, and the
 *     name is cross-checked against the 897-row `care_facilities` registry, so
 *     the visits point at hospitals that genuinely exist. No hardcoded UUIDs
 *     for facilities, no Faker-generated clinic names.
 *   - Every row carries a stable UUID in the reserved 00000000-0000-0000-0c0x-
 *     block, so re-running inserts nothing new (idempotent).
 *   - Vitals are NOT touched. DemoPatientVitalsSeeder owns the latest readings
 *     (today 07:30 and -9 days); mobile exposes only `vitals/latest`, so extra
 *     historical readings would be invisible and could only conflict.
 *   - NOT registered in DatabaseSeeder — run it explicitly:
 *         php artisan db:seed --class=DemoClinicalCoreSeeder
 *
 * Vocabulary notes (checked against the code, not assumed):
 *   - allergy severity  'severe' | 'moderate' — EncounterController::recordAllergy
 *     validates in:mild,moderate,severe,life_threatening; FhirAllergyIntolerance-
 *     Mapper maps 'severe' → criticality high; mobile localises both.
 *   - diagnosis status  'chronic' | 'active' — the pair MobilePatientController
 *     counts (whereIn ['active','chronic']) and FhirConditionMapper maps to
 *     FHIR clinicalStatus 'active'.
 *   - visit type/status 'outpatient' / 'completed' — the values
 *     VisitManagementService writes and the mobile Records screen localises.
 *   - immunization status 'completed' — ImmunizationController validates
 *     in:completed,not_done.
 *   - blood group from the BloodGroup enum (->value), never a bare 'O+'.
 *
 * `is_demo = true` is mandatory on visits/diagnoses/allergies: those models use
 * the IsDemoRecord trait, whose global scope hides is_demo=false rows whenever
 * OPESCARE_DEMO_MODE is on. A row without it is invisible to the API.
 * (immunization_records has no is_demo column and no such scope.)
 */
class DemoClinicalCoreSeeder extends Seeder
{
    private const PATIENT    = '00000000-0000-0000-0000-300000000001'; // Demo Patient One
    private const DOCTOR     = '00000000-0000-0000-0000-200000000001'; // Dr. Amara Diallo
    private const SPECIALIST = '00000000-0000-0000-0000-200000000011'; // Dr. Ibrahim Sow, Cardiologist
    private const NURSE      = '00000000-0000-0000-0000-200000000010'; // Nurse Fatou Traoré

    private const YAOUNDE = 'Hôpital Central de Yaoundé';
    private const DOUALA  = 'Hôpital Laquintinie de Douala';

    /** Reserved UUID block — 0c = "clinical core". */
    private const VISIT_DIABETES     = '00000000-0000-0000-0c01-000000000001';
    private const VISIT_DOUALA       = '00000000-0000-0000-0c01-000000000002';
    private const VISIT_HYPERTENSION = '00000000-0000-0000-0c01-000000000003';
    private const VISIT_REVIEW_1     = '00000000-0000-0000-0c01-000000000004';
    private const VISIT_REVIEW_2     = '00000000-0000-0000-0c01-000000000005';
    private const VISIT_REVIEW_3     = '00000000-0000-0000-0c01-000000000006';
    private const VISIT_REVIEW_4     = '00000000-0000-0000-0c01-000000000007';

    public function run(): void
    {
        if (! DB::table('patients')->where('id', self::PATIENT)->exists()) {
            $this->command?->warn('DemoClinicalCoreSeeder skipped: demo patient not present.');
            return;
        }

        if (! DB::table('users')->where('id', self::DOCTOR)->exists()) {
            $this->command?->warn('DemoClinicalCoreSeeder skipped: demo clinician not present (diagnoses.provider_id and allergy_records.provider_id are NOT NULL).');
            return;
        }

        $yaounde = $this->resolveFacility(self::YAOUNDE);
        $douala  = $this->resolveFacility(self::DOUALA);

        if (! $yaounde || ! $douala) {
            $this->command?->warn('DemoClinicalCoreSeeder skipped: ' . self::YAOUNDE . ' / ' . self::DOUALA . ' not found in `facilities`. Run the registry seeders first.');
            return;
        }

        $now = CarbonImmutable::now();

        $this->setBloodGroup();
        $this->seedVisits($now, $yaounde, $douala);
        $this->seedDiagnoses($now);
        $this->seedAllergies($now);
        $this->seedImmunizations($now, $yaounde);

        $this->report();
    }

    // ── Facilities ───────────────────────────────────────────────────────────

    /**
     * Resolve a hospital by name in `facilities` (the table visits and
     * immunizations actually reference), and only accept it if the same
     * facility is also in the national `care_facilities` registry — that is
     * what proves it is a real Cameroonian hospital rather than seeded noise.
     */
    private function resolveFacility(string $name): ?string
    {
        $facility = DB::table('facilities')->where('name', $name)->first(['id']);

        if (! $facility) {
            return null;
        }

        $inRegistry = DB::table('care_facilities')
            ->where('facility_name', $name)
            ->where('country_code', 'CM')
            ->exists();

        if (! $inRegistry) {
            $this->command?->warn("DemoClinicalCoreSeeder: '{$name}' is in `facilities` but not in the care_facilities registry — refusing to use it.");
            return null;
        }

        return $facility->id;
    }

    // ── Blood group ──────────────────────────────────────────────────────────

    /**
     * O+ per the brief. Null renders as an em dash on the Health ID card and on
     * the home screen, which is exactly what a patient would never see on a
     * card they carry.
     */
    private function setBloodGroup(): void
    {
        $group = BloodGroup::OPositive->value;

        $current = DB::table('patients')->where('id', self::PATIENT)->value('blood_group');

        if ($current === $group) {
            return;
        }

        DB::table('patients')
            ->where('id', self::PATIENT)
            ->update(['blood_group' => $group, 'updated_at' => now()]);
    }

    // ── Visits ───────────────────────────────────────────────────────────────

    /**
     * Seven visits: the two diagnosis encounters, one episode away from home in
     * Douala, and four quarterly reviews ending a fortnight ago. The mobile
     * Records screen groups by month, so they are deliberately spread across
     * different months rather than clustered.
     *
     * `created_at` is set to the visit date, not now(): the timeline endpoint
     * orders by and reports created_at, so a "today" created_at would file a
     * three-year-old visit under this month.
     */
    private function seedVisits(CarbonImmutable $now, string $yaounde, string $douala): void
    {
        $visits = [
            // id, facility, provider, started, duration (min), visit_type
            [self::VISIT_DIABETES,     $yaounde, self::DOCTOR,     $now->subYearsNoOverflow(3)->setTime(9, 15),    50, 'outpatient'],
            [self::VISIT_DOUALA,       $douala,  null,             $now->subMonthsNoOverflow(22)->setTime(11, 0),  40, 'outpatient'],
            [self::VISIT_HYPERTENSION, $yaounde, self::DOCTOR,     $now->subMonthsNoOverflow(18)->setTime(10, 30), 45, 'outpatient'],
            [self::VISIT_REVIEW_1,     $yaounde, self::DOCTOR,     $now->subMonthsNoOverflow(9)->setTime(8, 45),   30, 'outpatient'],
            [self::VISIT_REVIEW_2,     $yaounde, $this->specialist(), $now->subMonthsNoOverflow(6)->setTime(14, 0), 35, 'outpatient'],
            [self::VISIT_REVIEW_3,     $yaounde, self::DOCTOR,     $now->subMonthsNoOverflow(3)->setTime(9, 30),   30, 'outpatient'],
            [self::VISIT_REVIEW_4,     $yaounde, self::DOCTOR,     $now->subDays(18)->setTime(10, 0),    30, 'outpatient'],
        ];

        foreach ($visits as [$id, $facilityId, $providerId, $startedAt, $minutes, $type]) {
            if (DB::table('visits')->where('id', $id)->exists()) {
                continue;
            }

            DB::table('visits')->insert([
                'id'          => $id,
                'patient_id'  => self::PATIENT,
                'facility_id' => $facilityId,
                'provider_id' => $providerId,
                'visit_type'  => $type,
                'status'      => 'completed',
                'started_at'  => $startedAt,
                'ended_at'    => $startedAt->addMinutes($minutes),
                'is_demo'     => true,
                'created_at'  => $startedAt,
                'updated_at'  => $startedAt,
            ]);
        }
    }

    /** The cardiologist takes one BP review; fall back to her GP if absent. */
    private function specialist(): string
    {
        return DB::table('users')->where('id', self::SPECIALIST)->exists()
            ? self::SPECIALIST
            : self::DOCTOR;
    }

    // ── Diagnoses ────────────────────────────────────────────────────────────

    /**
     * Her two chronic problems, each attached to the visit it was made at
     * (diagnoses.visit_id is NOT NULL).
     *
     * Codes are the real ones, not placeholders: ICD-10 E11 "Type 2 diabetes
     * mellitus" and I10 "Essential (primary) hypertension", with the matching
     * SNOMED CT concepts 44054006 and 59621000 in the dedicated snomed columns.
     */
    private function seedDiagnoses(CarbonImmutable $now): void
    {
        $rows = [
            [
                'id'             => '00000000-0000-0000-0c02-000000000001',
                'visit_id'       => self::VISIT_DIABETES,
                'provider_id'    => self::DOCTOR,
                'code_system'    => 'ICD-10',
                'code'           => 'E11',
                'display_name'   => 'Type 2 diabetes mellitus',
                'snomed_code'    => '44054006',
                'snomed_display' => 'Diabetes mellitus type 2',
                'status'         => 'chronic',
                'recorded_at'    => $now->subYearsNoOverflow(3)->setTime(9, 40),
            ],
            [
                'id'             => '00000000-0000-0000-0c02-000000000002',
                'visit_id'       => self::VISIT_HYPERTENSION,
                'provider_id'    => self::DOCTOR,
                'code_system'    => 'ICD-10',
                'code'           => 'I10',
                'display_name'   => 'Essential (primary) hypertension',
                'snomed_code'    => '59621000',
                'snomed_display' => 'Essential hypertension',
                'status'         => 'active',
                'recorded_at'    => $now->subMonthsNoOverflow(18)->setTime(11, 0),
            ],
        ];

        foreach ($rows as $row) {
            if (DB::table('diagnoses')->where('id', $row['id'])->exists()) {
                continue;
            }

            DB::table('diagnoses')->insert([
                'id'             => $row['id'],
                'patient_id'     => self::PATIENT,
                'visit_id'       => $row['visit_id'],
                'provider_id'    => $row['provider_id'],
                'code_system'    => $row['code_system'],
                'code'           => $row['code'],
                'snomed_code'    => $row['snomed_code'],
                'snomed_display' => $row['snomed_display'],
                'display_name'   => $row['display_name'],
                'status'         => $row['status'],
                'is_primary'     => true,
                'is_demo'        => true,
                'created_at'     => $row['recorded_at'],
                'updated_at'     => $row['recorded_at'],
            ]);
        }
    }

    // ── Allergies ────────────────────────────────────────────────────────────

    /**
     * Penicillin (severe) and Peanuts (moderate).
     *
     * The penicillin allergy is the clinically load-bearing row in the whole
     * demo dataset: it must be visible anywhere medication is shown. It is
     * recorded at the first encounter, the peanut allergy at the later one, so
     * the two land in different months of the Records timeline.
     *
     * The reaction ("anaphylaxis") has nowhere to live — allergy_records is
     * (substance, severity, status) only — so severity 'severe' carries it,
     * which FhirAllergyIntoleranceMapper turns into criticality 'high' /
     * reaction severity 'severe'.
     */
    private function seedAllergies(CarbonImmutable $now): void
    {
        $rows = [
            [
                'id'          => '00000000-0000-0000-0c03-000000000001',
                'substance'   => 'Penicillin',
                'severity'    => 'severe',
                'recorded_at' => $now->subYearsNoOverflow(3)->setTime(9, 20),
            ],
            [
                'id'          => '00000000-0000-0000-0c03-000000000002',
                'substance'   => 'Peanuts',
                'severity'    => 'moderate',
                'recorded_at' => $now->subMonthsNoOverflow(18)->setTime(10, 50),
            ],
        ];

        foreach ($rows as $row) {
            if (DB::table('allergy_records')->where('id', $row['id'])->exists()) {
                continue;
            }

            DB::table('allergy_records')->insert([
                'id'          => $row['id'],
                'patient_id'  => self::PATIENT,
                'provider_id' => self::DOCTOR,
                'substance'   => $row['substance'],
                'severity'    => $row['severity'],
                'status'      => 'active',
                'is_demo'     => true,
                'created_at'  => $row['recorded_at'],
                'updated_at'  => $row['recorded_at'],
            ]);
        }
    }

    // ── Immunizations ────────────────────────────────────────────────────────

    /**
     * A 34-year-old Cameroonian adult's realistic card: hepatitis B series,
     * yellow fever (required in Cameroon), a tetanus-diphtheria booster, the
     * COVID-19 primary dose and a later COVID-19 booster.
     *
     * The four older entries are `is_historical = true` — transcribed from her
     * paper card, which is why they carry no lot number and no administering
     * clinician (FHIR primarySource = false). Only the recent booster was given
     * at the facility that holds the record.
     *
     * Codes are WHO/EPI vaccine abbreviations under the schema's own
     * `vaccine_system` default ('WHO-EPI', the value ImmunizationService
     * writes), not invented numeric codes.
     */
    private function seedImmunizations(CarbonImmutable $now, string $facilityId): void
    {
        $nurse = DB::table('users')->where('id', self::NURSE)->exists() ? self::NURSE : null;

        $rows = [
            [
                'id'            => '00000000-0000-0000-0c04-000000000001',
                'vaccine_code'  => 'HepB',
                'vaccine_name'  => 'Hepatitis B (Engerix-B)',
                'manufacturer'  => 'GlaxoSmithKline',
                'administered'  => $now->subYearsNoOverflow(11)->setTime(10, 0),
                'dose_number'   => 3,
                'dose_sequence' => 'primary series',
                'dose_quantity' => 1.00,
                'site'          => 'left deltoid',
                'historical'    => true,
            ],
            [
                'id'            => '00000000-0000-0000-0c04-000000000002',
                'vaccine_code'  => 'YF',
                'vaccine_name'  => 'Yellow Fever (Stamaril)',
                'manufacturer'  => 'Sanofi Pasteur',
                'administered'  => $now->subYearsNoOverflow(8)->setTime(9, 30),
                'dose_number'   => 1,
                'dose_sequence' => 'single dose',
                'dose_quantity' => 0.50,
                'site'          => 'left deltoid',
                'historical'    => true,
            ],
            [
                'id'            => '00000000-0000-0000-0c04-000000000003',
                'vaccine_code'  => 'COVID-19',
                'vaccine_name'  => 'COVID-19 Vaccine Janssen',
                'manufacturer'  => 'Janssen (Johnson & Johnson)',
                'administered'  => $now->subYearsNoOverflow(4)->subMonthsNoOverflow(6)->setTime(11, 15),
                'dose_number'   => 1,
                'dose_sequence' => 'single dose',
                'dose_quantity' => 0.50,
                'site'          => 'right deltoid',
                'historical'    => true,
            ],
            [
                'id'            => '00000000-0000-0000-0c04-000000000004',
                'vaccine_code'  => 'Td',
                'vaccine_name'  => 'Tetanus-diphtheria (Td)',
                'manufacturer'  => null,
                'administered'  => $now->subYearsNoOverflow(4)->setTime(15, 45),
                'dose_number'   => 1,
                'dose_sequence' => 'booster',
                'dose_quantity' => 0.50,
                'site'          => 'left deltoid',
                'historical'    => true,
            ],
            [
                'id'            => '00000000-0000-0000-0c04-000000000005',
                'vaccine_code'  => 'COVID-19',
                'vaccine_name'  => 'COVID-19 (Comirnaty)',
                'manufacturer'  => 'Pfizer-BioNTech',
                'administered'  => $now->subMonthsNoOverflow(14)->setTime(12, 30),
                'dose_number'   => 2,
                'dose_sequence' => 'booster',
                'dose_quantity' => 0.30,
                'site'          => 'left deltoid',
                'historical'    => false,
            ],
        ];

        foreach ($rows as $row) {
            if (DB::table('immunization_records')->where('id', $row['id'])->exists()) {
                continue;
            }

            DB::table('immunization_records')->insert([
                'id'                  => $row['id'],
                'patient_id'          => self::PATIENT,
                'facility_id'         => $facilityId,
                'administered_by_id'  => $row['historical'] ? null : $nurse,
                'vaccine_code'        => $row['vaccine_code'],
                'vaccine_system'      => 'WHO-EPI',
                'vaccine_name'        => $row['vaccine_name'],
                'manufacturer'        => $row['manufacturer'],
                'administered_at'     => $row['administered'],
                'dose_number'         => $row['dose_number'],
                'dose_sequence'       => $row['dose_sequence'],
                'route'               => 'intramuscular',
                'site'                => $row['site'],
                'dose_quantity'       => $row['dose_quantity'],
                'dose_unit'           => 'mL',
                'status'              => 'completed',
                'verification_status' => 'unverified',
                'is_historical'       => $row['historical'],
                'created_at'          => $row['administered'],
                'updated_at'          => $row['administered'],
            ]);
        }
    }

    // ── Reporting ────────────────────────────────────────────────────────────

    private function report(): void
    {
        $this->command?->info(sprintf(
            'DemoClinicalCoreSeeder: blood group %s · %d visit(s) · %d diagnosis(es) · %d allergy(ies) · %d immunization(s) for %s.',
            DB::table('patients')->where('id', self::PATIENT)->value('blood_group') ?? '—',
            DB::table('visits')->where('id', 'like', '00000000-0000-0000-0c01-%')->count(),
            DB::table('diagnoses')->where('patient_id', self::PATIENT)->count(),
            DB::table('allergy_records')->where('patient_id', self::PATIENT)->count(),
            DB::table('immunization_records')->where('patient_id', self::PATIENT)->count(),
            self::PATIENT,
        ));
    }
}
