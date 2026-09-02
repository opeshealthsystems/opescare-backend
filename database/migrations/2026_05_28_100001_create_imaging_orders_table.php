<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('ordered_by')->constrained('users')->cascadeOnDelete();
            $table->string('modality')->default('xray')
                ->comment('xray|ct|mri|ultrasound|echo|nuclear|pet|fluoroscopy|other');
            $table->string('body_part', 100)->nullable();
            $table->text('clinical_indication')->nullable();
            $table->string('urgency', 20)->default('routine')
                ->comment('routine|urgent|stat');
            $table->string('status', 30)->default('pending')
                ->comment('pending|scheduled|in_progress|completed|cancelled');
            $table->string('referring_physician', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('accession_number', 100)->nullable()->unique()
                ->comment('Assigned by PACS/RIS system on scheduling');
            $table->timestamp('ordered_at')->useCurrent();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['facility_id', 'status']);
            $table->index('ordered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_orders');
    }
};
