<?php

namespace Tests\Feature\Seeders;

use App\Models\Appointment;
use App\Models\FamilyLink;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoChildrenSeeder;
use Database\Seeders\DemoFamilySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The family demo: demo.guardian@ becomes a patient who can sign in and sees
 * her two children; demo.reviewer@ manages Demo Grandmother read-only as the
 * end state of an accepted invitation. Idempotent.
 */
class DemoFamilySeederTest extends TestCase
{
    use RefreshDatabase;

    private Role $guardianRole;

    protected function setUp(): void
    {
        parent::setUp();
        // Production runs in demo mode; demo rows are only visible there.
        Config::set('demo.enabled', true);
        Role::firstOrCreate(['name' => 'patient'], ['description' => 'Patient']);
        $this->guardianRole = Role::firstOrCreate(['name' => 'guardian'], ['description' => 'Guardian']);

        // What production already has: the reviewer (a demo patient with a
        // login) and demo.guardian@ as a guardian-role user with NO patient row.
        $reviewer = Patient::factory()->create([
            'email' => DemoFamilySeeder::REVIEWER_EMAIL, 'first_name' => 'Demo', 'last_name' => 'Reviewer',
            'sex' => 'female', 'date_of_birth' => '1979-04-10', 'is_demo' => true, 'identity_status' => 'verified',
        ]);
        User::factory()->create(['email' => DemoFamilySeeder::REVIEWER_EMAIL, 'name' => 'Demo Reviewer', 'patient_id' => $reviewer->id, 'is_demo' => true]);
        User::factory()->create(['email' => DemoFamilySeeder::GUARDIAN_EMAIL, 'name' => 'Guardian Marie Dupont', 'patient_id' => null, 'is_demo' => true, 'role_id' => $this->guardianRole->id]);
    }

    public function test_the_guardian_gets_a_patient_row_and_two_children_with_full_access(): void
    {
        $this->seed(DemoFamilySeeder::class);

        $guardian = Patient::withoutGlobalScopes()->where('email', DemoFamilySeeder::GUARDIAN_EMAIL)->firstOrFail();
        $this->assertMatchesRegularExpression('/^CM-HID-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $guardian->health_id);
        $this->assertSame('Demo', $guardian->first_name);
        $this->assertSame('Guardian', $guardian->last_name);
        $this->assertSame('female', $guardian->sex);
        $this->assertSame('1985-06-02', $guardian->date_of_birth->toDateString());
        $this->assertSame('O+', $guardian->blood_group);
        $this->assertSame('verified', $guardian->verification_status->value);
        $this->assertTrue($guardian->is_demo);

        $user = User::withoutGlobalScopes()->where('email', DemoFamilySeeder::GUARDIAN_EMAIL)->firstOrFail();
        $this->assertSame($guardian->id, $user->patient_id, 'The existing user row is linked, not duplicated.');
        $this->assertSame('Demo Guardian', $user->name);
        $this->assertSame($this->guardianRole->id, $user->role_id, 'An existing role is left alone.');
        $this->assertNotNull($user->profile_completed_at);
        $this->assertSame(1, User::withoutGlobalScopes()->where('email', DemoFamilySeeder::GUARDIAN_EMAIL)->count());

        foreach (DemoChildrenSeeder::CHILDREN as $child) {
            $dependent = Patient::withoutGlobalScopes()->where('email', $child['email'])->firstOrFail();
            $link = FamilyLink::where('guardian_user_id', $user->id)->where('dependent_patient_id', $dependent->id)->firstOrFail();
            $this->assertSame('active', $link->status);
            $this->assertSame('parent', $link->relationship, 'The guardian is the child\'s parent.');
            $this->assertSame('full', $link->access_level);
            $this->assertSame('self_registered', $link->created_by);
        }
    }

    public function test_the_grandmother_has_her_own_login_and_is_managed_read_only_by_the_reviewer(): void
    {
        $this->seed(DemoFamilySeeder::class);

        $grandmother = Patient::withoutGlobalScopes()->where('email', DemoFamilySeeder::GRANDMOTHER_EMAIL)->firstOrFail();
        $this->assertSame('1948-02-20', $grandmother->date_of_birth->toDateString());
        $this->assertSame('female', $grandmother->sex);
        $this->assertSame('A+', $grandmother->blood_group);
        $this->assertSame('verified', $grandmother->verification_status->value);
        $this->assertTrue($grandmother->is_demo);
        $this->assertGreaterThanOrEqual(75, $grandmother->date_of_birth->age, 'She is an older person.');

        $grandmaUser = User::withoutGlobalScopes()->where('email', DemoFamilySeeder::GRANDMOTHER_EMAIL)->firstOrFail();
        $this->assertSame($grandmother->id, $grandmaUser->patient_id);
        $this->assertSame('patient', $grandmaUser->role?->name);

        $reviewer = User::withoutGlobalScopes()->where('email', DemoFamilySeeder::REVIEWER_EMAIL)->firstOrFail();
        $link = FamilyLink::where('guardian_user_id', $reviewer->id)->where('dependent_patient_id', $grandmother->id)->firstOrFail();
        $this->assertSame('active', $link->status);
        $this->assertSame('child', $link->relationship, 'relationship is the guardian\'s role: the reviewer is her mother\'s child.');
        $this->assertSame('read_only', $link->access_level);
        $this->assertTrue($link->isReadOnly());
        $this->assertSame('invite_accepted', $link->created_by);
        $this->assertNotNull($link->dependent_consented_at, 'An adult dependent\'s consent is on file.');
    }

    public function test_the_guardian_signs_in_through_the_app_and_sees_two_members(): void
    {
        $this->seed(DemoFamilySeeder::class);

        $token = $this->postJson('/api/mobile/auth/login-email', [
            'email'    => DemoFamilySeeder::GUARDIAN_EMAIL,
            'password' => DemoFamilySeeder::PASSWORD,
        ])->assertOk()->assertJsonStructure(['access_token'])->json('access_token');

        $members = collect($this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/mobile/family')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data'));

        $this->assertEqualsCanonicalizing(['Demo Boy', 'Demo Girl'], $members->pluck('patient.full_name')->all());
        $this->assertEqualsCanonicalizing(['male', 'female'], $members->pluck('patient.sex')->all());
        $this->assertSame(['full', 'full'], $members->pluck('access_level')->all());
        $this->assertSame(['verified', 'verified'], $members->pluck('patient.status')->all());

        // …and can open a child's card through the member detail.
        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/mobile/family/members/' . $members->first()['id'])
            ->assertOk()
            ->assertJsonPath('data.patient.id', $members->first()['patient']['id']);
    }

