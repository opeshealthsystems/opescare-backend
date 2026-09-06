<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who RELEASED a laboratory order.
 *
 * `lab_results.verified_by` records who signed off each analyte; the order
 * itself had no column for the person who released the whole panel to the
 * patient and the ordering clinician. The lab desk's release is a clinical act
 * with an author, so the order now carries one. Nullable: every order released
 * before this column existed keeps reading honestly as "not recorded".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_orders') || Schema::hasColumn('lab_orders', 'verified_by')) {
            return;
        }

        Schema::table('lab_orders', function (Blueprint $table) {
            $table->uuid('verified_by')->nullable()->after('resulted_at');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('lab_orders') && Schema::hasColumn('lab_orders', 'verified_by')) {
            Schema::table('lab_orders', function (Blueprint $table) {
                $table->dropColumn('verified_by');
            });
        }
    }
};
