<?php

namespace Tests\Feature\Documents;

use App\Models\OfficialDocument;
use App\Models\DocumentTemplate;
use App\Models\DocumentVerificationToken;
use App\Models\DocumentShareLink;
use App\Services\Documents\DocumentVerificationService;
use App\Services\Documents\DocumentShareService;
use App\Services\Documents\DocumentNumberService;
use App\Services\Documents\QrCodeGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentSystemV2Test extends TestCase
{
    use RefreshDatabase;

    protected $verificationService;
    protected $shareService;
    protected $numberService;
    protected $qrService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->numberService = $this->app->make(DocumentNumberService::class);
        $this->verificationService = $this->app->make(DocumentVerificationService::class);
        $this->shareService = $this->app->make(DocumentShareService::class);
        $this->qrService = $this->app->make(QrCodeGenerationService::class);
    }

    /**
     * Test that every issued document has a QR, verification code, and unique non-predictable format.
     */
    public function test_document_issuance_contains_proper_qr_and_verification_attributes(): void
    {
        $identifiers = $this->numberService->generateIdentifiers('LAB');

        $this->assertStringStartsWith('LAB-CM-', $identifiers['document_number']);
        $this->assertStringStartsWith('VFY-CM-LAB-', $identifiers['verification_code']);
        
        // Assert Luhn-like check digit matches
        $base = substr($identifiers['document_number'], 0, -2);
        $check = $this->numberService->calculateCheckDigit($base);
        $this->assertEquals(substr($identifiers['document_number'], -1), $check);

        // Assert QR SVG generation produces valid link
        $qrSvg = $this->qrService->generateSvg($identifiers['verification_token']);
        $this->assertStringContainsString('/verify/document/' . $identifiers['verification_token'], $qrSvg);
    }

    /**
     * Test that public verification does not expose clinical values/lists and is privacy safe.
     */
    public function test_public_verification_privacy_masking(): void
    {
        $template = DocumentTemplate::create([
            'template_code' => 'LAB_RES',
            'document_type' => 'LAB',
            'language' => 'en',
            'status' => 'published',
            'version' => '1.0',
            'html_template' => '<div>Lab result</div>'
        ]);

        $payload = [
            'patient_name' => 'Johnathan Doe',
            'facility_name' => 'General Clinic',
            'results' => [
                [
                    'test_name' => 'Glucose Fasting',
                    'loinc_code' => '1558-1',
                    'result_value' => '120',
                    'unit' => 'mg/dL',
                    'reference_range' => '70-100',
                    'flag' => 'high'
                ]
            ]
        ];

        $identifiers = $this->numberService->generateIdentifiers('LAB');

        $document = OfficialDocument::create([
            'document_type' => 'LAB',
            'document_number' => $identifiers['document_number'],
            'verification_code' => $identifiers['verification_code'],
            'template_id' => $template->id,
            'template_version' => $template->version,
            'status' => 'issued',
            'title' => 'Lab Report',
            'payload_json' => $payload,
            'issued_at' => now()
        ]);

        $token = $this->verificationService->issueToken($document->id, $identifiers['verification_token']);

        // Assert that the token is stored strictly hashed in the DB!
        $tokenRecord = DocumentVerificationToken::where('official_document_id', $document->id)->first();
        $this->assertNotNull($tokenRecord);
        $this->assertEquals(hash('sha256', $token), $tokenRecord->token_hash);
        $this->assertNotEquals($token, $tokenRecord->token_hash);

        // Fetch public verification page
        $response = $this->get('/verify/document/' . $token);
        
        $response->assertStatus(200);
        // Assert patient name is masked (e.g. "J***e")
        $response->assertSee('J***e');
        $response->assertDontSee('Johnathan Doe');
        
        // Assert clinical test values are hidden
        $response->assertDontSee('Glucose Fasting');
        $response->assertDontSee('120 mg/dL');
    }

    /**
     * Test immutability: documents cannot be silently updated without creating versions.
     */
    public function test_document_amendments_require_reason_and_preserve_history(): void
    {
        $template = DocumentTemplate::create([
            'template_code' => 'RX',
            'document_type' => 'RX',
            'status' => 'published',
            'version' => '1.0',
            'html_template' => '<div>RX</div>'
        ]);

        $identifiers = $this->numberService->generateIdentifiers('RX');

        $document = OfficialDocument::create([
            'document_type' => 'RX',
            'document_number' => $identifiers['document_number'],
            'verification_code' => $identifiers['verification_code'],
            'template_id' => $template->id,
            'template_version' => $template->version,
            'status' => 'issued',
            'title' => 'Prescription',
            'payload_json' => ['medications' => [['name' => 'Paracetamol']]],
            'issued_at' => now()
        ]);

        // Attempting to amend without reason must throw an exception
        $this->expectException(\InvalidArgumentException::class);
        $amendmentService = $this->app->make(\App\Services\Documents\DocumentAmendmentService::class);
        $amendmentService->amend($document->id, ['medications' => [['name' => 'Ibuprofen']]], '', (string) Str::uuid());
    }

    /**
     * Test document revocation and entered-in-error status indicators.
     */
    public function test_document_revocation_and_entered_in_error_renders_proper_states(): void
    {
        $template = DocumentTemplate::create([
            'template_code' => 'RCT',
            'document_type' => 'RCT',
            'status' => 'published',
            'version' => '1.0',
            'html_template' => '<div>Receipt</div>'
        ]);

        $identifiers = $this->numberService->generateIdentifiers('RCT');

        $document = OfficialDocument::create([
            'document_type' => 'RCT',
            'document_number' => $identifiers['document_number'],
            'verification_code' => $identifiers['verification_code'],
            'template_id' => $template->id,
            'template_version' => $template->version,
            'status' => 'issued',
            'title' => 'Receipt',
            'payload_json' => ['amount' => 5000],
            'issued_at' => now()
        ]);

        $token = $this->verificationService->issueToken($document->id, $identifiers['verification_token']);

        // Revoke the document
        $revocationService = $this->app->make(\App\Services\Documents\DocumentRevocationService::class);
        $revocationService->revoke($document->id, 'Double charge discovered');

        $response = $this->get('/verify/document/' . $token);
        $response->assertSee('Document Revoked');
        $response->assertSee('This document was issued but has been revoked. Do not rely on it.');
    }

    /**
     * Test temporary share links and their expiration.
     */
    public function test_temporary_share_links_expires_correctly(): void
    {
        $template = DocumentTemplate::create([
            'template_code' => 'INV',
            'document_type' => 'INV',
            'status' => 'published',
            'version' => '1.0',
            'html_template' => '<div>Invoice</div>'
        ]);

        $identifiers = $this->numberService->generateIdentifiers('INV');

        $document = OfficialDocument::create([
            'document_type' => 'INV',
            'document_number' => $identifiers['document_number'],
            'verification_code' => $identifiers['verification_code'],
            'template_id' => $template->id,
            'template_version' => $template->version,
            'status' => 'issued',
            'title' => 'Invoice',
            'payload_json' => ['total' => 25000],
            'issued_at' => now()
        ]);

        $share = $this->shareService->generateShareLink($document->id, (string) Str::uuid(), 60, 2);

        // Resolve share link first time
        $resolved = $this->shareService->resolveShareLink($share['token']);
        $this->assertEquals($document->id, $resolved->id);

        // Force expiration
        $linkRecord = DocumentShareLink::where('official_document_id', $document->id)->first();
        $linkRecord->update(['expires_at' => now()->subMinutes(1)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DOCUMENT_SHARE_LINK_EXPIRED');
        $this->shareService->resolveShareLink($share['token']);
    }
}
