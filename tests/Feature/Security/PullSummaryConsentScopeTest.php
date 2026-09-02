<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class PullSummaryConsentScopeTest extends TestCase
{
    public function test_pull_summary_references_consent_grant(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Api/V1/Connect/RecordController.php')
        );

        $this->assertStringContainsString(
            'consent_grant',
            $source,
            'pullSummary must reference consent_grant from request attributes for scope-based filtering'
        );
    }
}
