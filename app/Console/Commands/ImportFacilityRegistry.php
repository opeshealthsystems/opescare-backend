<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportFacilityRegistry extends Command
{
    protected $signature = 'registry:import-facilities
                            {--file= : Path to CSV file (required)}
                            {--mode=merge : merge (default) or replace}
                            {--dry-run : Validate without writing}';

    protected $description = 'Import Cameroon health facilities from a CSV file into facility_registry';

    private const VALID_TYPES = [
        'hospital','clinic','health_center','dispensary','pharmacy','laboratory',
        'imaging_center','diagnostic_center','maternity','dental','eye_clinic',
        'blood_bank','specialist','nursing_home',
    ];

    private const VALID_REGIONS = [
        'Adamaoua','Centre','Est','Extrême-Nord','Littoral',
        'Nord','Nord-Ouest','Ouest','Sud','Sud-Ouest',
    ];

    private const VALID_OWNERSHIP = ['public','private','faith_based','ngo','military',null,''];

    public function handle(): int
    {
        $file   = $this->option('file');
        $mode   = $this->option('mode');
        $dryRun = (bool) $this->option('dry-run');

        if (!$file || !file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info("Cameroon Facility Registry Import");
        $this->info("File: {$file}  Mode: {$mode}  Dry-run: " . ($dryRun ? 'yes' : 'no'));
        $this->line(str_repeat('━', 50));

        // Handle replace mode: remove unclaimed rows before importing
        if ($mode === 'replace' && !$dryRun) {
            $deleted = DB::table('facility_registry')
                ->whereNull('claimed_facility_id')
                ->delete();
            $this->line("Replace mode: removed {$deleted} unclaimed rows before import.");
        }

        $added   = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = [];
        $rowNum  = 1;

        $handle = fopen($file, 'r');
        try {
            $headers = array_map('trim', fgetcsv($handle));

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                }
                $data = array_combine($headers, array_slice($row, 0, count($headers)));

                // Validate required fields
                if (empty(trim($data['name'] ?? ''))) {
                    $errors[] = "Row {$rowNum}: name is required";
                    continue;
                }
                if (!in_array($data['type'] ?? '', self::VALID_TYPES, true)) {
                    $errors[] = "Row {$rowNum}: invalid type \"{$data['type']}\" — use one of: " . implode(', ', self::VALID_TYPES);
                    continue;
                }
                if (!in_array($data['region'] ?? '', self::VALID_REGIONS, true)) {
                    $errors[] = "Row {$rowNum}: region \"{$data['region']}\" not recognised — use one of: " . implode(', ', self::VALID_REGIONS);
                    continue;
                }
                if (!empty($data['ownership']) && !in_array($data['ownership'], ['public','private','faith_based','ngo','military'], true)) {
                    $errors[] = "Row {$rowNum}: invalid ownership \"{$data['ownership']}\"";
                    continue;
                }

                // Check if claimed — never overwrite
                $claimedCount = DB::table('facility_registry')
                    ->where('name', $data['name'])
                    ->where('region', $data['region'])
                    ->where('city', $data['city'] ?: null)
                    ->whereNotNull('claimed_facility_id')
                    ->count();

                if ($claimedCount > 0) {
                    $skipped++;
                    continue;
                }

                // Build payload
                $payload = [
                    'name'                => trim($data['name']),
                    'type'                => $data['type'],
                    'ownership'           => $data['ownership'] ?: null,
                    'region'              => $data['region'],
                    'division'            => $data['division'] ?: null,
                    'city'                => $data['city'] ?: null,
                    'address'             => $data['address'] ?: null,
                    'phone'               => $data['phone'] ?: null,
                    'email'               => $data['email'] ?: null,
                    'website'             => $data['website'] ?: null,
                    'ministry_code'       => $data['ministry_code'] ?: null,
                    'accreditation_level' => $data['accreditation_level'] ?: null,
                    'bed_capacity'        => is_numeric($data['bed_capacity'] ?? '') ? (int)$data['bed_capacity'] : null,
                    'gps_lat'             => is_numeric($data['gps_lat'] ?? '') ? (float)$data['gps_lat'] : null,
                    'gps_lng'             => is_numeric($data['gps_lng'] ?? '') ? (float)$data['gps_lng'] : null,
                    'services'            => !empty($data['services'])
                        ? json_encode(array_values(array_filter(explode('|', $data['services']))))
                        : null,
                    'source'              => 'csv_import_' . date('Y'),
                    'status'              => 'unverified',
                    'updated_at'          => now(),
                ];

                if ($dryRun) {
                    $added++;
                    continue;
                }

                $existing = DB::table('facility_registry')
                    ->where('name', $data['name'])
                    ->where('region', $data['region'])
                    ->where('city', $data['city'] ?: null)
                    ->first();

                if ($existing) {
                    DB::table('facility_registry')
                        ->where('id', $existing->id)
                        ->update($payload);
                    $updated++;
                } else {
                    DB::table('facility_registry')->insert(array_merge($payload, [
                        'id'         => (string) Str::uuid(),
                        'created_at' => now(),
                    ]));
                    $added++;
                }
            }
        } finally {
            fclose($handle);
        }

        $this->line(str_repeat('━', 50));
        $this->line("✓  Added:    {$added}");
        $this->line("~  Updated:  {$updated}");
        $this->line("⊘  Skipped:  {$skipped}  (already claimed — not overwritten)");
        if (count($errors) > 0) {
            $this->warn("✗  Errors:   " . count($errors));
            foreach ($errors as $err) {
                $this->warn("   {$err}");
            }
        } else {
            $this->line("✗  Errors:   0");
        }

        return self::SUCCESS;
    }
}
