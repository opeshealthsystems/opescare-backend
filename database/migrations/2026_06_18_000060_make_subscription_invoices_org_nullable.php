<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B2C (patient/household) subscribers have no organization, so a paid patient
 * subscription invoice cannot carry an organization_id. Relax the column to
 * nullable — mirrors the same change already made on organization_subscriptions
 * in 2026_06_18_000010.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->uuid('organization_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->uuid('organization_id')->nullable(false)->change();
        });
    }
};
