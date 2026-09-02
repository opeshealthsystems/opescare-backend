<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plan_installments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_plan_id')
                ->constrained('patient_payment_plans')
                ->cascadeOnDelete();
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending')->comment('pending|paid|partial|missed');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->timestamps();

            $table->index(['payment_plan_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plan_installments');
    }
};
