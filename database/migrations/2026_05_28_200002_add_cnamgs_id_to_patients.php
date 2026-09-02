<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('cnamgs_id', 50)->nullable()->unique()->after('health_id')
                ->comment('Cameroon CNAMGS social insurance card number (searchable, not encrypted)');
            $table->string('national_id_number', 50)->nullable()->after('cnamgs_id')
                ->comment('National ID / CNI number (Cameroun Carte Nationale d\'Identite)');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['cnamgs_id', 'national_id_number']);
        });
    }
};
