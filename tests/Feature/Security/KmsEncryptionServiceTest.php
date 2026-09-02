<?php
namespace Tests\Feature\Security;

use App\Services\Security\KmsEncryptionService;
use Tests\TestCase;

class KmsEncryptionServiceTest extends TestCase
{
    private KmsEncryptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['kms.driver' => 'local']);
        $this->service = new KmsEncryptionService();
    }

    public function test_encrypt_returns_non_empty_string(): void
    {
        $ciphertext = $this->service->encrypt('CNAMGS-123456789');
        $this->assertNotEmpty($ciphertext);
        $this->assertNotSame('CNAMGS-123456789', $ciphertext);
    }

    public function test_decrypt_roundtrip(): void
    {
        $plaintext  = 'sensitive-patient-data';
        $ciphertext = $this->service->encrypt($plaintext);
        $this->assertSame($plaintext, $this->service->decrypt($ciphertext));
    }

    public function test_encrypt_produces_different_ciphertext_each_call(): void
    {
        $first  = $this->service->encrypt('same-input');
        $second = $this->service->encrypt('same-input');
        $this->assertNotSame($first, $second);
    }

    public function test_decrypt_throws_on_tampered_ciphertext(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->decrypt('not-valid-base64-ciphertext!!');
    }

    public function test_re_encrypt_changes_ciphertext(): void
    {
        $plaintext = 'rotate-me';
        $old       = $this->service->encrypt($plaintext);
        $new       = $this->service->reEncrypt($old);
        $this->assertSame($plaintext, $this->service->decrypt($new));
        $this->assertNotSame($old, $new);
    }
}
