<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subscription & SaaS Billing tables (Module 23).
 *
 * NOTE: This is PLATFORM billing (facility subscriptions to OpesCare).
 * It is completely separate from patient medical billing (Module 08).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Subscription Plans ───────────────────────────────────────────────
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                   // Starter, Growth, Enterprise
            $table->string('slug')->unique();         // starter, growth, enterprise
            $table->string('billing_cycle');          // monthly, annual
            $table->unsignedInteger('price_kobo');    // price in smallest currency unit (kobo/cents)
            $table->string('currency', 3)->default('NGN');
            $table->text('description')->nullable();
            $table->json('features')->nullable();     // [{key, label, included:bool}]
            $table->unsignedInteger('max_facilities')->default(1);
            $table->unsignedInteger('max_staff')->nullable(); // null = unlimited
            $table->unsignedInteger('max_patients_per_month')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('trial_days')->default(0);
            $table->integer('sort_order')->default(0);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_public', 'sort_order']);
        });

        // ── Plan Features / Module Entitlements ──────────────────────────────
        Schema::create('plan_features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->string('feature_key');            // MODULE_CDSS, MODULE_BRIDGE, API_SDK, WEBHOOKS, ...
            $table->string('feature_label');
            $table->string('limit_type')->default('boolean'); // boolean, count, unlimited
            $table->unsignedInteger('limit_value')->nullable(); // null = unlimited
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('subscription_plans')->cascadeOnDelete();
            $table->unique(['plan_id', 'feature_key']);
        });

        // ── Organization Subscriptions ────────────────────────────────────────
        Schema::create('organization_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');          // links to facilities.id or future organizations table
            $table->string('organization_name');      // denormalized for display
            $table->uuid('plan_id');
            $table->string('status');                 // trialing, active, past_due, cancelled, expired, paused
            $table->date('trial_starts_at')->nullable();
            $table->date('trial_ends_at')->nullable();
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->date('cancelled_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_name')->nullable();
            $table->string('payment_reference')->nullable(); // external payment ref
            $table->string('payment_method')->nullable();    // bank_transfer, card, ussd
            $table->boolean('auto_renew')->default(true);
            $table->unsignedInteger('discount_percent')->default(0);
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('subscription_plans');
            $table->index(['organization_id', 'status']);
            $table->index(['status', 'current_period_end']);
        });

        // ── Subscription Invoices ────────────────────────────────────────────
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->uuid('organization_id');
            $table->string('invoice_number')->unique(); // INV-2026-00001
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('status');                 // draft, sent, paid, overdue, void
            $table->unsignedInteger('subtotal_kobo');
            $table->unsignedInteger('discount_kobo')->default(0);
            $table->unsignedInteger('tax_kobo')->default(0);
            $table->unsignedInteger('total_kobo');
            $table->string('currency', 3)->default('NGN');
            $table->json('line_items')->nullable();   // [{description, amount_kobo}]
            $table->string('payment_reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('organization_subscriptions')->cascadeOnDelete();
            $table->index(['subscription_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index('due_date');
        });

        // ── Usage Metrics ────────────────────────────────────────────────────
        Schema::create('subscription_usage_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->uuid('organization_id');
            $table->string('metric_key');             // PATIENTS_THIS_MONTH, STAFF_COUNT, API_CALLS, STORAGE_MB
            $table->unsignedBigInteger('metric_value')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('organization_subscriptions')->cascadeOnDelete();
            $table->unique(['subscription_id', 'metric_key', 'period_start']);
            $table->index(['organization_id', 'metric_key', 'period_start']);
        });

        // ── Module Entitlements (runtime enforcement) ─────────────────────────
        Schema::create('module_entitlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->uuid('organization_id');
            $table->string('module_key');             // CDSS, BRIDGE, SDK, WEBHOOKS, ANALYTICS_ADVANCED, ...
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('granted_by')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('organization_subscriptions')->cascadeOnDelete();
            $table->unique(['subscription_id', 'module_key']);
            $table->index(['organization_id', 'module_key', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_entitlements');
        Schema::dropIfExists('subscription_usage_metrics');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('organization_subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('subscription_plans');
    }
};
