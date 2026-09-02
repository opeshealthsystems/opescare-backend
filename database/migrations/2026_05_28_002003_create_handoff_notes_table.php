<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handoff_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visit_id');
            $table->foreignUuid('from_provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('to_provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->text('summary');
            $table->json('active_problems')->nullable();
            $table->json('pending_orders')->nullable();
            $table->string('patient_status', 20)
                ->comment('stable|unstable|critical');
            $table->boolean('flag_for_follow_up')->default(false);
            $table->timestamp('handed_off_at');
            $table->timestamps();

            $table->index('visit_id');
            $table->index('from_provider_id');
            $table->index('to_provider_id');
            $table->index('handed_off_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handoff_notes');
    }
};
