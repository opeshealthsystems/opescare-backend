<?php
namespace Tests\Feature;

use App\Models\Facility;
use App\Models\ProviderShift;
use App\Models\User;
use App\Services\Staff\ProviderShiftService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderShiftTest extends TestCase
{
    use RefreshDatabase;

    private ProviderShiftService $service;
    private User $provider;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service  = app(ProviderShiftService::class);
        $this->provider = User::factory()->create();
        $this->facility = Facility::factory()->create();
    }

    public function test_can_schedule_a_shift(): void
    {
        $shift = $this->service->scheduleShift([
            'provider_id'  => $this->provider->id,
            'facility_id'  => $this->facility->id,
            'shift_date'   => '2026-06-02',
            'start_time'   => '08:00:00',
            'end_time'     => '14:00:00',
            'shift_type'   => 'morning',
            'is_confirmed' => false,
        ]);

        $this->assertInstanceOf(ProviderShift::class, $shift);
        $this->assertEquals('morning', $shift->shift_type);
        $this->assertDatabaseHas('provider_shifts', ['id' => $shift->id]);
    }

    public function test_get_weekly_schedule_returns_shifts_for_week(): void
    {
        $monday = Carbon::parse('2026-06-01')->startOfWeek();

        $this->service->scheduleShift([
            'provider_id' => $this->provider->id,
            'facility_id' => $this->facility->id,
            'shift_date'  => $monday->toDateString(),
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'shift_type'  => 'morning',
        ]);
        $this->service->scheduleShift([
            'provider_id' => $this->provider->id,
            'facility_id' => $this->facility->id,
            'shift_date'  => $monday->copy()->addDays(2)->toDateString(),
            'start_time'  => '14:00:00',
            'end_time'    => '20:00:00',
            'shift_type'  => 'afternoon',
        ]);

        $schedule = $this->service->getWeeklySchedule($this->facility->id, $monday);

        $this->assertCount(2, $schedule);
    }

    public function test_request_swap_sets_target_provider(): void
    {
        $shift = $this->service->scheduleShift([
            'provider_id' => $this->provider->id,
            'facility_id' => $this->facility->id,
            'shift_date'  => '2026-06-05',
            'start_time'  => '08:00:00',
            'end_time'    => '14:00:00',
            'shift_type'  => 'morning',
        ]);

        $target  = User::factory()->create();
        $updated = $this->service->requestSwap($shift->id, $target->id);

        $this->assertEquals($target->id, $updated->swap_requested_with);
    }

    public function test_confirm_swap_updates_provider_on_shift(): void
    {
        $originalProvider = $this->provider;
        $targetProvider   = User::factory()->create();

        $shift = ProviderShift::create([
            'provider_id'         => $originalProvider->id,
            'facility_id'         => $this->facility->id,
            'shift_date'          => '2026-06-10',
            'start_time'          => '08:00:00',
            'end_time'            => '14:00:00',
            'shift_type'          => 'morning',
            'swap_requested_with' => $targetProvider->id,
            'is_confirmed'        => false,
        ]);

        $confirmed = $this->service->confirmSwap($shift->id);

        $this->assertEquals($targetProvider->id, $confirmed->provider_id);
        $this->assertNull($confirmed->swap_requested_with);
        $this->assertTrue($confirmed->is_confirmed);
    }
}
