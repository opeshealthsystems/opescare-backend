<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('care_team_members', function (Blueprint $table) {
            if (!Schema::hasColumn('care_team_members', 'facility_id')) {
                $table->uuid('facility_id')->nullable()->index();
            }
            if (!Schema::hasColumn('care_team_members', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('care_team_members', function (Blueprint $table) {
            if (Schema::hasColumn('care_team_members', 'facility_id')) {
                $table->dropColumn('facility_id');
            }
            if (Schema::hasColumn('care_team_members', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
