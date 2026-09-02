<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors patient_otp_codes (2026_05_24_220001_create_patient_otp_codes_table.php)
 * but keyed by email rather than phone_number — backs the mobile "forgot
 * password" flow (MobileAuthController::forgotPassword / resetPassword),
 * which issues a 6-digit emailed code rather than reusing Laravel's
 * link-based password_reset_tokens table (unsuitable for an in-app mobile
 * flow with no browser to land the link in).
 */
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('password_reset_codes')) {
            Schema::create('password_reset_codes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('email', 180)->index();
                $table->string('code_hash');
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('password_reset_codes');
    }
};
