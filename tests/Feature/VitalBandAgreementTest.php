<?php

namespace Tests\Feature;

use App\Enums\SelfReportedVitalMeasure;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\PatientRecordedVital;
use App\Models\TriageVitalSign;
use App\Support\VitalBandResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * One vital sign, one verdict.
 *
 * This is the vital-signs half of CriticalValueAgreementTest. That test exists
 * because a potassium of 6.9 mmol/L reached the patient as "critical" and the
 * ordering clinician as "the laboratory recorded no flags" — one row, two
 * answers, and the person who could act on it was the one not told.
 *
 * Vitals were one step from the same hole. The only adult bands in the codebase
 * were private methods on MobileVitalsController, so the patient's own screen
 * banded a blood pressure and the Health Passport — reading the SAME two tables
 * for the SAME patient — showed the same number with no interpretation at all.
 * Both surfaces now ask App\Support\VitalBandResolver, so the same reading means
 * the same thing on both, and the tests below pin the four properties that make
 * that worth having:
 *
 *   1. a normal value raises no alarm;
 *   2. a critical value is critical — on both surfaces;
 *   3. a measure with no band gets NO OPINION, never a guess and never
 *      "normal";
 *   4. the passport and the vitals endpoint agree, measure by measure, on one
 *      reading.
 *
 * And the one thing that must NOT be flagged: a reading the patient took at
 * home. It is unverified by design, the passport says so in words above the
 * table, and a clinical pill there would contradict the sentence it sits under.
 */
class VitalBandAgreementTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private const VITALS_URI   = '/api/mobile/vitals/latest';
    private const PASSPORT_URI = '/api/mobile/health-passport/pages/vitals';

    private Patient $patient;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::create([
            'name'   => 'Buea Regional Hospital',
            'type'   => 'hospital',
            'status' => 'active',
        ]);

        $this->patient = Patient::create([
            'health_id'     => 'OC-VBR-0001-0001-01',
            'first_name'    => 'Ngwa',
            'last_name'     => 'Bands',
            'sex'           => 'male',
            'date_of_birth' => '1988-02-09',
            'is_demo'       => false,
        ]);
    }

    // ── 1. A normal value raises no alarm ────────────────────────────────

    public function test_a_normal_value_raises_no_alarm_anywhere(): void
    {
        $verdict = VitalBandResolver::resolve(VitalBandResolver::HEART_RATE, 72, null, 'bpm');

        $this->assertSame(VitalBandResolver::NORMAL, $verdict['band']);
        $this->assertFalse(VitalBandResolver::isAlarming($verdict['band']));
        $this->assertSame('success', VitalBandResolver::severity($verdict['band']));

        // The judgement travels with its source: this platform's adult bands,
        // never a clinician's or a laboratory's own verdict.
        $this->assertSame(VitalBandResolver::SOURCE_ADULT, $verdict['source']);

        $this->recordFacilityVitals([
            'pulse_rate'        => 72,
            'systolic_bp'       => 118,
            'diastolic_bp'      => 76,
            'oxygen_saturation' => 98,
            'temperature'       => 36.8,
            'respiratory_rate'  => 16,
        ]);

        $measures = $this->endpointMeasures();

        foreach (['heart_rate', 'blood_pressure', 'oxygen_saturation', 'temperature', 'respiratory_rate'] as $key) {
            $this->assertSame('normal', $measures[$key]['status'], "{$key} should not raise an alarm");
            $this->assertSame(VitalBandResolver::SOURCE_ADULT, $measures[$key]['status_source']);
        }
    }

    // ── 2. A critical value is critical ──────────────────────────────────

    public function test_a_critical_value_is_critical_on_both_surfaces(): void
    {
        // A hypertensive crisis (ACC/AHA: >180 and/or >120) with a saturation
        // NEWS2 scores 3 for. Neither may read as merely "high".
        $this->recordFacilityVitals([
            'systolic_bp'       => 186,
            'diastolic_bp'      => 118,
            'oxygen_saturation' => 87,
        ]);

        $measures = $this->endpointMeasures();
        $this->assertSame('critical', $measures['blood_pressure']['status']);
        $this->assertSame('critical', $measures['oxygen_saturation']['status']);

        $critical = __(VitalBandResolver::labelKey(VitalBandResolver::CRITICAL));

        $bp   = $this->passportRowFor('186/118', 'mmHg');
        $spo2 = $this->passportRowFor('87', '%');

        $this->assertSame($critical, $bp[self::FLAG]['v']);
        $this->assertSame('danger', $bp[self::FLAG]['pill'] ?? null);
        $this->assertSame($critical, $spo2[self::FLAG]['v']);
        $this->assertSame('danger', $spo2[self::FLAG]['pill'] ?? null);
    }

    // ── 3. A measure with no band gets no opinion ────────────────────────

    /**
     * Weight is the worked example of the resolver's refusal: no adult range
     * exists for a body weight without a height, and neither surface reads one.
     * It must come back as "no opinion" — which is NOT `normal`, and must not be
     * a guess dressed up as one.
     */
    public function test_a_measure_with_no_band_returns_no_opinion(): void
    {
        $weight = VitalBandResolver::resolve(SelfReportedVitalMeasure::Weight, 71.5, null, 'kg');

        $this->assertSame(VitalBandResolver::UNKNOWN, $weight['band']);
        $this->assertNull($weight['source'], 'a verdict nobody made has no source');
        $this->assertNotSame(VitalBandResolver::NORMAL, $weight['band']);
        $this->assertFalse(VitalBandResolver::hasBand(SelfReportedVitalMeasure::Weight));
        $this->assertNull(VitalBandResolver::labelKey($weight['band']), 'no opinion has no word to print');

        // A measure this codebase does not write at all is unrecognised, and
        // unrecognised is also no opinion rather than a default band.
        $unknown = VitalBandResolver::resolve('gcs_score', 7);
        $this->assertSame(VitalBandResolver::UNKNOWN, $unknown['band']);
        $this->assertNull($unknown['source']);
        $this->assertNull($unknown['measure']);

        // A band read in the wrong unit is no band: 5.6 is a normal glucose in
        // mmol/L and incompatible with life in mg/dL, so an unreadable unit
        // must not be resolved against either.
        $badUnit = VitalBandResolver::resolve(VitalBandResolver::BLOOD_SUGAR, '5.6', null, 'arb/units');
        $this->assertSame(VitalBandResolver::UNKNOWN, $badUnit['band']);
        $this->assertNull($badUnit['source']);

        // And on the page: the weight row is printed, with an honestly blank
        // flag and NO pill. A blank the reader can see, not a silence.
        $this->recordFacilityVitals(['pulse_rate' => 72, 'weight_kg' => 71.5]);

        $row = $this->passportRowFor('71.5', 'kg');
        $this->assertSame(__('passport.not_recorded'), $row[self::FLAG]['v']);
        $this->assertArrayNotHasKey('pill', $row[self::FLAG], 'an unassessed weight must carry no pill');
    }

    // ── 4. The two surfaces agree on one reading ─────────────────────────

    /**
     * The property the resolver exists for. One reading-set, read by the
     * patient's vitals card and by page 25 of their passport; every measure
     * must carry the same verdict on both.
     */
    public function test_the_passport_and_the_vitals_endpoint_agree_on_the_same_reading(): void
    {
        $this->recordFacilityVitals([
            'pulse_rate'        => 128,   // high
            'systolic_bp'       => 186,   // critical
            'diastolic_bp'      => 118,
            'oxygen_saturation' => 92,    // low
            'temperature'       => 39.4,  // high
            'respiratory_rate'  => 16,    // normal
            'weight_kg'         => 71.5,  // no band
        ]);

        $measures = $this->endpointMeasures();

        // Every band the reading actually spans is exercised, so an agreement
        // that only holds for "normal" cannot pass this test.
        $this->assertSame('high', $measures['heart_rate']['status']);
        $this->assertSame('critical', $measures['blood_pressure']['status']);
        $this->assertSame('low', $measures['oxygen_saturation']['status']);
        $this->assertSame('high', $measures['temperature']['status']);
        $this->assertSame('normal', $measures['respiratory_rate']['status']);

        foreach ($measures as $measure) {
            $row = $this->passportRowFor($measure['value'], (string) $measure['unit']);

            $this->assertSame(
                __(VitalBandResolver::labelKey($measure['status'])),
                $row[self::FLAG]['v'],
                "the passport and the vitals card disagree about {$measure['key']} = {$measure['value']}"
            );
        }
    }

    // ── The one thing that must never be flagged ─────────────────────────

    /**
     * A home reading is unverified by design. VitalProvenance says it "has not
     * been checked by a health worker and is not a clinical measurement", the
     * passport prints that sentence above the table, and a clinical pill under
     * it would contradict it. The resolver would happily band 186/118; the
     * self-reported block does not ask.
     */
    public function test_a_self_reported_reading_is_never_clinically_flagged(): void
    {
        PatientRecordedVital::create([
            'patient_id'      => $this->patient->id,
            'measure'         => SelfReportedVitalMeasure::BloodPressure,
            'value_primary'   => 186,
            'value_secondary' => 118,
            'unit'            => 'mmHg',
            'device_label'    => 'Omron M2 home cuff',
            'measured_at'     => Carbon::parse('2026-09-01 06:40:00'),
        ]);

        $sections = $this->passportSections();

        $selfReported = collect($sections)
            ->filter(fn (array $s) => ($s['layout'] ?? null) === 'table')
            ->first(fn (array $s) => collect($s['rows'] ?? [])
                ->contains(fn (array $row) => ($row[1]['v'] ?? null) === '186/118'));

        $this->assertNotNull($selfReported, 'the home reading should still be printed');

        // No flag column at all — the two blocks cannot be misread as one.
        $this->assertNotContains(__('passport.columns.flag'), $selfReported['columns']);

        foreach ($selfReported['rows'] as $row) {
            foreach ($row as $cell) {
                $this->assertArrayNotHasKey('pill', $cell, 'a home reading must carry no clinical pill');
            }
        }
    }

    // ── Fixtures and readers ─────────────────────────────────────────────

    /** The index of the flag column in a facility-measured passport row. */
    private const FLAG = 3;

    private function recordFacilityVitals(array $vitals): void
    {
        TriageVitalSign::create(array_merge([
            'triage_record_id' => (string) Str::uuid(),
            'patient_id'       => $this->patient->id,
            'recorded_by'      => (string) Str::uuid(),
            'recorded_at'      => Carbon::parse('2026-09-02 08:15:00'),
        ], $vitals));
    }

    /** @return array<string, array<string, mixed>> keyed by measure */
    private function endpointMeasures(): array
    {
        return collect(
            $this->mobileGetJson($this->patient, self::VITALS_URI)->assertOk()->json('data.measures')
        )->keyBy('key')->all();
    }

    /** @return list<array<string, mixed>> */
    private function passportSections(): array
    {
        return $this->mobileGetJson($this->patient, self::PASSPORT_URI)
            ->assertOk()
            ->json('data.sections');
    }

    /**
     * The facility-measured row printing exactly this value in exactly this
     * unit. Matching on both is what keeps a "16 /min" from being compared
     * against a "16" that is something else entirely.
     *
     * @return list<array<string, mixed>>
     */
    private function passportRowFor(string $value, string $unit): array
    {
        $table = collect($this->passportSections())
            ->first(fn (array $s) => ($s['layout'] ?? null) === 'table'
                && in_array(__('passport.columns.flag'), $s['columns'] ?? [], true));

        $this->assertNotNull($table, 'the facility-measured table should be on the page');

        $row = collect($table['rows'])
            ->first(fn (array $r) => ($r[1]['v'] ?? null) === $value && ($r[2]['v'] ?? null) === $unit);

        $this->assertNotNull($row, "the passport should print {$value} {$unit}");

        return $row;
    }
}
