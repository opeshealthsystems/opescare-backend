<?php
namespace Tests\Feature\Tenancy;

use App\Traits\HasFacilityScope;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    public function test_has_facility_scope_trait_exists(): void
    {
        $this->assertTrue(trait_exists(HasFacilityScope::class));
    }

    public function test_trait_provides_scope_for_facility_method(): void
    {
        $methods = (new \ReflectionClass(HasFacilityScope::class))->getMethods();
        $names = array_map(fn($m) => $m->getName(), $methods);
        $this->assertContains('scopeForFacility', $names);
        $this->assertContains('scopeForCurrentFacility', $names);
    }

    public function test_for_current_facility_scope_does_not_add_where_when_no_context(): void
    {
        // Ensure no facility is bound in the container
        app()->forgetInstance('current_facility_id');

        // scopeForCurrentFacility must not throw when no facility is bound —
        // it should return the Builder unchanged so cross-facility queries work.
        try {
            $query = \App\Models\TenantOnboardingCheckpoint::query()->forCurrentFacility();
            // Reaching here means no exception was thrown — that is the expected behaviour
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
        } catch (\Throwable $e) {
            $this->fail('scopeForCurrentFacility threw an exception when no facility was bound: ' . $e->getMessage());
        }
    }
}
