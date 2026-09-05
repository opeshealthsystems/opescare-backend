<?php

namespace Tests\Feature\Mpi;

use App\Models\HealthIdAlias;
use App\Models\MpiCandidate;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\PatientIdentifier;
use App\Models\PatientMerge;
use App\Models\User;
use App\Modules\MasterPatientIndex\Services\MasterPatientIndexService;
use App\Modules\MasterPatientIndex\Services\PatientMergeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The merge: rows move, blanks fill, the retired ID keeps working, and all of
 * it comes back on unmerge.
 */
class PatientMergeTest extends MpiTestCase
{
    private PatientMergeService $merges;
    private MasterPatientIndexService $mpi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merges = app(PatientMergeService::class);
        $this->mpi    = app(MasterPatientIndexService::class);
    }

    /** Give a patient a login, a session token and an identifier — rows in three FK tables. */
    private function furnish(Patient $p, string $email, string $identifierValue): void
    {
        User::factory()->create(['email' => $email, 'patient_id' => $p->id]);

        PatientAccessToken::create([
            'patient_id'   => $p->id,
            'token_hash'   => Hash::make(Str::random(40)),
            'token_prefix' => 'pat_' . Str::random(8),
            'expires_at'   => now()->addDays(30),
        ]);

        PatientIdentifier::create([
            'patient_id'       => $p->id,
            'identifier_type'  => 'facility_mrn',
            'identifier_value' => $identifierValue,
            'issuer'           => 'test',
        ]);
    }

    // ─── The merge itself ───────────────────────────────────────────────────

    public function test_every_referencing_row_moves_to_the_survivor(): void
    {
        $survivor = $this->patient();
        $retired  = $this->patient();
        $this->furnish($survivor, 'a@example.cm', 'MRN-A');
        $this->furnish($retired,  'b@example.cm', 'MRN-B');

        $merge = $this->merges->merge($survivor, $retired, null, 'same person, checked the card');

        // Both logins, both tokens, both identifiers now open the survivor.
        $this->assertSame(2, User::where('patient_id', $survivor->id)->count());
        $this->assertSame(0, User::where('patient_id', $retired->id)->count());
        $this->assertSame(2, PatientAccessToken::where('patient_id', $survivor->id)->count());
        $this->assertSame(2, PatientIdentifier::where('patient_id', $survivor->id)->count());

        $this->assertSame(3, $merge->movedRowCount());
        $this->assertSame(0, $merge->keptRowCount());

        // The manifest names each row it moved.
        $tables = collect($merge->manifest)->pluck('table')->all();
        $this->assertContains('users', $tables);
        $this->assertContains('patient_access_tokens', $tables);
        $this->assertContains('patient_identifiers', $tables);
    }

    public function test_the_foreign_key_discovery_covers_clinical_tables_the_models_guard(): void
    {
        // Prescription refuses patient_id changes through Eloquent. The merge
        // reaches it anyway — by design, with the previous value in the manifest.
        $keys = collect($this->merges->foreignKeysToPatients())->map(fn ($k) => $k['table'] . '.' . $k['column']);

        $this->assertTrue($keys->contains('prescriptions.patient_id'));
        $this->assertTrue($keys->contains('appointments.patient_id'));
        $this->assertGreaterThan(80, $keys->count());

        // …and never the tables that record identity decisions.
        $this->assertFalse($keys->contains('patient_merges.survivor_patient_id'));
        $this->assertFalse($keys->contains('mpi_candidates.source_patient_id'));
        $this->assertFalse($keys->contains('patients.merged_into_patient_id'));
    }

    public function test_the_retired_record_is_retired_not_deleted(): void
    {
        $survivor = $this->patient();
        $retired  = $this->patient();

        $this->merges->merge($survivor, $retired, null);

        $r = $this->reload($retired);
        $this->assertTrue($r->isMerged());
        $this->assertSame((string) $survivor->id, (string) $r->merged_into_patient_id);
        $this->assertNotNull($r->merged_at);
        $this->assertDatabaseHas('patients', ['id' => $retired->id]);
    }

    public function test_the_retired_health_id_still_opens_the_survivor(): void
    {
        $survivor = $this->patient();
        $retired  = $this->patient();
        $retiredId = $retired->health_id;

        $this->merges->merge($survivor, $retired, null);

        $this->assertDatabaseHas('health_id_aliases', ['patient_id' => $survivor->id, 'alias_value' => $retiredId, 'status' => 'active']);
        $this->assertDatabaseHas('patient_merge_aliases', ['alias_health_id' => $retiredId, 'canonical_patient_id' => $survivor->id]);

        $resolved = Patient::resolveHealthId($retiredId);
        $this->assertNotNull($resolved);
        $this->assertSame((string) $survivor->id, (string) $resolved->id);

        // Lower-case and padded input is the same identifier.
        $this->assertSame((string) $survivor->id, (string) Patient::resolveHealthId('  ' . strtolower($retiredId) . ' ')->id);

        // And the survivor's own ID, of course.
        $this->assertSame((string) $survivor->id, (string) Patient::resolveHealthId($survivor->health_id)->id);
    }

    public function test_survivorship_fills_blanks_and_never_overwrites(): void
    {
        $survivor = $this->patient(['email' => null, 'middle_name' => null, 'blood_group' => 'O+']);
        $retired  = $this->patient(['email' => 'kept@example.cm', 'middle_name' => 'Tabi', 'blood_group' => 'A-']);

        $merge = $this->merges->merge($survivor, $retired, null);

        $s = $this->reload($survivor);
        $this->assertSame('kept@example.cm', $s->email, 'NULL email filled from the retired record');
        $this->assertSame('Tabi', $s->middle_name);
        $this->assertSame('O+', $s->blood_group, 'a value the survivor already had is untouched');

        $this->assertEqualsCanonicalizing(['email', 'middle_name'], array_keys($merge->survivorship));
    }

    public function test_a_one_per_patient_row_the_survivor_already_has_is_kept_not_clobbered(): void
    {
        $survivor = $this->patient();
        $retired  = $this->patient();

        // mobile_app_settings is UNIQUE(patient_id).
        foreach ([$survivor, $retired] as $p) {
            DB::table('mobile_app_settings')->insert(array_merge(
                ['id' => (string) Str::uuid(), 'patient_id' => $p->id, 'created_at' => now(), 'updated_at' => now()],
                $this->defaultsFor('mobile_app_settings'),
            ));
        }

        $merge = $this->merges->merge($survivor, $retired, null);

        $entry = collect($merge->manifest)->firstWhere('table', 'mobile_app_settings');
        $this->assertNotNull($entry);
        $this->assertCount(0, $entry['moved']);
        $this->assertCount(1, $entry['kept']);

        // Survivor still has exactly its own; the retired row is still there.
        $this->assertSame(1, DB::table('mobile_app_settings')->where('patient_id', $survivor->id)->count());
        $this->assertSame(1, DB::table('mobile_app_settings')->where('patient_id', $retired->id)->count());
    }

    public function test_a_merge_writes_an_audit_event(): void
    {
        $survivor = $this->patient();
        $retired  = $this->patient();

        $this->merges->merge($survivor, $retired, null, 'checked in person');

        $this->assertDatabaseHas('audit_events', [
            'action_type'   => 'health_id_merged',
            'resource_type' => 'patient',
            'patient_id'    => $survivor->id,
            'source_system' => 'mpi',
        ]);
    }

    // ─── Guards ─────────────────────────────────────────────────────────────

    public function test_a_record_cannot_be_merged_with_itself_or_twice(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        $c = $this->patient();

        try {
            $this->merges->merge($a, $a, null);
            $this->fail('same-patient merge should refuse');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith('MPI_MERGE_SAME_PATIENT', $e->getMessage());
        }

        $this->merges->merge($a, $b, null);

        try {
            $this->merges->merge($c, $b, null);   // b is already retired
            $this->fail('retired patient cannot be merged again');
        } catch (\RuntimeException $e) {
            $this->assertStringStartsWith('MPI_PATIENT_ALREADY_MERGED', $e->getMessage());
        }

        try {
            $this->merges->merge($b, $c, null);   // b cannot be a survivor either
            $this->fail('retired patient cannot survive a merge');
        } catch (\RuntimeException $e) {
            $this->assertStringStartsWith('MPI_SURVIVOR_ALREADY_MERGED', $e->getMessage());
        }
    }

    public function test_confirming_a_candidate_requires_a_survivor_from_the_pair(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        $other = $this->patient(['first_name' => 'Someone']);
        $this->mpi->detectDuplicates();
        $c = MpiCandidate::firstOrFail();

        $this->expectException(\InvalidArgumentException::class);
        $this->mpi->confirmMatch($c, (string) Str::uuid(), (string) $other->id, 'wrong');
    }

    public function test_confirming_a_candidate_merges_and_records_the_decision(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        $this->mpi->detectDuplicates();
        $c = MpiCandidate::firstOrFail();
        $admin = $this->platformAdmin();

        $merge = $this->mpi->confirmMatch($c, $admin->id, (string) $a->id, 'Both cards in hand; same person.');

        $c->refresh();
        $this->assertSame('merged', $c->status);
        $this->assertSame((string) $a->id, (string) $c->survivor_patient_id);
        $this->assertSame((string) $admin->id, (string) $c->reviewed_by);
        $this->assertSame('Both cards in hand; same person.', $c->review_reason);
        $this->assertSame((string) $c->id, (string) $merge->candidate_id);
        $this->assertTrue($this->reload($b)->isMerged());
    }

    // ─── Unmerge ────────────────────────────────────────────────────────────

    public function test_unmerge_puts_back_exactly_what_the_merge_moved(): void
    {
        $survivor = $this->patient(['email' => null]);
        $retired  = $this->patient(['email' => 'r@example.cm']);
        $this->furnish($survivor, 'a@example.cm', 'MRN-A');
        $this->furnish($retired,  'b@example.cm', 'MRN-B');
        $retiredHealthId = $retired->health_id;

        $merge = $this->merges->merge($survivor, $retired, null, 'mistake');

        // Something the survivor acquires AFTER the merge must stay put.
        PatientIdentifier::create(['patient_id' => $survivor->id, 'identifier_type' => 'facility_mrn', 'identifier_value' => 'MRN-NEW', 'issuer' => 'test']);

        $this->merges->unmerge($merge, null, 'the reviewer misread the DOB');

        // Rows home.
        $this->assertSame(1, User::where('patient_id', $retired->id)->count());
        $this->assertSame(1, User::where('patient_id', $survivor->id)->count());
        $this->assertSame(1, PatientAccessToken::where('patient_id', $retired->id)->count());
        $this->assertSame(['MRN-B'], PatientIdentifier::where('patient_id', $retired->id)->pluck('identifier_value')->all());
        $this->assertEqualsCanonicalizing(['MRN-A', 'MRN-NEW'], PatientIdentifier::where('patient_id', $survivor->id)->pluck('identifier_value')->all());

        // Survivorship undone; the retired record is live again.
        $this->assertNull($this->reload($survivor)->email);
        $r = $this->reload($retired);
        $this->assertFalse($r->isMerged());
        $this->assertNull($r->merged_into_patient_id);
        $this->assertSame('provisional', $r->identity_status instanceof \BackedEnum ? $r->identity_status->value : $r->identity_status);

        // The alias no longer resolves to the survivor; the retired ID is its own record.
        $this->assertSame('inactive', HealthIdAlias::where('alias_value', $retiredHealthId)->value('status'));
        $this->assertSame((string) $retired->id, (string) Patient::resolveHealthId($retiredHealthId)->id);

        $merge->refresh();
        $this->assertSame(PatientMerge::STATUS_UNMERGED, $merge->status);
        $this->assertNotNull($merge->unmerged_at);

        $this->assertDatabaseHas('audit_events', ['action_type' => 'health_id_unmerged', 'patient_id' => $survivor->id]);
    }

    public function test_unmerge_returns_the_candidate_to_the_queue(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        $this->mpi->detectDuplicates();
        $c = MpiCandidate::firstOrFail();

        $merge = $this->mpi->confirmMatch($c, null, (string) $a->id, 'same person');
        $this->merges->unmerge($merge, null, 'was not the same person');

        $this->assertSame('pending_review', $c->fresh()->status);
        $this->assertNull($c->fresh()->survivor_patient_id);
    }

    public function test_an_unmerge_cannot_run_twice(): void
    {
        $merge = $this->merges->merge($this->patient(), $this->patient(), null);
        $this->merges->unmerge($merge, null, 'once');

        $this->expectException(\RuntimeException::class);
        $this->merges->unmerge($merge->fresh(), null, 'twice');
    }

    // ─── helpers ────────────────────────────────────────────────────────────

    /**
     * NOT NULL columns without a default, filled with a value of the right
     * type — so a fixture row can be inserted into a table whose exact shape
     * this test does not otherwise care about.
     */
    private function defaultsFor(string $table): array
    {
        $cols = DB::select("
            SELECT column_name, data_type
              FROM information_schema.columns
             WHERE table_name = ? AND is_nullable = 'NO' AND column_default IS NULL
               AND column_name NOT IN ('id', 'patient_id', 'created_at', 'updated_at')", [$table]);

        $out = [];
        foreach ($cols as $c) {
            $out[$c->column_name] = match (true) {
                str_contains($c->data_type, 'int')                          => 0,
                $c->data_type === 'boolean'                                 => false,
                str_contains($c->data_type, 'json')                         => '{}',
                str_contains($c->data_type, 'timestamp') || $c->data_type === 'date' => now(),
                $c->data_type === 'uuid'                                    => (string) Str::uuid(),
                default                                                     => 'x',
            };
        }

        return $out;
    }
}
