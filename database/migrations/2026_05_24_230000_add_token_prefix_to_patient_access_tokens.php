<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('patient_access_tokens') && !Schema::hasColumn('patient_access_tokens', 'token_prefix')) {
            Schema::table('patient_access_tokens', function (Blueprint $table) {
                $table->string('token_prefix', 12)->nullable()->unique()->after('token_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('patient_access_tokens') && Schema::hasColumn('patient_access_tokens', 'token_prefix')) {
            Schema::table('patient_access_tokens', function (Blueprint $table) {
                $table->dropUnique(['token_prefix']);
                $table->dropColumn('token_prefix');
            });
        }
    }
};
