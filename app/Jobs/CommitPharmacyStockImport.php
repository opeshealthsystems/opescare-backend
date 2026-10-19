<?php

namespace App\Jobs;

use App\Models\PharmacyStockImport;
use App\Services\Pharmacy\StockImport\PharmacyStockImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Commits a large stock spreadsheet off the request, on the `imports` queue
 * (finder-queue.service works default,imports,webhooks). The import id and
 * the facility on it are the whole context — never a request value.
 */
class CommitPharmacyStockImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        public readonly string $importId,
        public readonly bool $acceptFuzzy,
        public readonly ?string $actorId,
    ) {
    }

    public function handle(PharmacyStockImportService $service): void
    {
        $import = PharmacyStockImport::query()->find($this->importId);

        if (! $import || $import->status !== 'queued') {
            return;
        }

        $service->commitNow($import, $this->acceptFuzzy, $this->actorId);
    }
}
