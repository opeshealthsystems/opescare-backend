<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payment_plan_items')) {
            return;
        }
        Schema::create('payment_plan_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_plan_id')
                ->constrained('payment_plans')->cascadeOnDelete();
            $table->date('due_date');
            $table->unsignedBigInteger('amount');
            $table->enum('status', ['pending', 'paid', 'overdue', 'waived'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plan_items');
    }
};
