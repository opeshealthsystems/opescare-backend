<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('remittance_advices')) {
            return;
        }
        Schema::create('remittance_advices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('claim_submission_id')
                ->constrained('claim_submissions')->cascadeOnDelete();
            $table->unsignedBigInteger('paid_amount');
            $table->unsignedBigInteger('adjustment_amount')->default(0);
            $table->string('adjustment_reason')->nullable();
            $table->date('paid_on');
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittance_advices');
    }
};
