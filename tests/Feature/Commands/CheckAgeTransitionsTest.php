<?php
namespace Tests\Feature\Commands;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckAgeTransitionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sets_grace_period_on_18th_birthday(): void
    {
        Notification::fake();

        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create([
            'is_demo'       => false,
            'date_of_birth' => now()->subYears(18)->toDateString(),
        ]);
        $link = FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'status'              => 'active',
        ]);

        $this->artisan('family:check-age-transitions')->assertExitCode(0);

        $link->refresh();
        $this->assertNotNull($link->age_transition_expires_at);
        $this->assertTrue($link->age_transition_expires_at->isFuture());
    }

    public function test_command_expires_links_past_grace_period(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);
        $link = FamilyLink::factory()->create([
            'guardian_user_id'          => $guardian->id,
            'dependent_patient_id'      => $dependent->id,
            'status'                    => 'active',
            'age_transition_expires_at' => now()->subDay(),
        ]);

        $this->artisan('family:check-age-transitions')->assertExitCode(0);

        $link->refresh();
        $this->assertEquals('expired', $link->status);
    }

    public function test_command_sends_60_day_warning(): void
    {
        Notification::fake();

        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create([
            'is_demo'       => false,
            'date_of_birth' => now()->subYears(18)->addDays(60)->toDateString(),
        ]);
        $link = FamilyLink::factory()->create([
            'guardian_user_id'           => $guardian->id,
            'dependent_patient_id'       => $dependent->id,
            'status'                     => 'active',
            'age_transition_notified_at' => null,
        ]);

        $this->artisan('family:check-age-transitions')->assertExitCode(0);

        $link->refresh();
        $this->assertNotNull($link->age_transition_notified_at);
    }
}
