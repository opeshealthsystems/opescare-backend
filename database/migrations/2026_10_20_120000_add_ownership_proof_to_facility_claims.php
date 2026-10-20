<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ownership proof for directory claims (readiness OB-1, OB-2, PH-2).
 *
 * A claim now carries two pieces of evidence and the outcome of approval:
 *
 *  - a one-time code sent by SMS to the LISTING's registered phone (stored as
 *    an HMAC only, with expiry, attempt count and send count) and when it was
 *    confirmed (`phone_verified_at`), or why it could not be sent
 *    (`sms_status`) so an administrator can call the listing instead;
 *  - a licence / authorisation document on the private disk;
 *  - the tenant the approval created or joined (`created_facility_id`) and
 *    when the claimant activated it (`activated_at`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->string('verification_code_hash', 128)->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->unsignedSmallInteger('verification_attempts')->default(0);
            $table->unsignedSmallInteger('verification_send_count')->default(0);
            $table->timestamp('verification_sent_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('sms_status', 32)->nullable();
            $table->string('sms_phone_masked', 16)->nullable();

            $table->string('document_path')->nullable();
            $table->string('document_mime', 64)->nullable();
            $table->string('document_original_name', 255)->nullable();
            $table->unsignedInteger('document_size')->nullable();

            $table->uuid('created_facility_id')->nullable();
            $table->timestamp('activated_at')->nullable();

            $table->index(['care_facility_id', 'claim_status']);
        });
    }

    public function down(): void
    {
        Schema::table('facility_claims', function (Blueprint $table) {
            $table->dropIndex(['care_facility_id', 'claim_status']);
            $table->dropColumn([
                'verification_code_hash', 'verification_code_expires_at', 'verification_attempts',
                'verification_send_count', 'verification_sent_at', 'phone_verified_at', 'sms_status',
                'sms_phone_masked', 'document_path', 'document_mime', 'document_original_name',
                'document_size', 'created_facility_id', 'activated_at',
            ]);
        });
    }
};
