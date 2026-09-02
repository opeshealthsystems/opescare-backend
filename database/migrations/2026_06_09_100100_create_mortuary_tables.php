<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mortuary_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('facility_id')->constrained('facilities');
            $table->foreignUuid('patient_id')->nullable()->constrained('patients');
            $table->string('body_number', 30)->unique();
            $table->string('full_name', 200);
            $table->string('sex', 10)->nullable();
            $table->integer('approximate_age')->nullable();
            $table->text('cause_of_death')->nullable();
            $table->date('death_date')->nullable();
            $table->date('admission_date');
            $table->uuid('admitted_by')->nullable();
            $table->foreign('admitted_by')->references('id')->on('users')->nullOnDelete();
            $table->string('storage_location', 100)->nullable();
            $table->string('status', 20)->default('admitted');
            $table->uuid('identified_by')->nullable();
            $table->foreign('identified_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('identified_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('released_to', 200)->nullable();
            $table->uuid('released_by')->nullable();
            $table->foreign('released_by')->references('id')->on('users')->nullOnDelete();
            $table->string('next_of_kin_name', 200)->nullable();
            $table->string('next_of_kin_contact', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('facility_id');
            $table->index('patient_id');
            $table->index('status');
            $table->index('body_number');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE mortuary_records ADD CONSTRAINT chk_mortuary_status CHECK (status IN ('admitted','identified','released','transferred'))");
        }

        Schema::create('autopsy_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mortuary_record_id')->constrained('mortuary_records');
            $table->foreignUuid('facility_id')->constrained('facilities');
            $table->string('type', 10);
            $table->uuid('pathologist_id');
            $table->foreign('pathologist_id')->references('id')->on('users');
            $table->timestamp('performed_at');
            $table->text('gross_findings')->nullable();
            $table->text('microscopic_findings')->nullable();
            $table->text('toxicology_results')->nullable();
            $table->text('cause_of_death_confirmed');
            $table->string('manner_of_death', 30)->nullable();
            $table->text('external_findings')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index('mortuary_record_id');
            $table->index('facility_id');
            $table->index('pathologist_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE autopsy_reports ADD CONSTRAINT chk_autopsy_type CHECK (type IN ('clinical','forensic'))");
            DB::statement("ALTER TABLE autopsy_reports ADD CONSTRAINT chk_autopsy_status CHECK (status IN ('draft','signed','released'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('autopsy_reports');
        Schema::dropIfExists('mortuary_records');
    }
};
