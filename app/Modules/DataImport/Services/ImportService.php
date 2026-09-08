<?php

namespace App\Modules\DataImport\Services;

use App\Models\ImportAuditEvent;
use App\Models\ImportJob;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImportService
{
    // Supported import types and their required/optional system fields
    public const IMPORT_TYPES = [
        'patients'             => ['label' => 'Patients',             'required' => ['first_name', 'last_name', 'date_of_birth', 'gender'], 'optional' => ['email', 'phone', 'national_id', 'address', 'blood_group']],
        'staff'                => ['label' => 'Staff',                'required' => ['first_name', 'last_name', 'job_title', 'staff_category', 'hire_date'], 'optional' => ['email', 'phone', 'department', 'employment_type']],
        'appointments'         => ['label' => 'Appointments',         'required' => ['patient_id', 'scheduled_at', 'appointment_type'], 'optional' => ['provider_name', 'notes', 'status']],
        'pharmacy_stock'       => ['label' => 'Pharmacy Stock',       'required' => ['drug_name', 'quantity', 'unit'], 'optional' => ['generic_name', 'form', 'batch_number', 'expiry_date', 'manufacturer', 'reorder_level']],
        'medicine_catalog'     => ['label' => 'Medicine Catalog',     'required' => ['drug_name', 'generic_name', 'form', 'strength'], 'optional' => ['manufacturer', 'unit', 'route', 'atc_code', 'notes']],
        'lab_test_catalog'     => ['label' => 'Lab Test Catalog',     'required' => ['test_name', 'test_code'], 'optional' => ['specimen_type', 'turnaround_hours', 'unit', 'reference_range', 'notes']],
        'insurance_providers'  => ['label' => 'Insurance Providers',  'required' => ['provider_name', 'provider_code'], 'optional' => ['contact_email', 'contact_phone', 'address', 'country']],
        'price_lists'          => ['label' => 'Price Lists',          'required' => ['item_name', 'item_type', 'amount', 'currency'], 'optional' => ['item_code', 'tax_rate', 'notes']],
        'inventory_items'      => ['label' => 'Inventory Items',      'required' => ['item_name', 'category', 'quantity', 'unit'], 'optional' => ['item_code', 'reorder_level', 'supplier', 'notes']],
    ];

    private const ALLOWED_EXTENSIONS = ['csv', 'xlsx', 'xls'];
    private const MAX_FILE_MB = 25;

    public function uploadFile(
        UploadedFile $file,
        string $importType,
        string $facilityId,
        string $actorId
    ): ImportJob {
        // Validate type
        if (!array_key_exists($importType, self::IMPORT_TYPES)) {
            throw new RuntimeException("Unsupported import type: {$importType}");
        }

        // Validate extension
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
            throw new RuntimeException('Only CSV and Excel (xlsx/xls) files are accepted.');
        }

        // Validate size
        $maxBytes = self::MAX_FILE_MB * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            throw new RuntimeException('File exceeds the maximum allowed size of ' . self::MAX_FILE_MB . ' MB.');
        }

        // Store privately
        $storedName = 'import_' . Str::uuid() . '.' . $ext;
        $path = $file->storeAs('imports/private', $storedName, 'local');

        // Parse headers
        $headers = $this->parseHeaders($path, $ext);

        // Determine initial status
        $status = $this->suggestMapping($headers, $importType) ? 'preview_ready' : 'mapping_required';

        $job = ImportJob::create([
            'facility_id'       => $facilityId,
            'import_type'       => $importType,
            'status'            => $status,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path'       => $path,
            'file_extension'    => $ext,
            'file_size_bytes'   => $file->getSize(),
            'detected_headers'  => $headers,
            'mapping'           => $status === 'preview_ready' ? $this->suggestMapping($headers, $importType) : null,
            'created_by'        => $actorId,
        ]);

        $this->audit($job, 'uploaded', $actorId, ['filename' => $file->getClientOriginalName(), 'import_type' => $importType]);

        return $job;
    }

    public function saveMapping(ImportJob $job, array $mapping, string $actorId): ImportJob
    {
        if (!$job->canBeMapped()) {
            throw new RuntimeException("Job cannot be mapped in status: {$job->status}");
        }

        $job->forceFill([
            'mapping' => $mapping,
            'status'  => 'preview_ready',
        ])->save();

        $this->audit($job, 'mapping_saved', $actorId, ['mapping' => $mapping]);

        return $job->fresh();
    }

    public function cancelJob(ImportJob $job, string $actorId): ImportJob
    {
        if (!$job->canBeCancelled()) {
            throw new RuntimeException("Job cannot be cancelled in status: {$job->status}");
        }

        $job->forceFill(['status' => 'cancelled'])->save();
        $this->audit($job, 'cancelled', $actorId);

        return $job->fresh();
    }

    /**
     * Auto-suggest a field mapping based on header names.
     * Returns null if < 50% of required fields can be matched.
     */
    public function suggestMapping(array $headers, string $importType): ?array
    {
        $fields     = self::IMPORT_TYPES[$importType] ?? null;
        if (!$fields) {
            return null;
        }

        $all        = array_merge($fields['required'], $fields['optional']);
        $mapping    = [];
        $matchCount = 0;

        foreach ($headers as $header) {
            $norm = $this->normalizeKey($header);
            foreach ($all as $field) {
                if ($this->normalizeKey($field) === $norm || similar_text($norm, $this->normalizeKey($field)) / max(strlen($norm), strlen($this->normalizeKey($field))) > 0.85) {
                    $mapping[$header] = $field;
                    if (in_array($field, $fields['required'])) {
                        $matchCount++;
                    }
                    break;
                }
            }
        }

        // Only auto-apply if we matched at least half the required fields
        $minMatch = max(1, (int) ceil(count($fields['required']) * 0.5));
        return $matchCount >= $minMatch ? $mapping : null;
    }

    /**
     * Parse first row (headers) from file.
     * Returns a simple array of column names.
     */
    public function parseHeaders(string $storedPath, string $ext): array
    {
        $fullPath = Storage::disk('local')->path($storedPath);

        if (!file_exists($fullPath)) {
            return [];
        }

        if ($ext === 'csv') {
            $handle = fopen($fullPath, 'r');
            if (!$handle) {
                return [];
            }
            $row = fgetcsv($handle);
            fclose($handle);
            return $row ? array_map('trim', $row) : [];
        }

        // For xlsx/xls we can only attempt basic parsing without a full library.
        // Return empty so user is prompted to map manually.
        return [];
    }

    /**
     * Read all data rows from file (skip header row).
     * Returns array of assoc arrays keyed by header.
     */
    public function readRows(string $storedPath, string $ext, array $headers, int $limit = 0): array
    {
        $fullPath = Storage::disk('local')->path($storedPath);

        if (!file_exists($fullPath) || $ext !== 'csv') {
            return [];
        }

        $handle = fopen($fullPath, 'r');
        if (!$handle) {
            return [];
        }

        // Skip header row
        fgetcsv($handle);

        $rows    = [];
        $lineNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;
            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
            // Zip headers with values
            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = $row[$i] ?? null;
            }
            $assoc['__row__'] = $lineNum;
            $rows[] = $assoc;
        }

        fclose($handle);
        return $rows;
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function normalizeKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '_', $key));
    }

    public function audit(ImportJob $job, string $action, string $actorId, array $details = []): void
    {
        ImportAuditEvent::create([
            'import_job_id' => $job->id,
            'action'        => $action,
            'actor_id'      => $actorId,
            'details'       => $details ?: null,
            'occurred_at'   => Carbon::now(),
        ]);
    }
}
