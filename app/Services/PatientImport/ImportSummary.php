<?php

namespace App\Services\PatientImport;

/** Imported/skipped counts per resource type, with the reason for every skip. */
class ImportSummary
{
    /** @var array<string,array{imported:int,skipped:int}> */
    private array $byType = [];

    /** @var array<string,int> "type|reason" => count */
    private array $reasons = [];

    public function import(string $type): void
    {
        $this->byType[$type] ??= ['imported' => 0, 'skipped' => 0];
        $this->byType[$type]['imported']++;
    }

    public function skip(string $type, string $reason): void
    {
        $this->byType[$type] ??= ['imported' => 0, 'skipped' => 0];
        $this->byType[$type]['skipped']++;
        $key = $type . '|' . $reason;
        $this->reasons[$key] = ($this->reasons[$key] ?? 0) + 1;
    }

    /** @return array<string,mixed> */
    public function toArray(string $batchId): array
    {
        $skipped = [];
        foreach ($this->reasons as $key => $count) {
            [$type, $reason] = explode('|', $key, 2);
            $skipped[] = [
                'resource_type' => $type,
                'reason'        => $reason,
                'reason_label'  => __('patient_import.reason.' . $reason),
                'count'         => $count,
            ];
        }

        return [
            'import_batch_id' => $batchId,
            'provenance'      => 'patient_import',
            'verified'        => false,
            'label'           => __('patient_import.unverified_label'),
            'totals'          => [
                'imported' => array_sum(array_column($this->byType, 'imported')),
                'skipped'  => array_sum(array_column($this->byType, 'skipped')),
            ],
            'by_type'         => $this->byType,
            'skipped'         => $skipped,
        ];
    }
}
