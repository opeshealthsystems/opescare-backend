<?php

namespace Tests\Feature;

use App\Models\CodeSystemMapping;
use App\Services\Terminology\TerminologyProposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Proposing a gap-report finding.
 *
 * The proposal step exists so a reviewer can ACT on a finding. The tests that
 * matter are therefore not "does it create a row" but the four refusals and
 * the one absence:
 *
 *  - nothing is ever created approved;
 *  - a code that fails its own check digit is refused;
 *  - a code that is not among the report's own suggestions is refused;
 *  - a second proposal for a value already awaiting a decision is refused;
 *  - and the clinical row the finding came from is byte-for-byte unchanged.
 */
class TerminologyProposalTest extends TestCase
{
    use RefreshDatabase;

    private TerminologyProposer $proposer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proposer = app(TerminologyProposer::class);
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

    private function medicine(string $name, ?string $atc): string
    {
        $id = (string) Str::uuid();

        DB::table('medicines')->insert([
            'id' => $id,
            'name' => $name,
            'generic_name' => $name,
            'atc_code' => $atc,
            'category' => 'other',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * The real production finding, reproduced: a medicine still carrying the
     * ATC code WHO retired, and the code WHO moved it to sitting in the
     * dictionary under a different subgroup of the same branch.
     */
    private function tretinoinGap(): string
    {
        $this->dictionary('ATC', 'L01XF01', 'trétinoïne');
        $this->dictionary('ATC', 'L01XX02', 'asparaginase');

        return $this->medicine('Tretinoin', 'L01XX14');
    }

    private function propose(string $key, string $value, string $code): array
    {
        return $this->proposer->propose($key, $value, $code, null, 'platform_admin', null, '127.0.0.1');
    }

    // ── The happy path is still a proposal, not a fix ────────────────────

    public function test_a_finding_becomes_a_pending_proposal_and_never_an_approved_one(): void
    {
        $this->tretinoinGap();

        $result = $this->propose('medicines.atc_code', 'L01XX14', 'L01XF01');

        $this->assertNull($result['refused']);
        $this->assertNotNull($result['mapping']);

        $mapping = $result['mapping']->fresh();

        // The whole safety property of this feature in one assertion.
        $this->assertSame('pending', $mapping->status);
        $this->assertNull($mapping->approved_by);
        $this->assertNull($mapping->approved_at);

        $this->assertSame('L01XX14', $mapping->local_code);
        $this->assertSame('L01XF01', $mapping->standard_code);
        $this->assertSame('ATC', $mapping->standard_system);
        $this->assertSame('medication', $mapping->resource_type);

        // Machine-suggested, and it says so where an approver will read it.
        $this->assertSame('approximate', $mapping->mapping_confidence);
        $this->assertStringContainsString('gap report', $mapping->notes);
        $this->assertStringContainsString('Machine suggestion', $mapping->notes);

        // Nothing anywhere in this table came out approved.
        $this->assertSame(0, CodeSystemMapping::query()->where('status', 'approved')->count());
    }

    public function test_the_clinical_row_is_untouched_by_a_proposal(): void
    {
        $id = $this->tretinoinGap();

        $before = DB::table('medicines')->where('id', $id)->first();
        $labs = DB::table('lab_results')->count();

        $this->propose('medicines.atc_code', 'L01XX14', 'L01XF01');

        $after = DB::table('medicines')->where('id', $id)->first();

        // Not just the code column: the whole row, every column, unchanged.
        $this->assertEquals((array) $before, (array) $after);
        $this->assertSame('L01XX14', $after->atc_code);
        $this->assertSame($labs, DB::table('lab_results')->count());
    }

    public function test_the_proposal_is_recorded_in_the_audit_trail(): void
    {
        $this->tretinoinGap();

        $result = $this->propose('medicines.atc_code', 'L01XX14', 'L01XF01');

        $event = DB::table('audit_events')
            ->where('resource_id', (string) $result['mapping']->id)
            ->where('action_type', TerminologyProposer::ACTION_PROPOSE)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('code_system_mapping', $event->resource_type);

        // actor_id is a uuid column. A marker string has broken this codebase
        // before, so null must stay a legal actor here.
        $this->assertNull($event->actor_id);

        $after = json_decode($event->after_state, true);
        $this->assertTrue($after['machine_suggested']);
        $this->assertSame('pending', $after['status']);
        $this->assertSame('display', $after['suggestion_basis']);
    }

    // ── Refusals ─────────────────────────────────────────────────────────

    public function test_a_code_failing_its_own_check_digit_is_refused(): void
    {
        // 70563-7 is the real example from ClinicalCode: it looks like a LOINC
        // code, was offered as one, and fails its own mod-10 check digit.
        $this->assertFalse(\App\Support\Terminology\ClinicalCode::isLoinc('70563-7'));

        $this->dictionary('LOINC', '70563-7', 'Malaria rapid test');

        $patient = \App\Models\Patient::factory()->create();
        $order = \App\Models\LabOrder::factory()->create(['patient_id' => $patient->id]);

        DB::table('lab_results')->insert([
            'id' => (string) Str::uuid(),
            // NOT NULL on this table, and nothing this feature reads.
            'lab_order_id' => $order->id,
            'patient_id' => $patient->id,
            'parameter_name' => 'Malaria RDT',
            'value' => 'Negative',
            'loinc_code' => 'MAL-RDT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->propose('lab_results.loinc_code', 'MAL-RDT', '70563-7');

        $this->assertNotNull($result['refused']);
        $this->assertNull($result['mapping']);
        $this->assertSame(0, CodeSystemMapping::query()->count());
    }

    public function test_a_code_the_report_did_not_suggest_is_refused(): void
    {
        $this->tretinoinGap();

        // A real, assignable ATC code — but not one the report offered for
        // this value. A form field is not evidence.
        $this->dictionary('ATC', 'N02BE01', 'paracetamol');

        $result = $this->propose('medicines.atc_code', 'L01XX14', 'N02BE01');

        $this->assertSame('terminology.propose_refuse_not_suggested', $result['refused']);
        $this->assertSame(0, CodeSystemMapping::query()->count());
    }

    public function test_a_value_that_is_not_a_finding_is_refused(): void
    {
        $this->tretinoinGap();

        $result = $this->propose('medicines.atc_code', 'NOT-ON-ANY-ROW', 'L01XF01');

        $this->assertSame('terminology.propose_refuse_no_finding', $result['refused']);
        $this->assertSame(0, CodeSystemMapping::query()->count());
    }

    public function test_a_duplicate_of_a_pending_proposal_is_refused(): void
    {
        $this->tretinoinGap();

        $first = $this->propose('medicines.atc_code', 'L01XX14', 'L01XF01');
        $this->assertNull($first['refused']);

        $second = $this->propose('medicines.atc_code', 'L01XX14', 'L01XF01');

        $this->assertSame('terminology.propose_refuse_duplicate', $second['refused']);
        $this->assertNull($second['mapping']);
        $this->assertSame(1, CodeSystemMapping::query()->count());
    }

    // ── Findings that have no proposal to make ───────────────────────────

    public function test_unit_and_route_findings_are_not_proposable_and_say_why(): void
    {
        foreach (['lab_results.unit', 'immunization_records.dose_unit'] as $key) {
            $this->assertFalse($this->proposer->isProposable($key));
            $this->assertSame('terminology.propose_why_unit', $this->proposer->notProposableReason($key));
        }

        foreach (['immunization_records.route', 'prescription_items.route'] as $key) {
            $this->assertFalse($this->proposer->isProposable($key));
            $this->assertSame('terminology.propose_why_route', $this->proposer->notProposableReason($key));
        }

        // The reason is a real translated sentence in both languages, not a
        // key leaking onto the screen.
        foreach (['en', 'fr'] as $locale) {
            app()->setLocale($locale);
            $this->assertNotSame('terminology.propose_why_unit', __('terminology.propose_why_unit'));
            $this->assertNotSame('terminology.propose_why_route', __('terminology.propose_why_route'));
        }
    }

    public function test_a_non_proposable_target_is_refused_by_the_service_too(): void
    {
        // Not only hidden on the screen: refused at the service, so a hand-made
        // POST cannot mint a unit-spelling mapping.
        $result = $this->propose('lab_results.unit', '10^9/L', 'x10^9/L');

        $this->assertSame('terminology.propose_why_unit', $result['refused']);
        $this->assertSame(0, CodeSystemMapping::query()->count());
    }
}
