<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a phone_number_hash column to allow efficient lookup of patients by phone number
     * now that the phone_number column is encrypted and cannot be queried directly.
     *
     * The hash is a keyed HMAC-SHA256 using APP_KEY, making it safe to index and query.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('phone_number_hash', 64)->nullable()->after('phone_number');
            $table->index('phone_number_hash');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['phone_number_hash']);
            $table->dropColumn('phone_number_hash');
        });
    }
};
