<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('mobile_money_transactions')) {
            return;
        }
        Schema::create('mobile_money_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->enum('provider', ['mtn_momo', 'orange_money']);
            $table->unsignedBigInteger('amount_xaf');
            $table->string('phone_number', 25);
            $table->string('reference')->unique();
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('provider_ref')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_money_transactions');
    }
};
