<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('claim_submissions')) {
            return;
        }
        Schema::create('claim_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->string('insurer_name');
            $table->string('claim_number')->unique();
            $table->date('service_date');
            $table->unsignedBigInteger('billed_amount');
            $table->unsignedBigInteger('paid_amount')->nullable();
            $table->json('diagnosis_codes');
            $table->json('procedure_codes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'paid', 'denied', 'appealed'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->text('denial_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_submissions');
    }
};
