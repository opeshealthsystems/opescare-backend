<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('client_id')->unique()->index();
            $table->string('client_secret');
            $table->string('facility_id')->index();
            $table->jsonb('scopes');
            $table->string('status')->default('active'); // active, suspended, revoked
            $table->string('environment')->default('sandbox'); // sandbox, production
            $table->timestamps();
        });

        Schema::create('idempotency_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key')->index();
            $table->string('client_id')->index();
            $table->string('request_hash')->index();
            $table->integer('response_status');
            $table->jsonb('response_body');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['idempotency_key', 'client_id']);
        });

        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('client_id')->index();
            $table->string('callback_url');
            $table->string('webhook_secret');
            $table->jsonb('subscribed_events');
            $table->string('status')->default('active'); // active, paused
            $table->timestamps();
        });

        Schema::create('webhook_delivery_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_id')->index();
            $table->string('event_type');
            $table->jsonb('payload');
            $table->string('status')->default('pending'); // pending, delivered, failed
            $table->integer('retry_count')->default(0);
            $table->timestamps();
        });

        Schema::create('reconciliation_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('mismatch_reason');
            $table->string('external_reference')->nullable();
            $table->jsonb('submitted_payload');
            $table->string('status')->default('pending'); // pending, resolved
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_cases');
        Schema::dropIfExists('webhook_delivery_logs');
        Schema::dropIfExists('webhook_subscriptions');
        Schema::dropIfExists('idempotency_records');
        Schema::dropIfExists('integration_clients');
    }
};
