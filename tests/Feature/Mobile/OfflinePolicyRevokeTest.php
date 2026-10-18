<?php

namespace Tests\Feature\Mobile;

use App\Models\AuditEvent;
use App\Models\LocalCachePolicy;
use App\Models\OfflineAuditEvent;

/**
 * POST /api/mobile/offline/policies/{id}/revoke — a patient revokes their own
 * offline cache policy server-side; later queue writes against it are refused.
 */
class OfflinePolicyRevokeTest extends FamilyTestCase
{
    private function createPolicy(array $headers): string
    {
        return $this->as($headers)->postJson('/api/mobile/offline/policies', [
            'device_id' => 'device-1',
            'allowed_scopes' => ['demographics'],
        ])->assertCreated()->json('data.id');
    }

    public function test_owner_revokes_policy_and_queue_is_then_rejected(): void
    {
        $g = $this->makeGuardian();
        $id = $this->createPolicy($g['headers']);

        $this->as($g['headers'])->postJson("/api/mobile/offline/policies/{$id}/queue", ['payload' => ['a' => 1]])
            ->assertCreated();

        $this->as($g['headers'])->postJson("/api/mobile/offline/policies/{$id}/revoke")
            ->assertOk()
            ->assertJsonPath('data.status', 'revoked');

        $policy = LocalCachePolicy::find($id);
        $this->assertSame('revoked', $policy->status);
        $this->assertNotNull($policy->revoked_at);
        $this->assertSame($g['user']->id, $policy->revoked_by);

        $this->as($g['headers'])->postJson("/api/mobile/offline/policies/{$id}/queue", ['payload' => ['b' => 2]])
            ->assertStatus(422)
            ->assertJsonPath('message', 'OFFLINE_POLICY_NOT_ACTIVE');
    }

    public function test_revoke_is_idempotent_and_audited_once(): void
    {
        $g = $this->makeGuardian();
        $id = $this->createPolicy($g['headers']);

        $first = $this->as($g['headers'])->postJson("/api/mobile/offline/policies/{$id}/revoke")->assertOk()->json('data.revoked_at');
        $second = $this->as($g['headers'])->postJson("/api/mobile/offline/policies/{$id}/revoke")->assertOk()->json('data.revoked_at');

        $this->assertNotNull($first);
        $this->assertSame($first, $second);
        $this->assertSame(1, AuditEvent::where('resource_id', $id)->where('action_type', 'policy_revoke')->count());
        $this->assertSame(1, OfflineAuditEvent::where('local_cache_policy_id', $id)->where('event_type', 'policy_revoked')->count());
    }

    public function test_cannot_revoke_another_patients_policy(): void
    {
        $owner = $this->makeGuardian();
        $other = $this->makeGuardian();
        $id = $this->createPolicy($owner['headers']);

        $this->as($other['headers'])->postJson("/api/mobile/offline/policies/{$id}/revoke")->assertNotFound();

        $this->assertSame('active', LocalCachePolicy::find($id)->status);
    }

    public function test_unauthenticated_revoke_is_refused(): void
    {
        $g = $this->makeGuardian();
        $id = $this->createPolicy($g['headers']);

        $this->flushHeaders()->postJson("/api/mobile/offline/policies/{$id}/revoke")->assertUnauthorized();
        $this->assertSame('active', LocalCachePolicy::find($id)->status);
    }
}
