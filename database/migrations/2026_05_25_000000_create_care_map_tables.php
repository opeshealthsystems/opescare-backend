<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. care_facilities
        Schema::create('care_facilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('partner_id')->nullable();
            $table->uuid('organization_id')->nullable();
            $table->uuid('facility_id')->nullable(); // link to internal facilities table for slot lookup
            $table->string('facility_name');
            $table->string('facility_type'); // hospital, clinic, pharmacy, laboratory, blood_bank, etc.
            $table->string('ownership_type')->nullable(); // public, private, faith-based, ngo
            $table->string('license_number')->nullable();
            $table->string('license_status')->default('active'); // active, expired, suspended
            $table->string('verification_status')->default('unverified'); // unverified, self_reported, license_verified, government_verified
            $table->string('listing_status')->default('active'); // active, draft, closed, suspended, archived
            $table->string('country_code', 3)->default('US');
            $table->string('region')->nullable();
            $table->string('city');
            $table->text('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('geocoding_accuracy')->nullable(); // exact, street_level, area_level
            $table->string('phone_primary');
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('integration_status')->default('none'); // none, connected, read_only
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('last_profile_update_at')->nullable();
            $table->timestamp('last_availability_update_at')->nullable();
            $table->timestamps();
        });

        // 2. care_facility_services
        Schema::create('care_facility_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->string('service_name');
            $table->string('service_category'); // emergency, diagnostic, consultation, etc.
            $table->string('specialty')->nullable();
            $table->string('service_code')->nullable();
            $table->string('availability_status')->default('available');
            $table->boolean('appointment_required')->default(false);
            $table->boolean('walk_in_allowed')->default(true);
            $table->boolean('telemedicine_available')->default(false);
            $table->string('price_range')->nullable(); // low, medium, high
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
        });

        // 3. care_facility_hours
        Schema::create('care_facility_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->integer('day_of_week'); // 0 (Sunday) to 6 (Saturday)
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_24_hours')->default(false);
            $table->string('service_context')->nullable(); // General, Emergency, Pharmacy
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
        });

        // 4. care_facility_insurance
        Schema::create('care_facility_insurance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->uuid('insurance_partner_id')->nullable();
            $table->string('insurance_name');
            $table->string('plan_name')->nullable();
            $table->string('coverage_type')->nullable();
            $table->boolean('preauthorization_required')->default(false);
            $table->boolean('cashless_available')->default(false);
            $table->boolean('claim_supported')->default(true);
            $table->timestamp('last_verified_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
        });

        // 5. pharmacy_stock_availability
        Schema::create('pharmacy_stock_availability', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->string('medicine_name');
            $table->string('generic_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('strength')->nullable();
            $table->string('form')->nullable();
            $table->string('local_medicine_code')->nullable();
            $table->string('gtin')->nullable();
            $table->string('availability_status')->default('reported_available'); // reported_available, low_stock, out_of_stock, unknown
            $table->string('quantity_available_range')->nullable(); // >100, 10-50, etc.
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('reservation_enabled')->default(false);
            $table->string('source_system')->nullable();
            $table->string('freshness_status')->default('fresh'); // fresh, recent, stale
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
        });

        // 6. lab_test_availability
        Schema::create('lab_test_availability', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->string('test_name');
            $table->string('local_test_code')->nullable();
            $table->string('loinc_code')->nullable();
            $table->string('specimen_type')->nullable();
            $table->string('turnaround_time')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('requires_doctor_order')->default(false);
            $table->boolean('sample_collection_available')->default(true);
            $table->boolean('home_sample_collection_available')->default(false);
            $table->string('availability_status')->default('available');
            $table->string('freshness_status')->default('fresh');
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
        });

        // 7. blood_availability
        Schema::create('blood_availability', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->string('blood_group'); // A+, A-, B+, B-, AB+, AB-, O+, O-
            $table->string('component_type')->default('whole_blood'); // whole_blood, red_cells, platelets, plasma
            $table->string('units_available_range')->nullable();
            $table->string('availability_status')->default('available');
            $table->string('freshness_status')->default('fresh');
            $table->string('emergency_contact')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
        });

        // 8. facility_claims
        Schema::create('facility_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->uuid('claimant_user_id');
            $table->string('claim_status')->default('submitted'); // submitted, under_review, approved, rejected, revoked
            $table->text('claim_reason');
            $table->timestamp('submitted_at')->useCurrent();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
            $table->foreign('claimant_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });

        // 9. facility_reports
        Schema::create('facility_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->uuid('reported_by_user_id')->nullable();
            $table->string('report_type'); // wrong_location, wrong_phone, closed, inaccurate_stock, etc.
            $table->text('description')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status')->default('new'); // new, under_review, confirmed, rejected, resolved
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
            $table->foreign('reported_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });

        // 10. facility_update_audits
        Schema::create('facility_update_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->uuid('actor_id')->nullable();
            $table->string('actor_type'); // user, api_partner, system
            $table->string('field_changed');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('source')->nullable();
            $table->boolean('requires_review')->default(false);
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        // 11. saved_facilities
        Schema::create('saved_facilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('facility_id');
            $table->string('label')->nullable(); // e.g. "My Pharmacy", "Closest Hospital"
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
            $table->unique(['user_id', 'facility_id']);
        });

        // 12. medicine_reservation_requests
        Schema::create('medicine_reservation_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->nullable();
            $table->uuid('facility_id');
            $table->string('medicine_name');
            $table->integer('quantity_requested')->default(1);
            $table->string('status')->default('requested'); // requested, confirmed, rejected, cancelled, expired
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('care_facilities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_reservation_requests');
        Schema::dropIfExists('saved_facilities');
        Schema::dropIfExists('facility_update_audits');
        Schema::dropIfExists('facility_reports');
        Schema::dropIfExists('facility_claims');
        Schema::dropIfExists('blood_availability');
        Schema::dropIfExists('lab_test_availability');
        Schema::dropIfExists('pharmacy_stock_availability');
        Schema::dropIfExists('care_facility_insurance');
        Schema::dropIfExists('care_facility_hours');
        Schema::dropIfExists('care_facility_services');
        Schema::dropIfExists('care_facilities');
    }
};
