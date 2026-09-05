<?php

namespace Tests\Feature\Mpi;

use App\Models\MpiCandidate;
use App\Models\Patient;
use App\Models\PatientIdentifier;
use App\Models\PatientMerge;
use App\Models\PatientMergeAlias;
use App\Models\User;
use App\Modules\MasterPatientIndex\Exceptions\IdentifierHeldByAnotherPatient;
use App\Modules\MasterPatientIndex\Services\MasterPatientIndexService;
use App\Modules\MasterPatientIndex\Services\PatientMergeService;
use App\Modules\PatientIdentity\Exceptions\DuplicateIdentityFound;
use App\Modules\PatientIdentity\Services\PatientIdentityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The second pass: every gap the first audit of the MPI found, pinned.
 */
class MpiHardeningTest extends MpiTestCase
{
    private MasterPatientIndexService $mpi;
    private PatientMergeService $merges;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mpi    = app(MasterPatientIndexService::class);
        $this->merges = app(PatientMergeService::class);
    }

    // ─── Regression: a purge must not be blocked by merge history ───────────

    public function test_a_merged_party_can_still_be_purged_and_the_record_keeps_both_health_ids(): void
    {
        $survivor = $this->patient();
        $retired  = $this->patient();
        $survivorHid = $survivor->health_id;
        $retiredHid  = $retired->health_id;

        $merge = $this->merges->merge($survivor, $retired, null, 'same person');

        // Account deletion's purge step is a plain delete of the patient row.
        // With RESTRICT this threw; the nightly job would have failed forever.
        Patient::withoutGlobalScope('isolate_demo')->whereKey($retired->id)->first()->delete();

        $merge->refresh();
        $this->assertNull($merge->retired_patient_id, 'key set to NULL by the purge');
        $this->assertSame($retiredHid, $merge->retired_health_id, 'but the audit trail still says who');
        $this->assertSame($survivorHid, $merge->survivor_health_id);

        // …and an unmerge is refused rather than restoring into nothing.
        try {
            $this->merges->unmerge($merge, null, 'try');
            $this->fail('expected MPI_UNMERGE_PARTY_PURGED');
        } catch (\RuntimeException $e) {
            $this->assertStringStartsWith('MPI_UNMERGE_PARTY_PURGED', $e->getMessage());
        }
    }

    // ─── Regression: one resolver, no stale redirect after unmerge ──────────

    public function test_unmerge_removes_the_merge_history_alias_and_the_model_resolver_agrees(): void
    {
        $survivor = $this->patient();
        $retired  = $this->patient();
        $retiredHid = $retired->health_id;

        $merge = $this->merges->merge($survivor, $retired, null);

        $this->assertSame((string) $survivor->id, (string) PatientMergeAlias::resolveHealthId($retiredHid)?->id);
        $this->assertDatabaseHas('patient_merge_aliases', ['alias_health_id' => $retiredHid, 'merge_direction' => 'forward']);

        $this->merges->unmerge($merge, null, 'wrong');

        $this->assertDatabaseMissing('patient_merge_aliases', ['alias_health_id' => $retiredHid]);
        $this->assertSame((string) $retired->id, (string) PatientMergeAlias::resolveHealthId($retiredHid)?->id, 'the retired ID is its own record again — via the ONE resolver');
    }

    // ─── Unmerge order ──────────────────────────────────────────────────────

    public function test_unmerge_is_last_in_first_out(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        $c = $this->patient();

        $first  = $this->merges->merge($b, $a, null);   // a → b
        $second = $this->merges->merge($c, $b, null);   // b → c  (a's rows moved again)

        try {
            $this->merges->unmerge($first, null, 'out of order');
            $this->fail('expected MPI_UNMERGE_OUT_OF_ORDER');
        } catch (\RuntimeException $e) {
            $this->assertStringStartsWith('MPI_UNMERGE_OUT_OF_ORDER', $e->getMessage());
        }

        // Undo the later one, then the earlier one is allowed.
        $this->merges->unmerge($second, null, 'first things first');
        $this->merges->unmerge($first->fresh(), null, 'now this one');

        foreach ([$a, $b, $c] as $p) {
            $this->assertFalse($this->reload($p)->isMerged());
        }
    }

    // ─── Stale candidates are superseded on merge ───────────────────────────

    public function test_other_pending_pairs_naming_the_retired_patient_are_superseded(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        $c = $this->patient();
        $this->mpi->detectDuplicates();               // (a,b) (a,c) (b,c)
        $this->assertSame(3, MpiCandidate::count());

        $ab = MpiCandidate::where(fn ($q) => $q->where('source_patient_id', $a->id)->orWhere('target_patient_id', $a->id))
            ->where(fn ($q) => $q->where('source_patient_id', $b->id)->orWhere('target_patient_id', $b->id))->firstOrFail();

        $this->mpi->confirmMatch($ab, null, (string) $b->id, 'same person');

        $statuses = MpiCandidate::all()->mapWithKeys(fn ($x) => [$x->source_patient_id . '|' . $x->target_patient_id => $x->status]);
        $this->assertSame(1, $statuses->filter(fn ($s) => $s === 'merged')->count());
        $this->assertSame(1, $statuses->filter(fn ($s) => $s === 'superseded')->count(), '(a,c) is no longer a question about a');
        $this->assertSame(1, $statuses->filter(fn ($s) => $s === 'pending_review')->count(), '(b,c) is still open');

        // The nightly scan sees b changed and re-pairs it — no duplicate row, no crash.
        $report = $this->mpi->detectDuplicates(since: now()->subMinute());
        $this->assertSame(0, $report['created']);
        $this->assertSame(3, MpiCandidate::count());
    }

    // ─── Family self-links ──────────────────────────────────────────────────

    public function test_a_family_link_that_becomes_self_referential_is_removed_and_restored_on_unmerge(): void
    {
        if (! Schema::hasTable('family_links')) {
            $this->markTestSkipped('no family_links table');
        }

        $survivor = $this->patient();
        $retired  = $this->patient();
        $guardian = User::factory()->create(['patient_id' => $retired->id]);   // the retired record's login…

        $linkId = (string) Str::uuid();
        DB::table('family_links')->insert(array_merge(
            ['id' => $linkId, 'guardian_user_id' => $guardian->id, 'dependent_patient_id' => $survivor->id, 'created_at' => now(), 'updated_at' => now()],
            $this->defaultsFor('family_links', ['id', 'guardian_user_id', 'dependent_patient_id']),
        ));                                                                    // …guards the survivor

        $merge = $this->merges->merge($survivor, $retired, null);

        // After the merge the guardian's login belongs to the survivor: a self-link.
        $this->assertDatabaseMissing('family_links', ['id' => $linkId]);
        $entry = collect($merge->manifest)->firstWhere('column', 'self_link');
        $this->assertNotNull($entry);
        $this->assertCount(1, $entry['deleted']);

        $this->merges->unmerge($merge, null, 'undo');
        $this->assertDatabaseHas('family_links', ['id' => $linkId, 'dependent_patient_id' => $survivor->id]);
    }

    // ─── Detection fixes ────────────────────────────────────────────────────

    public function test_accented_and_unaccented_spellings_are_blocked_together(): void
    {
        $this->patient(['last_name' => 'Nshomé']);
        $this->patient(['last_name' => 'Nshome']);

        $report = $this->mpi->detectDuplicates();

        $this->assertSame(1, $report['pairs_scored'], 'the SQL name block folds accents');
        $this->assertSame(1, $report['created']);
    }

    public function test_a_null_issuer_collision_is_caught_not_a_raw_unique_violation(): void
    {
        $a = $this->patient(['first_name' => 'Ada', 'last_name' => 'Ngu']);
        $b = $this->patient(['first_name' => 'Kofi', 'last_name' => 'Mensah']);

        $this->mpi->linkIdentifier($a->id, ['type' => 'nhis_number', 'value' => 'NH-7']);   // issuer NULL

        $this->expectException(IdentifierHeldByAnotherPatient::class);
        $this->mpi->linkIdentifier($b->id, ['type' => 'nhis_number', 'value' => 'NH-7']);
    }

    public function test_a_pending_pair_is_rescored_when_a_record_changes(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        $this->mpi->detectDuplicates();
        $c = MpiCandidate::firstOrFail();
        $this->assertSame(70.0, (float) $c->match_score);     // name + dob + sex

        // Sleep-free: push the candidate's clock back, then give both a phone.
        $c->forceFill(['updated_at' => now()->subHour()])->save();
        foreach ([$a, $b] as $p) {
            $this->reload($p)->forceFill(['phone_number' => '+237600111222'])->save();
        }

        $report = $this->mpi->detectDuplicates();
        $this->assertSame(1, $report['rescored']);
        $this->assertSame(100.0, (float) $c->fresh()->match_score);   // + phone 30
        $this->assertContains('phone_match', $c->fresh()->match_reasons);
    }

    public function test_a_pair_raised_by_hand_lands_on_the_desk_below_threshold(): void
    {
        $a = $this->patient(['first_name' => 'Ada',  'last_name' => 'Ngu',    'sex' => 'female']);
        $b = $this->patient(['first_name' => 'Kofi', 'last_name' => 'Mensah', 'sex' => 'male']);

        $this->assertSame(0, $this->mpi->detectDuplicates()['pairs_scored'], 'nothing blocks them');

        $c = $this->mpi->raiseCandidate($a, $b, null, 'Same person, two cards, seen at the counter.');

        $this->assertSame('pending_review', $c->status);
        $this->assertSame('manual', $c->detected_by);
        $this->assertLessThan(MasterPatientIndexService::MATCH_THRESHOLD, (float) $c->match_score);
        $this->assertContains('sex_differs', $c->match_reasons, 'the chips still say where the machine disagrees');
    }

    public function test_raising_a_merged_pair_is_refused(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        $this->merges->merge($a, $b, null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MPI_PAIR_ALREADY_MERGED');
        $this->mpi->raiseCandidate($a, $this->reload($b), null);
    }

    public function test_ensure_candidate_survives_a_race_on_the_unique_pair(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        [$x, $y] = strcmp((string) $a->id, (string) $b->id) < 0 ? [$a, $b] : [$b, $a];

        // Someone else inserted the pair between "first" and "create".
        MpiCandidate::create(['source_patient_id' => $x->id, 'target_patient_id' => $y->id, 'match_score' => 70, 'status' => 'pending_review']);

        $report = $this->mpi->detectDuplicates();
        $this->assertSame(1, $report['already_known']);
        $this->assertSame(1, MpiCandidate::count());
    }

    // ─── Resolution by UUID, and the creation-time national-ID gate ─────────

    public function test_a_merged_record_looked_up_by_uuid_resolves_to_its_survivor(): void
    {
        $survivor = $this->patient();
        $retired  = $this->patient();
        $this->merges->merge($survivor, $retired, null);

        $this->assertSame((string) $survivor->id, (string) Patient::resolveId((string) $retired->id)?->id);
        $this->assertSame((string) $survivor->id, (string) Patient::resolveId((string) $survivor->id)?->id);
        $this->assertNull(Patient::resolveId('not-a-uuid'));
    }

    public function test_a_national_id_already_on_the_register_is_an_existing_identity(): void
    {
        $this->patient(['first_name' => 'Ada', 'last_name' => 'Ngu', 'national_id_number' => 'cm-0099']);

        $hit = Patient::findExistingIdentity('Someone', 'Else', '1970-01-01', null, ' CM-0099 ');
        $this->assertNotNull($hit, 'normalised, exact, and enough on its own');
        $this->assertNull(Patient::findExistingIdentity('Someone', 'Else', '1970-01-01', null, 'CM-0100'));
    }

    public function test_find_existing_identity_follows_a_merge_to_the_survivor(): void
    {
        $survivor = $this->patient(['phone_number' => null]);
        $retired  = $this->patient(['phone_number' => '+237600111222']);
        $this->merges->merge($survivor, $retired, null);

        // The phone now hashes on the survivor (survivorship), but even a hit
        // on the retired row would resolve forward.
        $hit = Patient::findExistingIdentity(null, null, null, '+237600111222');
        $this->assertSame((string) $survivor->id, (string) $hit?->id);
    }

    // ─── The identity front door ────────────────────────────────────────────

    public function test_patient_identity_service_creates_once_and_refuses_twice_with_the_matches(): void
    {
        $svc = app(PatientIdentityService::class);

        $p = $svc->createPatientCandidate([
            'first_name' => 'Amara', 'last_name' => 'Diallo', 'date_of_birth' => '1985-02-03', 'sex' => 'female',
            'phone_number' => '+237600777888', 'identifiers' => [['type' => 'insurance_number', 'value' => 'INS-1', 'issuer' => 'CNPS']],
        ]);

        $this->assertMatchesRegularExpression('/^CM-HID-/', $p->health_id);
        $this->assertDatabaseHas('patient_identifiers', ['patient_id' => $p->id, 'identifier_value' => 'INS-1']);
        $this->assertDatabaseHas('audit_events', ['patient_id' => $p->id, 'action_type' => 'create', 'source_system' => 'identity']);

        try {
            $svc->createPatientCandidate(['first_name' => 'AMARA', 'last_name' => 'diallo', 'date_of_birth' => 'February 3, 1985']);
            $this->fail('expected DuplicateIdentityFound');
        } catch (DuplicateIdentityFound $e) {
            $this->assertSame((string) $p->id, (string) $e->matches->first()->id);
        }

        $this->assertSame(1, Patient::withoutGlobalScope('isolate_demo')->where('last_name', 'Diallo')->count());
    }

    // ─── helpers ────────────────────────────────────────────────────────────

    /** NOT NULL columns without a default, filled with a value of the right type. */
    private function defaultsFor(string $table, array $except): array
    {
        $cols = DB::select("
            SELECT column_name, data_type
              FROM information_schema.columns
             WHERE table_name = ? AND is_nullable = 'NO' AND column_default IS NULL
               AND column_name NOT IN ('created_at', 'updated_at')", [$table]);

        $out = [];
        foreach ($cols as $c) {
            if (in_array($c->column_name, $except, true)) {
                continue;
            }
            $out[$c->column_name] = match (true) {
                str_contains($c->data_type, 'int')                                   => 0,
                $c->data_type === 'boolean'                                          => false,
                str_contains($c->data_type, 'json')                                  => '{}',
                str_contains($c->data_type, 'timestamp') || $c->data_type === 'date' => now(),
                $c->data_type === 'uuid'                                             => (string) Str::uuid(),
                default                                                              => 'x',
            };
        }

        return $out;
    }
}
