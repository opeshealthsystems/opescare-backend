<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_waitlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignUuid('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('appointment_type', 100);
            $table->date('preferred_earliest_date')->nullable();
            $table->date('preferred_latest_date')->nullable();
            $table->string('urgency', 20)->default('routine')
                ->comment('routine|urgent');
            $table->string('status', 20)->default('waiting')
                ->comment('waiting|notified|booked|expired|cancelled');
            $table->text('notes')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->uuid('booked_appointment_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('facility_id');
            $table->index('status');
            $table->index(['facility_id', 'provider_id', 'status']);
            $table->index('preferred_latest_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_waitlists');
    }
};
