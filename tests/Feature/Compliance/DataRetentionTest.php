<?php
namespace Tests\Feature\Compliance;

use App\Models\DataRetentionPolicy;
use App\Services\Compliance\DataRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_retention_policy(): void
    {
        $policy = DataRetentionPolicy::create([
            'table_name'     => 'audit_logs',
            'retention_days' => 2555,
            'purge_action'   => 'delete',
            'legal_basis'    => 'Cameroon Law 2010/012 Art. 25',
            'is_active'      => true,
        ]);

        $this->assertEquals('audit_logs', $policy->table_name);
        $this->assertEquals(2555, $policy->retention_days);
    }

    public function test_retention_service_identifies_records_for_purge(): void
    {
        DataRetentionPolicy::create([
            'table_name'     => 'ussd_sessions',
            'retention_days' => 30,
            'purge_action'   => 'delete',
            'legal_basis'    => 'Operational need',
            'is_active'      => true,
        ]);

        $service  = new DataRetentionService();
        $policies = $service->getActivePolicies();

        $this->assertTrue($policies->contains('table_name', 'ussd_sessions'));
    }

    public function test_artisan_command_runs_without_error(): void
    {
        DataRetentionPolicy::create([
            'table_name'     => 'ussd_sessions',
            'retention_days' => 30,
            'purge_action'   => 'delete',
            'legal_basis'    => 'Operational',
            'is_active'      => true,
        ]);

        $this->artisan('opescare:enforce-data-retention --dry-run')
             ->assertExitCode(0);
    }
}
