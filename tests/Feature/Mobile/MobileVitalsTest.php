<?php

namespace Tests\Feature\Mobile;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\TriageVitalSign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * Mobile Health Vitals — GET /api/mobile/vitals/latest.
 *
 * Covers the contract the home dashboard's Health Vitals card depends on:
 * the latest reading per measure across BOTH vitals tables plus the lab-sourced
 * blood sugar, honest per-measure timestamps, advisory status bands, a real
 * empty state (never zeros), and — the one that matters most — that a patient
 * can never read another patient's vitals.
 */
class MobileVitalsTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private const URI = '/api/mobile/vitals/latest';

    private Patient $patient;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::create([
            'name'   => 'Vitals Test Clinic',
            'type'   => 'clinic',
            'status' => 'active',
        ]);

        $this->patient = $this->makePatient('OC-VIT-0001-0001-01', 'Aline');
    }

    // ── Contract ─────────────────────────────────────────────────────────────

    public function test_it_requires_authentication(): void
    {
        $this->getJson(self::URI)->assertStatus(401);
    }

    /** No readings must produce a real empty state — an empty list, not zeros. */
    public function test_it_returns_an_empty_payload_when_the_patient_has_no_vitals(): void
    {
        $response = $this->mobileGetJson($this->patient, self::URI)->assertOk();

        $response->assertJsonPath('data.measures', []);
        $response->assertJsonPath('data.recorded_at', null);
        $response->assertJsonPath('meta.count', 0);
    }

    public function test_it_returns_the_latest_reading_of_each_measure(): void
    {
        $recordedAt = Carbon::parse('2026-08-30 07:30:00');

        $this->recordTriageVitals($this->patient, $recordedAt, [
            'pulse_rate'        => 72,
            'systolic_bp'       => 120,
            'diastolic_bp'      => 80,
            'oxygen_saturation' => 98,
            'temperature'       => 36.8,
            'respiratory_rate'  => 16,
        ]);

        $response = $this->mobileGetJson($this->patient, self::URI)->assertOk();

        $measures = collect($response->json('data.measures'))->keyBy('key');

        $this->assertSame('72', $measures['heart_rate']['value']);
        $this->assertSame('bpm', $measures['heart_rate']['unit']);
        $this->assertSame('normal', $measures['heart_rate']['status']);
        $this->assertSame('vitals', $measures['heart_rate']['source']);

        $this->assertSame('120/80', $measures['blood_pressure']['value']);
        $this->assertSame('mmHg', $measures['blood_pressure']['unit']);
        $this->assertSame('normal', $measures['blood_pressure']['status']);

        $this->assertSame('98', $measures['oxygen_saturation']['value']);
        $this->assertSame('36.8', $measures['temperature']['value']);
        $this->assertSame('16', $measures['respiratory_rate']['value']);

        $this->assertSame(
            $recordedAt->toIso8601String(),
            $response->json('data.recorded_at'),
        );
    }

    /** A null column must leave the measure ABSENT, never zero-filled. */
    public function test_a_measure_that_was_never_recorded_is_absent_rather_than_zero(): void
    {
        $this->recordTriageVitals($this->patient, Carbon::parse('2026-08-30 07:30:00'), [
            'pulse_rate'        => 68,
            'oxygen_saturation' => null,
            'systolic_bp'       => 118,
            'diastolic_bp'      => null,   // half a blood pressure is not a blood pressure
        ]);

        $keys = collect($this->mobileGetJson($this->patient, self::URI)->assertOk()->json('data.measures'))
            ->pluck('key')
            ->all();

        $this->assertContains('heart_rate', $keys);
        $this->assertNotContains('oxygen_saturation', $keys);
        $this->assertNotContains('blood_pressure', $keys);
    }

    /** Newest wins per measure, and each measure keeps its own reading time. */
    public function test_each_measure_carries_its_own_recorded_at_and_the_newest_value_wins(): void
    {
        $old   = Carbon::parse('2026-08-01 09:00:00');
        $fresh = Carbon::parse('2026-08-30 07:30:00');

        // Older row is the only one carrying a respiratory rate.
        $this->recordTriageVitals($this->patient, $old, [
            'pulse_rate'       => 95,
            'respiratory_rate' => 18,
        ]);
        $this->recordTriageVitals($this->patient, $fresh, [
            'pulse_rate'       => 72,
            'respiratory_rate' => null,
        ]);

        $measures = collect($this->mobileGetJson($this->patient, self::URI)->assertOk()->json('data.measures'))
            ->keyBy('key');

        $this->assertSame('72', $measures['heart_rate']['value']);
        $this->assertSame($fresh->toIso8601String(), $measures['heart_rate']['recorded_at']);

        // The stale respiratory rate is still shown, but dated honestly.
        $this->assertSame('18', $measures['respiratory_rate']['value']);
        $this->assertSame($old->toIso8601String(), $measures['respiratory_rate']['recorded_at']);
    }

    /** The live triage write path (`vital_signs`) must be readable too. */
    public function test_it_reads_the_legacy_visit_scoped_vital_signs_table(): void
    {
        $recordedAt = Carbon::parse('2026-08-29 14:00:00');
        $this->recordLegacyVitals($this->patient, $recordedAt, [
            'pulse'                    => 88,
            'blood_pressure_systolic'  => 132,
            'blood_pressure_diastolic' => 84,
            'oxygen_saturation'        => 96,
        ]);

        $measures = collect($this->mobileGetJson($this->patient, self::URI)->assertOk()->json('data.measures'))
            ->keyBy('key');

        $this->assertSame('88', $measures['heart_rate']['value']);
        $this->assertSame('132/84', $measures['blood_pressure']['value']);
        $this->assertSame($recordedAt->toIso8601String(), $measures['heart_rate']['recorded_at']);
    }

    /** Blood sugar comes from a published lab result, with the lab's own flag. */
    public function test_it_reads_blood_sugar_from_a_published_lab_result(): void
    {
        $resultedAt = Carbon::parse('2026-08-30 07:30:00');
        $this->recordGlucose($this->patient, $resultedAt, '102', 'mg/dL', 'normal');

        $measures = collect($this->mobileGetJson($this->patient, self::URI)->assertOk()->json('data.measures'))
            ->keyBy('key');

        $this->assertSame('102', $measures['blood_sugar']['value']);
        $this->assertSame('mg/dL', $measures['blood_sugar']['unit']);
        $this->assertSame('normal', $measures['blood_sugar']['status']);
        $this->assertSame('lab', $measures['blood_sugar']['source']);
    }

    /** The laboratory's own flag beats any range hardcoded in the controller. */
    public function test_the_laboratory_flag_decides_the_blood_sugar_status(): void
    {
        // 102 mg/dL sits inside the controller's normal band, but the lab
        // flagged it high — the lab wins.
        $this->recordGlucose($this->patient, Carbon::parse('2026-08-30 07:30:00'), '102', 'mg/dL', 'H');

        $measures = collect($this->mobileGetJson($this->patient, self::URI)->assertOk()->json('data.measures'))
            ->keyBy('key');

        $this->assertSame('high', $measures['blood_sugar']['status']);
    }

    /** An unrecognised unit must return `unknown`, never a guessed band. */
    public function test_an_unrecognised_glucose_unit_is_not_classified(): void
    {
        $this->recordGlucose($this->patient, Carbon::parse('2026-08-30 07:30:00'), '5.6', 'arb/units', null);

        $measures = collect($this->mobileGetJson($this->patient, self::URI)->assertOk()->json('data.measures'))
            ->keyBy('key');

        $this->assertSame('unknown', $measures['blood_sugar']['status']);
    }

    // ── Advisory bands ───────────────────────────────────────────────────────

    #[DataProvider('outOfRangeProvider')]
    public function test_it_flags_out_of_range_values(array $vitals, string $key, string $expected): void
    {
        $this->recordTriageVitals($this->patient, Carbon::parse('2026-08-30 07:30:00'), $vitals);

        $measures = collect($this->mobileGetJson($this->patient, self::URI)->assertOk()->json('data.measures'))
            ->keyBy('key');

        $this->assertSame($expected, $measures[$key]['status']);
    }

    public static function outOfRangeProvider(): array
    {
        return [
            'bradycardia'        => [['pulse_rate' => 52], 'heart_rate', 'low'],
            'tachycardia'        => [['pulse_rate' => 112], 'heart_rate', 'high'],
            'extreme heart rate' => [['pulse_rate' => 148], 'heart_rate', 'critical'],
            'hypertension'       => [['systolic_bp' => 152, 'diastolic_bp' => 96], 'blood_pressure', 'high'],
            'hypertensive crisis' => [['systolic_bp' => 186, 'diastolic_bp' => 118], 'blood_pressure', 'critical'],
            'hypotension'        => [['systolic_bp' => 96, 'diastolic_bp' => 58], 'blood_pressure', 'low'],
            'mild hypoxia'       => [['oxygen_saturation' => 92], 'oxygen_saturation', 'low'],
            'severe hypoxia'     => [['oxygen_saturation' => 87], 'oxygen_saturation', 'critical'],
            'fever'              => [['temperature' => 38.6], 'temperature', 'high'],
            'hyperpyrexia'       => [['temperature' => 40.2], 'temperature', 'critical'],
            'tachypnoea'         => [['respiratory_rate' => 24], 'respiratory_rate', 'high'],
        ];
    }

    // ── Isolation (IDOR) ─────────────────────────────────────────────────────

    /**
     * The whole point of the endpoint's design: `patient_id` comes from the
     * bearer token only. Another patient's vitals — in either table, and their
     * lab-sourced blood sugar — must be completely invisible.
     */
    public function test_a_patient_cannot_read_another_patients_vitals(): void
    {
        $other = $this->makePatient('OC-VIT-0002-0002-02', 'Bertrand');

        $this->recordTriageVitals($other, Carbon::parse('2026-08-30 07:30:00'), [
            'pulse_rate'        => 130,
            'systolic_bp'       => 180,
            'diastolic_bp'      => 110,
            'oxygen_saturation' => 88,
        ]);
        $this->recordLegacyVitals($other, Carbon::parse('2026-08-29 07:30:00'), ['pulse' => 44]);
        $this->recordGlucose($other, Carbon::parse('2026-08-30 07:30:00'), '311', 'mg/dL', 'HH');

        // The caller has no readings of their own — and must see exactly that.
        $response = $this->mobileGetJson($this->patient, self::URI)->assertOk();
        $response->assertJsonPath('data.measures', []);
        $response->assertJsonPath('meta.count', 0);

        // And the other patient's own token still sees their own data, so the
        // assertion above is scoping and not simply a broken query.
        $otherMeasures = collect($this->mobileGetJson($other, self::URI)->assertOk()->json('data.measures'))
            ->keyBy('key');
        $this->assertSame('130', $otherMeasures['heart_rate']['value']);
        $this->assertSame('311', $otherMeasures['blood_sugar']['value']);
    }

    /** A caller must not be able to widen the scope with request input. */
    public function test_patient_identifiers_in_request_input_are_ignored(): void
    {
        $other = $this->makePatient('OC-VIT-0003-0003-03', 'Chantal');
        $this->recordTriageVitals($other, Carbon::parse('2026-08-30 07:30:00'), ['pulse_rate' => 101]);

        $response = $this->mobileGetJson($this->patient, self::URI, [
            'patient_id' => $other->id,
            'patient'    => $other->id,
        ])->assertOk();

        $response->assertJsonPath('data.measures', []);
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    private function makePatient(string $healthId, string $firstName): Patient
    {
        return Patient::create([
            'health_id'     => $healthId,
            'first_name'    => $firstName,
            'last_name'     => 'Vitals',
            'sex'           => 'female',
            'date_of_birth' => '1992-05-15',
            'is_demo'       => false,
        ]);
    }

    /** A row in `triage_vital_signs` — the patient-scoped table. */
    private function recordTriageVitals(Patient $patient, Carbon $recordedAt, array $vitals): void
    {
        TriageVitalSign::create(array_merge([
            'triage_record_id' => (string) Str::uuid(),
            'patient_id'       => $patient->id,
            'recorded_by'      => (string) Str::uuid(),
            'recorded_at'      => $recordedAt,
        ], $vitals));
    }

    /** A row in `vital_signs`, reachable only through triage_records -> visits. */
    private function recordLegacyVitals(Patient $patient, Carbon $recordedAt, array $vitals): void
    {
        $visitId  = (string) Str::uuid();
        $triageId = (string) Str::uuid();

        DB::table('visits')->insert([
            'id'          => $visitId,
            'patient_id'  => $patient->id,
            'facility_id' => $this->facility->id,
            'visit_type'  => 'outpatient',
            'status'      => 'closed',
            'started_at'  => $recordedAt,
            'created_at'  => $recordedAt,
            'updated_at'  => $recordedAt,
        ]);

        DB::table('triage_records')->insert([
            'id'         => $triageId,
            'visit_id'   => $visitId,
            'created_at' => $recordedAt,
            'updated_at' => $recordedAt,
        ]);

        DB::table('vital_signs')->insert(array_merge([
            'id'               => (string) Str::uuid(),
            'triage_record_id' => $triageId,
            'created_at'       => $recordedAt,
            'updated_at'       => $recordedAt,
        ], $vitals));
    }

    /** A published glucose lab result — the only real blood-sugar source. */
    private function recordGlucose(
        Patient $patient,
        Carbon $resultedAt,
        string $value,
        ?string $unit,
        ?string $flag,
    ): void {
        $orderId = (string) Str::uuid();

        DB::table('lab_orders')->insert([
            'id'          => $orderId,
            'patient_id'  => $patient->id,
            'facility_id' => $this->facility->id,
            'test_name'   => 'Random blood glucose',
            'status'      => 'resulted',
            'ordered_at'  => $resultedAt,
            'resulted_at' => $resultedAt,
            'created_at'  => $resultedAt,
            'updated_at'  => $resultedAt,
        ]);

        DB::table('lab_results')->insert([
            'id'             => (string) Str::uuid(),
            'lab_order_id'   => $orderId,
            'patient_id'     => $patient->id,
            'parameter_name' => 'Glucose (random)',
            'value'          => $value,
            'unit'           => $unit,
            'flag'           => $flag,
            'resulted_at'    => $resultedAt,
            'created_at'     => $resultedAt,
            'updated_at'     => $resultedAt,
        ]);
    }
}
