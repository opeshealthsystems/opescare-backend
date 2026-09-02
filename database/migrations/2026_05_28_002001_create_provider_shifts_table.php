<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->date('shift_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('shift_type', 20)
                ->comment('morning|afternoon|evening|night|on_call|off');
            $table->boolean('is_confirmed')->default(false);
            $table->uuid('swap_requested_with')->nullable();
            $table->foreign('swap_requested_with')->references('id')->on('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('provider_id');
            $table->index('facility_id');
            $table->index('shift_date');
            $table->index(['facility_id', 'shift_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_shifts');
    }
};
