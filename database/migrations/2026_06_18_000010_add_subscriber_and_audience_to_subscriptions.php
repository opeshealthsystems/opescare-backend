<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generalize the subscription engine from organization-only to a polymorphic
 * subscriber (organization | patient | household) and tag the plan catalog by
 * audience. Additive and non-destructive: existing organization subscriptions
 * are backfilled and keep working unchanged.
 *
 * See docs/superpowers/specs/2026-06-17-subscription-billing-design.md (Phase 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // patient | household | facility | insurer | lab | pharmacy | healthorg | developer
            $table->string('audience')->default('facility')->after('slug');
            // Annual price in minor units (same convention as price_kobo). Null = no annual option.
            $table->unsignedInteger('annual_price_kobo')->nullable()->after('price_kobo');
            $table->index(['audience', 'is_active', 'is_public']);
        });

        Schema::table('organization_subscriptions', function (Blueprint $table) {
            // Polymorphic subscriber. Token-based morph map (organization|patient|household)
            // rather than FQCN so the same engine serves B2B and B2C cleanly.
            $table->string('subscriber_type')->default('organization')->after('id');
            $table->uuid('subscriber_id')->nullable()->after('subscriber_type');
            // Per-subscription cadence (the plan carries both monthly and annual prices).
            $table->string('interval')->default('monthly')->after('plan_id');
            $table->index(['subscriber_type', 'subscriber_id', 'status']);

            // B2C subscribers (patient/household) have no organization — relax the
            // legacy NOT NULL constraints so they aren't forced to carry org fields.
            $table->uuid('organization_id')->nullable()->change();
            $table->string('organization_name')->nullable()->change();
        });

        // Backfill: every existing row is an organization subscription.
        DB::table('organization_subscriptions')->update([
            'subscriber_type' => 'organization',
        ]);
        // subscriber_id mirrors the legacy organization_id (kept for compatibility).
        DB::statement('UPDATE organization_subscriptions SET subscriber_id = organization_id WHERE subscriber_id IS NULL');

        // Existing catalog is all facility/org plans.
        DB::table('subscription_plans')->update(['audience' => 'facility']);
    }

    public function down(): void
    {
        Schema::table('organization_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['subscriber_type', 'subscriber_id', 'status']);
            $table->dropColumn(['subscriber_type', 'subscriber_id', 'interval']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropIndex(['audience', 'is_active', 'is_public']);
            $table->dropColumn(['audience', 'annual_price_kobo']);
        });
    }
};
