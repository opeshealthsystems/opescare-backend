<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $tables = [
        'users',
        'facilities',
        'patients',
        'visits',
        'clinical_notes',
        'diagnoses',
        'allergy_records',
        'consent_requests',
        'consent_grants',
        'security_incidents',
        'access_logs',
        'emergency_access_events',
        'emergency_review_cases'
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->boolean('is_demo')->default(false)->index();
                    $table->string('demo_seed_key')->nullable()->index();
                    $table->string('demo_reset_group')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn(['is_demo', 'demo_seed_key', 'demo_reset_group']);
                });
            }
        }
    }
};
