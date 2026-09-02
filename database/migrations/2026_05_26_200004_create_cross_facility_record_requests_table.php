<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('cross_facility_record_requests')) {
            return;
        }
        Schema::create('cross_facility_record_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('requesting_facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignUuid('source_facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignUuid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('purpose');
            $table->json('record_types');
            $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled', 'expired'])->default('pending');
            $table->boolean('consent_obtained')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cross_facility_record_requests');
    }
};
