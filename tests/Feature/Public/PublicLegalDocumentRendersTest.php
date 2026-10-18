<?php

/*
 * Copyright (c) 2026 OPES Health Systems. All rights reserved.
 */

namespace Tests\Feature\Public;

use App\Modules\Legal\Services\LegalDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A published legal document renders on its public page, sanitised.
 *
 * Guards the public legal view, which once referenced the sanitiser with a
 * mangled class name: every page with a current version answered 500 while
 * the index (no version rendered) still looked healthy.
 */
class PublicLegalDocumentRendersTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_document_renders_and_is_sanitised(): void
    {
        $service  = app(LegalDocumentService::class);
        $document = $service->ensureDocument('privacy-policy', 'Privacy Policy', 'privacy');

        $service->publishVersion(
            document: $document,
            version: '1.0',
            contentHtml: '<h2>Our promise</h2><p>We never sell data.</p><script>alert(1)</script>',
            publishedBy: null,
        );

        $this->get(route('public.legal.show', 'privacy-policy'))
            ->assertOk()
            ->assertSee('Our promise')
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
