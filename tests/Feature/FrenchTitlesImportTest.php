<?php

namespace Tests\Feature;

use App\Console\Commands\ImportFrenchTitles;
use App\Models\ClinicalCodeDictionary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/**
 * What opescare:import-french-titles is allowed to do to the dictionary.
 *
 * These tests exercise the WRITE PATH, not the network. Whether tx.fhir.org
 * has French for a given LOINC code is the publisher's business and changes
 * with every release; what must never change is that this command writes
 * `display_fr` and nothing else, and that it can only ever update a row that
 * already exists. Those two properties are what make a terminology import
 * safe to re-run against a live dictionary, so those are what is pinned here.
 *
 * The harvest itself is deliberately untested: a test that asserted "2965-2 is
 * 'Densité …'" would be asserting Regenstrief's editorial decisions, and would
 * go red the day they reword a long name — telling us nothing about our code.
 */
class FrenchTitlesImportTest extends TestCase
{
    use RefreshDatabase;

    private function row(array $attributes = []): ClinicalCodeDictionary
    {
        return ClinicalCodeDictionary::create(array_merge([
            'id' => (string) Str::uuid(),
            'system' => 'LOINC',
            'code' => '2965-2',
            'display' => 'Specific gravity of Urine',
            'kind' => 'term',
            'is_common' => true,
            'is_active' => true,
            'source' => 'NLM Clinical Table Search Service (LOINC)',
            'source_version' => '2.78',
        ], $attributes));
    }

    /** Reach one private method of the command under test. */
    private function invokePrivate(string $method, array $arguments): mixed
    {
        $reflected = new ReflectionMethod(ImportFrenchTitles::class, $method);
        $reflected->setAccessible(true);

        return $reflected->invokeArgs(app(ImportFrenchTitles::class), $arguments);
    }

    public function test_writing_french_leaves_every_other_column_byte_identical(): void
    {
        // The whole point of the command. A terminology importer that quietly
        // rewrote `display`, flipped `is_common`, or restamped `source` would
        // corrupt a table that partner systems read — and it would do it
        // 19,000 rows at a time.
        $this->row();
        $this->row(['code' => '718-7', 'display' => 'Hemoglobin [Mass/volume] in Blood', 'is_common' => false]);

        $before = DB::table('clinical_code_dictionary')
            ->orderBy('code')
            ->get(['id', 'system', 'code', 'display', 'short_name', 'kind', 'is_common',
                'common_reason', 'is_active', 'source', 'source_version', 'created_at']);

        $this->invokePrivate('write', ['LOINC', [
            '2965-2' => 'Densité [Densité relative] Urine ; Numérique',
            '718-7' => 'Hémoglobine [Masse/Volume] Sang ; Numérique',
        ]]);

        $after = DB::table('clinical_code_dictionary')
            ->orderBy('code')
            ->get(['id', 'system', 'code', 'display', 'short_name', 'kind', 'is_common',
                'common_reason', 'is_active', 'source', 'source_version', 'created_at']);

        $this->assertEquals($before, $after, 'A column other than display_fr changed.');

        // …and the French did land, so the assertion above is not passing for
        // the trivial reason that nothing happened at all.
        $this->assertSame(
            'Densité [Densité relative] Urine ; Numérique',
            DB::table('clinical_code_dictionary')->where('code', '2965-2')->value('display_fr'),
        );
        $this->assertSame(
            'Hémoglobine [Masse/Volume] Sang ; Numérique',
            DB::table('clinical_code_dictionary')->where('code', '718-7')->value('display_fr'),
        );
    }

    public function test_no_row_is_ever_created_by_a_french_title(): void
    {
        // The French sources are national variants that carry codes we do not
        // hold, on purpose (CIM-10 FR has 19,075 codes against WHO's 12,599).
        // A French title for a code we do not stock must vanish, not become a
        // new dictionary entry nobody chose to import.
        $this->row();

        $this->invokePrivate('write', ['LOINC', [
            '2965-2' => 'Densité [Densité relative] Urine ; Numérique',
            '99999-9' => 'Un code que nous ne détenons pas',
        ]]);

        $this->assertSame(1, DB::table('clinical_code_dictionary')->count());
        $this->assertNull(DB::table('clinical_code_dictionary')->where('code', '99999-9')->first());
    }

    public function test_a_french_title_never_crosses_from_one_system_to_another(): void
    {
        // LOINC and ICD-10 codes can look alike enough to collide if a write
        // matched on `code` alone. Every update is scoped to (system, code).
        $this->row(['system' => 'LOINC', 'code' => 'A00', 'display' => 'A LOINC row that happens to be A00']);
        $this->row(['system' => 'ICD-10', 'code' => 'A00', 'display' => 'Cholera']);

        $this->invokePrivate('write', ['ICD-10', ['A00' => 'Choléra']]);

        $this->assertSame('Choléra', DB::table('clinical_code_dictionary')
            ->where('system', 'ICD-10')->where('code', 'A00')->value('display_fr'));
        $this->assertNull(DB::table('clinical_code_dictionary')
            ->where('system', 'LOINC')->where('code', 'A00')->value('display_fr'));
    }

    public function test_rerunning_the_same_write_changes_nothing(): void
    {
        // Idempotence, because this command is meant to be re-run after every
        // release of a source rather than run once and feared.
        $this->row();

        $this->invokePrivate('write', ['LOINC', ['2965-2' => 'Densité [Densité relative] Urine ; Numérique']]);
        $first = $this->invokePrivate('immutableDigest', []);

        $this->invokePrivate('write', ['LOINC', ['2965-2' => 'Densité [Densité relative] Urine ; Numérique']]);

        $this->assertSame($first, $this->invokePrivate('immutableDigest', []));
        $this->assertSame(1, DB::table('clinical_code_dictionary')->count());
    }

    public function test_the_guard_digest_notices_a_forbidden_column_change(): void
    {
        // The guard has to actually detect something, or it is decoration. If
        // this test ever goes red because the digest stopped moving, the
        // rollback in handle() has stopped protecting anything.
        $this->row();

        $before = $this->invokePrivate('immutableDigest', []);

        DB::table('clinical_code_dictionary')->where('code', '2965-2')
            ->update(['display' => 'Something a French import had no business writing']);

        $this->assertNotSame($before, $this->invokePrivate('immutableDigest', []));
    }

    public function test_the_guard_digest_ignores_the_two_columns_that_may_move(): void
    {
        // The mirror of the test above: display_fr and updated_at are the
        // command's to write, so they must NOT trip the guard — otherwise
        // every successful run would roll itself back.
        $this->row();

        $before = $this->invokePrivate('immutableDigest', []);

        DB::table('clinical_code_dictionary')->where('code', '2965-2')
            ->update(['display_fr' => 'Densité [Densité relative] Urine ; Numérique', 'updated_at' => now()->addDay()]);

        $this->assertSame($before, $this->invokePrivate('immutableDigest', []));
    }

    public function test_the_guard_digest_notices_a_row_appearing_or_disappearing(): void
    {
        // A command that adds no codes must be caught if it ever adds one.
        $this->row();
        $before = $this->invokePrivate('immutableDigest', []);

        $this->row(['code' => '718-7', 'display' => 'Hemoglobin [Mass/volume] in Blood']);

        $this->assertNotSame($before, $this->invokePrivate('immutableDigest', []));
    }
}
