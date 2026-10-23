<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use App\Models\DeveloperAccount;
use App\Models\DeveloperApp;
use App\Models\DeveloperOrganization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Create a ready-to-use developer account for an integration partner.
 *
 *   php8.3 artisan opescare:create-developer triage-dev@opesware.com \
 *       --org="OPES Triage" --website=https://triage.opesware.com --app="OPES Triage"
 *
 * Ends in the same state as developer-portal onboarding: a developer-role
 * user, an active sandbox-only DeveloperAccount, a DeveloperOrganization with
 * production NOT approved, and one sandbox DeveloperApp. Production access
 * stays an admin approval so the facility link and scopes are chosen
 * consciously. The password is prompted (hidden, confirmed, min 12) or, with
 * --generate-password, generated and printed once. It is never logged.
 */
class CreateDeveloperAccount extends Command
{
    protected $signature = 'opescare:create-developer {email} {--org=} {--website=} {--app=} {--name=}
        {--generate-password : Generate a strong password and print it once}';

    protected $description = 'Create a sandbox developer account (user, organization, app) for an integration partner';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $org = trim((string) ($this->option('org') ?? ''));
        $website = trim((string) ($this->option('website') ?? '')) ?: null;
        $appName = trim((string) ($this->option('app') ?? '')) ?: $org;
        $name = trim((string) ($this->option('name') ?? '')) ?: ($org !== '' ? $org . ' Developer' : 'Developer');

        $v = Validator::make(
            ['email' => $email, 'org' => $org, 'website' => $website, 'app' => $appName],
            [
                'email' => ['required', 'email'],
                'org' => ['required', 'string', 'max:150'],
                'website' => ['nullable', 'url', 'max:255'],
                'app' => ['required', 'string', 'max:150'],
            ]
        );
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }
            return self::FAILURE;
        }

        if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
            $this->error('An account with this email already exists; nothing was changed.');
            return self::FAILURE;
        }

        $role = Role::query()->where('name', 'developer')->first();
        if (! $role) {
            $this->error("Role 'developer' does not exist in this database.");
            return self::FAILURE;
        }

        $generated = (bool) $this->option('generate-password');
        if ($generated) {
            $password = Str::password(24);
        } else {
            $password = (string) $this->secret('Password (min 12 characters)');
            if (mb_strlen($password) < 12) {
                $this->error('Password must be at least 12 characters; nothing was changed.');
                return self::FAILURE;
            }
            if (! hash_equals($password, (string) $this->secret('Confirm password'))) {
                $this->error('Passwords do not match; nothing was changed.');
                return self::FAILURE;
            }
        }

        [$user, $account, $organization, $app] = DB::transaction(function () use ($email, $name, $org, $website, $appName, $role, $password) {
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = $password; // hashed by the model's 'hashed' cast
            $user->status = 'active';
            $user->role_id = $role->id;
            $user->is_demo = false;
            $user->email_verified_at = now();
            $user->save();

            $account = DeveloperAccount::firstOrCreate(['email' => $email], [
                'user_id' => $user->id,
                'display_name' => $name,
                'company_name' => $org,
                'website_url' => $website,
                'status' => 'active',
                'api_terms_accepted' => true,
                'api_terms_accepted_at' => now(),
                'api_terms_version' => '1.0',
                'sandbox_only' => true,
                'email_verified_at' => now(),
            ]);

            $organization = DeveloperOrganization::firstOrCreate(
                ['developer_account_id' => $account->id, 'name' => $org],
                ['website' => $website, 'status' => 'active', 'production_approved' => false]
            );

            $app = DeveloperApp::firstOrCreate(
                [
                    'developer_account_id' => $account->id,
                    'developer_organization_id' => $organization->id,
                    'app_name' => $appName,
                    'environment' => 'sandbox',
                ],
                [
                    'status' => 'active',
                    'description' => 'Created by opescare:create-developer',
                    'allowed_scopes' => [],
                    'redirect_uris' => $website ? [$website] : [],
                ]
            );

            AuditEvent::create([
                'actor_id' => null,
                'actor_role' => 'console',
                'action_type' => 'developer_account_created',
                'resource_type' => 'developer_account',
                'resource_id' => $account->id,
                'emergency_override' => false,
                'source_system' => 'console',
                'reason' => 'opescare:create-developer',
                'before_state' => [],
                'after_state' => [
                    'email' => $email,
                    'organization_id' => $organization->id,
                    'app_id' => $app->id,
                    'sandbox_only' => true,
                ],
                'created_at' => now(),
            ]);

            return [$user, $account, $organization, $app];
        });

        $this->newLine();
        $this->info("Developer account created: {$email}");
        $this->line('  Login:           ' . url('/login'));
        $this->line("  User:            {$user->id}");
        $this->line("  Developer acct:  {$account->id} (active, sandbox only)");
        $this->line("  Organization:    {$organization->id} ({$org}" . ($website ? ", {$website}" : '') . ')');
        $this->line("  App:             {$app->id} ({$appName}, sandbox)");

        if ($generated) {
            $this->newLine();
            $this->warn('One-time password (shown once, not stored or logged; hand it over securely now):');
            $this->line('    ' . $password);
        }

        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Sign in at the login URL above and change the password.');
        $this->line('  2. Complete the developer profile in the developer portal.');
        $this->line('  3. Create sandbox API keys under Apps.');
        $this->line('  4. Request production access with the FHIR scopes needed (e.g. system/Observation.write).');
        $this->line('  5. A platform admin approves the request and binds the facility.');

        return self::SUCCESS;
    }
}
