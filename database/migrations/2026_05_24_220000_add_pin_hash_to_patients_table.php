<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('patients') && !Schema::hasColumn('patients', 'pin_hash')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('pin_hash')->nullable()->after('phone_number');
            });
        }
    }
    public function down(): void {
        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'pin_hash')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('pin_hash');
            });
        }
    }
};
