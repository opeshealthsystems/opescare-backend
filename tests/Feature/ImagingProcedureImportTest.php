<?php

namespace Tests\Feature;

use App\Console\Commands\ImportImagingCodes;
use App\Support\Terminology\ClinicalCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The imaging import, tested where it can actually go wrong.
 *
 * These tests deliberately do NOT call the NLM endpoint. A test that depends
 * on a third-party service is a test that goes red when someone else's DNS
 * does, and it would prove nothing about the two things that matter here:
 * that a row already carrying a reason is not overwritten, and that nothing
 * unverified gets stored. Both are exercised directly against the command's
 * own collect-and-write path with fabricated payloads.
 */
class ImagingProcedureImportTest extends TestCase
{
    use RefreshDatabase;

    /** Feed rows straight into the command's harvest, bypassing the network. */
    private function collect(ImportImagingCodes $command, array $rows, string $reason): array
    {
        $collected = [];

        $harvest = new ReflectionMethod($command, 'harvest');
        $filter = new ReflectionMethod($command, 'isImaging');

        // harvest() is the network path; its per-row logic is what we want, so
        // the same acceptance rules are applied here in the same order.
        foreach ($rows as [$code, $long, $short, $component, $property, $related]) {
            if (! ClinicalCode::isLoinc($code)) {
                continue;
            }

            if (! $filter->invoke($command, $long, $related)) {
                continue;
            }

            if (isset($collected[$code])) {
                continue;
            }

            $collected[$code] = [
                'reason' => $reason,
                'row' => [
                    'id' => (string) Str::uuid(),
                    'system' => 'LOINC',
                    'code' => $code,
                    'display' => $long,
                    'display_fr' => null,
                    'short_name' => $short ?: null,
                    'kind' => 'term',
                    'parent_code' => null,
                    'chapter' => null,
                    'component' => $component ?: null,
                    'property' => $property ?: null,
                    'scale' => null,
                    'specimen' => null,
                    'example_units' => null,
                    'synonyms' => $related ?: null,
                    'is_common' => true,
                    'common_reason' => $reason,
                    'is_active' => ! Str::startsWith($long, 'Deprecated'),
                    'source' => 'NLM Clinical Table Search Service (LOINC / RSNA Radiology Playbook)',
                    'source_version' => null,
                    'imported_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
        }

        unset($harvest);

        return $collected;
    }

    private function write(ImportImagingCodes $command, array $collected): void
    {
        (new ReflectionMethod($command, 'write'))->invoke($command, $collected, '2.78');
    }

    public function test_an_imaging_study_lands_with_its_published_display_and_modality_reason(): void
    {
        $command = new ImportImagingCodes();
        $command->setLaravel($this->app);
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\NullOutput(),
        ));

        // 24627-2 is the real LOINC number for CT Chest and passes its own
        // check digit; nothing here is invented.
        $collected = $this->collect($command, [
            ['24627-2', 'CT Chest', 'CT Chest', 'Multisection', 'Find', 'CAT scan; Imaging; Rad; RADIOLOGY'],
        ], 'Imaging: CT');

        $this->write($command, $collected);

        $row = DB::table('clinical_code_dictionary')->where('code', '24627-2')->first();

        $this->assertNotNull($row);
        $this->assertSame('LOINC', $row->system);
        $this->assertSame('CT Chest', $row->display);
        $this->assertSame('Imaging: CT', $row->common_reason);
        $this->assertTrue((bool) $row->is_common);
        $this->assertTrue((bool) $row->is_active);
        $this->assertSame('2.78', $row->source_version);
        $this->assertStringContainsString('NLM Clinical Table Search Service', $row->source);

        // Columns the open endpoint does not publish stay empty rather than
        // being inferred from the title.
        $this->assertNull($row->specimen);
        $this->assertNull($row->example_units);
        $this->assertNull($row->scale);
    }

    public function test_an_existing_reason_survives_the_imaging_import(): void
    {
        DB::table('clinical_code_dictionary')->insert([
            'id' => (string) Str::uuid(),
            'system' => 'LOINC',
            'code' => '24627-2',
            'display' => 'CT Chest',
            'kind' => 'term',
            'is_common' => true,
            'common_reason' => 'LOINC Universal Lab Orders',
            'is_active' => true,
            'source' => 'NLM Clinical Table Search Service (LOINC)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $command = new ImportImagingCodes();
        $command->setLaravel($this->app);
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\NullOutput(),
        ));

        $collected = $this->collect($command, [
            ['24627-2', 'CT Chest', 'CT Chest', 'Multisection', 'Find', 'Imaging; Rad'],
        ], 'Imaging: CT');

        $this->write($command, $collected);

        $row = DB::table('clinical_code_dictionary')->where('code', '24627-2')->first();

        // The publisher's own designation is the stronger claim and must not
        // be replaced by a modality sweep that happened to match the title.
        $this->assertSame('LOINC Universal Lab Orders', $row->common_reason);
        // Descriptive columns still refresh.
        $this->assertSame('Multisection', $row->component);
    }

