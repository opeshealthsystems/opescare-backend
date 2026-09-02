<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_incidents', function (Blueprint $table) {
            $table->string('contained_by', 36)->nullable()->after('contained_at');
            $table->string('resolved_by', 36)->nullable()->after('resolved_at');
        });

        Schema::table('data_export_requests', function (Blueprint $table) {
            $table->string('rejected_by', 36)->nullable()->after('approved_by');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('security_incidents', function (Blueprint $table) {
            $table->dropColumn(['contained_by', 'resolved_by']);
        });

        Schema::table('data_export_requests', function (Blueprint $table) {
            $table->dropColumn(['rejected_by', 'rejected_at']);
        });
    }
};
