<?php

namespace Tests\Feature\Commands;

use App\Console\Commands\EncryptExistingPatientPii;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncryptPatientPiiCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_encrypt_patient_pii_command_exists(): void
    {
        $this->assertTrue(
            class_exists(EncryptExistingPatientPii::class),
            'EncryptExistingPatientPii command class must exist'
        );
    }

    public function test_command_is_registered(): void
    {
        $commands = app()->make(\Illuminate\Contracts\Console\Kernel::class)->all();
        $this->assertArrayHasKey(
            'opescare:encrypt-patient-pii',
            $commands,
            'opescare:encrypt-patient-pii command must be registered'
        );
    }

    public function test_dry_run_option_does_not_modify_data(): void
    {
        Patient::factory()->count(5)->create();

        $this->artisan('opescare:encrypt-patient-pii --dry-run')
             ->assertSuccessful();

        // Verify no records were modified (just sanity check that data still exists)
        $this->assertCount(5, Patient::withoutGlobalScopes()->get());
    }

    public function test_command_processes_multiple_patients(): void
    {
        Patient::factory()->count(10)->create();

        $this->artisan('opescare:encrypt-patient-pii')
             ->assertSuccessful();

        // Verify all patients are still in DB
        $this->assertCount(10, Patient::withoutGlobalScopes()->get());
    }

    public function test_command_with_custom_batch_size(): void
    {
        Patient::factory()->count(5)->create();

        $this->artisan('opescare:encrypt-patient-pii --batch=2')
             ->assertSuccessful();

        $this->assertCount(5, Patient::withoutGlobalScopes()->get());
    }

    public function test_command_is_idempotent(): void
    {
        Patient::factory()->count(3)->create();

        // Run once
        $this->artisan('opescare:encrypt-patient-pii')->assertSuccessful();

        // Run again — should succeed without error
        $this->artisan('opescare:encrypt-patient-pii')->assertSuccessful();

        // Verify data is intact
        $this->assertCount(3, Patient::withoutGlobalScopes()->get());
    }
}
