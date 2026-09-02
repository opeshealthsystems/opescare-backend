<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update patients table
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'country_code')) {
                $table->string('country_code', 2)->default('CM')->after('health_id');
            }
            if (!Schema::hasColumn('patients', 'verification_status')) {
                $table->string('verification_status')->default('provisional')->after('identity_status');
            }
        });

        // 2. patient_identity_profiles
        Schema::create('patient_identity_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('patient_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('preferred_language', 5)->default('en');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });

        // 3. health_id_aliases
        Schema::create('health_id_aliases', function (Blueprint $table) {
            $table->id();
            $table->uuid('patient_id');
            $table->string('alias_type'); // old_health_id, merged_health_id, facility_mrn, etc.
            $table->string('alias_value');
            $table->uuid('source_facility_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->index(['patient_id', 'alias_type']);
        });

        // 4. health_id_qr_tokens
        Schema::create('health_id_qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('patient_id');
            $table->string('token_hash'); // Store hashed
            $table->string('token_type'); // static_identity_qr, temporary_consent_qr
            $table->string('status')->default('active'); // active, revoked
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->index('status');
        });

        // 5. health_id_verification_events
        Schema::create('health_id_verification_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('patient_id');
            $table->string('verification_type');
            $table->uuid('verified_by_user_id')->nullable();
            $table->uuid('verified_by_facility_id')->nullable();
            $table->string('verification_status');
            $table->string('evidence_reference')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });

        // 6. patient_external_identifiers
        Schema::create('patient_external_identifiers', function (Blueprint $table) {
            $table->id();
            $table->uuid('patient_id');
            $table->uuid('facility_id')->nullable();
            $table->string('source_system');
            $table->string('external_patient_id');
            $table->string('identifier_type');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->index(['external_patient_id', 'source_system']);
        });

        // 7. medical_id_access_events
        Schema::create('medical_id_access_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('patient_id')->nullable();
            $table->string('health_id')->nullable();
            $table->uuid('actor_id')->nullable();
            $table->string('actor_type')->nullable(); // user, api_client, public
            $table->uuid('facility_id')->nullable();
            $table->string('access_type');
            $table->string('purpose')->nullable();
            $table->string('result'); // success, denied
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });

        // 8. identity_merge_cases
        Schema::create('identity_merge_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('primary_patient_id');
            $table->uuid('secondary_patient_id');
            $table->string('status')->default('pending_review'); // pending_review, merged, rejected
            $table->float('match_score')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamps();

            $table->foreign('primary_patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('secondary_patient_id')->references('id')->on('patients')->onDelete('cascade');
        });

        // 9. identity_risk_flags
        Schema::create('identity_risk_flags', function (Blueprint $table) {
            $table->id();
            $table->uuid('patient_id');
            $table->string('flag_type');
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->string('status')->default('active'); // active, resolved
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_risk_flags');
        Schema::dropIfExists('identity_merge_cases');
        Schema::dropIfExists('medical_id_access_events');
        Schema::dropIfExists('patient_external_identifiers');
        Schema::dropIfExists('health_id_verification_events');
        Schema::dropIfExists('health_id_qr_tokens');
        Schema::dropIfExists('health_id_aliases');
        Schema::dropIfExists('patient_identity_profiles');

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'verification_status']);
        });
    }
};
