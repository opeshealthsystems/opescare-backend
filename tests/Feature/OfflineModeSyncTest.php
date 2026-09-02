<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Modules\Offline\Services\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

class OfflineModeSyncTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    public function test_offline_policy_requires_encryption_and_blocks_full_emr_scope()
    {
        [$facility, $patient, $user] = $this->offlineActors();
        $service = app(SyncService::class);

        $policy = $service->createLocalCachePolicy([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'device_id' => 'device-001',
            'allowed_scopes' => ['demographics', 'appointments'],
        ], $user->id);

        $this->assertTrue($policy->encryption_required);
        $this->assertEquals('AES-256-GCM local database encryption required', $policy->encryption_policy);
        $this->assertEquals(['demographics', 'appointments'], $policy->allowed_scopes);

        $this->expectExceptionMessage('OFFLINE_SCOPE_NOT_ALLOWED');
        $service->createLocalCachePolicy([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'device_id' => 'device-001',
            'allowed_scopes' => ['full_emr'],
        ], $user->id);
    }

    public function test_offline_queue_stores_encrypted_payload_without_plaintext()
    {
        [$facility, $patient, $user] = $this->offlineActors();
        $service = app(SyncService::class);
        $policy = $service->createLocalCachePolicy([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'device_id' => 'device-002',
            'allowed_scopes' => ['demographics'],
        ], $user->id);

        $job = $service->queueEncryptedPayload($policy, [
            'health_id' => 'OPC-OFFLINE',
            'first_name' => 'Offline',
            'last_name' => 'Patient',
        ], $user->id);

        $this->assertStringNotContainsString('OPC-OFFLINE', $job->encrypted_payload);
        $this->assertEquals('queued', $job->status);
        $decrypted = json_decode(Crypt::decryptString($job->encrypted_payload), true);
        $this->assertEquals('OPC-OFFLINE', $decrypted['health_id']);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'offline_queue',
            'resource_id' => $job->id,
            'action_type' => 'queue',
            'actor_id' => $user->id,
        ]);
    }

    public function test_sync_retry_conflict_resolution_and_offline_audit_are_recorded()
    {
        [$facility, $patient, $user] = $this->offlineActors();
        $service = app(SyncService::class);
        $policy = $service->createLocalCachePolicy([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'device_id' => 'device-003',
            'allowed_scopes' => ['demographics'],
        ], $user->id);
        $job = $service->queueEncryptedPayload($policy, ['first_name' => 'Conflict'], $user->id);

        $retry = $service->markSyncFailed($job, 'Network timeout', $user->id);
        $conflict = $service->detectConflict($retry, 'remote_version_changed', $user->id);
        $resolved = $service->resolveConflict($conflict, 'server_wins', $user->id);

        $this->assertEquals(1, $retry->retry_count);
        $this->assertEquals('resolved', $resolved->status);
        $this->assertDatabaseHas('offline_audit_events', [
            'offline_queue_id' => $job->id,
            'event_type' => 'conflict_detected',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'sync_conflict',
            'resource_id' => $conflict->id,
            'action_type' => 'resolve',
        ]);
    }

    public function test_emergency_offline_access_is_limited_and_review_required()
    {
        [$facility, $patient, $user] = $this->offlineActors();

        $policy = app(SyncService::class)->createLocalCachePolicy([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'device_id' => 'device-004',
            'allowed_scopes' => ['emergency_profile'],
            'emergency_access' => true,
        ], $user->id);

        $this->assertTrue($policy->emergency_access);
        $this->assertTrue($policy->review_required);
        $this->assertEquals(['emergency_profile'], $policy->allowed_scopes);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'local_cache_policy',
            'resource_id' => $policy->id,
            'action_type' => 'emergency_policy_create',
            'emergency_override' => true,
        ]);
    }

    public function test_offline_policy_api_returns_queue_metadata_only()
    {
        [$facility, $patient, $user] = $this->offlineActors();

        $mobileHeaders = $this->mobileAuthHeaders($patient);

        $created = $this->withHeaders($mobileHeaders)
            ->postJson('/api/mobile/offline/policies', [
                'patient_id' => $patient->id,
                'facility_id' => $facility->id,
                'device_id' => 'device-005',
                'allowed_scopes' => ['demographics'],
                'actor_id' => $user->id,
            ]);

        $created->assertCreated()
            ->assertJsonPath('data.encryption_required', true)
            ->assertJsonPath('data.allowed_scopes.0', 'demographics');

        $queued = $this->withHeaders($mobileHeaders)
            ->postJson('/api/mobile/offline/policies/'.$created->json('data.id').'/queue', [
                'payload' => ['health_id' => 'OPC-API-OFFLINE'],
                'actor_id' => $user->id,
            ]);

        $queued->assertCreated()
            ->assertJsonMissing(['health_id' => 'OPC-API-OFFLINE'])
            ->assertJsonPath('data.status', 'queued');
    }

    private function offlineActors(): array
    {
        $facility = Facility::create([
            'name' => 'Offline Clinic',
            'type' => 'clinic',
            'status' => 'active',
            'license_number' => 'LIC-OFFLINE',
        ]);
        $patient = Patient::create([
            'health_id' => 'OPC-OFFLINE',
            'country_code' => 'NG',
            'first_name' => 'Offline',
            'last_name' => 'Patient',
            'date_of_birth' => '1990-01-01',
            'sex' => 'female',
            'identity_status' => 'verified',
            'verification_status' => 'verified',
        ]);
        $user = User::create([
            'name' => 'Offline Operator',
            'email' => 'offline-operator@test.com',
            'password' => 'password',
            'primary_facility_id' => $facility->id,
        ]);

        return [$facility, $patient, $user];
    }
}
