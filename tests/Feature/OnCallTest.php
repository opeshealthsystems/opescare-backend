<?php
namespace Tests\Feature;

use App\Models\Facility;
use App\Models\OnCallSchedule;
use App\Models\User;
use App\Services\Staff\OnCallService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnCallTest extends TestCase
{
    use RefreshDatabase;

    private OnCallService $service;
    private Facility $facility;
    private User $provider;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-05-28 12:00:00');
        $this->service  = app(OnCallService::class);
        $this->facility = Facility::factory()->create();
        $this->provider = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_can_schedule_on_call(): void
    {
        $schedule = $this->service->schedule([
            'provider_id'  => $this->provider->id,
            'facility_id'  => $this->facility->id,
            'specialty'    => 'general',
            'on_call_date' => '2026-06-10',
            'start_time'   => '18:00:00',
            'end_time'     => '08:00:00',
            'is_confirmed' => false,
        ]);

        $this->assertInstanceOf(OnCallSchedule::class, $schedule);
        $this->assertEquals('general', $schedule->specialty);
        $this->assertDatabaseHas('on_call_schedules', ['id' => $schedule->id]);
    }

    public function test_get_current_on_call_returns_active_providers(): void
    {
        $now = Carbon::now();

        OnCallSchedule::create([
            'provider_id'  => $this->provider->id,
            'facility_id'  => $this->facility->id,
            'specialty'    => 'emergency',
            'on_call_date' => $now->toDateString(),
            'start_time'   => $now->copy()->subHours(2)->format('H:i:s'),
            'end_time'     => $now->copy()->addHours(6)->format('H:i:s'),
            'is_confirmed' => true,
        ]);

        $current = $this->service->getCurrentOnCall($this->facility->id);

        $this->assertCount(1, $current);
        $this->assertEquals($this->provider->id, $current->first()->provider_id);
    }

    public function test_get_on_call_providers_filters_by_specialty(): void
    {
        $surgeon = User::factory()->create();
        $gp      = User::factory()->create();
        $now     = Carbon::now();

        OnCallSchedule::create([
            'provider_id'  => $surgeon->id,
            'facility_id'  => $this->facility->id,
            'specialty'    => 'surgery',
            'on_call_date' => $now->toDateString(),
            'start_time'   => $now->copy()->subHour()->format('H:i:s'),
            'end_time'     => $now->copy()->addHours(8)->format('H:i:s'),
            'is_confirmed' => true,
        ]);

        OnCallSchedule::create([
            'provider_id'  => $gp->id,
            'facility_id'  => $this->facility->id,
            'specialty'    => 'general',
            'on_call_date' => $now->toDateString(),
            'start_time'   => $now->copy()->subHour()->format('H:i:s'),
            'end_time'     => $now->copy()->addHours(8)->format('H:i:s'),
            'is_confirmed' => true,
        ]);

        $surgeons = $this->service->getOnCallProviders($this->facility->id, $now, 'surgery');

        $this->assertCount(1, $surgeons);
        $this->assertEquals($surgeon->id, $surgeons->first()->provider_id);
    }

    public function test_monthly_roster_returns_all_entries_for_month(): void
    {
        $this->service->schedule([
            'provider_id'  => $this->provider->id,
            'facility_id'  => $this->facility->id,
            'specialty'    => 'general',
            'on_call_date' => '2026-07-05',
            'start_time'   => '18:00:00',
            'end_time'     => '08:00:00',
        ]);
        $this->service->schedule([
            'provider_id'  => $this->provider->id,
            'facility_id'  => $this->facility->id,
            'specialty'    => 'general',
            'on_call_date' => '2026-07-20',
            'start_time'   => '18:00:00',
            'end_time'     => '08:00:00',
        ]);

        $roster = $this->service->getMonthlyRoster($this->facility->id, 2026, 7);

        $this->assertCount(2, $roster);
    }
}
