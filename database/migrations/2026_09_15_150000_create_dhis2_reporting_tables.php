<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DHIS2 aggregate reporting (platform import, group E).
 *
 * The platform hard-coded one org unit (DHIS2_ORG_UNIT) and kept data element
 * UIDs in env. The Finder reports per facility, so both mappings live in tables,
 * and every value sent is recorded once per (facility, period, indicator) so a
 * re-push is idempotent and DHIS2's import summary is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dhis2_org_unit_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->unique();
            $table->string('org_unit_id', 64);
            $table->uuid('set_by')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            $table->foreign('set_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('dhis2_data_element_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('indicator_code', 120)->unique();
            $table->string('data_element_id', 64);
            $table->string('category_option_combo_id', 64)->nullable();
            $table->uuid('set_by')->nullable();
            $table->timestamps();

            $table->foreign('set_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('dhis2_push_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id')->index();
            $table->uuid('facility_id');
            $table->string('period', 8);                 // DHIS2 monthly period, YYYYMM
            $table->string('indicator_code', 120);
            $table->string('org_unit_id', 64);
            $table->string('data_element_id', 64);
            $table->string('category_option_combo_id', 64)->nullable();
            $table->integer('value');
            $table->string('status', 20);                // imported | failed
            $table->json('import_summary')->nullable();  // DHIS2's response, no credentials
            $table->unsignedInteger('attempts')->default(0);
            $table->uuid('pushed_by')->nullable();
            $table->timestamp('pushed_at')->nullable();
            $table->timestamps();

            $table->unique(['facility_id', 'period', 'indicator_code']);
            $table->foreign('report_id')->references('id')->on('public_health_reports')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            $table->foreign('pushed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dhis2_push_records');
        Schema::dropIfExists('dhis2_data_element_mappings');
        Schema::dropIfExists('dhis2_org_unit_mappings');
    }
};
