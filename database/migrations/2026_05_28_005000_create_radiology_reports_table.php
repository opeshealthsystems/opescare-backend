<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('radiology_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->uuid('imaging_order_id')->nullable()->index(); // soft FK — no FK constraint
            $table->foreignUuid('ordered_by')->constrained('users');
            $table->foreignUuid('reported_by')->constrained('users');
            $table->enum('modality', ['xray','ct','mri','ultrasound','echo','nuclear','pet','other']);
            $table->string('body_part', 150);
            $table->dateTime('study_date');
            $table->text('clinical_indication');
            $table->text('findings');
            $table->text('impression');
            $table->text('recommendation')->nullable();
            $table->enum('status', ['draft','preliminary','final','amended','corrected'])->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('amended_at')->nullable();
            $table->text('amendment_reason')->nullable();
            $table->json('distributed_to')->nullable();
            $table->timestamp('distributed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['facility_id', 'status']);
            $table->index(['patient_id', 'study_date']);
            $table->index('reported_by');
        });
    }
    public function down(): void { Schema::dropIfExists('radiology_reports'); }
};
