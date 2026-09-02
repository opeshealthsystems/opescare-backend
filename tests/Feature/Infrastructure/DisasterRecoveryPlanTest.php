<?php
namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

class DisasterRecoveryPlanTest extends TestCase
{
    public function test_dr_plan_document_exists(): void
    {
        $this->assertFileExists(base_path('docs/disaster-recovery-plan.md'));
    }

    public function test_dr_plan_contains_rto_target(): void
    {
        $content = file_get_contents(base_path('docs/disaster-recovery-plan.md'));
        $this->assertStringContainsString('4 hours', $content);
    }

    public function test_dr_plan_contains_rpo_target(): void
    {
        $content = file_get_contents(base_path('docs/disaster-recovery-plan.md'));
        $this->assertStringContainsString('1 hour', $content);
    }

    public function test_dr_plan_contains_backup_strategy(): void
    {
        $content = file_get_contents(base_path('docs/disaster-recovery-plan.md'));
        $this->assertStringContainsString('Backup Strategy', $content);
    }
}