    public function test_the_grandmother_signs_in_and_the_reviewer_sees_her_read_only(): void
    {
        $this->seed(DemoFamilySeeder::class);

        $this->postJson('/api/mobile/auth/login-email', [
            'email'    => DemoFamilySeeder::GRANDMOTHER_EMAIL,
            'password' => DemoFamilySeeder::PASSWORD,
        ])->assertOk()->assertJsonStructure(['access_token']);

        $reviewerPatient = Patient::withoutGlobalScopes()->where('email', DemoFamilySeeder::REVIEWER_EMAIL)->firstOrFail();
        $raw = 'pat_' . \Illuminate\Support\Str::random(40);
        \App\Models\PatientAccessToken::create([
            'patient_id'   => $reviewerPatient->id,
            'token_hash'   => \Illuminate\Support\Facades\Hash::make($raw),
            'token_prefix' => substr($raw, 0, 12),
            'expires_at'   => now()->addDay(),
        ]);

        $this->withHeaders(['Authorization' => "Bearer {$raw}"])
            ->getJson('/api/mobile/family')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.relationship', 'child')
            ->assertJsonPath('data.0.access_level', 'read_only')
            ->assertJsonPath('data.0.is_read_only', true)
            ->assertJsonPath('data.0.patient.full_name', 'Demo Grandmother')
            ->assertJsonPath('data.0.patient.dob', '1948-02-20')
            ->assertJsonPath('data.0.patient.sex', 'female')
            ->assertJsonPath('data.0.patient.blood_type', 'A+');
    }

    public function test_running_it_twice_changes_nothing(): void
    {
        $this->seed(DemoFamilySeeder::class);

        $snapshot = fn () => [
            Patient::withoutGlobalScopes()->count(),
            User::withoutGlobalScopes()->count(),
            FamilyLink::count(),
            ImmunizationRecord::count(),
            Appointment::count(),
            DatabaseNotification::count(),
        ];
        $consented = FamilyLink::where('created_by', 'invite_accepted')->value('dependent_consented_at');

        $before = $snapshot();
        $this->seed(DemoFamilySeeder::class);

        $this->assertSame($before, $snapshot());
        $this->assertEquals($consented, FamilyLink::where('created_by', 'invite_accepted')->value('dependent_consented_at'), 'Consent is stamped once.');
    }
}
