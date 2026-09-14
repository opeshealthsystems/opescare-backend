<?php

namespace Tests\Feature\Admin;

use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use App\Support\BackupStatus;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The admin Backups page: status plus instructions for adding off-site keys.
 *
 * What these tests hold is what makes the page safe to have at all: it never
 * shows a credential, not even a fragment; it has no way to write one; and it
 * is invisible to anyone below the platform tier, because knowing where a
 * platform's backups live is itself useful to an attacker.
 */
class BackupStatusPageTest extends TestCase
{
    use RefreshDatabase;

    private const FAKE_KEY = 'AKIAFAKEKEYFORTEST0001';
    private const FAKE_SECRET = 'fakeSecretValueThatMustNeverRender9x8y7z';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);

        Storage::fake('local');
        Storage::fake('s3');
    }

    private function userWithRole(string $roleName, bool $withFacility = true): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create([
            'status'              => 'active',
            'primary_facility_id' => $withFacility ? Facility::factory()->create()->id : null,
        ]);
        $user->role_id = $role->id;
        $user->save();

        return $user->fresh();
    }

    /**
     * A platform admin who has passed MFA. Without the session flag every
     * request is an MFA redirect — and a test that inspects a redirect page
     * for a credential will pass without having looked at this page at all.
     */
    private function asPlatformAdmin(): static
    {
        return $this->actingAs($this->userWithRole('super_admin', false))
            ->withSession(['mfa.verified' => true]);
    }

    private function onHostOnly(): void
    {
        config([
            'backup.backup.destination.disks' => ['local'],
            'filesystems.disks.s3.key'        => null,
            'filesystems.disks.s3.secret'     => null,
            'filesystems.disks.s3.bucket'     => 'opescare-backups',
        ]);
    }

    private function keyed(): void
    {
        config([
            'backup.backup.destination.disks' => ['local', 's3'],
            'filesystems.disks.s3.key'        => self::FAKE_KEY,
            'filesystems.disks.s3.secret'     => self::FAKE_SECRET,
            'filesystems.disks.s3.bucket'     => 'opescare-backups',
            'filesystems.disks.s3.region'     => 'eu-west-1',
        ]);
    }

    public function test_a_platform_admin_sees_the_page_and_the_steps(): void
    {
        $this->onHostOnly();

        $this->asPlatformAdmin()
            ->get('/admin/cc/backups')
            ->assertOk()
            ->assertSee(__('backups.state_on_host_only'))
            ->assertSee(__('backups.section_steps'))
            ->assertSee('/home/finder/htdocs/finder.opescare.cloud/.env', false)
            ->assertSee('artisan backup:run', false);
    }

    public function test_a_facility_admin_cannot_see_where_backups_live(): void
    {
        $response = $this->actingAs($this->userWithRole('clinic_admin'))->get('/admin/cc/backups');

        $this->assertNotSame(200, $response->getStatusCode(),
            'A facility-tier admin must not reach platform backup configuration.');
    }

    public function test_no_credential_ever_renders_not_even_a_fragment(): void
    {
        // A screenshot of this page will be shared eventually. A key's last
        // four characters are still a piece of the key.
        $this->keyed();

        $html = $this->asPlatformAdmin()
            ->get('/admin/cc/backups')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(self::FAKE_KEY, $html);
        $this->assertStringNotContainsString(self::FAKE_SECRET, $html);
        $this->assertStringNotContainsString(substr(self::FAKE_KEY, -6), $html);
        $this->assertStringNotContainsString(substr(self::FAKE_SECRET, -6), $html);
    }

    public function test_the_page_has_no_way_to_write_a_key(): void
    {
        // Keys go in on the server. A form that writes secrets into .env would
        // be a way to take over the application.
        // assertOk + assertSee first: this test once passed while inspecting an
        // MFA redirect page, which has no form because it has no content.
        $html = $this->asPlatformAdmin()
            ->get('/admin/cc/backups')
            ->assertOk()
            ->assertSee(__('backups.section_steps'))
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/<input[^>]+name="[^"]*(key|secret|aws|password)/i', $html);

        $this->assertFalse(
            collect(app('router')->getRoutes())->contains(
                fn ($r) => str_contains($r->uri(), 'admin/cc/backups') && ! in_array('GET', $r->methods(), true)
            ),
            'The backups page must be read-only: no write route may exist.'
        );
    }

    public function test_keys_without_a_remote_copy_is_not_reported_as_safe(): void
    {
        // "Configured" is not "protected". Only a copy that has actually
        // arrived off-site counts.
        $this->keyed();

        $this->assertSame('configured', BackupStatus::read()['state']);
        $this->assertTrue(BackupStatus::offHostConfigured());
    }

    public function test_a_remote_copy_is_what_turns_the_verdict_green(): void
    {
        $this->keyed();
        Storage::disk('s3')->put(config('backup.backup.name') . '/2026-09-14-01-00-00.zip', 'archive');

        $status = BackupStatus::read();

        $this->assertSame('off_host', $status['state']);
        $this->assertSame(1, $status['remote']['count']);
    }

    public function test_s3_switched_on_without_a_secret_is_flagged_as_broken(): void
    {
        config([
            'backup.backup.destination.disks' => ['local', 's3'],
            'filesystems.disks.s3.key'        => self::FAKE_KEY,
            'filesystems.disks.s3.secret'     => null,
            'filesystems.disks.s3.bucket'     => 'opescare-backups',
            'filesystems.disks.s3.region'     => 'eu-west-1',
        ]);

        $this->assertSame('misconfigured', BackupStatus::read()['state']);
    }

    public function test_the_page_is_linked_from_the_admin_sidebar(): void
    {
        $this->onHostOnly();

        $this->asPlatformAdmin()
            ->get('/admin/cc/health')
            ->assertSee(route('portals.admin.cc.backups'), false);
    }
}
