<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provenance + rollback support for data imports (GAP-005).
 *
 * Each row an import creates is recorded here, linking it back to the ImportJob
 * that produced it. Rollback deletes exactly these records — so an import can be
 * reverted cleanly without guessing which rows it touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_record_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('import_job_id');
            $table->string('target_table');   // e.g. 'patients'
            $table->string('record_id');      // PK of the created row (uuid or int as string)
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('import_job_id')->references('id')->on('import_jobs')->onDelete('cascade');
            $table->index(['import_job_id']);
            $table->index(['target_table', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_record_links');
    }
};
