<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('health_id_qr_tokens') && !Schema::hasColumn('health_id_qr_tokens', 'lookup_id')) {
            Schema::table('health_id_qr_tokens', function (Blueprint $table) {
                // Indexed lookup key for O(1) token verification without full table scan.
                // Format: qrx_{lookup_id}_{secret} — lookup_id stored plaintext, secret stored hashed.
                $table->string('lookup_id', 16)->nullable()->unique()->after('token_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('health_id_qr_tokens') && Schema::hasColumn('health_id_qr_tokens', 'lookup_id')) {
            Schema::table('health_id_qr_tokens', function (Blueprint $table) {
                $table->dropUnique(['lookup_id']);
                $table->dropColumn('lookup_id');
            });
        }
    }
};
