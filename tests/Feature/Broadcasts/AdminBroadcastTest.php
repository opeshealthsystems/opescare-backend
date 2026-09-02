<?php

namespace Tests\Feature\Broadcasts;

use App\Models\User;
use App\Modules\Broadcasts\Models\Broadcast;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminBroadcastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsurePortalAccess::class,
            \App\Http\Middleware\EnsureTwoFactorVerified::class,
            \App\Http\Middleware\RequirePlatformAdmin::class,
            \App\Http\Middleware\RequireFacilityContext::class,
        ]);
    }

    public function test_admin_can_create_a_draft_broadcast(): void
    {
        $admin = User::factory()->create();
        $title = 'Scheduled maintenance '.Str::random(6);

        $this->actingAs($admin)->post(route('portals.admin.broadcasts.store'), [
            'title' => $title,
            'body' => 'The portal will be down 02:00–03:00.',
            'broadcast_type' => 'maintenance',
            'target_type' => 'all_users',
            'priority' => 'high',
        ])->assertSessionHasNoErrors()->assertRedirect(route('portals.admin.broadcasts'));

        $this->assertDatabaseHas('broadcasts', [
            'title' => $title, 'broadcast_type' => 'maintenance',
            'target_type' => 'all_users', 'status' => 'draft',
        ]);
    }

    public function test_publish_now_creates_a_published_broadcast(): void
    {
        $admin = User::factory()->create();
        $title = 'Outbreak alert '.Str::random(6);

        $this->actingAs($admin)->post(route('portals.admin.broadcasts.store'), [
            'title' => $title,
            'body' => 'Cholera cases reported in the district.',
            'broadcast_type' => 'alert',
            'target_type' => 'all_staff',
            'publish_now' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('broadcasts', ['title' => $title, 'status' => 'published']);
    }

    public function test_admin_can_publish_then_cancel_a_draft(): void
    {
        $admin = User::factory()->create();
        $bc = Broadcast::create([
            'uuid' => (string) Str::uuid(), 'broadcast_type' => 'announcement',
            'title' => 'Hi '.Str::random(5), 'body' => 'x', 'target_type' => 'all_users',
            'target_ids_json' => '[]', 'priority' => 'normal', 'language' => 'en',
            'requires_acknowledgement' => false, 'status' => 'draft', 'created_by' => (string) $admin->id,
        ]);

        $this->actingAs($admin)->post(route('portals.admin.broadcasts.publish', $bc->uuid))->assertRedirect();
        $this->assertSame('published', $bc->fresh()->status);

        $this->actingAs($admin)->post(route('portals.admin.broadcasts.cancel', $bc->uuid))->assertRedirect();
        $this->assertSame('cancelled', $bc->fresh()->status);
    }
}
