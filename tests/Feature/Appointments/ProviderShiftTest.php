<?php
namespace Tests\Feature\Appointments;

use App\Models\Facility;
use App\Models\ProviderShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_provider_shift(): void
    {
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $shift = ProviderShift::create([
            'provider_id' => $provider->id,
            'facility_id' => $facility->id,
            'shift_date'  => '2026-07-01',
            'start_time'  => '08:00',
            'end_time'    => '16:00',
            'shift_type'  => 'morning',
            'department'  => 'General Medicine',
        ]);

        $this->assertEquals('morning', $shift->shift_type);
        $this->assertEquals('General Medicine', $shift->department);
    }

    public function test_on_call_shift_is_flagged(): void
    {
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $shift = ProviderShift::create([
            'provider_id' => $provider->id,
            'facility_id' => $facility->id,
            'shift_date'  => '2026-07-01',
            'start_time'  => '22:00',
            'end_time'    => '08:00',
            'shift_type'  => 'on_call',
            'is_on_call'  => true,
        ]);

        $this->assertTrue($shift->is_on_call);
    }

    public function test_can_query_todays_on_call_providers(): void
    {
        $p1       = User::factory()->create();
        $p2       = User::factory()->create();
        $facility = Facility::factory()->create();

        ProviderShift::create(['provider_id' => $p1->id, 'facility_id' => $facility->id, 'shift_date' => now()->toDateString(), 'start_time' => '00:00', 'end_time' => '23:59', 'shift_type' => 'on_call', 'is_on_call' => true]);
        ProviderShift::create(['provider_id' => $p2->id, 'facility_id' => $facility->id, 'shift_date' => now()->toDateString(), 'start_time' => '08:00', 'end_time' => '16:00', 'shift_type' => 'morning', 'is_on_call' => false]);

        $onCall = ProviderShift::whereDate('shift_date', now()->toDateString())
            ->where('is_on_call', true)->get();

        $this->assertCount(1, $onCall);
        $this->assertEquals($p1->id, $onCall->first()->provider_id);
    }
}
