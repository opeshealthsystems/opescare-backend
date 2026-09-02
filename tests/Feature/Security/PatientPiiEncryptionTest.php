<?php
namespace Tests\Feature\Security;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientPiiEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_date_of_birth_is_stored_encrypted_in_database(): void
    {
        $patient = Patient::factory()->create([
            'date_of_birth' => '1990-04-15',
            'is_demo'       => false,
        ]);

        $raw = DB::table('patients')->where('id', $patient->id)->value('date_of_birth');

        $this->assertNotEquals('1990-04-15', $raw,
            'date_of_birth must be stored encrypted, not as plain text');

        $decrypted = $patient->fresh()->date_of_birth;
        $asString = $decrypted instanceof \Carbon\Carbon
            ? $decrypted->format('Y-m-d')
            : (string) $decrypted;
        $this->assertStringContainsString('1990-04-15', $asString);
    }

    public function test_phone_number_is_stored_encrypted(): void
    {
        $patient = Patient::factory()->create([
            'phone_number' => '+237600123456',
            'is_demo'      => false,
        ]);

        $raw = DB::table('patients')->where('id', $patient->id)->value('phone_number');
        $this->assertNotEquals('+237600123456', $raw, 'phone_number must be stored encrypted');
        $this->assertEquals('+237600123456', $patient->fresh()->phone_number);
    }

    public function test_address_is_stored_encrypted(): void
    {
        $patient = Patient::factory()->create([
            'address' => '123 Main Street, Yaoundé',
            'is_demo' => false,
        ]);

        $raw = DB::table('patients')->where('id', $patient->id)->value('address');
        $this->assertNotEquals('123 Main Street, Yaoundé', $raw, 'address must be stored encrypted');
        $this->assertEquals('123 Main Street, Yaoundé', $patient->fresh()->address);
    }

    public function test_health_id_is_not_encrypted(): void
    {
        $patient = Patient::factory()->create([
            'health_id' => 'OC-CMR-TEST-XXXX',
            'is_demo'   => false,
        ]);

        $raw = DB::table('patients')->where('id', $patient->id)->value('health_id');
        $this->assertEquals('OC-CMR-TEST-XXXX', $raw,
            'health_id must remain unencrypted (it is a lookup key)');
    }
}