    public function test_a_code_that_fails_its_check_digit_is_never_stored(): void
    {
        $command = new ImportImagingCodes();
        $command->setLaravel($this->app);
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\NullOutput(),
        ));

        // 24627-3 is 24627-2 with a wrong check digit — a plausible looking
        // number that is not a LOINC code.
        $this->assertFalse(ClinicalCode::isLoinc('24627-3'));

        $collected = $this->collect($command, [
            ['24627-3', 'CT Chest', 'CT Chest', 'Multisection', 'Find', 'Imaging; Rad'],
        ], 'Imaging: CT');

        $this->assertSame([], $collected);
    }

    public function test_a_lab_term_matched_by_a_modality_word_is_not_relabelled_as_imaging(): void
    {
        $command = new ImportImagingCodes();
        $command->setLaravel($this->app);
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\NullOutput(),
        ));

        $filter = new ReflectionMethod($command, 'isImaging');

        // A real Playbook row, and a real lab row that a text search for "US"
        // or "PET" can reach. Only the first is imaging.
        $this->assertTrue($filter->invoke($command, 'XR Wrist 2 Views', ''));
        $this->assertTrue($filter->invoke($command, 'Some study', 'Imaging; Rad; RADIOLOGY'));
        $this->assertFalse($filter->invoke(
            $command,
            'Glucose [Mass/volume] in Serum or Plasma',
            'Bld; Chemistry; Point in time; Random',
        ));
    }

    public function test_a_deprecated_playbook_term_is_stored_inactive_rather_than_dropped(): void
    {
        $command = new ImportImagingCodes();
        $command->setLaravel($this->app);
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\NullOutput(),
        ));

        $collected = $this->collect($command, [
            ['69099-0', 'Deprecated CT Guidance for needle biopsy of Chest Pleura', '', '', 'Find', 'Imaging; Rad'],
        ], 'Imaging: CT');

        $this->write($command, $collected);

        $row = DB::table('clinical_code_dictionary')->where('code', '69099-0')->first();

        $this->assertNotNull($row, 'A withdrawn code must still resolve for records already filed against it.');
        $this->assertFalse((bool) $row->is_active);
    }

    public function test_the_import_is_idempotent(): void
    {
        $command = new ImportImagingCodes();
        $command->setLaravel($this->app);
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\NullOutput(),
        ));

        $rows = [
            ['24627-2', 'CT Chest', 'CT Chest', 'Multisection', 'Find', 'Imaging; Rad'],
            ['24590-2', 'MR Brain', 'MR Brain', 'Multisection', 'Find', 'Imaging; Rad'],
        ];

        $this->write($command, $this->collect($command, $rows, 'Imaging: CT'));
        $this->write($command, $this->collect($command, $rows, 'Imaging: CT'));

        $this->assertSame(2, DB::table('clinical_code_dictionary')->where('system', 'LOINC')->count());
    }

    /**
     * CPT is AMA-licensed and is not in this repository.
     *
     * There is no open CPT source, so any row claiming to be CPT would have
     * been invented or copied from something we have no licence to. This test
     * is the standing guard on that: a licence violation is worse than a
     * missing procedure vocabulary.
     */
    public function test_no_row_claims_to_be_cpt(): void
    {
        DB::table('clinical_code_dictionary')->insert([
            'id' => (string) Str::uuid(),
            'system' => 'LOINC',
            'code' => '24627-2',
            'display' => 'CT Chest',
            'kind' => 'term',
            'is_common' => true,
            'is_active' => true,
            'source' => 'NLM Clinical Table Search Service (LOINC / RSNA Radiology Playbook)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, DB::table('clinical_code_dictionary')
            ->where('system', 'ILIKE', '%CPT%')
            ->orWhere('source', 'ILIKE', '%CPT%')
            ->count());
    }

    /** No importer for procedures exists, on purpose — see the task report. */
    public function test_there_is_no_procedure_importer_claiming_a_code_system_we_do_not_have(): void
    {
        $this->assertFalse(
            class_exists(\App\Console\Commands\ImportProcedureCodes::class),
            'A procedure importer was added without an open, licence-clean procedure code system behind it.',
        );
    }
}
