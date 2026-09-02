<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Interoperability Platform — facility/client scoping columns
 *
 * These columns were added by the security audit (2026-06-07) to fix:
 *
 *   H-2: WebhookService dispatch() fan-out to ALL subscriptions — cross-facility
 *        data leak. Fix requires facility_id + client_id on webhook_events and
 *        facility_id on webhook_subscriptions so delivery can be scoped.
 *
 *   H-1: ReconciliationController::listCases() returned all cases from all
 *        facilities (IDOR). Fix requires facility_id on reconciliation_cases.
 *
 * All columns are nullable to preserve backward-compatibility with existing rows
 * and to allow the webhook pipeline to operate even when facility context is
 * absent (e.g. system-level events that are not patient-data events).
 *
 * ISO 27001 A.9.1 / OWASP API1 — data must be scoped to authorised parties only.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── webhook_events ────────────────────────────────────────────────────
        // facility_id : scopes which facility originated this event
        // client_id   : scopes which integration client originated this event
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->uuid('facility_id')->nullable()->after('payload')
                ->comment('Originating facility — null for system-level events');
            $table->string('client_id')->nullable()->after('facility_id')
                ->comment('Originating integration client');

            $table->index('facility_id', 'we_facility_id_idx');
            $table->index('client_id',   'we_client_id_idx');
        });

        // ── webhook_subscriptions ─────────────────────────────────────────────
        // facility_id : scope delivery to subscribers of this facility only
        Schema::table('webhook_subscriptions', function (Blueprint $table) {
            $table->uuid('facility_id')->nullable()->after('client_id')
                ->comment('Subscribing facility — scopes delivery in WebhookService');

            $table->index('facility_id', 'ws_facility_id_idx');
        });

        // ── reconciliation_cases ──────────────────────────────────────────────
        // facility_id : ownership field; listCases() and resolveCase() filter by it
        Schema::table('reconciliation_cases', function (Blueprint $table) {
            $table->uuid('facility_id')->nullable()->after('id')
                ->comment('Owning facility — required for IDOR-safe case listing and resolution');

            $table->index('facility_id', 'rc_facility_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropIndex('we_facility_id_idx');
            $table->dropIndex('we_client_id_idx');
            $table->dropColumn(['facility_id', 'client_id']);
        });

        Schema::table('webhook_subscriptions', function (Blueprint $table) {
            $table->dropIndex('ws_facility_id_idx');
            $table->dropColumn('facility_id');
        });

        Schema::table('reconciliation_cases', function (Blueprint $table) {
            $table->dropIndex('rc_facility_id_idx');
            $table->dropColumn('facility_id');
        });
    }
};
