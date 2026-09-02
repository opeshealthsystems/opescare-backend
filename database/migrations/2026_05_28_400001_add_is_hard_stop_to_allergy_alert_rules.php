<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('allergy_alert_rules', function (Blueprint $table) {
            $table->boolean('is_hard_stop')->default(false)->after('is_active')
                ->comment('If true, prescribing is blocked — clinician cannot override without supervisor co-sign');
            $table->foreignUuid('facility_id')->nullable()->after('is_hard_stop')
                ->constrained()->nullOnDelete()
                ->comment('null = global rule; set = facility-specific override');
        });
    }

    public function down(): void
    {
        Schema::table('allergy_alert_rules', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->dropColumn(['is_hard_stop', 'facility_id']);
        });
    }
};
