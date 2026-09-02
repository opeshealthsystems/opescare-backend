<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('provider_availabilities', function (Blueprint $table) {
            if (!Schema::hasColumn('provider_availabilities', 'slot_duration_minutes')) {
                $table->unsignedSmallInteger('slot_duration_minutes')->default(30);
            }
        });
    }

    public function down(): void
    {
        Schema::table('provider_availabilities', function (Blueprint $table) {
            if (Schema::hasColumn('provider_availabilities', 'slot_duration_minutes')) {
                $table->dropColumn('slot_duration_minutes');
            }
        });
    }
};
