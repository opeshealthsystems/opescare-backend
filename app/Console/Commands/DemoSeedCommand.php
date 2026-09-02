<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DemoSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opescare:demo:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with OpesCare demo data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!config('demo.enabled')) {
            $this->error('Demo mode is not enabled in configuration.');
            return 1;
        }

        $this->info('Starting Demo Data Seed Process...');
        Log::channel('single')->info('demo_seed_started', ['requested_by' => 'cli']);

        Artisan::call('db:seed', ['--class' => 'DemoDatabaseSeeder']);

        $this->info('Demo data seeded successfully.');
        Log::channel('single')->info('demo_seed_completed');

        return 0;
    }
}
