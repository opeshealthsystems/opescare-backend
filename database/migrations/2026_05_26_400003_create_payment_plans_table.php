<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payment_plans')) {
            return;
        }
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->unsignedBigInteger('total_amount');
            $table->unsignedTinyInteger('installment_count');
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly'])->default('monthly');
            $table->date('start_date');
            $table->enum('status', ['active', 'completed', 'defaulted', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};
