<?php

namespace Tests\Feature\Mpi;

use App\Models\MpiCandidate;
use App\Models\PatientIdentifier;
use App\Modules\MasterPatientIndex\Exceptions\IdentifierHeldByAnotherPatient;
use App\Modules\MasterPatientIndex\Services\MasterPatientIndexService;
use Illuminate\Support\Facades\Config;

/**
 * Detection: which pairs reach the review desk, and which do not.
 *
 * The old routine could see one facility at a time, inside the demo
 * partition, oldest 500 first, and could not say "these are DIFFERENT people".
 * Each test here pins one of those.
 */
class DuplicateDetectionTest extends MpiTestCase
{
    private MasterPatientIndexService $mpi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mpi = app(MasterPatientIndexService::class);
    }

    // ─── Cross-facility, cross-partition ────────────────────────────────────

    public function test_the_same_person_at_two_facilities_is_found(): void
    {
        $a = $this->patient(['facility_id' => $this->facility('Yaoundé')->id]);
        $b = $this->patient(['facility_id' => $this->facility('Douala')->id]);

        $report = $this->mpi->detectDuplicates();

        $this->assertSame(1, $report['created']);
        $this->assertSame(1, MpiCandidate::count());
    }

    public function test_patients_with_no_facility_at_all_are_scanned(): void
    {
        // Production: 0 of 5 patients carry a facility_id. The old code scanned nothing.
        $this->patient();
        $this->patient();

        $this->assertSame(1, $this->mpi->detectDuplicates()['created']);
    }

    public function test_detection_sees_across_the_demo_partition(): void
    {
        Config::set('demo.enabled', true);
        $this->patient(demo: true);
        $this->patient(demo: false);

        $this->assertSame(1, $this->mpi->detectDuplicates()['created']);
    }

    // ─── Blocking keys ──────────────────────────────────────────────────────

    public function test_a_shared_phone_finds_a_pair_whose_names_differ(): void
    {
        $this->patient(['first_name' => 'Jude',  'last_name' => 'Nshome', 'phone_number' => '+237600111222']);
        $this->patient(['first_name' => 'J.',    'last_name' => 'Nshome-Tabi', 'phone_number' => '+237600111222']);

        $report = $this->mpi->detectDuplicates();
        $this->assertSame(1, $report['pairs_scored'], 'the phone block should pair them');

        // Phone 30 + DOB 20 + sex 10 = 60 → exactly the threshold.
        $this->assertSame(1, $report['created']);
        $this->assertContains('phone_match', MpiCandidate::first()->match_reasons);
    }

    public function test_a_shared_national_id_is_deterministic(): void
    {
        $this->patient(['first_name' => 'Amara', 'last_name' => 'Diallo', 'national_id_number' => 'CM-123456789']);
        $this->patient(['first_name' => 'Amara', 'last_name' => 'Diallo', 'national_id_number' => 'CM-123456789']);

        $this->mpi->detectDuplicates();

        $c = MpiCandidate::firstOrFail();
        $this->assertGreaterThanOrEqual(MasterPatientIndexService::TIER_DETERMINISTIC, (float) $c->match_score);
        $this->assertSame('deterministic', $c->tier());
        $this->assertContains('national_id_match', $c->match_reasons);
    }

    public function test_accents_and_case_do_not_hide_a_name_match_in_scoring(): void
    {
        $a = $this->patient(['first_name' => 'Jude', 'last_name' => 'Nshome']);
        $b = $this->patient(['first_name' => 'JUDE', 'last_name' => 'Nshomé']);

        [$score, $reasons] = $this->mpi->scoreMatch($a, $b);
        $this->assertContains('name_match', $reasons);
    }

    // ─── Negative evidence ──────────────────────────────────────────────────

    public function test_same_name_and_dob_but_different_sex_stays_below_threshold(): void
    {
        $this->patient(['sex' => 'male']);
        $this->patient(['sex' => 'female']);

        $report = $this->mpi->detectDuplicates();

        // name 40 + dob 20 − sex 30 = 30. Two people, not one.
        $this->assertSame(1, $report['pairs_scored']);
        $this->assertSame(0, $report['created']);
        $this->assertSame(1, $report['below_threshold']);
    }

    public function test_different_phones_are_recorded_as_evidence_against(): void
    {
        $a = $this->patient(['phone_number' => '+237600111222']);
        $b = $this->patient(['phone_number' => '+237699999999']);

        [$score, $reasons] = $this->mpi->scoreMatch($a, $b);

        $this->assertContains('phone_differs', $reasons);
        // name 40 + dob 20 + sex 10 − phone 15 = 55: the reviewer never sees it.
        $this->assertLessThan(MasterPatientIndexService::MATCH_THRESHOLD, $score);
    }

    public function test_an_estimated_date_of_birth_is_worth_less(): void
    {
        $a = $this->patient(['is_dob_estimated' => true]);
        $b = $this->patient();

        [$score, $reasons] = $this->mpi->scoreMatch($a, $b);

        $this->assertContains('dob_match_estimated', $reasons);
        $this->assertSame(60.0, $score); // 40 + 10 + 10
    }

    // ─── Idempotence, ordering, exclusions ──────────────────────────────────

    public function test_a_second_run_creates_nothing_new(): void
    {
        $this->patient();
        $this->patient();

        $this->assertSame(1, $this->mpi->detectDuplicates()['created']);

        $second = $this->mpi->detectDuplicates();
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['already_known']);
        $this->assertSame(1, MpiCandidate::count());
    }

    public function test_a_pair_is_stored_once_regardless_of_direction(): void
    {
        $a = $this->patient();
        $b = $this->patient();

        $this->mpi->detectDuplicates();
        $c = MpiCandidate::firstOrFail();

        $this->assertLessThan(0, strcmp((string) $c->source_patient_id, (string) $c->target_patient_id), 'canonical ordering: smaller id first');

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        MpiCandidate::create([
            'source_patient_id' => $c->source_patient_id,
            'target_patient_id' => $c->target_patient_id,
            'match_score'       => 70,
            'status'            => 'pending_review',
        ]);
    }

    public function test_a_merged_record_is_never_a_candidate_again(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        $b->forceFill(['identity_status' => 'merged', 'merged_into_patient_id' => $a->id])->save();

        $this->assertSame(0, $this->mpi->detectDuplicates()['pairs_scored']);
    }

    public function test_since_limits_the_focus_set_but_pairs_against_everyone(): void
    {
        $old = $this->patient();
        $old->forceFill(['created_at' => now()->subDays(30), 'updated_at' => now()->subDays(30)])->save();
        $new = $this->patient();

        $report = $this->mpi->detectDuplicates(since: now()->subDay());

        $this->assertSame(1, $report['scanned'], 'only the new arrival is in focus');
        $this->assertSame(1, $report['created'], 'but it is paired against the old record');
    }

    public function test_dry_run_scores_and_writes_nothing(): void
    {
        $this->patient();
        $this->patient();

        $report = $this->mpi->detectDuplicates(dryRun: true);

        $this->assertSame(1, $report['created']);
        $this->assertSame(0, MpiCandidate::count());
    }

    // ─── Identifier collisions ──────────────────────────────────────────────

    public function test_linking_an_identifier_another_patient_holds_queues_a_deterministic_candidate(): void
    {
        $a = $this->patient(['first_name' => 'Ada', 'last_name' => 'Ngu']);
        $b = $this->patient(['first_name' => 'Kofi', 'last_name' => 'Mensah']);

        $this->mpi->linkIdentifier($a->id, ['type' => 'insurance_number', 'value' => 'INS-42', 'issuer' => 'CNPS']);

        try {
            $this->mpi->linkIdentifier($b->id, ['type' => 'insurance_number', 'value' => 'INS-42', 'issuer' => 'CNPS']);
            $this->fail('expected IdentifierHeldByAnotherPatient');
        } catch (IdentifierHeldByAnotherPatient $e) {
            $this->assertSame('identifier_collision', $e->candidate->detected_by);
            $this->assertSame(100.0, (float) $e->candidate->match_score);
            $this->assertContains('identifier_collision:insurance_number', $e->candidate->match_reasons);
        }

        // The identifier was NOT silently attached to the second patient.
        $this->assertSame(1, PatientIdentifier::where('identifier_value', 'INS-42')->count());
    }

    // ─── Pre-create search ──────────────────────────────────────────────────

    public function test_search_finds_by_phone_alone_and_by_name_and_dob(): void
    {
        $p = $this->patient(['phone_number' => '+237600111222']);

        $this->assertCount(1, $this->mpi->searchCandidates(['phone_number' => '+237600111222']));
        $this->assertCount(1, $this->mpi->searchCandidates(['first_name' => 'jude', 'last_name' => 'NSHOME', 'date_of_birth' => '1990-04-17T00:00:00Z']));
        $this->assertCount(1, $this->mpi->searchCandidates(['first_name' => ' Jude ', 'last_name' => 'Nshome', 'date_of_birth' => '17 April 1990']));
        $this->assertCount(0, $this->mpi->searchCandidates(['first_name' => 'Someone', 'last_name' => 'Else', 'date_of_birth' => '1990-04-17']));
    }

    public function test_the_command_runs_and_reports(): void
    {
        $this->patient();
        $this->patient();

        $this->artisan('mpi:detect', ['--since' => '2 days'])
            ->expectsOutputToContain('1 pair(s) waiting on the review desk.')
            ->assertSuccessful();
    }
}
