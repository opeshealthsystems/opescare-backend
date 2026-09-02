<?php
namespace Tests\Feature\Staff;

use App\Models\Facility;
use App\Models\ProviderCredential;
use App\Models\User;
use App\Services\Staff\CredentialingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_provider_credential(): void
    {
        $provider = User::factory()->create();

        $credential = ProviderCredential::create([
            'provider_id'       => $provider->id,
            'credential_type'   => 'medical_license',
            'credential_number' => 'CM-MED-2024-001',
            'issuing_body'      => 'Ordre National des Médecins du Cameroun',
            'issued_date'       => '2024-01-01',
            'expiry_date'       => '2027-01-01',
            'status'            => 'active',
        ]);

        $this->assertEquals('active', $credential->status);
        $this->assertEquals('CM-MED-2024-001', $credential->credential_number);
    }

    public function test_expired_credential_is_flagged(): void
    {
        $provider = User::factory()->create();

        ProviderCredential::create([
            'provider_id'       => $provider->id,
            'credential_type'   => 'medical_license',
            'credential_number' => 'CM-MED-2020-001',
            'issuing_body'      => 'Ordre National des Médecins du Cameroun',
            'issued_date'       => '2020-01-01',
            'expiry_date'       => '2023-01-01',
            'status'            => 'active',
        ]);

        $service = new CredentialingService();
        $expired = $service->getExpiredCredentials();

        $this->assertGreaterThan(0, $expired->count());
    }

    public function test_credentials_expiring_soon_are_identified(): void
    {
        $provider = User::factory()->create();

        ProviderCredential::create([
            'provider_id'       => $provider->id,
            'credential_type'   => 'medical_license',
            'credential_number' => 'CM-MED-2024-099',
            'issuing_body'      => 'Ordre National',
            'issued_date'       => now()->subYear()->toDateString(),
            'expiry_date'       => now()->addDays(25)->toDateString(),
            'status'            => 'active',
        ]);

        $service  = new CredentialingService();
        $expiring = $service->getExpiringWithin(30);

        $this->assertGreaterThan(0, $expiring->count());
    }
}
