<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lead → tenant conversion (PHARMACY_DASHBOARD_AUDIT P-6). A converted lead
 * remembers the `facilities` row it became, which is what makes the action
 * idempotent: a second conversion is refused and the desk shows the tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->uuid('converted_facility_id')->nullable()->after('notes');
            $table->timestamp('converted_at')->nullable()->after('converted_facility_id');
            $table->uuid('converted_by')->nullable()->after('converted_at');

            $table->index('converted_facility_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['converted_facility_id']);
            $table->dropColumn(['converted_facility_id', 'converted_at', 'converted_by']);
        });
    }
};
