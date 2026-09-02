<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('requesting_facility_id')->index();
            $table->uuid('requesting_user_id')->nullable()->index();
            $table->string('purpose');
            $table->jsonb('requested_scope');
            $table->integer('duration_minutes');
            $table->string('status')->default('pending_patient_approval'); // pending_patient_approval, approved, denied, expired
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('requesting_facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('requesting_user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('consent_grants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('consent_request_id')->nullable();
            $table->string('authorizing_actor')->comment('patient, guardian, facility_policy');
            $table->jsonb('scope');
            $table->string('status')->default('active'); // active, revoked, expired
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('consent_request_id')->references('id')->on('consent_requests')->onDelete('set null');
        });

        Schema::create('consent_revocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('consent_grant_id')->index();
            $table->uuid('revoked_by')->nullable()->index();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('consent_grant_id')->references('id')->on('consent_grants')->onDelete('cascade');
            $table->foreign('revoked_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_revocations');
        Schema::dropIfExists('consent_grants');
        Schema::dropIfExists('consent_requests');
    }
};
