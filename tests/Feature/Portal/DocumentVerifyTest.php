<?php

namespace Tests\Feature\Portal;

use App\Models\DocumentVerification;
use App\Models\Patient;
use App\Services\Patient\DocumentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The QR code on an OpesCare Secure document resolves to a page that says
 * valid / revoked / invalid, shows what the document says about itself, and
 * never leaks the token.
 */
class DocumentVerifyTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private DocumentVerificationService $documents;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::factory()->create([
            'health_id'  => 'CM-HID-TEST-DOCV-0001',
            'first_name' => 'Amara',
            'last_name'  => 'Ndi',
            'is_demo'    => (bool) config('demo.enabled'),
        ]);
        $this->documents = app(DocumentVerificationService::class);
    }

    public function test_a_valid_document_shows_type_reference_holder_and_fingerprint(): void
    {
        $issued = $this->documents->issue($this->patient, DocumentVerificationService::TYPE_MEDICAL_RECORD);
        $this->documents->seal($issued['record'], 'pdf-bytes');

        $this->assertMatchesRegularExpression('/^OC-DOC-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $issued['record']->reference);
        $this->assertStringStartsWith('doc_', $issued['raw_token']);
        $this->assertStringContainsString('/verify/doc/doc_', $issued['verify_url']);

        $response = $this->get($issued['verify_url'])->assertOk();

        $response->assertSee(__('verify.doc_valid_title'))
            ->assertSee(__('verify.doc_type_medical_record'))
            ->assertSee($issued['record']->reference)
            ->assertSee('Amara Ndi')
            ->assertSee('CM-HID-TEST-DOCV-0001')
            ->assertSee(strtoupper(substr(hash('sha256', 'pdf-bytes'), 0, 4)))
            ->assertDontSee($issued['raw_token']);

        $this->assertSame(1, $issued['record']->fresh()->scan_count);
        $this->assertNotNull($issued['record']->fresh()->last_verified_at);
    }

    public function test_a_revoked_document_is_reported_as_withdrawn(): void
    {
        $issued = $this->documents->issue($this->patient, DocumentVerificationService::TYPE_MEDICAL_RECORD);
        $this->documents->revoke($issued['record'], 'Reissued with corrections');

        $this->get($issued['verify_url'])
            ->assertOk()
            ->assertSee(__('verify.doc_revoked_title'))
            ->assertDontSee($issued['record']->reference)
            ->assertDontSee('Amara Ndi');

        $this->assertSame(0, $issued['record']->fresh()->scan_count, 'A refused check must not count as a verification.');
    }

    public function test_an_unknown_or_tampered_token_is_invalid_and_reveals_nothing(): void
    {
        $issued = $this->documents->issue($this->patient, DocumentVerificationService::TYPE_MEDICAL_RECORD);
        $tampered = substr($issued['raw_token'], 0, -4) . 'zzzz';

        foreach (['doc_nosuchrow_secret', $tampered, 'hello', 'qrx_abc_def'] as $token) {
            $this->get(route('verify.document', ['token' => $token]))
                ->assertOk()
                ->assertSee(__('verify.doc_invalid_title'))
                ->assertDontSee($issued['record']->reference)
                ->assertDontSee('Amara Ndi');
        }

        $this->assertSame(1, DocumentVerification::count());
        $this->assertSame(0, $issued['record']->fresh()->scan_count);
    }
}
