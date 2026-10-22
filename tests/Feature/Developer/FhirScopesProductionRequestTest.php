<?php

namespace Tests\Feature\Developer;

use App\Http\Controllers\Api\Fhir\FhirIngestController;
use App\Models\DeveloperAccount;
use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A self-service partner (e.g. OPES Triage) must be able to REQUEST the FHIR
 * write scopes that routes/fhir.php checks; otherwise no developer app can
 * ever be approved for /api/fhir/R4 through the portal.
 */
class FhirScopesProductionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_request_form_offers_fhir_write_scopes(): void
    {
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);

        $user = User::factory()->create([
            'status'              => 'active',
            'primary_facility_id' => Facility::factory()->create()->id,
        ]);
        $user->role_id = Role::where('name', 'developer')->firstOrFail()->id;
        $user->save();

        DeveloperAccount::create([
            'display_name'       => 'OPES Triage',
            'email'              => $user->email,
            'status'             => 'active',
            'email_verified_at'  => now(),
            'api_terms_accepted' => true,
            'sandbox_only'       => true,
        ]);

        $response = $this->actingAs($user->fresh())
            ->withSession(['mfa.verified' => true, 'auth_email' => $user->email])
            ->get(route('portals.developer.production_requests.create'));

        $response->assertOk();
        foreach ([
            FhirIngestController::SCOPE_OBSERVATION,
            FhirIngestController::SCOPE_DIAGNOSTIC_REPORT,
            FhirIngestController::SCOPE_IMMUNIZATION,
        ] as $scope) {
            $response->assertSee('value="' . $scope . '"', false);
        }
    }
}
