<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('waitlist_entries')) {
            return;
        }
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->json('preferred_dates');
            $table->text('reason')->nullable();
            $table->enum('status', ['waiting', 'notified', 'booked', 'expired'])->default('waiting');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->index(['provider_id', 'facility_id', 'status']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
