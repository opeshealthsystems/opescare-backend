<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A governance desk that can only say yes is not a governance desk.
 *
 * `data_export_requests` already carried `rejected_by` and `rejected_at` but
 * nowhere to put WHY — so a refusal was an unexplained no on a statutory
 * right. Correction requests already have `record_correction_decisions`
 * (which carries `decision_reason`); export requests had no counterpart.
 *
 * One nullable text column, written on every decision — approval included, so
 * the reason a request was granted is recorded next to the reason one was not.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('data_export_requests', 'decision_reason')) {
            return;
        }

        Schema::table('data_export_requests', function (Blueprint $table) {
            $table->text('decision_reason')->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('data_export_requests', 'decision_reason')) {
            return;
        }

        Schema::table('data_export_requests', function (Blueprint $table) {
            $table->dropColumn('decision_reason');
        });
    }
};
