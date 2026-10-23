<?php

namespace Tests\Feature\Commands;

use App\Models\AuditEvent;
use App\Models\DeveloperAccount;
use App\Models\DeveloperApp;
use App\Models\DeveloperOrganization;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateDeveloperAccountCommandTest extends TestCase
{
    use RefreshDatabase;

    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Role::query()->firstOrCreate(['name' => 'developer'], ['description' => 'Developer Account']);
        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            $this->logged[] = $e->message . ' ' . json_encode($e->context);
        });
    }

    public function test_creates_user_org_account_and_app_with_prompted_password(): void
    {
        $secret = 'Correct-Horse-Battery-9';

        $this->artisan('opescare:create-developer', [
            'email' => 'Triage-Dev@opesware.com',
            '--org' => 'OPES Triage',
            '--website' => 'https://triage.opesware.com',
            '--app' => 'OPES Triage',
        ])
            ->expectsQuestion('Password (min 12 characters)', $secret)
            ->expectsQuestion('Confirm password', $secret)
            ->doesntExpectOutputToContain($secret)
            ->expectsOutputToContain('Next steps')
            ->assertSuccessful();

        $user = User::withoutGlobalScopes()->where('email', 'triage-dev@opesware.com')->firstOrFail();
        $this->assertSame('developer', $user->role->name ?? \App\Models\Role::find($user->role_id)->name);
        $this->assertTrue(Hash::check($secret, $user->password));

        $account = DeveloperAccount::where('email', 'triage-dev@opesware.com')->firstOrFail();
        $this->assertSame('active', $account->status);
        $this->assertTrue($account->sandbox_only);
        $this->assertSame('https://triage.opesware.com', $account->website_url);

        $org = DeveloperOrganization::where('developer_account_id', $account->id)->firstOrFail();
        $this->assertSame('OPES Triage', $org->name);
        $this->assertSame('https://triage.opesware.com', $org->website);
        $this->assertFalse($org->production_approved);

        $app = DeveloperApp::where('developer_organization_id', $org->id)->firstOrFail();
        $this->assertSame('sandbox', $app->environment);
        $this->assertSame(['https://triage.opesware.com'], $app->redirect_uris);

        $this->assertTrue(AuditEvent::where('action_type', 'developer_account_created')
            ->where('resource_id', $account->id)->exists());
        $this->assertStringNotContainsString($secret, implode("\n", $this->logged));
    }

    public function test_refuses_duplicate_email_and_does_not_duplicate(): void
    {
        $args = ['email' => 'dup@opesware.com', '--org' => 'Dup Org', '--generate-password' => true];
        $this->artisan('opescare:create-developer', $args)->assertSuccessful();

        $this->artisan('opescare:create-developer', $args)
            ->expectsOutputToContain('already exists')
            ->assertFailed();

        $this->assertSame(1, User::withoutGlobalScopes()->where('email', 'dup@opesware.com')->count());
        $this->assertSame(1, DeveloperAccount::where('email', 'dup@opesware.com')->count());
        $this->assertSame(1, DeveloperOrganization::where('name', 'Dup Org')->count());
        $this->assertSame(1, DeveloperApp::where('app_name', 'Dup Org')->count());
    }

    public function test_generated_password_is_printed_once_works_and_is_not_logged(): void
    {
        $exit = Artisan::call('opescare:create-developer', [
            'email' => 'gen@opesware.com', '--org' => 'Gen Org', '--generate-password' => true,
        ]);
        $this->assertSame(0, $exit);
        $out = Artisan::output();

        $lines = preg_split('/\R/', $out);
        $idx = null;
        foreach ($lines as $i => $line) {
            if (str_contains($line, 'One-time password')) {
                $idx = $i;
            }
        }
        $this->assertNotNull($idx, 'one-time password heading printed');
        $password = trim($lines[$idx + 1]);
        $this->assertSame(24, strlen($password));

        $this->assertSame(1, substr_count($out, $password));
        $user = User::withoutGlobalScopes()->where('email', 'gen@opesware.com')->firstOrFail();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertStringNotContainsString($password, implode("\n", $this->logged));
        $this->assertStringNotContainsString($password, (string) json_encode(AuditEvent::all()->toArray()));
    }

    public function test_rejects_short_or_mismatched_password(): void
    {
        $this->artisan('opescare:create-developer', ['email' => 'short@opesware.com', '--org' => 'X'])
            ->expectsQuestion('Password (min 12 characters)', 'short')
            ->assertFailed();

        $this->artisan('opescare:create-developer', ['email' => 'short@opesware.com', '--org' => 'X'])
            ->expectsQuestion('Password (min 12 characters)', 'long-enough-pass-1')
            ->expectsQuestion('Confirm password', 'long-enough-pass-2')
            ->assertFailed();

        $this->assertFalse(User::withoutGlobalScopes()->where('email', 'short@opesware.com')->exists());
    }
}
