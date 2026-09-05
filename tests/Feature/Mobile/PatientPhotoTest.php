<?php

namespace Tests\Feature\Mobile;

use App\Models\Patient;
use App\Models\PatientAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The patient's own photo: set from the app, carried on /mobile/me and the
 * Health ID card as a signed link, served only through that link, removable.
 */
class PatientPhotoTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->patient = Patient::factory()->create([
            'health_id'  => 'CM-HID-TEST-PHOT-0001',
            'first_name' => 'Amara',
            'last_name'  => 'Ndi',
            'is_demo'    => (bool) config('demo.enabled'),
        ]);

        $this->token = 'pat_' . Str::random(40);
        PatientAccessToken::create([
            'patient_id'   => $this->patient->id,
            'token_hash'   => Hash::make($this->token),
            'token_prefix' => substr($this->token, 0, 12),
            'expires_at'   => now()->addDays(30),
        ]);
    }

    private function asPatient(): static
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->token}"]);
    }

    public function test_profile_has_no_photo_link_until_one_is_set(): void
    {
        $this->asPatient()->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('photo_url', null);
    }

    public function test_upload_stores_a_square_jpeg_and_returns_a_signed_link_that_serves_it(): void
    {
        $upload = UploadedFile::fake()->image('me.png', 900, 600);

        $response = $this->asPatient()->post('/api/mobile/me/photo', ['photo' => $upload], ['Accept' => 'application/json']);
        $response->assertOk();

        $url = $response->json('photo_url');
        $this->assertIsString($url);
        $this->assertStringContainsString('/api/mobile/patients/' . $this->patient->id . '/photo', $url);
        $this->assertStringContainsString('signature=', $url);

        $this->patient->refresh();
        $this->assertNotNull($this->patient->photo_path);
        $this->assertSame('image/jpeg', $this->patient->photo_mime);
        Storage::disk('local')->assertExists($this->patient->photo_path);

        // Stored as a 512×512 JPEG regardless of what came in.
        $stored = Storage::disk('local')->get($this->patient->photo_path);
        $info   = getimagesizefromstring($stored);
        $this->assertSame([512, 512], [$info[0], $info[1]]);
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);

        // The signed link serves it without a bearer token (an <img> has none)…
        $served = $this->get($url);
        $served->assertOk();
        $served->assertHeader('Content-Type', 'image/jpeg');
        $served->assertHeader('X-Content-Type-Options', 'nosniff');

        // …and the same path without the signature does not.
        $this->get('/api/mobile/patients/' . $this->patient->id . '/photo')->assertStatus(403);

        // /mobile/me and the Health ID card now carry the link.
        $this->asPatient()->getJson('/api/mobile/me')->assertOk()->assertJsonPath('photo_url', fn ($v) => is_string($v) && str_contains($v, 'signature='));
        $this->asPatient()->getJson('/api/mobile/health-id-card')->assertOk()->assertJsonPath('photo_url', fn ($v) => is_string($v));
    }

    public function test_a_replacement_deletes_the_previous_file(): void
    {
        $this->asPatient()->post('/api/mobile/me/photo', ['photo' => UploadedFile::fake()->image('a.jpg', 400, 400)], ['Accept' => 'application/json'])->assertOk();
        $first = $this->patient->refresh()->photo_path;

        $this->asPatient()->post('/api/mobile/me/photo', ['photo' => UploadedFile::fake()->image('b.jpg', 400, 400)], ['Accept' => 'application/json'])->assertOk();
        $second = $this->patient->refresh()->photo_path;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        $this->asPatient()
            ->post('/api/mobile/me/photo', ['photo' => UploadedFile::fake()->create('notes.pdf', 120, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertNull($this->patient->refresh()->photo_path);
    }

    public function test_remove_clears_the_photo_and_deletes_the_file(): void
    {
        $this->asPatient()->post('/api/mobile/me/photo', ['photo' => UploadedFile::fake()->image('a.jpg', 400, 400)], ['Accept' => 'application/json'])->assertOk();
        $path = $this->patient->refresh()->photo_path;

        $this->asPatient()->deleteJson('/api/mobile/me/photo')->assertOk()->assertJsonPath('photo_url', null);

        $this->assertNull($this->patient->refresh()->photo_path);
        Storage::disk('local')->assertMissing($path);
        $this->asPatient()->getJson('/api/mobile/me')->assertJsonPath('photo_url', null);
    }

    public function test_photo_routes_require_the_patient_token(): void
    {
        $this->postJson('/api/mobile/me/photo')->assertStatus(401);
        $this->deleteJson('/api/mobile/me/photo')->assertStatus(401);
    }
}
