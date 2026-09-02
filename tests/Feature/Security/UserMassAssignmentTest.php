<?php
namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_id_is_not_in_user_fillable(): void
    {
        $fillable = (new User)->getFillable();
        $this->assertNotContains('role_id', $fillable,
            'role_id must not be in User.$fillable — prevents role escalation via mass assignment');
    }

    public function test_is_demo_is_not_in_user_fillable(): void
    {
        $fillable = (new User)->getFillable();
        $this->assertNotContains('is_demo', $fillable,
            'is_demo must not be in User.$fillable');
    }

    public function test_role_id_cannot_be_mass_assigned(): void
    {
        // User::create without role_id should create user successfully
        $user = User::factory()->create();

        // Attempt fill with role_id
        $user->fill(['role_id' => 'attacker-role-uuid', 'name' => 'Test Name']);
        // DO NOT save — just verify fill didn't change role_id

        $this->assertNull($user->role_id,
            'role_id must not be fillable — fill() should not change it');
    }
}
