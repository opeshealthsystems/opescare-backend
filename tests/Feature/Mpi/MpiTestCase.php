<?php

namespace Tests\Feature\Mpi;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Identity\HealthIdGeneratorService;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/** Fixtures the MPI tests share. */
abstract class MpiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * A patient with a real Health ID. `is_demo` is not fillable — it is
     * stamped by the trait from config — so it is placed explicitly.
     */
    protected function patient(array $overrides = [], ?bool $demo = null): Patient
    {
        // Default to the partition the app is currently SHOWING, so the scoped
        // lookups (resolveHealthId and the five endpoints behind it) can see
        // the fixture. A test that wants the other side says so.
        $demo ??= (bool) config('demo.enabled', false);
        $patient = app(HealthIdGeneratorService::class)->generate('CM', function (string $healthId) use ($overrides) {
            return Patient::create(array_merge([
                'health_id'           => $healthId,
                'first_name'          => 'Jude',
                'last_name'           => 'Nshome',
                'date_of_birth'       => '1990-04-17',
                'sex'                 => 'male',
                'identity_status'     => 'provisional',
                'verification_status' => 'unverified',
            ], $overrides));
        });

        $patient->forceFill(['is_demo' => $demo])->save();

        return $patient->fresh();
    }

    protected function facility(string $name = 'Hôpital de Test'): Facility
    {
        return Facility::create(['name' => $name, 'type' => 'hospital', 'status' => 'active']);
    }

    protected function platformAdmin(): User
    {
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);

        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $user->role_id = Role::where('name', 'super_admin')->firstOrFail()->id;
        $user->save();

        return $user;
    }

    /** Scope-blind reload — the demo partition hides half the register. */
    protected function reload(Patient $p): Patient
    {
        return Patient::withoutGlobalScope('isolate_demo')->findOrFail($p->getKey());
    }
}
