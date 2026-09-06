<?php
namespace Tests\Feature\Portal;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FamilyLinkModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_link_can_be_created(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);

        $link = FamilyLink::create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'relationship'        => 'parent',
            'access_level'        => 'full',
            'status'              => 'active',
            'created_by'          => 'self_registered',
        ]);

        $this->assertDatabaseHas('family_links', ['id' => $link->id]);
        $this->assertEquals($guardian->id, $link->guardianUser->id);
        $this->assertEquals($dependent->id, $link->dependentPatient->id);
    }

    public function test_active_scope_excludes_revoked(): void
    {
        $guardian  = User::factory()->create();
        $dep1 = Patient::factory()->create(['is_demo' => false]);
        $dep2 = Patient::factory()->create(['is_demo' => false]);

        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dep1->id,
            'status'              => 'active',
        ]);
        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dep2->id,
            'status'              => 'revoked',
        ]);

        $this->assertEquals(1, FamilyLink::active()->where('guardian_user_id', $guardian->id)->count());
    }

    public function test_is_expired_by_age_returns_true_when_grace_period_passed(): void
    {
        $link = FamilyLink::factory()->make([
            'age_transition_expires_at' => now()->subDay(),
        ]);
        $this->assertTrue($link->isExpiredByAge());
    }

    public function test_is_expired_by_age_returns_false_when_no_expiry(): void
    {
        $link = FamilyLink::factory()->make(['age_transition_expires_at' => null]);
        $this->assertFalse($link->isExpiredByAge());
    }

    public function test_unique_constraint_prevents_duplicate_links(): void
    {
        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);

        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
        ]);
    }

    public function test_can_relink_after_revoke(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Partial unique index not supported on SQLite — constraint only lifted on PostgreSQL via migration.');
        }

        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);

        // Create and revoke a link
        FamilyLink::factory()->create([
            'guardian_user_id'    => $guardian->id,
            'dependent_patient_id'=> $dependent->id,
            'status'              => 'revoked',
        ]);

        // Re-linking the same pair after revoke must succeed
        $newLink = FamilyLink::create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'relationship'         => 'caregiver',
            'access_level'         => 'full',
            'status'               => 'active',
            'created_by'           => 'self_registered',
        ]);

        $this->assertDatabaseHas('family_links', ['id' => $newLink->id, 'status' => 'active']);
    }

    public function test_notification_pref_for_returns_default_when_prefs_empty(): void
    {
        $link = FamilyLink::factory()->make(['notification_prefs' => []]);
        $this->assertTrue($link->notificationPrefFor('lab_result', 'portal'));
        $this->assertTrue($link->notificationPrefFor('lab_result', 'email'));
        $this->assertFalse($link->notificationPrefFor('lab_result', 'sms'));
        $this->assertTrue($link->notificationPrefFor('consent_request', 'sms'));
    }

    public function test_notification_pref_for_returns_override_when_set(): void
    {
        $link = FamilyLink::factory()->make([
            'notification_prefs' => ['lab_result' => ['portal' => false, 'email' => false, 'sms' => true]],
        ]);
        $this->assertFalse($link->notificationPrefFor('lab_result', 'portal'));
        $this->assertFalse($link->notificationPrefFor('lab_result', 'email'));
        $this->assertTrue($link->notificationPrefFor('lab_result', 'sms'));
        // Unoverridded event still uses default
        $this->assertTrue($link->notificationPrefFor('appointment', 'portal'));
    }

    public function test_notification_pref_for_returns_false_for_unknown_event(): void
    {
        $link = FamilyLink::factory()->make(['notification_prefs' => []]);
        $this->assertFalse($link->notificationPrefFor('unknown_event', 'email'));
    }

    // ── access_level vocabulary ──────────────────────────────────────────────

    public function test_access_level_is_read_as_the_canonical_pair_whatever_an_old_row_says(): void
    {
        $this->assertSame('read_only', FamilyLink::normalizeAccessLevel('view_only'));
        $this->assertSame('full', FamilyLink::normalizeAccessLevel('guardian'));
        $this->assertSame('full', FamilyLink::normalizeAccessLevel('FULL'));
        $this->assertSame('read_only', FamilyLink::normalizeAccessLevel('read_only'));
        $this->assertSame('read_only', FamilyLink::normalizeAccessLevel(null), 'Unknown fails closed.');
        $this->assertSame('read_only', FamilyLink::normalizeAccessLevel('admin'), 'Unknown fails closed.');

        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);

        // Written raw, past the mutator, the way the first app release wrote it.
        $id = (string) \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('family_links')->insert([
            'id' => $id, 'guardian_user_id' => $guardian->id, 'dependent_patient_id' => $dependent->id,
            'relationship' => 'parent', 'access_level' => 'view_only', 'status' => 'active',
            'created_by' => 'guardian_invited', 'notification_prefs' => '{}',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $link = FamilyLink::findOrFail($id);
        $this->assertSame('read_only', $link->access_level);
        $this->assertTrue($link->isReadOnly());
        $this->assertFalse($link->isFullAccess());
        $this->assertSame('read_only', $link->toArray()['access_level']);
    }

    public function test_setting_a_legacy_access_level_stores_the_canonical_value(): void
    {
        $link = FamilyLink::factory()->create(['access_level' => 'guardian']);
        $this->assertDatabaseHas('family_links', ['id' => $link->id, 'access_level' => 'full']);
        $this->assertTrue($link->fresh()->isFullAccess());

        $link->update(['access_level' => 'view_only']);
        $this->assertDatabaseHas('family_links', ['id' => $link->id, 'access_level' => 'read_only']);
    }

    public function test_grants_access_requires_active_and_not_age_expired(): void
    {
        $this->assertTrue(FamilyLink::factory()->make(['status' => 'active'])->grantsAccess());
        $this->assertFalse(FamilyLink::factory()->make(['status' => 'active', 'age_transition_expires_at' => now()->subDay()])->grantsAccess());
        $this->assertTrue(FamilyLink::factory()->make(['status' => 'active', 'age_transition_expires_at' => now()->addDay()])->grantsAccess());
        $this->assertFalse(FamilyLink::factory()->make(['status' => 'pending_invite'])->grantsAccess());
        $this->assertFalse(FamilyLink::factory()->make(['status' => 'revoked'])->grantsAccess());
    }

    public function test_dependent_consented_at_is_a_datetime(): void
    {
        $link = FamilyLink::factory()->create(['dependent_consented_at' => '2026-09-06 10:00:00', 'created_by' => 'invite_accepted']);
        $this->assertInstanceOf(\Carbon\CarbonInterface::class, $link->fresh()->dependent_consented_at);
    }

    // ── unique index ─────────────────────────────────────────────────────────

    public function test_can_relink_after_cancel_and_after_decline(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Partial unique index not supported on SQLite.');
        }

        $guardian  = User::factory()->create();
        $dependent = Patient::factory()->create(['is_demo' => false]);

        foreach (['cancelled', 'declined'] as $terminal) {
            FamilyLink::factory()->create([
                'guardian_user_id' => $guardian->id, 'dependent_patient_id' => $dependent->id, 'status' => $terminal,
            ]);
        }

        // Two terminal rows for the pair, and a live one on top: the index only
        // guards rows that still mean something.
        $live = FamilyLink::factory()->create([
            'guardian_user_id' => $guardian->id, 'dependent_patient_id' => $dependent->id, 'status' => 'pending_invite',
        ]);

        $this->assertDatabaseHas('family_links', ['id' => $live->id, 'status' => 'pending_invite']);
        $this->assertSame(3, FamilyLink::where('guardian_user_id', $guardian->id)->count());

        // …but a second live row is still refused.
        $this->expectException(\Illuminate\Database\QueryException::class);
        FamilyLink::factory()->create([
            'guardian_user_id' => $guardian->id, 'dependent_patient_id' => $dependent->id, 'status' => 'active',
        ]);
    }
}
