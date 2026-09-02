<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mobile sessions — authenticated patient app sessions
        Schema::create('mobile_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->string('device_fingerprint', 128);
            $table->string('platform', 20)->default('unknown'); // ios|android|web
            $table->string('app_version', 30)->nullable();
            $table->string('access_token_hash', 128)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason', 100)->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->index(['patient_id', 'revoked_at']);
            $table->index('device_fingerprint');
        });

        // Push device tokens — FCM/APNs tokens for push notifications
        Schema::create('push_device_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->string('device_fingerprint', 128);
            $table->string('platform', 20); // ios|android|web
            $table->text('push_token');
            $table->boolean('is_active')->default(true);
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->index(['patient_id', 'is_active']);
            $table->unique(['patient_id', 'device_fingerprint', 'platform']);
        });

        // Lab orders — test requests issued per visit
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('visit_id')->nullable();
            $table->uuid('ordered_by')->nullable(); // provider user id
            $table->string('test_name', 200);
            $table->string('test_code', 50)->nullable();
            $table->string('urgency', 20)->default('routine'); // routine|urgent|stat
            $table->string('status', 30)->default('pending');
            // pending|collected|processing|resulted|cancelled
            $table->text('clinical_indication')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('ordered_at')->useCurrent();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('resulted_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            $table->index(['patient_id', 'status']);
            $table->index(['patient_id', 'ordered_at']);
        });

        // Lab results — individual parameters per lab order
        Schema::create('lab_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lab_order_id');
            $table->uuid('patient_id');
            $table->string('parameter_name', 200);
            $table->string('value', 100);
            $table->string('unit', 50)->nullable();
            $table->string('reference_range', 100)->nullable();
            $table->string('flag', 20)->nullable(); // H|L|HH|LL|normal|abnormal
            $table->text('notes')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('resulted_at')->useCurrent();
            $table->timestamps();

            $table->foreign('lab_order_id')->references('id')->on('lab_orders')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->index(['patient_id', 'resulted_at']);
        });

        // Prescriptions — medication orders per visit
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('visit_id')->nullable();
            $table->uuid('prescribed_by')->nullable(); // provider user id
            $table->string('status', 30)->default('active');
            // active|dispensed|partially_dispensed|cancelled|expired
            $table->text('notes')->nullable();
            $table->timestamp('prescribed_at')->useCurrent();
            $table->timestamp('dispensed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            $table->index(['patient_id', 'status']);
            $table->index(['patient_id', 'prescribed_at']);
        });

        // Prescription items — individual drugs within a prescription
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prescription_id');
            $table->string('drug_name', 200);
            $table->string('drug_code', 50)->nullable();
            $table->string('dose', 100)->nullable();        // e.g. "500mg"
            $table->string('frequency', 100)->nullable();   // e.g. "3× daily"
            $table->string('route', 50)->nullable();        // oral|IV|IM|topical
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('quantity')->nullable();
            $table->string('status', 30)->default('pending'); // pending|dispensed|cancelled
            $table->timestamp('dispensed_at')->nullable();
            $table->text('dispense_notes')->nullable();
            $table->timestamps();

            $table->foreign('prescription_id')->references('id')->on('prescriptions')->cascadeOnDelete();
            $table->index('prescription_id');
        });

        // Mobile app settings — per-patient notification and preference settings
        Schema::create('mobile_app_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->unique();
            $table->boolean('push_appointments')->default(true);
            $table->boolean('push_lab_results')->default(true);
            $table->boolean('push_prescriptions')->default(true);
            $table->boolean('push_billing')->default(true);
            $table->boolean('push_consent_requests')->default(true);
            $table->string('preferred_language', 10)->default('en');
            $table->string('preferred_theme', 20)->default('system'); // light|dark|system
            $table->boolean('biometric_login_enabled')->default(false);
            $table->json('extra_preferences')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_settings');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('push_device_tokens');
        Schema::dropIfExists('mobile_sessions');
    }
};
