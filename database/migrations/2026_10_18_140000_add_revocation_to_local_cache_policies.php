<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('local_cache_policies', function (Blueprint $table) {
            $table->timestampTz('revoked_at')->nullable()->index();
            $table->uuid('revoked_by')->nullable();
            $table->foreign('revoked_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('local_cache_policies', function (Blueprint $table) {
            $table->dropForeign(['revoked_by']);
            $table->dropColumn(['revoked_at', 'revoked_by']);
        });
    }
};
