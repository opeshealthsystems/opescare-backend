<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff Profiles — core HR record for each staff member
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->unique(); // linked user account
            $table->uuid('facility_id');
            $table->string('employee_number')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('staff_category'); // clinical, administrative, support, management
            $table->string('employment_type')->default('full_time'); // full_time, part_time, contract, locum
            $table->date('hire_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->string('status')->default('active'); // active, inactive, on_leave, suspended, terminated
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('restrict');
        });

        // Professional Licenses
        Schema::create('professional_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('staff_profile_id');
            $table->string('profession'); // doctor, nurse, pharmacist, lab_technician, etc.
            $table->string('license_number');
            $table->string('issuing_body'); // Medical Council of Cameroon, etc.
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('active'); // active, expired, suspended, revoked
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('staff_profile_id')->references('id')->on('staff_profiles')->onDelete('cascade');
        });

        // Department Assignments (staff can serve multiple departments)
        Schema::create('department_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('staff_profile_id');
            $table->string('department');
            $table->boolean('is_primary')->default(false);
            $table->date('assigned_from')->nullable();
            $table->date('assigned_until')->nullable();
            $table->timestamps();

            $table->foreign('staff_profile_id')->references('id')->on('staff_profiles')->onDelete('cascade');
        });

        // Shift Definitions
        Schema::create('staff_shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->string('name'); // Morning, Afternoon, Night, On-Call
            $table->string('department')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('crosses_midnight')->default(false);
            $table->integer('duration_hours')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('restrict');
        });

        // Duty Rosters (a published schedule for a period)
        Schema::create('duty_rosters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id');
            $table->string('department');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('draft'); // draft, published, archived
            $table->string('created_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('restrict');
        });

        // Roster Assignments (who works which shift on which date)
        Schema::create('roster_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('duty_roster_id');
            $table->uuid('staff_profile_id');
            $table->uuid('staff_shift_id');
            $table->date('work_date');
            $table->string('status')->default('scheduled'); // scheduled, confirmed, swapped, cancelled
            $table->string('assigned_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('duty_roster_id')->references('id')->on('duty_rosters')->onDelete('cascade');
            $table->foreign('staff_profile_id')->references('id')->on('staff_profiles')->onDelete('restrict');
            $table->foreign('staff_shift_id')->references('id')->on('staff_shifts')->onDelete('restrict');

            // Prevent double-booking the same staff on the same date and shift
            $table->unique(['staff_profile_id', 'work_date', 'staff_shift_id'], 'roster_no_double_booking');
        });

        // Leave Requests
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('staff_profile_id');
            $table->string('leave_type'); // annual, sick, emergency, maternity, paternity, study, unpaid
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_requested')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, withdrawn, cancelled
            $table->string('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('staff_profile_id')->references('id')->on('staff_profiles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('roster_assignments');
        Schema::dropIfExists('duty_rosters');
        Schema::dropIfExists('staff_shifts');
        Schema::dropIfExists('department_assignments');
        Schema::dropIfExists('professional_licenses');
        Schema::dropIfExists('staff_profiles');
    }
};
