<?php

namespace Tests\Feature\Mobile;

use App\Models\FamilyLink;
use App\Models\Patient;
use Illuminate\Support\Facades\Config;

/**
 * POST /api/mobile/family — registering a CHILD: through the identity
 * service, keeping what was validated, on the guardian's side of the demo
 * partition, and never for an adult.
 */
class FamilyRegisterChildTest extends FamilyTestCase
{
    private const CHILD = [
        'full_name'     => 'Amina Ndi',
        'date_of_birth' => '2019-05-04',
        'sex'           => 'female',
        'relationship'  => 'parent',
        'blood_group'   => 'O+',
        'phone'         => '+237677001122',
    ];

    public function test_registers_a_child_through_the_identity_service_and_keeps_blood_group_and_phone(): void
    {
        $g = $this->makeGuardian();

        $response = $this->as($g['headers'])
            ->postJson('/api/mobile/family', self::CHILD)
            ->assertCreated()
            ->assertJsonPath('message', __('api.family_member_added'))
            ->assertJsonPath('data.access_level', 'full')
            ->assertJsonPath('data.is_read_only', false)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.created_by', 'self_registered')
            ->assertJsonPath('data.relationship', 'parent')
            ->assertJsonPath('data.patient.full_name', 'Amina Ndi')
            ->assertJsonPath('data.patient.first_name', 'Amina')
            ->assertJsonPath('data.patient.last_name', 'Ndi')
            ->assertJsonPath('data.patient.sex', 'female')
            ->assertJsonPath('data.patient.dob', '2019-05-04')
            ->assertJsonPath('data.patient.blood_type', 'O+')
            ->assertJsonPath('data.patient.status', 'provisional');

        $this->assertMatchesRegularExpression('/^CM-HID-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $response->json('data.patient.health_id'));

        $child = Patient::withoutGlobalScopes()->findOrFail($response->json('data.patient.id'));
        $this->assertSame('O+', $child->blood_group);
        $this->assertSame('+237677001122', $child->phone_number, 'The phone used to be validated and dropped.');
        $this->assertSame($child->id, Patient::findByPhone('+237677001122')?->id);
        $this->assertSame('provisional', $child->identity_status->value);
        $this->assertFalse($child->is_demo);

        $link = FamilyLink::findOrFail($response->json('data.id'));
        $this->assertSame($g['user']->id, $link->guardian_user_id);
        $this->assertSame($child->id, $link->dependent_patient_id);

        // Minted by PatientIdentityService: the creation is on the audit trail,
        // attributed to the guardian.
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'patient',
            'resource_id'   => $child->id,
            'action_type'   => 'create',
            'actor_id'      => $g['user']->id,
        ]);
    }

    public function test_an_adult_cannot_be_registered_as_a_dependent(): void
    {
        $g      = $this->makeGuardian();
        $before = Patient::withoutGlobalScopes()->count();

        $this->as($g['headers'])
            ->postJson('/api/mobile/family', array_merge(self::CHILD, ['date_of_birth' => '2000-01-01', 'full_name' => 'Grown Up']))
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'dependent_is_adult')
            ->assertJsonPath('message', __('family_api.dependent_is_adult', ['age' => 18]))
            ->assertJsonStructure(['errors' => ['date_of_birth']]);

        // Exactly eighteen today is an adult too.
        $this->as($g['headers'])
            ->postJson('/api/mobile/family', array_merge(self::CHILD, ['full_name' => 'Just Eighteen', 'date_of_birth' => now()->subYears(18)->toDateString(), 'phone' => null]))
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'dependent_is_adult');

        $this->assertSame($before, Patient::withoutGlobalScopes()->count());
        $this->assertSame(0, FamilyLink::count());
    }

    public function test_a_child_inherits_the_guardians_demo_flag_not_the_hosts_config(): void
    {
        // The real production shape: demo host, demo guardian.
        Config::set('demo.enabled', true);
        $demoGuardian = $this->makeGuardian();
        $this->assertTrue($demoGuardian['patient']->is_demo);

        $id = $this->as($demoGuardian['headers'])
            ->postJson('/api/mobile/family', self::CHILD)
            ->assertCreated()
            ->json('data.patient.id');
        $this->assertNotNull($id, 'The new child must be visible to the demo guardian who registered her.');
        $this->assertTrue(Patient::withoutGlobalScopes()->findOrFail($id)->is_demo);

        // And the flag follows the GUARDIAN even when the host says otherwise.
        Config::set('demo.enabled', false);
        $guardianPatient = Patient::factory()->create(['is_demo' => true, 'first_name' => 'Odd', 'last_name' => 'Case', 'date_of_birth' => '1980-01-01']);
        $guardianUser    = $this->makeUserFor($guardianPatient, ['is_demo' => false]);

        $this->as($this->headersFor($guardianPatient))
            ->postJson('/api/mobile/family', array_merge(self::CHILD, ['full_name' => 'Second Child', 'date_of_birth' => '2020-02-02', 'phone' => null]))
            ->assertCreated();

        $link = FamilyLink::where('guardian_user_id', $guardianUser->id)->firstOrFail();
        $this->assertTrue(Patient::withoutGlobalScopes()->findOrFail($link->dependent_patient_id)->is_demo);
    }

    public function test_access_level_defaults_to_full_and_read_only_is_honoured(): void
    {
        $g = $this->makeGuardian();

        $this->as($g['headers'])
            ->postJson('/api/mobile/family', self::CHILD + ['access_level' => 'read_only'])
            ->assertCreated()
            ->assertJsonPath('data.access_level', 'read_only')
            ->assertJsonPath('data.is_read_only', true);

        $this->as($g['headers'])
            ->postJson('/api/mobile/family', array_merge(self::CHILD, ['full_name' => 'Second Child', 'date_of_birth' => '2021-01-01', 'phone' => null, 'access_level' => 'view_only']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['access_level']);
    }

    public function test_relationship_is_required_and_from_the_shared_list(): void
    {
        $g = $this->makeGuardian();

        $this->as($g['headers'])
            ->postJson('/api/mobile/family', array_merge(self::CHILD, ['relationship' => 'uncle']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['relationship']);

        $this->as($g['headers'])
            ->postJson('/api/mobile/family', array_diff_key(self::CHILD, ['relationship' => 1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['relationship']);
    }

    public function test_a_person_who_already_holds_a_health_id_is_not_re_created(): void
    {
        $g = $this->makeGuardian();
        $this->makePatient(['first_name' => 'Amina', 'last_name' => 'Ndi', 'date_of_birth' => '2019-05-04', 'phone_number' => null]);

        $this->as($g['headers'])
            ->postJson('/api/mobile/family', array_merge(self::CHILD, ['phone' => null]))
            ->assertConflict()
            ->assertJsonPath('message', __('flash.family_member_already_registered'));

        // …nor by phone: the number belongs to someone already.
        $this->makePatient(['first_name' => 'Someone', 'last_name' => 'Else', 'date_of_birth' => '1990-01-01', 'phone_number' => '+237677001122']);
        $this->as($g['headers'])
            ->postJson('/api/mobile/family', array_merge(self::CHILD, ['full_name' => 'Brand New', 'date_of_birth' => '2018-01-01']))
            ->assertConflict();

        $this->assertSame(0, FamilyLink::count());
    }

    public function test_registration_needs_a_user_account_behind_the_token(): void
    {
        $loner = $this->makePatient();

        $this->as($this->headersFor($loner))
            ->postJson('/api/mobile/family', self::CHILD)
            ->assertForbidden()
            ->assertJsonPath('message', __('api.no_user_linked'));
    }

    /** The app posts first_name + last_name; the web form posts one full_name. Both register. */
    public function test_the_apps_first_and_last_name_shape_registers_a_child_too(): void
    {
        $g = $this->makeGuardian();

        $payload = self::CHILD;
        unset($payload['full_name']);
        $payload += ['first_name' => 'Amara', 'last_name' => 'Ndi'];
        $payload['sex'] = 'female';

        $response = $this->as($g['headers'])
            ->postJson('/api/mobile/family', $payload)
            ->assertCreated();

        $this->assertSame('Amara', $response->json('data.patient.first_name'));
        $this->assertSame('Ndi', $response->json('data.patient.last_name'));
        $this->assertSame('female', $response->json('data.patient.sex'));

        // Neither name shape given → validation, not a 500.
        $none = self::CHILD;
        unset($none['full_name']);
        $this->as($g['headers'])
            ->postJson('/api/mobile/family', $none)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'first_name']);
    }
}
