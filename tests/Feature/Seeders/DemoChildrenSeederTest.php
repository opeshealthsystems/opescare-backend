<?php

namespace Tests\Feature\Seeders;

use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoChildrenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The boy and girl demo patients: real Health IDs, verified identities,
 * logins that work through the app's email sign-in, idempotent.
 */
class DemoChildrenSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Production runs in demo mode; demo rows are only visible there.
        Config::set('demo.enabled', true);
        Role::firstOrCreate(['name' => 'patient'], ['description' => 'Patient']);
    }

    public function test_seeds_a_boy_and_a_girl_with_real_health_ids_and_verified_identities(): void
    {
        $this->seed(DemoChildrenSeeder::class);

        $boy  = Patient::withoutGlobalScopes()->where('email', 'demo.boy@opescare.test')->firstOrFail();
        $girl = Patient::withoutGlobalScopes()->where('email', 'demo.girl@opescare.test')->firstOrFail();

        foreach ([$boy, $girl] as $child) {
            $this->assertMatchesRegularExpression('/^CM-HID-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $child->health_id);
            $this->assertTrue($child->is_demo);
            $this->assertSame('verified', $child->verification_status->value);
            $this->assertLessThan(18, $child->date_of_birth->age, 'A demo child must be a child.');
        }

        $this->assertSame('male', $boy->sex);
        $this->assertSame('female', $girl->sex);
        $this->assertSame('B+', $boy->blood_group);
        $this->assertSame('A-', $girl->blood_group);
    }

    public function test_each_child_signs_in_through_the_app_email_login(): void
    {
        $this->seed(DemoChildrenSeeder::class);

        foreach (DemoChildrenSeeder::CHILDREN as $child) {
            $user = User::withoutGlobalScopes()->where('email', $child['email'])->firstOrFail();
            $this->assertTrue($user->is_demo);

            $this->postJson('/api/mobile/auth/login-email', [
                'email'    => $child['email'],
                'password' => DemoChildrenSeeder::PASSWORD,
            ])->assertOk()->assertJsonStructure(['access_token']);
        }
    }

    public function test_running_it_twice_changes_nothing(): void
    {
        $this->seed(DemoChildrenSeeder::class);
        $patients = Patient::withoutGlobalScopes()->count();
        $users    = User::withoutGlobalScopes()->count();
        $shots    = ImmunizationRecord::count();

        $this->seed(DemoChildrenSeeder::class);

        $this->assertSame($patients, Patient::withoutGlobalScopes()->count());
        $this->assertSame($users, User::withoutGlobalScopes()->count());
        $this->assertSame($shots, ImmunizationRecord::count());
    }
}
