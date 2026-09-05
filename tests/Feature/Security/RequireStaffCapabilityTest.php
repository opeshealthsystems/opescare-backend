<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use App\Support\StaffCapabilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The one role → capability map, and the middleware that enforces it on the
 * web and on the clinician API.
 */
class RequireStaffCapabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'capability:prescribe'])->get('/_test/prescribe', fn () => 'desk');
        Route::middleware(['web', 'auth', 'capability:clinical_chart,lab_results'])->get('/_test/results', fn () => 'results');
        Route::middleware(['api', 'capability:prescribe'])->get('/api/_test/prescribe', fn () => ['ok' => true]);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)])->id,
        ]);
    }

    public function test_the_map_reads_as_intended(): void
    {
        $this->assertTrue(StaffCapabilities::roleCan('doctor', 'prescribe'));
        $this->assertTrue(StaffCapabilities::roleCan('doctor', 'clinical_chart'));
        $this->assertTrue(StaffCapabilities::roleCan('nurse', 'clinical_chart'));
        $this->assertFalse(StaffCapabilities::roleCan('nurse', 'prescribe'));
        $this->assertTrue(StaffCapabilities::roleCan('pharmacist', 'dispense'));
        $this->assertFalse(StaffCapabilities::roleCan('pharmacist', 'clinical_chart'));
        $this->assertTrue(StaffCapabilities::roleCan('labtech', 'lab_results'));
        $this->assertFalse(StaffCapabilities::roleCan('labtech', 'prescribe'));
        $this->assertTrue(StaffCapabilities::roleCan('receptionist', 'front_desk'));
        $this->assertFalse(StaffCapabilities::roleCan('receptionist', 'clinical_chart'));
        $this->assertFalse(StaffCapabilities::roleCan('receptionist', 'lab_results'));
        $this->assertTrue(StaffCapabilities::roleCan('receptionist', 'identify_patient'));
        $this->assertFalse(StaffCapabilities::roleCan('patient', 'identify_patient'));
        $this->assertFalse(StaffCapabilities::roleCan(null, 'messaging'));
        $this->assertContains('prescribe', StaffCapabilities::forRole('doctor'));
        $this->assertSame([], StaffCapabilities::forRole('patient'));
        $this->assertNotEmpty(StaffCapabilities::all());
    }

    public function test_web_route_admits_the_capability_and_refuses_others_with_403(): void
    {
        $this->actingAs($this->userWithRole('doctor'))->get('/_test/prescribe')->assertOk();
        $this->actingAs($this->userWithRole('nurse'))->get('/_test/prescribe')->assertForbidden();
        $this->actingAs($this->userWithRole('receptionist'))->get('/_test/prescribe')->assertForbidden();

        // Any-of: results are for clinicians AND for the lab.
        $this->actingAs($this->userWithRole('labtech'))->get('/_test/results')->assertOk();
        $this->actingAs($this->userWithRole('nurse'))->get('/_test/results')->assertOk();
        $this->actingAs($this->userWithRole('pharmacist'))->get('/_test/results')->assertForbidden();
    }

    public function test_api_refusal_is_json_with_a_reason(): void
    {
        $this->actingAs($this->userWithRole('pharmacist'))
            ->getJson('/api/_test/prescribe')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden')
            ->assertJsonPath('required.0', 'prescribe');
    }
}
