<?php

namespace Tests\Feature\Blood;

use App\Models\BloodDonor;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The donor register, and the line it must never cross.
 *
 * The reference screen (SB-02) is a browsable list of named people with their
 * blood groups. Backed by `patients` that would be enumeration of patients and
 * of health data, which this platform forbids. It is backed by a consent
 * register instead, and these tests are what keeps that true: they assert not
 * only that search works, but that everyone who did not consent is invisible
 * and that nothing identifying leaks into a result.
 */
class BloodDonorRegisterTest extends TestCase
{
    use RefreshDatabase;

    private function actingPatient(): array
    {
        $patient = Patient::factory()->create();
        $plain = 'test_' . Str::random(40);

        PatientAccessToken::create([
            'patient_id'        => $patient->id,
            'token_prefix'      => substr($plain, 0, 12),
            'token_hash'        => Hash::make($plain),
            'token_lookup_hash' => hash('sha256', $plain),
            'name'              => 'test',
            'expires_at'        => now()->addHour(),
        ]);

        return [$patient, ['Authorization' => 'Bearer ' . $plain, 'Accept' => 'application/json']];
    }

    public function test_a_volunteer_joins_and_appears_in_search(): void
    {
        [, $headers] = $this->actingPatient();

        $this->postJson('/api/mobile/blood/donors', [
            'blood_group'     => 'O-',
            'city'            => 'Douala',
            'listing_consent' => true,
        ], $headers)->assertCreated();

        [, $searcher] = $this->actingPatient();

        $response = $this->getJson('/api/mobile/blood/donors?blood_group=O-', $searcher)
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_without_listing_consent_a_volunteer_is_invisible(): void
    {
        [, $headers] = $this->actingPatient();

        $this->postJson('/api/mobile/blood/donors', [
            'blood_group'     => 'O-',
            'city'            => 'Douala',
            // Still a created registration - it simply is not listed.
            'listing_consent' => false,
        ], $headers)->assertCreated();

        [, $searcher] = $this->actingPatient();

        $this->getJson('/api/mobile/blood/donors?blood_group=O-', $searcher)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_paused_volunteer_is_invisible_but_keeps_their_place(): void
    {
        [, $headers] = $this->actingPatient();

        $this->postJson('/api/mobile/blood/donors', [
            'blood_group'     => 'A+',
            'listing_consent' => true,
        ], $headers)->assertCreated();

        $this->postJson('/api/mobile/blood/donors/status', ['status' => 'paused'], $headers)
            ->assertOk();

        [, $searcher] = $this->actingPatient();
        $this->getJson('/api/mobile/blood/donors?blood_group=A%2B', $searcher)
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Still registered, and their consent timestamp survives the pause.
        $this->getJson('/api/mobile/blood/donors/me', $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'paused')
            ->assertJsonPath('data.listed', true);
    }

    public function test_a_search_result_carries_no_identifying_detail(): void
    {
        [$donorPatient, $headers] = $this->actingPatient();
        $donorPatient->update(['first_name' => 'Estelle', 'last_name' => 'Kamga']);

        $this->postJson('/api/mobile/blood/donors', [
            'blood_group'     => 'B+',
            'city'            => 'Douala',
            'listing_consent' => true,
            'share_phone'     => false,
        ], $headers)->assertCreated();

        [, $searcher] = $this->actingPatient();
        $row = $this->getJson('/api/mobile/blood/donors?blood_group=B%2B', $searcher)
            ->assertOk()
            ->json('data.0');

        // Given name and an initial — not the full name printed next to a
        // blood group.
        $this->assertSame('Estelle K.', $row['display_name']);

        // The things the reference draws and this endpoint refuses to hold.
        foreach (['patient_id', 'health_id', 'date_of_birth', 'age', 'distance_km', 'photo_url'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $row, $forbidden . ' must never be exposed');
        }

        // No phone unless separately consented.
        $this->assertNull($row['contact_phone']);
        $this->assertFalse($row['phone_shared']);
    }

    public function test_a_phone_number_appears_only_on_a_second_explicit_consent(): void
    {
        [, $headers] = $this->actingPatient();

        $this->postJson('/api/mobile/blood/donors', [
            'blood_group'     => 'AB+',
            'listing_consent' => true,
            'share_phone'     => true,
            'contact_phone'   => '+237600000123',
        ], $headers)->assertCreated();

        [, $searcher] = $this->actingPatient();
        $row = $this->getJson('/api/mobile/blood/donors?blood_group=AB%2B', $searcher)
            ->assertOk()
            ->json('data.0');

        $this->assertTrue($row['phone_shared']);
        $this->assertSame('+237600000123', $row['contact_phone']);
    }

    public function test_there_is_no_call_that_lists_every_volunteer(): void
    {
        [, $headers] = $this->actingPatient();

        // No blood group: refused outright, rather than answered with everyone.
        $this->getJson('/api/mobile/blood/donors', $headers)
            ->assertStatus(422);
    }

    public function test_withdrawing_removes_the_listing_consent(): void
    {
        [, $headers] = $this->actingPatient();

        $this->postJson('/api/mobile/blood/donors', [
            'blood_group'     => 'O+',
            'listing_consent' => true,
        ], $headers)->assertCreated();

        $this->postJson('/api/mobile/blood/donors/status', ['status' => 'withdrawn'], $headers)
            ->assertOk()
            ->assertJsonPath('data.listed', false);

        $this->assertSame(
            0,
            BloodDonor::query()->searchable()->count(),
            'a withdrawn volunteer must not be searchable',
        );
    }

    public function test_the_register_is_not_reachable_without_authentication(): void
    {
        $this->getJson('/api/mobile/blood/donors?blood_group=O-')->assertUnauthorized();
        $this->postJson('/api/mobile/blood/donors', [])->assertUnauthorized();
    }
}
