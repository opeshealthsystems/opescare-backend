<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product Analytics & KPI Framework Tables
 *
 * - product_events: Raw event stream for product analytics
 * - metric_definitions: Catalog of named KPI metrics with computation rules
 * - metric_snapshots: Pre-computed metric values per period
 * - kpi_exports: Export jobs for KPI data
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Product Events ────────────────────────────────────────────────────
        Schema::create('product_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_name', 100);          // e.g. 'patient.registered', 'visit.completed'
            $table->string('event_category', 50);       // clinical|billing|admin|mobile|api|lab|pharmacy
            $table->uuid('facility_id')->nullable();
            $table->string('actor_id', 100)->nullable(); // user id or 'system'
            $table->string('actor_role', 50)->nullable();
            $table->uuid('patient_id')->nullable();
            $table->string('resource_type', 80)->nullable(); // 'Visit', 'Prescription', 'Invoice', etc.
            $table->uuid('resource_id')->nullable();
            $table->jsonb('properties')->nullable();     // event-specific payload (non-PHI)
            $table->string('source_system', 50)->default('opescare'); // opescare|lite|mobile|api
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['event_name', 'occurred_at']);
            $table->index(['facility_id', 'occurred_at']);
            $table->index(['event_category', 'occurred_at']);
        });

        // ── Metric Definitions ────────────────────────────────────────────────
        Schema::create('metric_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 100)->unique();       // e.g. 'daily_active_patients'
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 50);             // volume|quality|efficiency|financial|safety
            $table->string('unit', 50)->nullable();     // count|percentage|minutes|currency
            $table->string('aggregation', 30)->default('count'); // count|sum|avg|min|max|rate
            $table->string('granularity', 20)->default('daily'); // hourly|daily|weekly|monthly
            $table->string('scope', 20)->default('facility'); // platform|facility|role
            $table->boolean('is_active')->default(true);
            $table->jsonb('computation_config')->nullable(); // query params, filters, formulas
            $table->string('display_format', 30)->default('number'); // number|percentage|currency|duration
            $table->decimal('target_value', 12, 2)->nullable();
            $table->decimal('warning_threshold', 12, 2)->nullable();
            $table->decimal('critical_threshold', 12, 2)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        // ── Metric Snapshots ──────────────────────────────────────────────────
        Schema::create('metric_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('metric_definition_id');
            $table->uuid('facility_id')->nullable();    // null = platform-wide
            $table->date('period_date');                // the date this snapshot covers
            $table->string('period_granularity', 20);  // daily|weekly|monthly
            $table->decimal('value', 18, 4)->nullable();
            $table->decimal('previous_value', 18, 4)->nullable();
            $table->decimal('change_pct', 8, 2)->nullable();
            $table->string('status', 20)->default('normal'); // normal|warning|critical
            $table->integer('sample_count')->nullable(); // N observations in this period
            $table->jsonb('breakdown')->nullable();      // segmented data e.g. by visit_type
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['metric_definition_id', 'facility_id', 'period_date', 'period_granularity']);
            $table->index(['facility_id', 'period_date']);
            $table->index(['metric_definition_id', 'period_date']);
            $table->foreign('metric_definition_id')->references('id')->on('metric_definitions');
        });

        // ── KPI Exports ───────────────────────────────────────────────────────
        Schema::create('kpi_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('export_type', 50);          // csv|json|pdf
            $table->uuid('facility_id')->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->jsonb('metric_slugs');              // array of requested metric slugs
            $table->string('status', 30)->default('pending'); // pending|processing|ready|failed
            $table->string('file_path', 500)->nullable();
            $table->string('requested_by', 100);
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'status']);
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_exports');
        Schema::dropIfExists('metric_snapshots');
        Schema::dropIfExists('metric_definitions');
        Schema::dropIfExists('product_events');
    }
};
