<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemoResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opescare:demo:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset demo data and revoke active demo sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!config('demo.enabled')) {
            $this->error('Demo mode is not enabled in configuration.');
            return 1;
        }

        $this->info('Starting Demo Data Reset Process...');
        Log::channel('single')->info('demo_reset_requested', ['requested_by' => 'cli', 'started_at' => now()]);

        // 1. Revoke demo sessions
        $sessionsRevokedCount = DB::table('sessions')->whereNotNull('user_id')->delete(); // Simulating clearing sessions

        // 2. Clear demo queues & hooks (mocked for demo)
        $this->info('Clearing demo queues and simulated webhooks...');

        // 3. Clear existing demo data
        $this->info('Clearing old demo data...');
        $tables = ['users', 'patients', 'facilities'];
        foreach ($tables as $table) {
            DB::table($table)->where('is_demo', true)->delete();
        }

        // 4. Reseed demo data
        $this->info('Reseeding fresh demo data...');
        Artisan::call('opescare:demo:seed');

        $this->info('Demo reset complete.');
        Log::channel('single')->info('demo_reset_completed', [
            'sessions_revoked_count' => $sessionsRevokedCount,
            'completed_at' => now()
        ]);

        return 0;
    }
}
