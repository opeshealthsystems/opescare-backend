<?php

namespace Tests\Feature;

use App\Console\Commands\ImportProcedureClassification;
use App\Models\ClinicalCodeDictionary;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

/**
 * The WHO ICHI procedure import, tested where it can actually go wrong.
 *
 * Nothing here touches icd.who.int. A test that depends on WHO's uptime goes
 * red when someone else's DNS does, and it would prove nothing about the
 * three things that genuinely matter:
 *
 *   1. A procedure is NEVER offered where a diagnosis or a lab term belongs.
 *   2. The code pattern reads WHO's stem codes and refuses everything else —
 *      no placeholder, no axis token, no three-letter word from a title.
 *   3. The rows carry honest provenance and no invented French.
 *
 * The walk itself is exercised against a fabricated tree, so the parsing and
 * the scoping decisions are under test without a network call.
 */
class ProcedureClassificationTest extends TestCase
{
    use RefreshDatabase;

    /** Run the private breadth-first walk over a fabricated WHO response. */
    private function harvest(array $tree): ImportProcedureClassification
    {
        $command = new class($tree) extends ImportProcedureClassification
        {
            public function __construct(private array $tree)
            {
                parent::__construct();
            }

            /** Stand in for the network. Keys are concept ids. */
            protected function childrenOfMany(array $conceptIds): array
            {
                $out = [];

                foreach ($conceptIds as $id) {
                    $out[$id] = $this->tree[$id] ?? [];
                }

                return $out;
            }
        };

        // walk() reports each level as it goes. Without an output to report
        // to it fatals, so give it one that goes nowhere.
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput()));

        $walk = (new ReflectionClass(ImportProcedureClassification::class))->getMethod('walk');
        $walk->setAccessible(true);
        $walk->invoke($command, [['id' => 'root', 'parent_code' => null]]);

        return $command;
    }

    private function rowsOf(ImportProcedureClassification $command): array
    {
        $property = (new ReflectionClass(ImportProcedureClassification::class))->getProperty('rows');
        $property->setAccessible(true);

        return $property->getValue($command);
    }

    private function node(string $id, string $label, string $kind, bool $leaf): array
    {
        return ['id' => $id, 'label' => $label, 'kind' => $kind, 'leaf' => $leaf];
    }

    /**
     * THE point of the whole exercise: `assignable()` is what populates the
     * diagnosis and lab pickers, and an appendicectomy must never appear in
     * one. It is excluded by KIND, not by a filter somebody has to remember
     * to add at each call site.
     */
    public function test_a_procedure_is_never_offered_where_a_diagnosis_or_lab_term_belongs(): void
    {
        $this->seedRow('ICHI', 'KBO.JB.AA', 'Drainage of appendix', 'procedure');
        $this->seedRow('ICD-10', 'K35.8', 'Acute appendicitis, other', 'category');
        $this->seedRow('LOINC', '6690-2', 'Leukocytes [#/volume] in Blood', 'term');

        $assignable = ClinicalCodeDictionary::assignable()->pluck('code')->all();

        $this->assertContains('K35.8', $assignable);
        $this->assertContains('6690-2', $assignable);
        $this->assertNotContains('KBO.JB.AA', $assignable, 'A procedure was offered as an assignable clinical code.');

        $this->assertSame(0, ClinicalCodeDictionary::assignable()->where('system', 'ICHI')->count());
    }

    /** `kind` is never one of the two values `assignable()` accepts. */
    public function test_the_importer_writes_procedure_and_never_category_or_term(): void
    {
        $source = file_get_contents(
            (new ReflectionClass(ImportProcedureClassification::class))->getFileName(),
        );

        $this->assertStringContainsString("'kind' => 'procedure'", $source);
        $this->assertStringNotContainsString("'kind' => 'category'", $source);
        $this->assertStringNotContainsString("'kind' => 'term'", $source);
    }

    /**
     * WHO's stem codes are read; WHO's placeholders are not.
     *
     * `_LDINCOMPLETE`, `_NOCODEASSIGNED` and `??.ZZ.ZZ` are markers for
     * categories WHO has not finished coding. They are not codes and must
     * never reach the dictionary, where the unique key would make them look
     * like real ones.
     */
    public function test_who_placeholders_and_axis_tokens_are_refused_and_real_stem_codes_are_kept(): void
    {
        $command = $this->harvest([
            'root' => [
                $this->node('a', ' AAN.AD.AA Biopsy of cranial nerve or ganglion', 'category', true),
                $this->node('b', ' NME.JF.AA Delivery by caesarean section', 'category', true),
                $this->node('c', ' _LDINCOMPLETE Assessment of handling general tasks, unspecified', 'category', true),
                $this->node('d', ' ??.ZZ.ZZ Interventions on the environment, unspecified', 'category', true),
                $this->node('e', ' _NOCODEASSIGNED.AA.ZZ Something WHO has not coded', 'category', true),
                $this->node('f', ' ZZY Other specified target', 'category', true),
            ],
        ]);

        $this->assertSame(
            ['AAN.AD.AA', 'NME.JF.AA'],
            array_keys($this->rowsOf($command)),
        );
    }

    /**
     * A three-letter word at the start of a title is not a code.
     *
     * This is the specific fabrication the dot in the pattern exists to
     * prevent: "HIV testing" must not become code "HIV" with display
     * "testing".
     */
    public function test_a_three_letter_word_starting_a_title_is_not_read_as_a_code(): void
    {
        $command = $this->harvest([
            'root' => [
                $this->node('a', ' HIV testing', 'category', true),
                $this->node('b', ' ECG monitoring', 'category', true),
            ],
        ]);

        $this->assertSame([], $this->rowsOf($command));
    }

    /** Codeless group headings are descended through, never stored. */
    public function test_codeless_group_headings_are_not_stored_under_an_invented_code(): void
    {
        $command = $this->harvest([
            'root' => [
                $this->node('block', ' Interventions on brain', 'block', false),
            ],
            'block' => [
                $this->node('leaf', ' AAA.AD.AA Biopsy of brain', 'category', true),
            ],
        ]);

        $rows = $this->rowsOf($command);

        $this->assertSame(['AAA.AD.AA'], array_keys($rows));
        $this->assertNull($rows['AAA.AD.AA']['parent_code'], 'A codeless block was invented into a parent code.');
    }

    /** ICHI allows multiple parents; the dictionary key does not. */
    public function test_a_code_reached_down_two_branches_is_written_once(): void
    {
        $command = $this->harvest([
            'root' => [
                $this->node('b1', ' Branch one', 'block', false),
                $this->node('b2', ' Branch two', 'block', false),
            ],
            'b1' => [$this->node('x', ' AAA.AD.AA Biopsy of brain', 'category', true)],
            'b2' => [$this->node('x', ' AAA.AD.AA Biopsy of brain', 'category', true)],
        ]);

        $this->assertCount(1, $this->rowsOf($command));
    }

    /**
     * Nothing in this deployment may claim a CPT code, and the ICHI rows must
     * not smuggle one in through `system` or `source`.
     */
    public function test_no_row_this_importer_writes_claims_a_licensed_system(): void
    {
        $class = new ReflectionClass(ImportProcedureClassification::class);

        $this->assertSame('ICHI', $class->getConstant('SYSTEM'));
        $this->assertStringNotContainsStringIgnoringCase('CPT', $class->getConstant('SOURCE'));
        $this->assertStringNotContainsStringIgnoringCase('CCAM', $class->getConstant('SOURCE'));
        $this->assertStringContainsString('WHO ICHI', $class->getConstant('SOURCE'));
    }

    /**
     * WHO publishes no French ICHI — `/l-ichi/fr` answers
     * "[No translation available]" on every label. So the French column stays
     * empty and the reader falls back to the published English, exactly as
     * they already do for the ICD-10 U-chapter. A machine translation of a
     * surgical title would be a French term nobody published.
     */
    public function test_french_is_left_null_rather_than_machine_translated(): void
    {
        $source = file_get_contents(
            (new ReflectionClass(ImportProcedureClassification::class))->getFileName(),
        );

        $this->assertStringContainsString("'display_fr' => null", $source);

        $this->seedRow('ICHI', 'NME.JF.AA', 'Delivery by caesarean section', 'procedure');

        $row = ClinicalCodeDictionary::where('code', 'NME.JF.AA')->first();
        $this->assertNull($row->display_fr);

        app()->setLocale('fr');
        $this->assertSame('Delivery by caesarean section', $row->label);
    }

    /** A half-arrived classification must fail, not be written. */
    public function test_the_sanity_floor_is_high_enough_to_catch_a_truncated_walk(): void
    {
        $floor = (new ReflectionClass(ImportProcedureClassification::class))->getConstant('SANITY_FLOOR');

        // The 2026-09-08 harvest was 8,923. The floor has to be well below
        // that so a normal release does not trip it, and well above zero so a
        // walk that dies after two branches does.
        $this->assertGreaterThanOrEqual(1000, $floor);
        $this->assertLessThan(8923, $floor);
    }

    private function seedRow(string $system, string $code, string $display, string $kind): void
    {
        DB::table('clinical_code_dictionary')->insert([
            'id' => (string) Str::uuid(),
            'system' => $system,
            'code' => $code,
            'display' => $display,
            'kind' => $kind,
            'is_active' => true,
            'source' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
