<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Demonstration content for the Health Passport, one domain per file.
 *
 * The passport reads thirty corners of the record, and a demo account that only
 * carries prescriptions leaves two thirds of the document empty — which is
 * honest but tells a reviewer nothing about whether the page works. Each page's
 * demo rows therefore live in their own seeder under database/seeders/passport,
 * and this runs whichever ones exist.
 *
 * EVERY ROW THESE WRITE IS is_demo. Nothing here may touch a real patient, and
 * a demo row must never be presented as a live credential — the passport says
 * so on its face when the account is a demonstration one.
 */
class DemoHealthPassportSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('seeders/passport');

        if (! is_dir($dir)) {
            $this->command?->info('No passport demo seeders present.');

            return;
        }

        $files = glob($dir . '/*Seeder.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $class = 'Database\\Seeders\\Passport\\' . basename($file, '.php');

            if (! class_exists($class)) {
                require_once $file;
            }

            if (! class_exists($class)) {
                $this->command?->warn("skipped {$file}: no class {$class}");

                continue;
            }

            try {
                $this->call($class);
            } catch (\Throwable $e) {
                // One domain failing must not cost the reviewer the other
                // twenty-nine pages, but it must be visible rather than quiet.
                $this->command?->error(basename($file) . ' failed: ' . $e->getMessage());
            }
        }
    }
}
