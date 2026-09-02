<?php

namespace App\Modules\DataImport\Services;

use App\Models\ImportJob;
use App\Models\ImportRowError;

class ImportValidationService
{
    public function __construct(
        private ImportService $importService,
    ) {}

    /**
     * Validate all rows in the job's file against its mapping.
     * Stores errors in import_row_errors, updates summary counters.
     */
    public function validate(ImportJob $job): ImportJob
    {
        if (!$job->mapping) {
            throw new \RuntimeException('Cannot validate: no column mapping set.');
        }

        if (!$job->canBeValidated()) {
            throw new \RuntimeException("Job cannot be validated in status: {$job->status}");
        }

        // Clear previous errors
        $job->rowErrors()->delete();

        $importType  = ImportService::IMPORT_TYPES[$job->import_type] ?? null;
        $required    = $importType['required'] ?? [];
        $mapping     = $job->mapping;           // { file_col => system_field }
        $reversedMap = array_flip($mapping);    // { system_field => file_col }

        // Read data rows
        $rows  = $this->importService->readRows(
            $job->stored_path,
            $job->file_extension,
            $job->detected_headers ?? array_keys($mapping)
        );

        $total   = count($rows);
        $valid   = 0;
        $invalid = 0;

        foreach ($rows as $row) {
            $rowNum    = $row['__row__'] ?? 0;
            $rowErrors = [];

            // Required field checks
            foreach ($required as $field) {
                $fileCol = $reversedMap[$field] ?? null;
                if ($fileCol === null) {
                    $rowErrors[] = [
                        'field'      => $field,
                        'error_code' => 'required_field_not_mapped',
                        'message'    => "Required field '{$field}' has no mapped column.",
                    ];
                    continue;
                }

                $value = trim((string) ($row[$fileCol] ?? ''));
                if ($value === '') {
                    $rowErrors[] = [
                        'field'      => $field,
                        'error_code' => 'required_empty',
                        'message'    => "Required field '{$field}' (column '{$fileCol}') is empty.",
                    ];
                }
            }

            // Type-specific validations
            $typeErrors = $this->typeValidate($job->import_type, $row, $mapping);
            $rowErrors  = array_merge($rowErrors, $typeErrors);

            if (empty($rowErrors)) {
                $valid++;
            } else {
                $invalid++;
                foreach ($rowErrors as $e) {
                    ImportRowError::create([
                        'import_job_id' => $job->id,
                        'row_number'    => $rowNum,
                        'field'         => $e['field'] ?? null,
                        'error_code'    => $e['error_code'],
                        'message'       => $e['message'],
                        'row_data'      => $row,
                    ]);
                }
            }
        }

        $newStatus = ($valid > 0 || $total === 0) ? 'validated' : 'validation_failed';

        $job->forceFill([
            'status'             => $newStatus,
            'total_rows'         => $total,
            'valid_rows'         => $valid,
            'invalid_rows'       => $invalid,
            'validation_summary' => [
                'total'     => $total,
                'valid'     => $valid,
                'invalid'   => $invalid,
                'to_create' => $valid,
            ],
        ])->save();

        return $job->fresh();
    }

    // ── Type-specific validations ─────────────────────────────────

    private function typeValidate(string $importType, array $row, array $mapping): array
    {
        $reversed = array_flip($mapping);
        $errors   = [];

        $get = fn(string $field) => trim((string) ($row[$reversed[$field] ?? '__none__'] ?? ''));

        switch ($importType) {
            case 'patients':
                $dob = $get('date_of_birth');
                if ($dob !== '' && !\DateTime::createFromFormat('Y-m-d', $dob) && !\DateTime::createFromFormat('d/m/Y', $dob)) {
                    $errors[] = ['field' => 'date_of_birth', 'error_code' => 'invalid_date_format', 'message' => "date_of_birth must be YYYY-MM-DD or DD/MM/YYYY."];
                }
                $gender = strtolower($get('gender'));
                if ($gender !== '' && !in_array($gender, ['male', 'female', 'm', 'f'])) {
                    $errors[] = ['field' => 'gender', 'error_code' => 'invalid_value', 'message' => "gender must be male/female/other."];
                }
                break;

            case 'staff':
                $hireDate = $get('hire_date');
                if ($hireDate !== '' && !\DateTime::createFromFormat('Y-m-d', $hireDate)) {
                    $errors[] = ['field' => 'hire_date', 'error_code' => 'invalid_date_format', 'message' => "hire_date must be YYYY-MM-DD."];
                }
                break;

            case 'pharmacy_stock':
                $qty = $get('quantity');
                if ($qty !== '' && (!is_numeric($qty) || (float) $qty < 0)) {
                    $errors[] = ['field' => 'quantity', 'error_code' => 'invalid_number', 'message' => "quantity must be a non-negative number."];
                }
                $expiry = $get('expiry_date');
                if ($expiry !== '' && !\DateTime::createFromFormat('Y-m-d', $expiry)) {
                    $errors[] = ['field' => 'expiry_date', 'error_code' => 'invalid_date_format', 'message' => "expiry_date must be YYYY-MM-DD."];
                }
                break;

            case 'price_lists':
                $amount = $get('amount');
                if ($amount !== '' && (!is_numeric($amount) || (float) $amount < 0)) {
                    $errors[] = ['field' => 'amount', 'error_code' => 'invalid_number', 'message' => "amount must be a non-negative number."];
                }
                break;
        }

        return $errors;
    }
}
