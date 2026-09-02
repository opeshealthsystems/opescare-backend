<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_payment_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('down_payment', 12, 2)->default(0);
            $table->decimal('installment_amount', 12, 2);
            $table->unsignedInteger('installment_count');
            $table->unsignedInteger('paid_count')->default(0);
            $table->string('frequency', 20)->comment('weekly|biweekly|monthly');
            $table->string('status', 20)->default('active')->comment('active|completed|defaulted|cancelled');
            $table->date('next_due_date');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index(['facility_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_payment_plans');
    }
};
