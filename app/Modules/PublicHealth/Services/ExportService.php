<?php

namespace App\Modules\PublicHealth\Services;

use App\Models\PublicHealthReport;
use App\Models\ExportFile;

class ExportService
{
    /**
     * Export a public health report as a CSV file with small-cell suppression.
     */
    public function exportCsv(PublicHealthReport $report, string $userId): ExportFile
    {
        $payload = $report->payload_json ?? [];
        if (empty($payload)) {
            // Aggregate from items if payload_json is empty
            $payload = [];
            foreach ($report->items as $item) {
                $payload[] = [
                    'indicator_code' => $item->indicator_code,
                    'indicator_name' => $item->indicator_name,
                    'value' => $item->value
                ];
            }
        }

        // Apply Small-Cell Suppression (counts > 0 but < 5 are suppressed to "< 5")
        $suppressedPayload = [];
        foreach ($payload as $item) {
            $val = $item['value'] ?? 0;
            $displayValue = $val;

            if ($val > 0 && $val < 5) {
                $displayValue = '< 5';
            }

            $suppressedPayload[] = [
                'indicator_code' => $item['indicator_code'] ?? '',
                'indicator_name' => $item['indicator_name'] ?? '',
                'value' => $displayValue
            ];
        }

        // Build the CSV in memory and hand it to the private disk, so the
        // export lands on S3 (or any FILESYSTEM driver) when the app tier is
        // stateless — never on one node's local disk.
        $filePath = 'public_health_exports/public_health_report_' . $report->id . '_' . time() . '.csv';

        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, ['Indicator Code', 'Indicator Name', 'Value']);
        foreach ($suppressedPayload as $row) {
            fputcsv($fp, \App\Support\CsvSafe::row($row));
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        \Illuminate\Support\Facades\Storage::disk(config('filesystems.private_disk', 'local'))->put($filePath, $csv);

        $fileHash = hash('sha256', $csv);

        return ExportFile::create([
            'report_id' => $report->id,
            'file_type' => 'csv',
            'file_path' => $filePath,
            'file_hash' => $fileHash,
            'generated_by' => $userId,
            'generated_at' => now(),
            'download_count' => 0,
            'expires_at' => now()->addDays(7) // Secure file expires in 7 days
        ]);
    }
}
