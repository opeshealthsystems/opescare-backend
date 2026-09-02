<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 46a — Connect Suite: Developer Portal & Webhooks
 *
 * Adds:
 *   developer_organizations         — developer org accounts
 *   developer_apps                  — registered API/SDK apps
 *   api_credentials                 — client_id/secret pairs
 *   api_scope_grants                — granted scopes per credential
 *   integration_certification_runs  — runs of an integration cert test suite
 *   api_usage_metrics               — per-app API usage counters
 *   developer_support_tickets       — support tickets from developer portal
 *   webhook_endpoints               — consumer-registered webhook endpoints
 *   webhook_secrets                 — signing secrets per endpoint
 *   webhook_events                  — events dispatched via webhooks
 *   webhook_replays                 — manual replays of webhook events
 *   webhook_dead_letters            — undeliverable webhook events
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('developer_organizations')) {
            Schema::create('developer_organizations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('developer_account_id');
                $table->string('name');
                $table->string('website')->nullable();
                $table->string('status')->default('active'); // active|suspended|pending
                $table->boolean('production_approved')->default(false);
                $table->timestamps();

                $table->index('developer_account_id', 'dorg_account_idx');
            });
        }

        if (! Schema::hasTable('developer_apps')) {
            Schema::create('developer_apps', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('developer_account_id');
                $table->uuid('developer_organization_id')->nullable();
                $table->string('app_name');
                $table->string('environment');             // sandbox|production
                $table->string('status')->default('active'); // active|suspended|revoked
                $table->text('description')->nullable();
                $table->json('allowed_scopes')->nullable();
                $table->json('redirect_uris')->nullable();
                $table->timestamps();

                $table->index('developer_account_id', 'dapp_account_idx');
                $table->index('environment', 'dapp_env_idx');
            });
        }

        if (! Schema::hasTable('api_credentials')) {
            Schema::create('api_credentials', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('developer_app_id');
                $table->string('client_id')->unique();
                $table->string('client_secret_hash');      // bcrypt hash; never store plain
                $table->string('credential_type');         // server_to_server|auth_code|pkce
                $table->string('environment');             // sandbox|production
                $table->string('status')->default('active'); // active|revoked|rotated
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index('developer_app_id', 'ac_app_idx');
                $table->index('status', 'ac_status_idx');
            });
        }

        if (! Schema::hasTable('api_scope_grants')) {
            Schema::create('api_scope_grants', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('api_credential_id');
                $table->string('scope');                   // e.g. patient:read|lab:write|etc
                $table->string('granted_by')->nullable();
                $table->timestamp('granted_at');
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->index('api_credential_id', 'asg_credential_idx');
                $table->unique(['api_credential_id', 'scope'], 'asg_cred_scope_unique');
            });
        }

        if (! Schema::hasTable('integration_certification_runs')) {
            Schema::create('integration_certification_runs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('integration_certification_id')->nullable();
                $table->uuid('developer_app_id');
                $table->string('status');                  // pending|running|passed|failed
                $table->integer('tests_total')->default(0);
                $table->integer('tests_passed')->default(0);
                $table->integer('tests_failed')->default(0);
                $table->json('results')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index('developer_app_id', 'icr_app_idx');
                $table->index('status', 'icr_status_idx');
            });
        }

        if (! Schema::hasTable('api_usage_metrics')) {
            Schema::create('api_usage_metrics', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('api_credential_id');
                $table->string('endpoint')->nullable();
                $table->string('method')->nullable();      // GET|POST|PUT|DELETE
                $table->integer('request_count')->default(0);
                $table->integer('error_count')->default(0);
                $table->float('avg_response_ms')->nullable();
                $table->string('period');                  // hourly|daily|monthly
                $table->timestamp('period_start');
                $table->timestamps();

                $table->index('api_credential_id', 'aum_credential_idx');
                $table->index(['period', 'period_start'], 'aum_period_idx');
            });
        }

        if (! Schema::hasTable('developer_support_tickets')) {
            Schema::create('developer_support_tickets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('developer_account_id');
                $table->uuid('developer_app_id')->nullable();
                $table->string('subject');
                $table->text('description');
                $table->string('category');                // integration|billing|bug|feature_request
                $table->string('status')->default('open'); // open|in_progress|resolved|closed
                $table->string('priority')->default('normal'); // low|normal|high|critical
                $table->uuid('assigned_to')->nullable();
                $table->timestamps();

                $table->index('developer_account_id', 'dst_account_idx');
                $table->index('status', 'dst_status_idx');
            });
        }

        // ── Webhooks ──────────────────────────────────────────────────────────

        if (! Schema::hasTable('webhook_endpoints')) {
            Schema::create('webhook_endpoints', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('webhook_subscription_id')->nullable();
                $table->uuid('developer_app_id')->nullable();
                $table->string('url');
                $table->json('event_types');               // array of subscribed event types
                $table->string('status')->default('active'); // active|disabled|failed
                $table->integer('failure_count')->default(0);
                $table->timestamp('last_delivery_at')->nullable();
                $table->timestamps();

                $table->index('status', 'we_status_idx');
            });
        }

        if (! Schema::hasTable('webhook_secrets')) {
            Schema::create('webhook_secrets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('webhook_endpoint_id');
                $table->string('secret_hash');             // HMAC signing secret, hashed
                $table->string('algorithm')->default('sha256');
                $table->boolean('is_active')->default(true);
                $table->timestamp('rotated_at')->nullable();
                $table->timestamps();

                $table->index('webhook_endpoint_id', 'ws_endpoint_idx');
            });
        }

        if (! Schema::hasTable('webhook_events')) {
            Schema::create('webhook_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('event_type');
                $table->json('payload');
                $table->string('signature')->nullable();   // HMAC signature of payload
                $table->boolean('is_sensitive')->default(false);
                $table->timestamps();

                $table->index('event_type', 'wev_type_idx');
                $table->index('created_at', 'wev_created_idx');
            });
        }

        if (! Schema::hasTable('webhook_replays')) {
            Schema::create('webhook_replays', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('webhook_event_id');
                $table->uuid('webhook_endpoint_id');
                $table->uuid('replayed_by');
                $table->string('status');                  // pending|delivered|failed
                $table->integer('response_code')->nullable();
                $table->text('response_body')->nullable();
                $table->timestamps();

                $table->index('webhook_event_id', 'wr_event_idx');
                $table->index('status', 'wr_status_idx');
            });
        }

        if (! Schema::hasTable('webhook_dead_letters')) {
            Schema::create('webhook_dead_letters', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('webhook_event_id');
                $table->uuid('webhook_endpoint_id');
                $table->integer('attempt_count')->default(0);
                $table->text('last_error')->nullable();
                $table->integer('last_response_code')->nullable();
                $table->timestamp('last_attempted_at')->nullable();
                $table->boolean('manually_resolved')->default(false);
                $table->timestamps();

                $table->index('webhook_endpoint_id', 'wdl_endpoint_idx');
                $table->index('manually_resolved', 'wdl_resolved_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_dead_letters');
        Schema::dropIfExists('webhook_replays');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('webhook_secrets');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('developer_support_tickets');
        Schema::dropIfExists('api_usage_metrics');
        Schema::dropIfExists('integration_certification_runs');
        Schema::dropIfExists('api_scope_grants');
        Schema::dropIfExists('api_credentials');
        Schema::dropIfExists('developer_apps');
        Schema::dropIfExists('developer_organizations');
    }
};
