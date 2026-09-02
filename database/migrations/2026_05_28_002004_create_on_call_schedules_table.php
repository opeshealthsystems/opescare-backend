<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('on_call_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->string('specialty', 30)
                ->comment('general|surgery|paediatrics|obstetrics|internal_medicine|emergency|other');
            $table->date('on_call_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->uuid('backup_provider_id')->nullable();
            $table->foreign('backup_provider_id')->references('id')->on('users')->nullOnDelete();
            $table->boolean('is_confirmed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('provider_id');
            $table->index('facility_id');
            $table->index('on_call_date');
            $table->index(['facility_id', 'specialty', 'on_call_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('on_call_schedules');
    }
};
