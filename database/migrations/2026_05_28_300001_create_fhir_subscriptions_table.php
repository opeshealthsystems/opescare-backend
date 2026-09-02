<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fhir_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('requested')
                ->comment('requested|active|error|off');
            $table->string('reason', 500)->nullable();
            $table->string('criteria', 500)
                ->comment('FHIR subscription criteria e.g. Observation?category=laboratory');
            $table->string('channel_type', 30)->default('rest-hook')
                ->comment('rest-hook|websocket|email|message');
            $table->string('endpoint', 500)->nullable()
                ->comment('Webhook URL for rest-hook channel');
            $table->json('headers')->nullable()
                ->comment('HTTP headers to include in webhook delivery');
            $table->string('payload_type', 50)->default('application/fhir+json')
                ->comment('MIME type of notification payload');
            $table->timestamp('end')->nullable()
                ->comment('Subscription expiry (null = indefinite)');
            $table->string('created_by', 36)->nullable()
                ->comment('User or API client that created this subscription');
            $table->string('signing_secret', 128)->nullable()
                ->comment('HMAC secret for webhook signature verification');
            $table->timestamp('last_notified_at')->nullable();
            $table->integer('error_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'channel_type']);
            $table->index('facility_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fhir_subscriptions');
    }
};
