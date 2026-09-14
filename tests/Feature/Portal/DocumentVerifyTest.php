<?php

namespace Tests\Feature\Portal;

use App\Models\DocumentVerification;
use App\Models\DocumentVerificationToken;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Services\Documents\OfficialDocumentTokenService;
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

    // ── The other token family: `vdt_…` from document_verification_tokens ──

    private function officialDocument(array $overrides = []): OfficialDocument
    {
        return OfficialDocument::factory()->create(array_merge([
            'document_type'   => 'medical_record',
            'document_number' => 'RX-CM-2026-TEST-7',
            'patient_id'      => $this->patient->id,
            'status'          => 'issued',
            'payload_hash'    => hash('sha256', 'payload'),
            'issued_at'       => now()->subDay(),
            'is_demo'         => (bool) config('demo.enabled'),
        ], $overrides));
    }

    public function test_a_vdt_token_on_an_issued_official_document_verifies_as_genuine(): void
    {
        $document = $this->officialDocument();
        $raw = app(OfficialDocumentTokenService::class)->issueToken($document->id);

        $this->assertStringStartsWith('vdt_', $raw);
        $this->assertSame(1, DocumentVerificationToken::withoutGlobalScopes()->count());

        $this->get(route('verify.document', ['token' => $raw]))
            ->assertOk()
            ->assertSee(__('verify.doc_valid_title'))
            ->assertSee(__('verify.doc_type_medical_record'))
            ->assertSee('RX-CM-2026-TEST-7')
            ->assertSee('Amara Ndi')
            ->assertSee('CM-HID-TEST-DOCV-0001')
            ->assertSee(strtoupper(substr(hash('sha256', 'payload'), 0, 4)))
            ->assertDontSee($raw);

        $this->assertNotNull(DocumentVerificationToken::withoutGlobalScopes()->first()->last_used_at);
        $this->assertSame(0, DocumentVerification::count(), 'A vdt_ check must not create a document_verifications row.');
    }

    public function test_a_vdt_token_whose_document_was_withdrawn_reads_as_withdrawn_not_forged(): void
    {
        $document = $this->officialDocument(['status' => 'revoked', 'revoked_at' => now()]);
        $raw = app(OfficialDocumentTokenService::class)->issueToken($document->id);

        $this->get(route('verify.document', ['token' => $raw]))
            ->assertOk()
            ->assertSee(__('verify.doc_revoked_title'))
            ->assertDontSee(__('verify.doc_invalid_title'))
            ->assertDontSee('RX-CM-2026-TEST-7')
            ->assertDontSee('Amara Ndi');
    }

    public function test_an_expired_vdt_token_reads_as_expired(): void
    {
        $document = $this->officialDocument();
        $raw = app(OfficialDocumentTokenService::class)->issueToken($document->id, null, 5);
        DocumentVerificationToken::withoutGlobalScopes()->first()->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->get(route('verify.document', ['token' => $raw]))
            ->assertOk()
            ->assertSee(__('verify.doc_expired_title'))
            ->assertDontSee('RX-CM-2026-TEST-7')
            ->assertDontSee('Amara Ndi');
    }

    public function test_an_unknown_vdt_token_is_the_same_not_found_answer_as_any_other_garbage(): void
    {
        $this->officialDocument();

        foreach (['vdt_nosuchtoken', 'vdt_', 'vdt_' . str_repeat('x', 10)] as $token) {
            $this->get(route('verify.document', ['token' => $token]))
                ->assertOk()
                ->assertSee(__('verify.doc_invalid_title'))
                ->assertDontSee('RX-CM-2026-TEST-7')
                ->assertDontSee('Amara Ndi');
        }
    }

    public function test_a_doc_token_still_verifies_after_the_vdt_family_was_added(): void
    {
        $issued = $this->documents->issue($this->patient, DocumentVerificationService::TYPE_MEDICAL_RECORD);

        $this->get($issued['verify_url'])
            ->assertOk()
            ->assertSee(__('verify.doc_valid_title'))
            ->assertSee($issued['record']->reference);

        $this->assertSame(1, $issued['record']->fresh()->scan_count);
    }
}
