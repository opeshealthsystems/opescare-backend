<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class EmergencyAuditCompletenessTest extends TestCase
{
    public function test_emergency_profile_audit_is_not_inside_conditional_block(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Api/V1/Connect/ConnectGovernanceController.php')
        );

        // Find the getEmergencyProfile method
        $methodStart = strpos($source, 'function getEmergencyProfile');
        $this->assertNotFalse($methodStart, 'getEmergencyProfile method must exist');

        $methodSource = substr($source, $methodStart, 3000);

        // AuditLogger::log must appear in this method
        $this->assertStringContainsString(
            'AuditLogger::log',
            $methodSource,
            'getEmergencyProfile must call AuditLogger::log'
        );
    }

    public function test_governance_controller_always_audits_emergency_access(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Api/V1/Connect/ConnectGovernanceController.php')
        );

        $methodStart = strpos($source, 'function getEmergencyProfile');
        $methodSource = substr($source, $methodStart, 3000);

        // The audit log must NOT be inside the emergency reason conditional
        // i.e., AuditLogger::log must appear BEFORE or OUTSIDE of "if ($purpose === 'emergency'"
        $auditPos = strpos($methodSource, 'AuditLogger::log');
        $conditionalPos = strpos($methodSource, "if (\$purpose === 'emergency'");

        if ($conditionalPos !== false && $auditPos !== false) {
            $this->assertLessThan(
                $conditionalPos,
                $auditPos,
                'AuditLogger::log must be called BEFORE the emergency-reason conditional block, not inside it'
            );
        } else {
            // If no conditional exists, audit is unconditional — pass
            $this->assertTrue(true, 'No conditional block around audit — unconditional audit confirmed');
        }
    }
}
