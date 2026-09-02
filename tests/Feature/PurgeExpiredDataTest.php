<?php

namespace Tests\Feature;

use App\Models\UssdSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeExpiredDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_returns_counts_without_deleting(): void
    {
        // Seed an old USSD session
        UssdSession::create([
            'session_id'     => 'OLD-SESS-001',
            'phone_number'   => '+237670000010',
            'service_code'   => '*384#',
            'current_menu'   => 'MAIN',
            'initiated_at'   => Carbon::now()->subDays(60),
            'last_active_at' => Carbon::now()->subDays(60),
        ]);

        $this->assertDatabaseCount('ussd_sessions', 1);

        $this->artisan('opescare:purge-expired-data --dry-run')
             ->assertExitCode(0);

        // Record should still exist after dry-run
        $this->assertDatabaseCount('ussd_sessions', 1);
    }

    public function test_actual_run_deletes_old_ussd_sessions(): void
    {
        // Seed an old session (> 30 days)
        UssdSession::create([
            'session_id'     => 'OLD-SESS-002',
            'phone_number'   => '+237670000011',
            'service_code'   => '*384#',
            'current_menu'   => 'MAIN',
            'initiated_at'   => Carbon::now()->subDays(60),
            'last_active_at' => Carbon::now()->subDays(60),
        ]);

        // Seed a recent session (< 30 days)
        UssdSession::create([
            'session_id'     => 'NEW-SESS-001',
            'phone_number'   => '+237670000012',
            'service_code'   => '*384#',
            'current_menu'   => 'MAIN',
            'initiated_at'   => Carbon::now()->subDays(5),
            'last_active_at' => Carbon::now()->subDays(5),
        ]);

        $this->artisan('opescare:purge-expired-data')
             ->assertExitCode(0);

        $this->assertDatabaseMissing('ussd_sessions', ['session_id' => 'OLD-SESS-002']);
        $this->assertDatabaseHas('ussd_sessions', ['session_id' => 'NEW-SESS-001']);
    }
}
