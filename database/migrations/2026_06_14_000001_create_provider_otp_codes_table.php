<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_otp_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('device_fingerprint', 128);
            $table->string('code_hash');
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'device_fingerprint', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_otp_codes');
    }
};
