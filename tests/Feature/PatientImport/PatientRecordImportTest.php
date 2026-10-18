<?php

namespace Tests\Feature\PatientImport;

use App\Models\Patient;
use App\Models\PatientImportedRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Mobile\FamilyTestCase;

/**
 * A patient importing records from another provider: stored against the
 * authenticated patient only, patient-provided and unverified, deduped,
 * summarised, and never re-owned by identifiers inside the file.
 */
class PatientRecordImportTest extends FamilyTestCase
{
    private Patient $me;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('filesystems.private_disk', 'local'));
        $this->me      = $this->makePatient(['health_id' => 'CM-HID-IMPT-0001-AAAA']);
        $this->headers = $this->headersFor($this->me);
    }

    private function bundle(array $resources, array $extra = []): UploadedFile
    {
        $json = json_encode(array_merge([
            'resourceType' => 'Bundle',
            'type'         => 'collection',
            'meta'         => ['source' => 'https://emr.hopital-general.cm'],
            'entry'        => array_map(fn ($r) => ['resource' => $r], $resources),
        ], $extra));

        return UploadedFile::fake()->createWithContent('records.json', $json);
    }

    private function sample(): array
    {
        return [
            ['resourceType' => 'Organization', 'name' => 'Hopital General de Douala'],
            ['resourceType' => 'Patient', 'identifier' => [['system' => 'urn:mrn:hgd', 'value' => 'MRN-77']]],
            ['resourceType' => 'Observation', 'id' => 'obs-1', 'status' => 'final',
                'code' => ['coding' => [['system' => 'http://loinc.org', 'code' => '718-7', 'display' => 'Hemoglobin']]],
                'valueQuantity' => ['value' => 12.5, 'unit' => 'g/dL'], 'effectiveDateTime' => '2025-03-01T08:00:00Z'],
            ['resourceType' => 'Condition', 'id' => 'c-1', 'code' => ['text' => 'Malaria'], 'onsetDateTime' => '2024-11-02'],
            ['resourceType' => 'AllergyIntolerance', 'id' => 'a-1', 'code' => ['text' => 'Penicillin']],
            ['resourceType' => 'MedicationStatement', 'id' => 'm-1', 'status' => 'active', 'medicationCodeableConcept' => ['text' => 'Metformin 500mg']],
            ['resourceType' => 'Immunization', 'id' => 'i-1', 'status' => 'completed', 'vaccineCode' => ['text' => 'BCG'], 'occurrenceDateTime' => '1990-01-01'],
            ['resourceType' => 'DiagnosticReport', 'id' => 'd-1', 'status' => 'final', 'code' => ['text' => 'CBC'], 'conclusion' => 'Normal'],
            ['resourceType' => 'DocumentReference', 'id' => 'dr-1', 'status' => 'current', 'description' => 'Discharge summary',
                'content' => [['attachment' => ['contentType' => 'application/pdf', 'data' => base64_encode('xxx')]]]],
            ['resourceType' => 'Claim', 'id' => 'cl-1'],
            ['resourceType' => 'Observation', 'id' => 'obs-err', 'status' => 'entered-in-error', 'code' => ['text' => 'X']],
        ];
    }

    public function test_bundle_imports_allowed_types_for_the_authenticated_patient_as_unverified(): void
    {
        $res = $this->as($this->headers)
            ->post('/api/mobile/records/import/fhir', ['file' => $this->bundle($this->sample())], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('summary.totals.imported', 7)
            ->assertJsonPath('summary.totals.skipped', 4)
            ->assertJsonPath('summary.verified', false)
            ->assertJsonPath('summary.provenance', 'patient_import')
            ->assertJsonPath('summary.by_type.Observation.imported', 1)
            ->assertJsonPath('summary.by_type.Observation.skipped', 1)
            ->assertJsonPath('summary.by_type.Claim.skipped', 1);

        $reasons = collect($res->json('summary.skipped'))->pluck('reason')->all();
        $this->assertContains('unsupported_type', $reasons);
        $this->assertContains('entered_in_error', $reasons);
        $this->assertContains('context_resource_ignored', $reasons);

        $this->assertSame(7, PatientImportedRecord::where('patient_id', $this->me->id)->count());
        $obs = PatientImportedRecord::where('external_id', 'obs-1')->first();
        $this->assertSame('patient_import', $obs->provenance);
        $this->assertSame('unverified', $obs->verification_status);
        $this->assertSame('https://emr.hopital-general.cm', $obs->original_source_system);
        $this->assertSame('Hopital General de Douala', $obs->original_source_name);
        $this->assertSame('12.5 g/dL', $obs->value_text);

        // Inline attachment bytes are not kept.
        $doc = PatientImportedRecord::where('external_id', 'dr-1')->first();
        $this->assertArrayNotHasKey('data', $doc->payload['content'][0]['attachment']);
    }

    public function test_reimport_is_deduplicated(): void
    {
        $this->as($this->headers)->post('/api/mobile/records/import/fhir', ['file' => $this->bundle($this->sample())], ['Accept' => 'application/json'])->assertCreated();

        $res = $this->as($this->headers)
            ->post('/api/mobile/records/import/fhir', ['file' => $this->bundle($this->sample())], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('summary.totals.imported', 0);

        $this->assertSame(7, collect($res->json('summary.skipped'))->where('reason', 'duplicate')->sum('count'));
        $this->assertSame(7, PatientImportedRecord::where('patient_id', $this->me->id)->count());
    }

    public function test_a_bundle_naming_another_health_id_is_refused_and_nothing_is_written(): void
    {
        $other = $this->makePatient(['health_id' => 'CM-HID-OTHR-0002-BBBB']);
        $resources = [
            ['resourceType' => 'Patient', 'identifier' => [['system' => 'https://opescare.com/fhir/health-id', 'value' => 'CM-HID-OTHR-0002-BBBB']]],
            ['resourceType' => 'Condition', 'code' => ['text' => 'Asthma']],
        ];

        $this->as($this->headers)
            ->post('/api/mobile/records/import/fhir', ['file' => $this->bundle($resources)], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'BUNDLE_FOR_ANOTHER_PATIENT');

        $this->assertSame(0, PatientImportedRecord::count());
        $this->assertSame(0, PatientImportedRecord::where('patient_id', $other->id)->count());
    }

    public function test_own_health_id_in_the_bundle_is_accepted(): void
    {
        $resources = [
            ['resourceType' => 'Condition', 'code' => ['text' => 'Asthma'],
                'subject' => ['identifier' => ['system' => 'https://opescare.com/fhir/health-id', 'value' => 'CM-HID-IMPT-0001-AAAA']]],
        ];

        $this->as($this->headers)
            ->post('/api/mobile/records/import/fhir', ['file' => $this->bundle($resources)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('summary.totals.imported', 1);
    }

    public function test_non_bundle_and_bad_json_are_refused(): void
    {
        $this->as($this->headers)
            ->post('/api/mobile/records/import/fhir', ['file' => UploadedFile::fake()->createWithContent('x.json', '{"resourceType":"Observation"}')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('error_code', 'NOT_A_BUNDLE');

        $this->as($this->headers)
            ->post('/api/mobile/records/import/fhir', ['file' => UploadedFile::fake()->createWithContent('x.json', 'not json {')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('error_code', 'INVALID_JSON');
    }

    public function test_requires_authentication(): void
    {
        $this->flushHeaders()
            ->post('/api/mobile/records/import/fhir', ['file' => $this->bundle($this->sample())], ['Accept' => 'application/json'])
            ->assertStatus(401);
    }

    public function test_pdf_document_is_stored_privately_deduped_and_served_only_signed(): void
    {
        $pdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF";
        $file = fn () => UploadedFile::fake()->createWithContent('lab.pdf', $pdf);

        $res = $this->as($this->headers)
            ->post('/api/mobile/records/import/document', ['file' => $file(), 'title' => 'Lab results 2024'], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('summary.totals.imported', 1)
            ->assertJsonPath('record.verified', false)
            ->assertJsonPath('record.provenance', 'patient_import')
            ->assertJsonPath('record.title', 'Lab results 2024');

        $record = PatientImportedRecord::first();
        Storage::disk(config('filesystems.private_disk', 'local'))->assertExists($record->file_path);
        $this->assertStringStartsWith('patient-imports/' . $this->me->id . '/', $record->file_path);

        // Signed URL works; unsigned does not.
        $this->get($res->json('record.file_url'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/api/mobile/records/imported/' . $record->id . '/file')->assertStatus(403);

        $this->as($this->headers)
            ->post('/api/mobile/records/import/document', ['file' => $file()], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('summary.totals.imported', 0)
            ->assertJsonPath('summary.skipped.0.reason', 'duplicate');
        $this->assertSame(1, PatientImportedRecord::count());
    }

    public function test_document_with_a_disallowed_type_or_too_large_is_refused(): void
    {
        $this->as($this->headers)
            ->post('/api/mobile/records/import/document', ['file' => UploadedFile::fake()->createWithContent('evil.pdf', '<html><script>x</script></html>')], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->as($this->headers)
            ->post('/api/mobile/records/import/document', ['file' => UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertSame(0, PatientImportedRecord::count());
    }

    public function test_list_and_delete_are_scoped_to_the_owner(): void
    {
        $this->as($this->headers)->post('/api/mobile/records/import/fhir', ['file' => $this->bundle($this->sample())], ['Accept' => 'application/json'])->assertCreated();

        $list = $this->as($this->headers)->getJson('/api/mobile/records/imported')->assertOk()->assertJsonCount(7, 'records');
        $this->assertTrue(collect($list->json('records'))->every(fn ($r) => $r['verified'] === false && $r['verification_status'] === 'unverified'));

        $other   = $this->makePatient(['health_id' => 'CM-HID-OTHR-0003-CCCC']);
        $otherH  = $this->headersFor($other);
        $id      = $list->json('records.0.id');

        $this->as($otherH)->getJson('/api/mobile/records/imported')->assertOk()->assertJsonCount(0, 'records');
        $this->as($otherH)->deleteJson('/api/mobile/records/imported/' . $id)->assertNotFound();
        $this->as($this->headers)->deleteJson('/api/mobile/records/imported/' . $id)->assertOk();
        $this->assertSame(6, PatientImportedRecord::count());
    }

    public function test_the_model_cannot_be_promoted_to_verified(): void
    {
        $r = PatientImportedRecord::create([
            'patient_id' => $this->me->id, 'import_batch_id' => (string) \Illuminate\Support\Str::uuid(),
            'resource_type' => 'Condition', 'dedupe_hash' => str_repeat('a', 64),
            'provenance' => 'clinician', 'verification_status' => 'verified',
        ]);
        $this->assertSame('patient_import', $r->fresh()->provenance);
        $this->assertSame('unverified', $r->fresh()->verification_status);
    }

    public function test_nothing_is_written_to_stock_tables(): void
    {
        $tables = array_filter(['pharmacy_stock', 'medicine_stocks', 'blood_inventories', 'stock_levels'], fn ($t) => Schema::hasTable($t));
        $before = collect($tables)->mapWithKeys(fn ($t) => [$t => \DB::table($t)->count()]);

        $this->as($this->headers)->post('/api/mobile/records/import/fhir', ['file' => $this->bundle($this->sample())], ['Accept' => 'application/json'])->assertCreated();

        foreach ($tables as $t) {
            $this->assertSame($before[$t], \DB::table($t)->count());
        }
    }
}
