<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Services\Terminology\TerminologyReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The reconciler is a reporting tool, so the tests that matter are of two
 * kinds: does it find the four things a human already found by hand, and — far
 * more important — does it leave the data exactly as it was.
 */
class TerminologyReconcilerTest extends TestCase
{
    use RefreshDatabase;

    private TerminologyReconciler $reconciler;

    private ?string $patientId = null;

    private ?string $facilityId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reconciler = app(TerminologyReconciler::class);
        $this->patientId = null;
        $this->facilityId = null;
    }

    // ── Fixtures ─────────────────────────────────────────────────────────

    private function dictionary(string $system, string $code, string $display, string $kind = 'term'): void
    {
        DB::table('clinical_code_dictionary')->insert([
            'id' => (string) Str::uuid(),
            'system' => $system,
            'code' => $code,
            'display' => $display,
            'kind' => $kind,
            'is_active' => true,
            'is_common' => false,
            'source' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function medicine(string $name, ?string $atc): void
    {
        DB::table('medicines')->insert([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'generic_name' => $name,
            'atc_code' => $atc,
            // NOT NULL on this table, and not something the reconciler reads.
            'category' => 'other',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * One patient row, because the clinical tables below require one.
     *
     * Per test, not static: RefreshDatabase rolls the row back between tests
     * and a cached id would point at nothing on the second one.
     */
    private function patientId(): string
    {
        return $this->patientId ??= (string) Patient::factory()->create()->id;
    }

    /** A facility row, required by immunization_records and nothing else here. */
    private function facilityId(): string
    {
        return $this->facilityId ??= (string) Facility::factory()->create()->id;
    }

    private function finding(array $report, string $key): array
    {
        foreach ($report as $f) {
            if ($f['key'] === $key) {
                return $f;
            }
        }

        $this->fail("No finding for {$key}");
    }

    // ── Finding 1: a code the publisher moved ────────────────────────────

    public function test_it_reports_a_superseded_code_as_unresolved_and_suggests_where_it_went(): void
    {
        // The exact shape of the real case: the vocabulary still carries the
        // old branch with other children, so the code LOOKS plausible, and the
        // drug itself has moved to a new subgroup under a new code.
        $this->dictionary('ATC', 'L01XX11', 'estramustine');
        $this->dictionary('ATC', 'L01XX16', 'mitoguazone');
        $this->dictionary('ATC', 'L01XF01', 'trétinoïne');
        $this->dictionary('ATC', 'L01XF02', 'alitrétinoïne');

        $this->medicine('Tretinoin 10mg Capsule', 'L01XX14');
        $this->medicine('Estramustine', 'L01XX11');

        $finding = $this->finding($this->reconciler->report(), 'medicines.atc_code');

        $this->assertSame(TerminologyReconciler::STATE_CHECKED, $finding['state']);
        $this->assertSame(1, $finding['resolved_values']);
        $this->assertCount(1, $finding['unresolved']);
        $this->assertSame('L01XX14', $finding['unresolved'][0]['value']);

        // And it points at where WHO put the drug. Accent-folded: the medicine
        // says "Tretinoin", the dictionary says "trétinoïne".
        $codes = array_column($finding['unresolved'][0]['suggestions'], 'code');
        $this->assertContains('L01XF01', $codes);
        $this->assertSame('L01XF01', $codes[0], 'the same-branch match should rank first');
    }

    public function test_a_group_level_code_is_unresolved_but_flagged_as_present(): void
    {
        $this->dictionary('ATC', 'A07C', 'oral rehydration salts', 'group');
        $this->dictionary('ATC', 'A07CA01', 'a real product');

        $this->medicine('ORS sachet', 'A07C');

        $finding = $this->finding($this->reconciler->report(), 'medicines.atc_code');

        $this->assertCount(1, $finding['unresolved']);
        $this->assertTrue($finding['unresolved'][0]['heading_only'], 'a heading is present but not assignable');
    }

    // ── Findings 2 and 3: one concept, two spellings ─────────────────────

    public function test_it_pairs_two_spellings_of_one_unit(): void
    {
        $this->seedLabUnits(['10^9/L' => 6, 'x10^9/L' => 1]);

        $finding = $this->finding($this->reconciler->report(), 'lab_results.unit');

        $pairs = $this->pairsFor($finding, 'near_string');
        $this->assertContains(['10^9/L', 'x10^9/L'], $pairs);
    }

    public function test_it_does_not_pair_units_that_differ_only_by_magnitude(): void
    {
        // The single most dangerous false positive this tool could produce.
        // mmol/L and umol/L are one edit apart and a thousandfold different;
        // so are g/dL and mg/dL. Anyone acting on such a pairing would corrupt
        // a result, so the rule must stay silent here.
        $this->seedLabUnits(['mmol/L' => 4, 'umol/L' => 2, 'g/dL' => 3, 'mg/dL' => 1]);

        $finding = $this->finding($this->reconciler->report(), 'lab_results.unit');

        $this->assertSame([], $finding['duplicates']);
    }

    public function test_it_pairs_a_route_abbreviation_with_its_written_out_form(): void
    {
        $this->seedRoutes(['IM' => 15, 'intramuscular' => 10, 'SC' => 6]);

        $finding = $this->finding($this->reconciler->report(), 'immunization_records.route');

        $pairs = $this->pairsFor($finding, 'declared_synonym');
        $this->assertContains(['IM', 'intramuscular'], $pairs);

        // SC stands alone here, and must not be dragged into the IM cluster or
        // paired with it: they are different routes.
        foreach ($finding['duplicates'] as $cluster) {
            $values = array_column($cluster['members'], 'value');
            $this->assertNotContains('SC', $values);
        }
    }

    public function test_case_only_differences_are_reported_once_not_twice(): void
    {
        $this->seedRoutes(['oral' => 15, 'Oral' => 7]);

        $finding = $this->finding($this->reconciler->report(), 'immunization_records.route');

        // "oral" and "Oral" are both identical after folding AND both members
        // of the curated by-mouth set. One pair, one line.
        $this->assertCount(1, $finding['duplicates']);
        $this->assertSame('case_punctuation', $finding['duplicates'][0]['rule']);
    }

    public function test_it_does_not_pair_neighbouring_codes_in_a_code_column(): void
    {
        // Code systems are built so that neighbours differ by one character and
        // mean different things. N02BE01 is paracetamol and N02BA01 is aspirin.
        $this->dictionary('ATC', 'N02BE01', 'paracétamol');
        $this->dictionary('ATC', 'N02BA01', 'acide acétylsalicylique');

        $this->medicine('Paracetamol 500mg', 'N02BE01');
        $this->medicine('Aspirin 300mg', 'N02BA01');

        $finding = $this->finding($this->reconciler->report(), 'medicines.atc_code');

        $this->assertSame([], $finding['duplicates']);
    }

    // ── Finding 4: values backed by no vocabulary ────────────────────────

    public function test_local_codes_with_no_vocabulary_are_reported_as_unbacked(): void
    {
        $this->seedVaccines(['PENTA' => 6, 'YF' => 4]);

        $finding = $this->finding($this->reconciler->report(), 'immunization_records.vaccine_code');

        // No CVX rows were seeded in this test, so there is nothing to check
        // against — and that is a different statement from "these are wrong".
        $this->assertSame(TerminologyReconciler::STATE_VOCABULARY_ABSENT, $finding['state']);
        $this->assertSame(2, $finding['distinct']);
        $this->assertSame(10, $finding['filled']);
    }

    // ── The healthy case, and the three kinds of nothing ─────────────────

    public function test_a_clean_column_is_reported_clean(): void
    {
        $this->dictionary('ICD-10', 'B50.9', 'Plasmodium falciparum malaria, unspecified', 'category');
        $this->dictionary('ICD-10', 'I10', 'Essential (primary) hypertension', 'category');
        $this->dictionary('ICD-10', 'E11', 'Type 2 diabetes mellitus', 'category');

        // A real visit row: diagnoses.visit_id is a foreign key and Postgres
        // enforces it, whatever the factory's comment about SQLite says.
        $visitId = (string) Str::uuid();
        DB::table('visits')->insert([
            'id' => $visitId,
            'patient_id' => $this->patientId(),
            'facility_id' => $this->facilityId(),
            'visit_type' => 'outpatient',
            'status' => 'open',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['B50.9', 'I10', 'E11'] as $code) {
            Diagnosis::factory()->create([
                'patient_id' => $this->patientId(),
                'visit_id' => $visitId,
                'code_system' => 'ICD-10',
                'code' => $code,
                'display_name' => 'seeded',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $finding = $this->finding($this->reconciler->report(), 'diagnoses.code');

        $this->assertSame(TerminologyReconciler::STATE_CHECKED, $finding['state']);
        $this->assertSame(3, $finding['resolved_values']);
        $this->assertSame([], $finding['unresolved']);
    }

    public function test_an_absent_table_is_reported_absent_and_does_not_crash(): void
    {
        $finding = $this->finding($this->reconciler->report(), 'allergies.code');

        $this->assertSame(TerminologyReconciler::STATE_COLUMN_ABSENT, $finding['state']);
    }

    public function test_an_empty_column_is_reported_empty_not_clean(): void
    {
        $this->seedLabUnits(['g/dL' => 2]); // rows exist, loinc_code left null

        $finding = $this->finding($this->reconciler->report(), 'lab_results.loinc_code');

        $this->assertSame(TerminologyReconciler::STATE_EMPTY, $finding['state']);
        $this->assertSame(2, $finding['rows']);
        $this->assertSame(0, $finding['filled']);
    }

    // ── The one that matters most ────────────────────────────────────────

    public function test_running_the_report_writes_nothing_at_all(): void
    {
        $this->dictionary('ATC', 'L01XF01', 'trétinoïne');
        $this->medicine('Tretinoin 10mg Capsule', 'L01XX14');
        $this->seedLabUnits(['10^9/L' => 6, 'x10^9/L' => 1]);
        $this->seedRoutes(['IM' => 3, 'intramuscular' => 2]);
        $this->seedVaccines(['PENTA' => 2]);

        $tables = ['clinical_code_dictionary', 'medicines', 'lab_results', 'immunization_records'];

        $before = [];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }

        // Twice, because a cache warmed on the first pass must not write either.
        $this->reconciler->report();
        $this->reconciler->report();

        foreach ($tables as $table) {
            $this->assertSame(
                $before[$table],
                DB::table($table)->orderBy('id')->get()->toJson(),
                "the reconciler modified {$table} — it must only ever read"
            );
        }
    }

    public function test_the_console_command_runs_and_changes_nothing(): void
    {
        $this->dictionary('ATC', 'L01XF01', 'trétinoïne');
        $this->medicine('Tretinoin 10mg Capsule', 'L01XX14');

        $before = DB::table('medicines')->orderBy('id')->get()->toJson();

        $this->artisan('opescare:terminology-gaps')
            ->assertExitCode(0);

        $this->assertSame($before, DB::table('medicines')->orderBy('id')->get()->toJson());
    }

    // ── Folding, which every rule above rests on ─────────────────────────

    public function test_folding_ignores_case_accents_and_punctuation_only(): void
    {
        $this->assertSame('109l', TerminologyReconciler::fold('10^9/L'));
        $this->assertSame('x109l', TerminologyReconciler::fold('x10^9/L'));
        $this->assertSame('oral', TerminologyReconciler::fold('Oral'));
        $this->assertSame('tretinoine', TerminologyReconciler::fold('trétinoïne'));

        // Folding must not erase a magnitude: these stay different strings, and
        // it is the SI-prefix guard, not folding, that keeps them apart.
        $this->assertNotSame(
            TerminologyReconciler::fold('mmol/L'),
            TerminologyReconciler::fold('umol/L')
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /** The distinct values of one cluster rule, each sorted, for assertContains. */
    private function pairsFor(array $finding, string $rule): array
    {
        $pairs = [];

        foreach ($finding['duplicates'] as $cluster) {
            if ($cluster['rule'] !== $rule) {
                continue;
            }

            $values = array_column($cluster['members'], 'value');
            sort($values);
            $pairs[] = $values;
        }

        return $pairs;
    }

    private function seedLabUnits(array $unitCounts): void
    {
        $orderId = LabOrder::factory()->create(['patient_id' => $this->patientId()])->id;

        foreach ($unitCounts as $unit => $count) {
            for ($i = 0; $i < $count; $i++) {
                DB::table('lab_results')->insert([
                    'id' => (string) Str::uuid(),
                    'lab_order_id' => $orderId,
                    'patient_id' => $this->patientId(),
                    'parameter_name' => 'Platelet Count',
                    'value' => '210',
                    'unit' => $unit,
                    'resulted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedRoutes(array $routeCounts): void
    {
        foreach ($routeCounts as $route => $count) {
            $this->immunizations($count, ['route' => $route]);
        }
    }

    private function seedVaccines(array $codeCounts): void
    {
        foreach ($codeCounts as $code => $count) {
            $this->immunizations($count, ['vaccine_code' => $code, 'vaccine_name' => $code]);
        }
    }

    private function immunizations(int $count, array $attributes): void
    {
        for ($i = 0; $i < $count; $i++) {
            DB::table('immunization_records')->insert(array_merge([
                'id' => (string) Str::uuid(),
                'patient_id' => $this->patientId(),
                // NOT NULL, and irrelevant to a vocabulary census.
                'facility_id' => $this->facilityId(),
                'vaccine_code' => 'BCG',
                'vaccine_system' => 'WHO-EPI',
                'vaccine_name' => 'BCG',
                'administered_at' => now(),
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ], $attributes));
        }
    }
}
