<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Provider mobile sessions — authenticated provider app sessions
        Schema::create('provider_mobile_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');              // provider (User)
            $table->string('device_fingerprint', 128);
            $table->string('platform', 20)->default('unknown'); // ios|android|web
            $table->string('app_version', 30)->nullable();
            $table->string('access_token_hash', 128)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason', 100)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'revoked_at']);
            $table->index('device_fingerprint');
        });

        // Provider devices — registered provider mobile devices
        Schema::create('provider_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('device_name', 100)->nullable();
            $table->string('device_fingerprint', 128)->unique();
            $table->string('platform', 20);  // ios|android|web
            $table->string('push_token')->nullable();
            $table->boolean('push_active')->default(false);
            $table->string('status', 20)->default('active'); // active|suspended|revoked
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'status']);
        });

        // Mobile facility contexts — provider switches between facilities per session
        Schema::create('mobile_facility_contexts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('facility_id');
            $table->uuid('provider_mobile_session_id')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('switched_at')->useCurrent();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            $table->index(['user_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_facility_contexts');
        Schema::dropIfExists('provider_devices');
        Schema::dropIfExists('provider_mobile_sessions');
    }
};
