<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 34 — Developer Portal + Webhook Delivery Schema Fix
 *
 * 1. Patch webhook_delivery_logs — add columns that SendWebhookJob already writes
 *    (webhook_subscription_id, endpoint_url, attempts, http_status_code,
 *    delivered_at, response_body).  Safe to run even if rows already exist.
 *
 * 2. Create developer_accounts   — registered external developers
 * 3. Create production_access_requests — formal request for production API access
 * 4. Create api_usage_snapshots  — per-client daily request counts per endpoint group
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Patch webhook_delivery_logs ────────────────────────────────────
        Schema::table('webhook_delivery_logs', function (Blueprint $table) {
            // FK to the subscription that owns this delivery
            if (!Schema::hasColumn('webhook_delivery_logs', 'webhook_subscription_id')) {
                $table->string('webhook_subscription_id')->nullable()->after('event_id')->index();
            }
            // Full URL logged at delivery time (subscription URL may change later)
            if (!Schema::hasColumn('webhook_delivery_logs', 'endpoint_url')) {
                $table->string('endpoint_url')->nullable()->after('webhook_subscription_id');
            }
            // Rename retry_count → attempts (idiomatic with the job code)
            // We keep retry_count for backwards compat and add attempts alongside
            if (!Schema::hasColumn('webhook_delivery_logs', 'attempts')) {
                $table->unsignedSmallInteger('attempts')->default(0)->after('status');
            }
            if (!Schema::hasColumn('webhook_delivery_logs', 'http_status_code')) {
                $table->unsignedSmallInteger('http_status_code')->nullable()->after('attempts');
            }
            if (!Schema::hasColumn('webhook_delivery_logs', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('http_status_code');
            }
            if (!Schema::hasColumn('webhook_delivery_logs', 'response_body')) {
                $table->text('response_body')->nullable()->after('delivered_at');
            }
            // exhausted status support (set by SendWebhookJob::failed())
            // status column already exists, just document the extended enum in a comment
        });

        // ── 2. Developer Accounts ────────────────────────────────────────────
        Schema::create('developer_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Link to internal User (nullable — developer may not have a staff account)
            $table->string('user_id')->nullable()->index();

            $table->string('display_name');
            $table->string('email')->unique();
            $table->string('company_name')->nullable();
            $table->string('website_url')->nullable();

            // Status lifecycle: pending_verification → active → suspended → closed
            $table->string('status')->default('pending_verification');

            // Email verification
            $table->string('email_verification_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            // Developer-specific API terms acceptance
            $table->boolean('api_terms_accepted')->default(false);
            $table->timestamp('api_terms_accepted_at')->nullable();
            $table->string('api_terms_version')->nullable();

            // Sandbox-only flag (production requires approval)
            $table->boolean('sandbox_only')->default(true);

            // Notes from admin review
            $table->text('admin_notes')->nullable();
            $table->string('suspended_by')->nullable();
            $table->string('suspend_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // ── 3. Production Access Requests ────────────────────────────────────
        Schema::create('production_access_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('developer_account_id')->index();
            $table->foreign('developer_account_id')
                  ->references('id')->on('developer_accounts')
                  ->onDelete('cascade');

            // The integration_client being promoted to production
            $table->string('integration_client_id')->nullable()->index();

            $table->string('use_case');                 // brief description
            $table->text('technical_description')->nullable();
            $table->jsonb('requested_scopes');          // array of scope strings
            $table->string('estimated_daily_requests')->nullable(); // e.g. "< 1 000"
            $table->boolean('handles_patient_data')->default(false);
            $table->string('data_residency_region')->nullable();
            $table->boolean('security_review_done')->default(false);
            $table->boolean('terms_accepted')->default(false);
            $table->string('terms_version')->nullable();

            // Review lifecycle: pending → under_review → approved → rejected → revoked
            $table->string('status')->default('pending');

            // Admin review fields
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->jsonb('approved_scopes')->nullable(); // may differ from requested_scopes
            $table->timestamp('approved_at')->nullable();
            $table->string('rejected_reason')->nullable();

            $table->timestamps();
        });

        // ── 4. API Usage Snapshots ────────────────────────────────────────────
        // Aggregated per client per endpoint-group per day (not per-request logs)
        Schema::create('api_usage_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('client_id')->index();
            $table->string('endpoint_group');   // e.g. 'patients', 'labs', 'consents', 'webhooks'
            $table->date('period_date');
            $table->string('environment')->default('sandbox'); // sandbox | production

            $table->unsignedInteger('request_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->unsignedInteger('rate_limited_count')->default(0);
            $table->unsignedSmallInteger('p95_latency_ms')->nullable(); // p95 latency in ms

            $table->timestamp('last_request_at')->nullable();

            $table->timestamps();

            // One snapshot per client × endpoint_group × day × environment
            $table->unique(['client_id', 'endpoint_group', 'period_date', 'environment'], 'api_usage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_snapshots');
        Schema::dropIfExists('production_access_requests');
        Schema::dropIfExists('developer_accounts');

        Schema::table('webhook_delivery_logs', function (Blueprint $table) {
            $cols = ['webhook_subscription_id', 'endpoint_url', 'attempts',
                     'http_status_code', 'delivered_at', 'response_body'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('webhook_delivery_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
