<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix OpesCare Lite currency from NGN (Nigeria) to XAF (CFA Franc, Cameroon).
 *
 * The original migration shipped with 'NGN' as default for lite_configs.currency_code.
 * OpesCare is deployed in Cameroon (XAF). This migration corrects the column default
 * and back-fills any existing records that still carry the wrong currency code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lite_configs', function (Blueprint $table) {
            $table->string('currency_code', 10)->default('XAF')->change();
        });

        // Back-fill existing records
        DB::table('lite_configs')
            ->where('currency_code', 'NGN')
            ->update(['currency_code' => 'XAF']);
    }

    public function down(): void
    {
        Schema::table('lite_configs', function (Blueprint $table) {
            $table->string('currency_code', 10)->default('NGN')->change();
        });

        DB::table('lite_configs')
            ->where('currency_code', 'XAF')
            ->update(['currency_code' => 'NGN']);
    }
};
