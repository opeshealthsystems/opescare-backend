<?php

namespace App\Modules\DataImport\Services;

use App\Models\ImportJob;
use App\Models\ImportMapping;

class ImportMappingService
{
    public function __construct(
        private ImportService $importService,
    ) {}

    /**
     * Return saved mappings for a facility + import type.
     */
    public function savedMappings(string $facilityId, string $importType): array
    {
        return ImportMapping::where('facility_id', $facilityId)
            ->where('import_type', $importType)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Apply a mapping to a job and optionally save it for reuse.
     */
    public function applyMapping(
        ImportJob $job,
        array $mapping,
        string $actorId,
        ?string $saveAs = null
    ): ImportJob {
        if (!$job->canBeMapped()) {
            throw new \RuntimeException("Job cannot be re-mapped in status: {$job->status}");
        }

        $job->forceFill([
            'mapping' => $mapping,
            'status'  => 'preview_ready',
        ])->save();

        if ($saveAs) {
            ImportMapping::create([
                'facility_id'  => $job->facility_id,
                'import_type'  => $job->import_type,
                'name'         => $saveAs,
                'mapping'      => $mapping,
                'created_by'   => $actorId,
            ]);
        }

        (new ImportService())->audit($job, 'mapping_saved', $actorId, ['mapping' => $mapping, 'save_as' => $saveAs]);

        return $job->fresh();
    }

    /**
     * Return the expected system fields for a type (for UI rendering).
     */
    public function systemFields(string $importType): array
    {
        $def = ImportService::IMPORT_TYPES[$importType] ?? null;
        if (!$def) {
            return [];
        }

        $fields = [];
        foreach ($def['required'] as $f) {
            $fields[] = ['key' => $f, 'required' => true];
        }
        foreach ($def['optional'] as $f) {
            $fields[] = ['key' => $f, 'required' => false];
        }
        return $fields;
    }
}
