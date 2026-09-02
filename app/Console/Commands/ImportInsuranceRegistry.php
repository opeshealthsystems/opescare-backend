<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportInsuranceRegistry extends Command
{
    protected $signature = 'registry:import-insurers
                            {--file= : Path to CSV file (required)}
                            {--dry-run : Validate without writing}';

    protected $description = 'Import Cameroonian insurance providers from a CSV file into insurance_providers';

    public function handle(): int
    {
        $file   = $this->option('file');
        $dryRun = (bool) $this->option('dry-run');

        if (!$file || !file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info("Insurance Registry Import — File: {$file}  Dry-run: " . ($dryRun ? 'yes' : 'no'));

        $handle  = fopen($file, 'r');
        $added   = 0;
        $updated = 0;
        $errors  = [];
        $rowNum  = 1;

        try {
            $headers = array_map('trim', fgetcsv($handle));

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                }
                $data = array_combine($headers, array_slice($row, 0, count($headers)));

                if (empty(trim($data['name'] ?? ''))) {
                    $errors[] = "Row {$rowNum}: name is required";
                    continue;
                }
                if (empty(trim($data['code'] ?? ''))) {
                    $errors[] = "Row {$rowNum}: code is required";
                    continue;
                }

                $payload = [
                    'name'          => trim($data['name']),
                    'code'          => strtoupper(trim($data['code'])),
                    'country_code'  => trim($data['country_code'] ?? 'CM') ?: 'CM',
                    'contact_email' => trim($data['contact_email'] ?? '') ?: null,
                    'contact_phone' => trim($data['contact_phone'] ?? '') ?: null,
                    'portal_url'    => trim($data['portal_url'] ?? '') ?: null,
                    'api_endpoint'  => trim($data['api_endpoint'] ?? '') ?: null,
                    'status'        => trim($data['status'] ?? 'active') ?: 'active',
                    'updated_at'    => now(),
                ];

                if ($dryRun) {
                    $added++;
                    continue;
                }

                $existing = DB::table('insurance_providers')
                    ->where('code', $payload['code'])
                    ->first();

                if ($existing) {
                    DB::table('insurance_providers')
                        ->where('id', $existing->id)
                        ->update($payload);
                    $updated++;
                } else {
                    DB::table('insurance_providers')->insert(array_merge($payload, [
                        'id'         => (string) Str::uuid(),
                        'created_at' => now(),
                    ]));
                    $added++;
                }
            }
        } finally {
            fclose($handle);
        }

        $this->line("✓  Added:   {$added}");
        $this->line("~  Updated: {$updated}");
        if (count($errors) > 0) {
            $this->warn("✗  Errors:  " . count($errors));
            foreach ($errors as $err) {
                $this->warn("   {$err}");
            }
        } else {
            $this->line("✗  Errors:  0");
        }

        return self::SUCCESS;
    }
}
