<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix subscription billing currency from NGN (Nigeria) to XAF (CFA Franc, Cameroon).
 *
 * The original subscription billing tables shipped with 'NGN' as the default for the
 * currency column on subscription_plans and subscription_invoices. OpesCare is deployed
 * in Cameroon (XAF). This corrects the column defaults and back-fills existing records,
 * mirroring the earlier lite_configs currency fix.
 *
 * Note: amounts remain stored in the existing *_kobo integer columns (value × 100).
 * That representation is kept as-is to avoid a disruptive schema rename; only the
 * currency code and user-facing labels change to XAF / FCFA.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['subscription_plans', 'subscription_invoices'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'currency')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->string('currency', 3)->default('XAF')->change();
            });

            DB::table($tableName)
                ->where('currency', 'NGN')
                ->update(['currency' => 'XAF']);
        }
    }

    public function down(): void
    {
        foreach (['subscription_plans', 'subscription_invoices'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'currency')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->string('currency', 3)->default('NGN')->change();
            });

            DB::table($tableName)
                ->where('currency', 'XAF')
                ->update(['currency' => 'NGN']);
        }
    }
};
