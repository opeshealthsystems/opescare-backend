<?php

namespace Tests\Feature;

use App\Console\Commands\ImportLoincAxes;
use App\Models\ClinicalCodeDictionary;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * What opescare:import-loinc-axes is allowed to do to the dictionary.
 *
 * These tests never touch the network. tx.fhir.org's answers are canned
 * through the command's own transport seam, because what must be pinned is
 * OUR behaviour — which columns move, which do not, what happens when the
 * server refuses — not Regenstrief's editorial decisions, which change with
 * every LOINC release and would make a network-dependent test go red for
 * reasons that say nothing about this code.
 *
 * The one test that matters most is the last: it MUTATES the command so that
 * it tries to write `display`, and proves the digest guard catches it and
 * throws the whole chunk away. A guard nobody has watched fail is a comment.
 */
class LoincAxesImportTest extends TestCase
{
    use RefreshDatabase;

    /** LOINC Part codes the fake server knows, and what they resolve to. */
    private const PARTS = [
        'LP7057-5' => 'Bld',
        'LP7753-9' => 'Qn',
        'LP14449-0' => 'Hemoglobin',
        'LP6827-2' => 'MCnc',
        'LP7681-2' => 'Urine',
    ];

    private function row(array $attributes = []): ClinicalCodeDictionary
    {
        return ClinicalCodeDictionary::create(array_merge([
            'id' => (string) Str::uuid(),
            'system' => 'LOINC',
            'code' => '718-7',
            'display' => 'Hemoglobin [Mass/volume] in Blood',
            'short_name' => 'Hgb Bld-mCnc',
            'kind' => 'term',
            'component' => 'Hemoglobin',
            'property' => 'MCnc',
            'is_common' => true,
            'is_active' => true,
            'source' => 'NLM Clinical Table Search Service (LOINC)',
            'source_version' => '2.78',
        ], $attributes));
    }

    /**
     * Register a command instance under the real signature so Artisan::call
     * exercises handle() end to end, transport and all.
     */
    private function register(ImportLoincAxes $command): void
    {
        $command->setLaravel($this->app);
        $this->app[Kernel::class]->registerCommand($command);
    }

    private function immutableSnapshot(): \Illuminate\Support\Collection
    {
        return DB::table('clinical_code_dictionary')
            ->orderBy('code')
            ->get(['id', 'system', 'code', 'display', 'display_fr', 'short_name', 'kind',
                'parent_code', 'chapter', 'synonyms', 'is_common', 'common_reason',
                'is_active', 'source', 'imported_at', 'created_at']);
    }

    public function test_it_fills_specimen_scale_and_units_and_moves_nothing_else(): void
    {
        $this->row();
        $this->row([
            'code' => '2965-2',
            'display' => 'Specific gravity of Urine',
            'component' => 'Specific gravity',
            'property' => 'Rden',
        ]);

        $before = $this->immutableSnapshot();
        $rowCountBefore = DB::table('clinical_code_dictionary')->count();

        $this->register(new FakeLoincAxesServer);
        $this->assertSame(0, $this->artisan('opescare:import-loinc-axes', ['--delay' => 0])->run());

        $haemoglobin = ClinicalCodeDictionary::where('code', '718-7')->first();
        $this->assertSame('Bld', $haemoglobin->specimen);
        $this->assertSame('Qn', $haemoglobin->scale);
        $this->assertSame('g/dL', $haemoglobin->example_units);
        $this->assertSame('2.82', $haemoglobin->source_version);

        $urine = ClinicalCodeDictionary::where('code', '2965-2')->first();
        $this->assertSame('Urine', $urine->specimen, 'A urine test must not be filed as blood.');
        $this->assertSame('Qn', $urine->scale);

        $this->assertEquals($before, $this->immutableSnapshot(), 'A column outside the axis set changed.');
        $this->assertSame($rowCountBefore, DB::table('clinical_code_dictionary')->count(),
            'The import inserted or deleted a row.');
    }

