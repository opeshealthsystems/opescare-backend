<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Create a platform administrator with a random one-time password.
 *
 *   php8.3 artisan opescare:create-admin lord@opescare.com --name="Lord"
 *
 * Run by the owner on the server. The password is printed ONCE and never
 * stored in plain text or logged; the account must enrol MFA at first sign-in
 * (platform roles require it) and the owner changes the password then.
 * Refuses to touch an existing account.
 */
class CreatePlatformAdmin extends Command
{
    protected $signature = 'opescare:create-admin {email} {--name=Administrator} {--role=platform_admin}';

    protected $description = 'Create a platform administrator with a random one-time password (printed once)';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Not a valid email address.');
            return self::FAILURE;
        }

        $roleName = (string) $this->option('role');
        if (! in_array($roleName, \App\Http\Middleware\RequirePlatformAdmin::PLATFORM_ROLES, true)) {
            $this->error("'{$roleName}' is not a platform role.");
            return self::FAILURE;
        }

        if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
            $this->error('An account with this email already exists; nothing was changed.');
            return self::FAILURE;
        }

        $role = Role::query()->where('name', $roleName)->first();
        if (! $role) {
            $this->error("Role '{$roleName}' does not exist in this database.");
            return self::FAILURE;
        }

        // 24 characters from upper, lower, digits and symbols.
        $password = Str::password(24);

        $user = new User();
        $user->name = (string) $this->option('name');
        $user->email = $email;
        $user->password = $password;
        $user->status = 'active';
        $user->role_id = $role->id;
        $user->is_demo = false;
        $user->email_verified_at = now();
        $user->save();

        $this->newLine();
        $this->info("Platform administrator created: {$email} ({$roleName})");
        $this->line('One-time password (shown once, not stored or logged — save it now):');
        $this->newLine();
        $this->line('    ' . $password);
        $this->newLine();
        $this->line('Sign in at https://opescare.com/login, set up two-factor authentication, then change this password.');

        return self::SUCCESS;
    }
}
