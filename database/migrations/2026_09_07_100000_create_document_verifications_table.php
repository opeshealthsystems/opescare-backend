<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per document the platform generates on the OpesCare Secure sheet.
 *
 * The sheet prints a QR code that resolves to /verify/doc/{token}; the token
 * is `doc_{lookup_id}_{secret}` and only the secret's hash is stored, the same
 * split as health_id_qr_tokens. After the PDF is rendered its SHA-256 is
 * sealed onto the row, so a verifier can show the fingerprint of the file that
 * was actually issued. Revocation is a status flip, never a delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->string('document_type', 40)->index();
            $table->string('reference', 24)->unique();
            $table->string('lookup_id', 16)->unique();
            $table->string('token_hash');
            $table->char('sha256', 64)->nullable();
            $table->uuid('issued_by_user_id')->nullable()->index();
            $table->timestampTz('issued_at');
            $table->string('status', 16)->default('valid'); // valid, revoked
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revoke_reason', 200)->nullable();
            $table->unsignedInteger('scan_count')->default(0);
            $table->timestampTz('last_verified_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('issued_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_verifications');
    }
};