    public function test_it_does_not_clobber_component_and_property_that_nlm_already_filled(): void
    {
        // NLM's COMPONENT/PROPERTY are already LOINC's own published text on
        // all 19,314 rows. Re-rendering them from the Part display buys
        // nothing and could lose wording NLM got right.
        $this->row(['component' => 'NLM component text', 'property' => 'NLM property text']);

        $this->register(new FakeLoincAxesServer);
        $this->artisan('opescare:import-loinc-axes', ['--delay' => 0])->run();

        $row = ClinicalCodeDictionary::where('code', '718-7')->first();
        $this->assertSame('NLM component text', $row->component);
        $this->assertSame('NLM property text', $row->property);
        $this->assertSame('Bld', $row->specimen, 'The axes it CAN fill still filled.');
    }

    public function test_it_fills_component_only_where_the_column_is_still_null(): void
    {
        $this->row(['component' => null, 'property' => null]);

        $this->register(new FakeLoincAxesServer);
        $this->artisan('opescare:import-loinc-axes', ['--delay' => 0])->run();

        $row = ClinicalCodeDictionary::where('code', '718-7')->first();
        $this->assertSame('Hemoglobin', $row->component);
        $this->assertSame('MCnc', $row->property);
    }

    public function test_it_only_picks_up_rows_that_are_still_missing_their_specimen(): void
    {
        // Resumability, which is what makes a 19,000-request run survivable.
        $this->row(['specimen' => 'Bld', 'scale' => 'Qn', 'source_version' => '2.82']);
        $this->row(['code' => '2965-2', 'display' => 'Specific gravity of Urine']);

        $server = new FakeLoincAxesServer;
        $this->register($server);
        $this->artisan('opescare:import-loinc-axes', ['--delay' => 0])->run();

        $this->assertNotContains('718-7', $server->codesLookedUp,
            'An already-filled row was fetched again, so a resumed run would start over.');
        $this->assertContains('2965-2', $server->codesLookedUp);
    }

    public function test_a_code_the_server_will_not_answer_is_skipped_and_left_null(): void
    {
        // Never a placeholder, never a guess. NULL is the correct outcome.
        $this->row(['code' => '99999-9', 'display' => 'A code this server refuses']);

        $server = new FakeLoincAxesServer;
        $server->refuse = ['99999-9'];
        $this->register($server);
        $this->artisan('opescare:import-loinc-axes', ['--delay' => 0])->run();

        $row = ClinicalCodeDictionary::where('code', '99999-9')->first();
        $this->assertNull($row->specimen);
        $this->assertNull($row->scale);
        $this->assertSame('2.78', $row->source_version, 'A skipped row must keep its old provenance.');
    }

    public function test_a_long_run_of_codes_the_server_does_not_carry_does_not_stop_the_import(): void
    {
        // Our codes came from NLM at LOINC 2.78; tx.fhir.org serves 2.82, so
        // codes it has never heard of exist — and because rows are walked in
        // code order they arrive in RUNS. Counting a 404 as "the server is
        // refusing us" stopped a healthy import dead once already.
        for ($i = 0; $i < 40; $i++) {
            $this->row(['code' => '1' . str_pad((string) $i, 4, '0', STR_PAD_LEFT) . '-1']);
        }

        // ...and one real code AFTER them, which must still be reached.
        $this->row(['code' => '2965-2', 'display' => 'Specific gravity of Urine']);

        $this->register(new FakeLoincAxesServer);
        $this->assertSame(0, $this->artisan('opescare:import-loinc-axes', ['--delay' => 0])->run());

        $this->assertSame('Urine', ClinicalCodeDictionary::where('code', '2965-2')->first()->specimen);
        $this->assertNull(ClinicalCodeDictionary::where('code', '10000-1')->first()->specimen);
    }

