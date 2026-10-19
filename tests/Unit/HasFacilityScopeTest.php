<?php

namespace Tests\Unit;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class HasFacilityScopeTest extends TestCase
{
    public function test_unbound_facility_returns_empty_and_warns_once(): void
    {
        app()->forgetInstance('current_facility_id');

        $channel = Mockery::mock();
        $channel->shouldReceive('warning')->once()
            ->withArgs(fn ($msg, $ctx) => $ctx['model'] === Appointment::class);
        Log::shouldReceive('channel')->with('single')->andReturn($channel);

        $q = Appointment::query()->forCurrentFacility();
        $this->assertStringContainsString('1 = 0', $q->toSql());

        Appointment::query()->forCurrentFacility(); // second call: no second warning
    }

    public function test_bound_facility_scopes_by_facility_id(): void
    {
        app()->instance('current_facility_id', 'fac-123');

        $q = Appointment::query()->forCurrentFacility();

        $this->assertStringContainsString('"appointments"."facility_id" = ?', $q->toSql());
        $this->assertSame(['fac-123'], $q->getBindings());
        $this->assertStringNotContainsString('1 = 0', $q->toSql());
    }

    public function test_escape_hatch_reads_all_facilities(): void
    {
        app()->forgetInstance('current_facility_id');

        $q = Appointment::query()->acrossFacilities();

        $this->assertStringNotContainsString('facility_id', $q->toSql());
        $this->assertStringNotContainsString('1 = 0', $q->toSql());
        $this->assertSame([], $q->getBindings());
    }
}
