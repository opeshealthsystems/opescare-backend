<?php
namespace Tests\Feature;

use App\Models\CareTeamMember;
use App\Models\Facility;
use App\Models\HandoffNote;
use App\Models\Patient;
use App\Models\User;
use App\Services\Clinical\CareTeamService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CareTeamTest extends TestCase
{
    use RefreshDatabase;

    private CareTeamService $service;
    private Patient $patient;
    private Facility $facility;
    private User $attending;
    private User $nurse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service   = app(CareTeamService::class);
        $this->patient   = Patient::factory()->create();
        $this->facility  = Facility::factory()->create();
        $this->attending = User::factory()->create();
        $this->nurse     = User::factory()->create();
    }

    public function test_can_add_care_team_member(): void
    {
        $member = $this->service->addMember(
            $this->patient->id,
            $this->attending->id,
            'attending',
            null
        );

        $this->assertInstanceOf(CareTeamMember::class, $member);
        $this->assertEquals('attending', $member->role);
        $this->assertTrue($member->is_primary);
        $this->assertDatabaseHas('care_team_members', ['id' => $member->id]);
    }

    public function test_only_first_attending_is_primary(): void
    {
        $first  = $this->service->addMember($this->patient->id, $this->attending->id, 'attending');
        $second = $this->service->addMember($this->patient->id, User::factory()->create()->id, 'attending');

        $this->assertTrue($first->is_primary);
        $this->assertFalse($second->is_primary);
    }

    public function test_can_remove_care_team_member(): void
    {
        $member = $this->service->addMember($this->patient->id, $this->nurse->id, 'nursing');
        $this->service->removeMember($member->id);

        $this->assertNotNull($member->fresh()->left_at);
    }

    public function test_get_care_team_returns_active_members(): void
    {
        $this->service->addMember($this->patient->id, $this->attending->id, 'attending');
        $this->service->addMember($this->patient->id, $this->nurse->id, 'nursing');

        $third = $this->service->addMember($this->patient->id, User::factory()->create()->id, 'pharmacy');
        $this->service->removeMember($third->id);

        $team = $this->service->getCareTeam($this->patient->id);

        $this->assertCount(2, $team);
    }

    public function test_can_create_handoff_note(): void
    {
        $fromProvider = $this->attending;
        $toProvider   = User::factory()->create();
        $visitId      = Str::uuid()->toString();

        $handoff = $this->service->createHandoff([
            'visit_id'          => $visitId,
            'from_provider_id'  => $fromProvider->id,
            'to_provider_id'    => $toProvider->id,
            'facility_id'       => $this->facility->id,
            'summary'           => 'Patient stable post-op. Monitor vitals q4h.',
            'active_problems'   => ['Post-op pain', 'Hypertension'],
            'pending_orders'    => ['CBC tomorrow morning', 'Wound dressing change'],
            'patient_status'    => 'stable',
            'flag_for_follow_up'=> false,
            'handed_off_at'     => now()->toDateTimeString(),
        ]);

        $this->assertInstanceOf(HandoffNote::class, $handoff);
        $this->assertEquals('stable', $handoff->patient_status);
        $this->assertIsArray($handoff->active_problems);
        $this->assertDatabaseHas('handoff_notes', ['id' => $handoff->id]);
    }

    public function test_get_handoffs_for_provider_since_date(): void
    {
        $toProvider = User::factory()->create();
        $visitId    = Str::uuid()->toString();

        $this->service->createHandoff([
            'visit_id'          => $visitId,
            'from_provider_id'  => $this->attending->id,
            'to_provider_id'    => $toProvider->id,
            'facility_id'       => $this->facility->id,
            'summary'           => 'Handoff note.',
            'active_problems'   => [],
            'pending_orders'    => [],
            'patient_status'    => 'stable',
            'flag_for_follow_up'=> false,
            'handed_off_at'     => now()->toDateTimeString(),
        ]);

        $handoffs = $this->service->getHandoffsForProvider($toProvider->id, Carbon::yesterday());

        $this->assertCount(1, $handoffs);
        $this->assertEquals($toProvider->id, $handoffs->first()->to_provider_id);
    }
}
