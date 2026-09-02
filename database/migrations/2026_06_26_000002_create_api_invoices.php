<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('client_id');                 // IntegrationClient.client_id
            $table->string('facility_id')->nullable();
            $table->string('plan_key');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('included_requests')->nullable(); // null = unlimited plan
            $table->unsignedBigInteger('used_requests')->default(0);
            $table->unsignedBigInteger('overage_requests')->default(0);
            $table->unsignedBigInteger('base_amount_xaf')->default(0);
            $table->unsignedBigInteger('overage_amount_xaf')->default(0);
            $table->unsignedBigInteger('total_xaf')->default(0);
            $table->string('currency', 8)->default('XAF');
            $table->string('status')->default('issued'); // issued|paid|overdue|void
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_provider')->nullable(); // mtn_momo|orange_money
            $table->string('payment_reference')->nullable();
            $table->timestamps();

            // One invoice per client per billing period (idempotent generation).
            $table->unique(['client_id', 'period_start']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_invoices');
    }
};
