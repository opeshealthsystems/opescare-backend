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

        // Build temporary file path inside laravel storage
        $filename = 'public_health_report_' . $report->id . '_' . time() . '.csv';
        $directory = storage_path('app/public_health_exports');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $filePath = $directory . '/' . $filename;

        $fp = fopen($filePath, 'w');
        fputcsv($fp, ['Indicator Code', 'Indicator Name', 'Value']);
        foreach ($suppressedPayload as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        $fileHash = hash_file('sha256', $filePath);

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
