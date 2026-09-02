<?php
namespace Tests\Feature\Staff;

use App\Models\CareTeamMember;
use App\Models\Facility;
use App\Models\HandoffNote;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandoffNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_care_team_member(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $member = CareTeamMember::create([
            'patient_id'  => $patient->id,
            'provider_id' => $provider->id,
            'facility_id' => $facility->id,
            'role'        => 'primary_physician',
            'is_active'   => true,
        ]);

        $this->assertEquals('primary_physician', $member->role);
        $this->assertTrue($member->is_active);
    }

    public function test_can_create_handoff_note(): void
    {
        $patient  = Patient::factory()->create();
        $fromProv = User::factory()->create();
        $toProv   = User::factory()->create();
        $facility = Facility::factory()->create();

        $note = HandoffNote::create([
            'patient_id'      => $patient->id,
            'visit_id'        => \Illuminate\Support\Str::uuid(),
            'from_provider'   => $fromProv->id,
            'to_provider'     => $toProv->id,
            'from_provider_id'=> $fromProv->id,
            'to_provider_id'  => $toProv->id,
            'facility_id'     => $facility->id,
            'summary'         => 'Patient stable.',
            'content'         => 'Patient stable. Continue current medications. Follow up on K+ levels.',
            'priority'        => 'routine',
            'acknowledged'    => false,
            'patient_status'  => 'stable',
            'handed_off_at'   => now(),
        ]);

        $this->assertEquals('routine', $note->priority);
        $this->assertFalse($note->acknowledged);
    }

    public function test_handoff_note_can_be_acknowledged(): void
    {
        $patient  = Patient::factory()->create();
        $fromProv = User::factory()->create();
        $toProv   = User::factory()->create();
        $facility = Facility::factory()->create();

        $note = HandoffNote::create([
            'visit_id'        => \Illuminate\Support\Str::uuid(),
            'patient_id'      => $patient->id,
            'from_provider'   => $fromProv->id,
            'to_provider'     => $toProv->id,
            'from_provider_id'=> $fromProv->id,
            'to_provider_id'  => $toProv->id,
            'facility_id'     => $facility->id,
            'summary'         => 'Urgent: patient spiked fever.',
            'content'         => 'Urgent: patient spiked fever. Watch for sepsis signs.',
            'priority'        => 'urgent',
            'acknowledged'    => false,
            'patient_status'  => 'unstable',
            'handed_off_at'   => now(),
        ]);

        $note->update(['acknowledged' => true, 'acknowledged_at' => now()]);
        $this->assertTrue($note->fresh()->acknowledged);
    }
}
