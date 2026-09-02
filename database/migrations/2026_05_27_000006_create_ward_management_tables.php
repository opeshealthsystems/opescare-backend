<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wards (e.g. General Medicine, ICU, Maternity)
        Schema::create('wards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('facility_id')->index();
            $table->string('name');                         // "General Ward A"
            $table->string('ward_type');                    // general | icu | maternity | pediatric | surgical | emergency | isolation
            $table->unsignedSmallInteger('total_beds')->default(0);
            $table->string('floor')->nullable();
            $table->string('building')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('head_nurse_id')->nullable();    // staff UUID
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Beds within a ward
        Schema::create('beds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ward_id')->index();
            $table->foreign('ward_id')->references('id')->on('wards')->cascadeOnDelete();
            $table->string('bed_number');                   // e.g. "A-01"
            $table->string('status')->default('available'); // available | occupied | maintenance | reserved
            $table->string('bed_type')->default('standard'); // standard | icu | isolation | maternity
            $table->boolean('has_oxygen')->default(false);
            $table->boolean('has_monitor')->default(false);
            $table->timestamps();

            $table->unique(['ward_id', 'bed_number']);
        });

        // Admissions (patient <-> bed allocations)
        Schema::create('admissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('facility_id')->index();
            $table->string('patient_id')->index();
            $table->uuid('bed_id')->index();
            $table->foreign('bed_id')->references('id')->on('beds');
            $table->string('visit_id')->nullable()->index(); // link to Visit
            $table->string('admitted_by');                   // staff UUID
            $table->string('attending_physician_id')->nullable();
            $table->string('status')->default('active');     // active | discharged | transferred
            $table->string('admission_reason')->nullable();
            $table->string('discharge_reason')->nullable();
            $table->string('discharge_destination')->nullable(); // home | referral | ama | deceased
            $table->timestamp('admitted_at');
            $table->timestamp('discharged_at')->nullable();
            $table->timestamps();
        });

        // Bed transfer log
        Schema::create('bed_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admission_id')->index();
            $table->foreign('admission_id')->references('id')->on('admissions')->cascadeOnDelete();
            $table->uuid('from_bed_id')->nullable();
            $table->uuid('to_bed_id');
            $table->string('reason')->nullable();
            $table->string('transferred_by');
            $table->timestamp('transferred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_transfers');
        Schema::dropIfExists('admissions');
        Schema::dropIfExists('beds');
        Schema::dropIfExists('wards');
    }
};
