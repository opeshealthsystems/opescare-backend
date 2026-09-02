<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemAccountSeeder extends Seeder
{
    public function run(): void
    {
        $systemProviderId = config('opescare.system_provider_id',
            '00000000-0000-0000-0000-000000000001');

        $existing = User::find($systemProviderId);

        if ($existing) {
            $this->command->info("System provider account already exists: {$systemProviderId}");
            return;
        }

        $user = new User();
        $user->id       = $systemProviderId;
        $user->name     = 'OpesCare System';
        $user->email    = 'system@opescare.internal';
        $user->password = Hash::make(\Illuminate\Support\Str::random(64)); // unreachable password
        $user->status   = 'system';
        $user->is_demo  = false;
        $user->save();

        $this->command->info("System provider account created: {$systemProviderId}");
    }
}
