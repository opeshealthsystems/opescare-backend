<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Sprint — Item 4: Async FHIR Bulk Export Jobs Table
 *
 * Implements the FHIR Bulk Data Access IG (STU1) async polling pattern:
 *
 *   1. Client  →  GET /api/fhir/R4/$export
 *   2. Server  →  202 Accepted + Content-Location: /api/fhir/R4/bulkdata/{jobId}/status
 *   3. Client  →  GET /api/fhir/R4/bulkdata/{jobId}/status  (poll)
 *   4. Server  →  202 X-Progress: "30%" (still running)
 *             or  200 { output: [{ type, url }] }  (complete)
 *   5. Client  →  GET /api/fhir/R4/bulkdata/{jobId}/download/{file}
 *
 * Columns:
 *   - id            UUID — job identifier, used in all polling/download URLs
 *   - facility_id   — scopes which facility's consented patients are exported
 *   - status        — queued | processing | complete | failed | expired
 *   - progress      — 0-100 for the X-Progress polling response
 *   - parameters    — original $export query params (JSON)
 *   - output_files  — array of { type, url, count } when complete (JSON)
 *   - error         — error message if failed
 *   - requested_by  — integration_client_id from bearer token
 *   - expires_at    — download link expiry (1 hour after completion)
 *   - completed_at  — when FhirBulkExportJob finished
 *
 * Cleanup: nightly command purges expired job rows and their output files.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_export_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('facility_id')->index();
            $table->string('requested_by')->nullable()->comment('integration_client_id from bearer token.');

            $table->enum('status', ['queued', 'processing', 'complete', 'failed', 'expired'])
                ->default('queued');

            $table->unsignedTinyInteger('progress')->default(0)->comment('0-100 percent complete.');

            $table->json('parameters')->nullable()->comment('Original $export query params.');
            $table->json('output_files')->nullable()->comment('Array of {type, url, count} when complete.');
            $table->text('error')->nullable()->comment('Failure reason if status=failed.');

            $table->timestamp('expires_at')->nullable()->comment('Download links expire at this time.');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'status'], 'idx_bej_facility_status');
            $table->index('expires_at', 'idx_bej_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_export_jobs');
    }
};
