<?php

namespace Tests\Feature\Mobile;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'             => 'Amara',
            'last_name'              => 'Nkemtchou',
            'dob'                    => '1994-04-12',
            'sex'                    => 'female',
            'phone'                  => '0698123456',
            'email'                  => 'amara.nkemtchou@example.com',
            'emergency_name'         => 'Paul Nkemtchou',
            'emergency_relationship' => 'Brother',
            'emergency_phone'        => '0698654321',
            'password'               => 'supersecret1',
            'password_confirmation'  => 'supersecret1',
        ], $overrides);
    }

    public function test_register_creates_patient_and_returns_access_token(): void
    {
        Role::firstOrCreate(['name' => 'patient']);

        $response = $this->postJson('/api/mobile/auth/register', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'access_token', 'token_type', 'expires_in', 'patient_id', 'health_id'])
            ->assertJsonFragment(['status' => 'authenticated', 'token_type' => 'Bearer']);

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Amara',
            'last_name'  => 'Nkemtchou',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'amara.nkemtchou@example.com',
        ]);

        $patient = Patient::where('first_name', 'Amara')->where('last_name', 'Nkemtchou')->first();
        $this->assertNotNull($patient);
        $this->assertNotEmpty($patient->health_id);

        $user = User::where('email', 'amara.nkemtchou@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('supersecret1', $user->password));
        $this->assertEquals($patient->id, $user->patient_id);
    }

    public function test_register_rejects_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/mobile/auth/register', $this->validPayload([
            'password_confirmation' => 'somethingelse',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::forceCreate([
            'name'     => 'Existing User',
            'email'    => 'amara.nkemtchou@example.com',
            'password' => Hash::make('whatever123'),
            'status'   => 'active',
        ]);

        $response = $this->postJson('/api/mobile/auth/register', $this->validPayload());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_duplicate_phone_number(): void
    {
        Patient::forceCreate([
            'health_id'     => 'CM-HID-EXST-0001',
            'first_name'    => 'Other',
            'last_name'     => 'Person',
            'date_of_birth' => '1980-01-01',
            'sex'           => 'male',
            'phone_number'  => '0698123456',
            'is_demo'       => false,
        ]);

        $response = $this->postJson('/api/mobile/auth/register', $this->validPayload([
            'email' => 'someone.else@example.com',
        ]));

        $response->assertStatus(409);
    }

    public function test_register_rejects_duplicate_identity(): void
    {
        Patient::forceCreate([
            'health_id'     => 'CM-HID-EXST-0002',
            'first_name'    => 'Amara',
            'last_name'     => 'Nkemtchou',
            'date_of_birth' => '1994-04-12',
            'sex'           => 'female',
            'phone_number'  => '0611111111',
            'is_demo'       => false,
        ]);

        $response = $this->postJson('/api/mobile/auth/register', $this->validPayload([
            'phone' => '0622222222',
            'email' => 'another.amara@example.com',
        ]));

        $response->assertStatus(409);
    }

    public function test_register_rejects_underage_future_dob(): void
    {
        $response = $this->postJson('/api/mobile/auth/register', $this->validPayload([
            'dob' => now()->addYear()->format('Y-m-d'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dob']);
    }

    public function test_register_requires_emergency_contact_fields(): void
    {
        $payload = $this->validPayload();
        unset($payload['emergency_name'], $payload['emergency_relationship'], $payload['emergency_phone']);

        $response = $this->postJson('/api/mobile/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['emergency_name', 'emergency_relationship', 'emergency_phone']);
    }
}
