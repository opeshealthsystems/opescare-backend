<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Report versioning for the imaging report desk (docs/superpowers/specs/
| 2026-10-13-diagnostic-centre-dashboard-design.md): an amendment is a NEW
| row (version n+1) pointing at the row it replaces; the old one is kept,
| stamped `superseded_at`, and never edited again. The patient's documents
| and the worklist read the current version only (superseded_at IS NULL).
*/
return new class extends Migration {
    public function up(): void
    {
        Schema::table('radiology_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('radiology_reports', 'version')) {
                $table->unsignedInteger('version')->default(1);
            }
            if (! Schema::hasColumn('radiology_reports', 'supersedes_report_id')) {
                $table->uuid('supersedes_report_id')->nullable()->index();
            }
            if (! Schema::hasColumn('radiology_reports', 'superseded_at')) {
                $table->timestamp('superseded_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('radiology_reports', function (Blueprint $table) {
            foreach (['version', 'supersedes_report_id', 'superseded_at'] as $column) {
                if (Schema::hasColumn('radiology_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
