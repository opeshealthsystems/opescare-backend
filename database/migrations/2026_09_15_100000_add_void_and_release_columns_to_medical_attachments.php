<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient files imported from the platform (docs/PLATFORM_IMPORTS.md, group A).
 *
 * The platform deleted an attachment and its bytes. The Finder never
 * hard-deletes a clinical document: a wrong upload is VOIDED (status, reason,
 * who, when) and the bytes stay on disk. A file reaches the patient only once
 * a clinician RELEASES it; a radiology report release also links the
 * radiology_reports row it finalised.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('medical_attachments', 'status')) {
                $table->string('status', 20)->default('active')->index();
            }
            if (! Schema::hasColumn('medical_attachments', 'void_reason')) {
                $table->string('void_reason', 500)->nullable();
            }
            if (! Schema::hasColumn('medical_attachments', 'voided_by')) {
                $table->uuid('voided_by')->nullable();
            }
            if (! Schema::hasColumn('medical_attachments', 'voided_at')) {
                $table->timestamp('voided_at')->nullable();
            }
            if (! Schema::hasColumn('medical_attachments', 'released_at')) {
                $table->timestamp('released_at')->nullable();
            }
            if (! Schema::hasColumn('medical_attachments', 'released_by')) {
                $table->uuid('released_by')->nullable();
            }
            if (! Schema::hasColumn('medical_attachments', 'radiology_report_id')) {
                $table->uuid('radiology_report_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('medical_attachments', function (Blueprint $table) {
            foreach (['status', 'void_reason', 'voided_by', 'voided_at', 'released_at', 'released_by', 'radiology_report_id'] as $column) {
                if (Schema::hasColumn('medical_attachments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
