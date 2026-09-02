<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->string('status')->default('active');
            $table->decimal('outstanding_balance_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->unique(['patient_id', 'facility_id']);
        });

        Schema::create('price_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->string('service_code');
            $table->string('description');
            $table->decimal('unit_price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->unique(['facility_id', 'service_code']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('billing_account_id')->index();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('visit_id')->nullable()->index();
            $table->string('invoice_number')->unique();
            $table->string('status')->default('draft');
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('insurance_covered_amount', 12, 2)->default(0);
            $table->decimal('patient_responsibility_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('billing_account_id')->references('id')->on('billing_accounts')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('visit_id')->references('id')->on('visits')->onDelete('set null');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id')->index();
            $table->string('service_code')->nullable();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('line_total_amount', 12, 2);
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });

        Schema::create('cashier_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->uuid('cashier_id')->index();
            $table->string('status')->default('open');
            $table->decimal('cash_total_amount', 12, 2)->default(0);
            $table->timestampTz('opened_at')->useCurrent();
            $table->timestampTz('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('cashier_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id')->index();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('cashier_id')->nullable()->index();
            $table->uuid('cashier_session_id')->nullable()->index();
            $table->uuid('wallet_id')->nullable()->index();
            $table->string('payment_reference')->unique();
            $table->string('method');
            $table->string('status')->default('successful');
            $table->decimal('amount', 12, 2);
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('cashier_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cashier_session_id')->references('id')->on('cashier_sessions')->onDelete('set null');
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id')->index();
            $table->uuid('invoice_id')->index();
            $table->string('receipt_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->timestampTz('issued_at')->useCurrent();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });

        Schema::create('payment_reversals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id')->index();
            $table->uuid('invoice_id')->index();
            $table->uuid('actor_id')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('facility_id')->index();
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->unique(['patient_id', 'facility_id']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallet_id')->index();
            $table->uuid('payment_id')->nullable()->index();
            $table->uuid('actor_id')->nullable()->index();
            $table->string('transaction_type');
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('payment_reversals');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('cashier_sessions');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('billing_accounts');
    }
};