    public function test_the_digest_guard_catches_a_command_that_touches_display(): void
    {
        // THE GUARD, PROVEN. MutatingLoincAxesServer is this command with one
        // line changed — its UPDATE payload also carries `display`. If the
        // digest check were a comment rather than a check, the title below
        // would now read "MUTATED BY A BROKEN IMPORTER".
        $this->row();

        $before = $this->immutableSnapshot();

        $this->register(new MutatingLoincAxesServer);
        $exitCode = $this->artisan('opescare:import-loinc-axes', ['--delay' => 0])->run();

        $this->assertSame(1, $exitCode, 'A guard that does not fail the command is not a guard.');

        $row = ClinicalCodeDictionary::where('code', '718-7')->first();
        $this->assertSame('Hemoglobin [Mass/volume] in Blood', $row->display);
        $this->assertNull($row->specimen, 'The whole chunk must be rolled back, axes included.');
        $this->assertEquals($before, $this->immutableSnapshot());
    }

    public static function cannedLookup(string $url): ?array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $code = (string) ($query['code'] ?? '');

        if (isset(self::PARTS[$code])) {
            return ['resourceType' => 'Parameters', 'parameter' => [
                ['name' => 'display', 'valueString' => self::PARTS[$code]],
            ]];
        }

        $axes = match ($code) {
            '718-7' => [
                ['COMPONENT', 'valueCode', 'LP14449-0'],
                ['PROPERTY', 'valueCode', 'LP6827-2'],
                ['SYSTEM', 'valueCode', 'LP7057-5'],
                ['SCALE_TYP', 'valueCode', 'LP7753-9'],
                ['EXAMPLE_UNITS', 'valueString', 'g/dL'],
            ],
            '2965-2' => [
                ['SYSTEM', 'valueCode', 'LP7681-2'],
                ['SCALE_TYP', 'valueCode', 'LP7753-9'],
            ],
            default => null,
        };

        if ($axes === null) {
            return null;
        }

        $parameters = [
            ['name' => 'version', 'valueString' => '2.82'],
        ];

        foreach ($axes as [$property, $valueKey, $value]) {
            $parameters[] = ['name' => 'property', 'part' => [
                ['name' => 'code', 'valueCode' => $property],
                ['name' => 'value', $valueKey => $value],
            ]];
        }

        return ['resourceType' => 'Parameters', 'parameter' => $parameters];
    }
}

/**
 * The command with its transport replaced by canned FHIR payloads. Everything
 * else — the selection, the Part cache, the chunking, the guard — is the real
 * code path.
 */
class FakeLoincAxesServer extends ImportLoincAxes
{
    /** @var list<string> */
    public array $codesLookedUp = [];

    /** @var list<string> Codes this fake server refuses, however often it is asked. */
    public array $refuse = [];

    protected function request(string $url, int $delayUs): ?array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $code = (string) ($query['code'] ?? '');

        if (! str_starts_with($code, 'LP')) {
            $this->codesLookedUp[] = $code;
        }

        if (in_array($code, $this->refuse, true)) {
            $this->lastStatus = 0;

            return null;
        }

        $canned = LoincAxesImportTest::cannedLookup($url);

        // A code this server does not carry: a healthy 404, not a refusal.
        $this->lastStatus = $canned === null ? 404 : 200;

        return $canned;
    }

    /** The Part cache file must not leak between runs or into the repo. */
    protected function partDisplay(string $partCode, int $delayUs): ?string
    {
        $decoded = $this->request(
            'https://example.test/CodeSystem/$lookup?code=' . rawurlencode($partCode),
            $delayUs,
        );

        foreach ($decoded['parameter'] ?? [] as $parameter) {
            if (($parameter['name'] ?? null) === 'display') {
                return (string) $parameter['valueString'];
            }
        }

        return null;
    }
}

/** The same command, sabotaged to write a column it promised never to touch. */
class MutatingLoincAxesServer extends FakeLoincAxesServer
{
    protected function updatePayload(string $code, array $update): array
    {
        $update = parent::updatePayload($code, $update);
        $update['display'] = 'MUTATED BY A BROKEN IMPORTER';

        return $update;
    }
}
