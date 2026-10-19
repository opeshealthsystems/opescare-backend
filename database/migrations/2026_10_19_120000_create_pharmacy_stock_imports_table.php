<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One spreadsheet a pharmacy uploaded to load its stock.
 *
 * The uploaded FILE is never kept: it is parsed in the request that receives
 * it and only the cell values (stock lines, no patient data) land in `rows`.
 * `rows` is emptied when the import is committed or cancelled, and stale
 * uncommitted imports are purged after a day (PharmacyStockImportService::purgeStale).
 * What stays is the audit trail: who, when, which facility, how many lines
 * went in, the goods receipt they became, and the rejected lines' messages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->string('uploaded_by', 100)->nullable();
            $table->string('status', 20)->default('mapping'); // mapping|preview|queued|committed|failed|cancelled
            $table->string('mode', 20)->default('delivery');  // delivery|stocktake
            $table->string('file_format', 10);
            $table->string('original_name', 190)->nullable();
            $table->unsignedInteger('header_row')->default(0);
            $table->unsignedInteger('row_count')->default(0);
            $table->json('headers')->nullable();
            $table->json('rows')->nullable();
            $table->json('mapping')->nullable();
            $table->json('results')->nullable();
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->uuid('goods_receipt_id')->nullable();
            $table->string('failure', 255)->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_imports');
    }
};
