<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 45 — Final Gap Models
 *
 * Five remaining models identified from OPESCARE_OPERATIONAL_MODULES_AND_END_TO_END_FLOWS_IMPLEMENTATION.md:
 *
 *   service_prices           — service-level pricing (Billing)
 *   notification_events      — per-event notification tracking (Visit flow)
 *   file_share_tokens        — external file-sharing tokens (File Storage)
 *   mobile_consent_devices   — consent captured via mobile device (Mobile)
 *   facility_context_sessions — active facility context for multi-facility users (Provider Mobile)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_prices')) {
            Schema::create('service_prices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->uuid('price_list_id')->nullable();
                $table->string('service_code');
                $table->string('service_name');
                $table->string('service_category')->nullable(); // consultation|lab|procedure|imaging|etc
                $table->decimal('base_price', 12, 2);
                $table->string('currency', 3)->default('USD');
                $table->decimal('insurance_price', 12, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->timestamps();

                $table->index(['facility_id', 'service_code'], 'sp_facility_code_idx');
                $table->index('is_active', 'sp_active_idx');
            });
        }

        if (! Schema::hasTable('notification_events')) {
            Schema::create('notification_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('event_type');              // visit_created|appointment_confirmed|lab_ready|etc
                $table->string('notifiable_type');         // Patient|Staff|Provider
                $table->uuid('notifiable_id');
                $table->string('channel');                 // sms|email|push|in_app
                $table->string('status');                  // pending|sent|delivered|failed|read
                $table->json('payload')->nullable();       // notification content/data
                $table->string('reference_type')->nullable(); // Visit|Appointment|etc
                $table->uuid('reference_id')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamps();

                $table->index(['notifiable_type', 'notifiable_id'], 'ne_notifiable_idx');
                $table->index(['event_type', 'status'], 'ne_event_status_idx');
                $table->index(['reference_type', 'reference_id'], 'ne_reference_idx');
            });
        }

        if (! Schema::hasTable('file_share_tokens')) {
            Schema::create('file_share_tokens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_asset_id');
                $table->string('token')->unique();
                $table->uuid('shared_by');
                $table->string('share_purpose')->nullable();  // external_lab|insurance|patient|etc
                $table->string('recipient_email')->nullable();
                $table->integer('max_uses')->default(1);
                $table->integer('use_count')->default(0);
                $table->timestamp('expires_at');
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->index('token', 'fst_token_idx');
                $table->index('file_asset_id', 'fst_asset_idx');
                $table->index('expires_at', 'fst_expires_idx');
            });
        }

        if (! Schema::hasTable('mobile_consent_devices')) {
            Schema::create('mobile_consent_devices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('patient_id');
                $table->uuid('consent_id')->nullable();     // links to existing consent record
                $table->string('consent_type');             // treatment|data_sharing|telemedicine|research
                $table->string('device_identifier');
                $table->string('platform');                 // ios|android|web
                $table->string('consent_method');           // tap|signature|voice_recorded|face_id
                $table->string('ip_address')->nullable();
                $table->timestamp('consented_at');
                $table->timestamp('revoked_at')->nullable();
                $table->boolean('is_valid')->default(true);
                $table->timestamps();

                $table->index('patient_id', 'mcd_patient_idx');
                $table->index(['consent_type', 'is_valid'], 'mcd_type_valid_idx');
            });
        }

        if (! Schema::hasTable('facility_context_sessions')) {
            Schema::create('facility_context_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->uuid('facility_id');
                $table->uuid('organization_id')->nullable();
                $table->string('device_id')->nullable();
                $table->string('platform')->nullable();     // web|ios|android
                $table->timestamp('activated_at');
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('terminated_at')->nullable();
                $table->string('status')->default('active'); // active|expired|terminated
                $table->timestamps();

                $table->index(['user_id', 'status'], 'fcs_user_status_idx');
                $table->index(['facility_id', 'status'], 'fcs_facility_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_context_sessions');
        Schema::dropIfExists('mobile_consent_devices');
        Schema::dropIfExists('file_share_tokens');
        Schema::dropIfExists('notification_events');
        Schema::dropIfExists('service_prices');
    }
};
