<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditFacilityScopeCommand extends Command
{
    protected $signature   = 'opescare:audit-facility-scope';
    protected $description = 'Scan all models for facility_id in $fillable that are missing HasFacilityScope trait';

    public function handle(): int
    {
        $modelPath = app_path('Models');
        $files     = File::allFiles($modelPath);
        $missing   = [];
        $already   = [];

        foreach ($files as $file) {
            $content  = File::get($file->getPathname());
            $hasFacId = str_contains($content, "'facility_id'") || str_contains($content, '"facility_id"');
            $hasScope = str_contains($content, 'HasFacilityScope');

            if ($hasFacId && ! $hasScope) {
                $missing[] = $file->getRelativePathname();
            } elseif ($hasFacId && $hasScope) {
                $already[] = $file->getRelativePathname();
            }
        }

        $this->info('Models WITH facility_id already using HasFacilityScope: ' . count($already));
        foreach ($already as $m) {
            $this->line("  [OK] {$m}");
        }

        $this->newLine();
        $this->warn('Models WITH facility_id MISSING HasFacilityScope: ' . count($missing));
        foreach ($missing as $m) {
            $this->line("  [MISSING] {$m}");
        }

        $this->newLine();
        $this->info('Run: php artisan opescare:audit-facility-scope to re-audit after applying traits.');

        return self::SUCCESS;
    }
}
