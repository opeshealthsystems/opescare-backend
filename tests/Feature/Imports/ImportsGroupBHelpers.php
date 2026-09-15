<?php

namespace Tests\Feature\Imports;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;

/** Shared fixtures for the group B import tests (tasks, support, broadcasts). */
trait ImportsGroupBHelpers
{
    protected function seedRoles(): void
    {
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    /** A staff member posted at one facility, exactly as the middleware leaves one. */
    protected function staffAt(?Facility $facility = null, string $roleName = 'nurse'): array
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => 'hospital']);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'primary_facility_id' => $facility->id,
            'status' => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'role_id' => $role->id,
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    protected function actingAtFacility(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id, 'mfa.verified' => true]);
    }

    protected function actingAsAdmin(User $admin): static
    {
        return $this->actingAs($admin)->withSession(['mfa.verified' => true]);
    }

    protected function platformAdmin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'super_admin')->firstOrFail()->id,
            'primary_facility_id' => null,
            'status' => 'active',
        ]);
    }
}
