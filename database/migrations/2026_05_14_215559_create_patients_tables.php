<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('health_id')->unique()->comment('The visible public OpesCare Health ID');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_dob_estimated')->default(false);
            $table->string('sex')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->jsonb('emergency_contact')->nullable();
            $table->string('identity_status')->default('provisional')->comment('provisional, verified_by_facility, merged, deceased');
            $table->uuid('verified_by_facility_id')->nullable()->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('verified_by_facility_id')->references('id')->on('facilities')->onDelete('set null');
        });

        Schema::create('patient_identifiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->string('identifier_type')->comment('national_id, insurance_number, local_hospital_id');
            $table->string('identifier_value');
            $table->string('issuer')->nullable();
            $table->uuid('facility_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('set null');

            $table->unique(['identifier_type', 'identifier_value', 'issuer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_identifiers');
        Schema::dropIfExists('patients');
    }
};
