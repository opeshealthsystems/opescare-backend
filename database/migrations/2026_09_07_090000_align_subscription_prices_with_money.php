<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make subscription prices mean what the payment rail means by an amount.
 *
 * ── The 100× error this removes ────────────────────────────────────────────
 * `App\Services\Payments\Domain\Money` states the rule explicitly and is the
 * class that actually moves money: XAF has an ISO 4217 exponent of **0**, so
 * for this currency the minor unit IS the franc. `Money::ofMinor(5000)` is
 * 5 000 FCFA, not 50.00.
 *
 * `subscription_plans` disagreed. Its column was named `price_kobo` — kobo is
 * Nigerian, and there is no centime in circulation for the CFA franc — and
 * `SubscriptionPlan::priceFormatted()` divided by 100 while labelling the
 * result FCFA. A plan priced the way the payment rail prices things would have
 * displayed at one hundredth of what it charged.
 *
 * Nothing had been priced yet (the table was empty), so this is the moment to
 * fix it rather than the moment to inherit it. The columns are renamed so the
 * next person cannot make the same mistake by reading the schema.
 *
 * ── Why three prices ───────────────────────────────────────────────────────
 * A patient may pay daily, monthly or yearly, and a facility pays yearly. A
 * plan therefore offers whichever intervals carry a price; a null means that
 * interval is not on offer. `billing_cycle` stays as the default choice.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The rename is only safe because nothing is priced yet. If a
        // deployment somehow has rows, stop rather than silently divide.
        if (Schema::hasColumn('subscription_plans', 'price_kobo') && DB::table('subscription_plans')->exists()) {
            throw new RuntimeException(
                'subscription_plans already holds rows priced under the old kobo convention. '
                . 'Convert them deliberately before running this migration.'
            );
        }

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_plans', 'price_kobo')) {
                $table->renameColumn('price_kobo', 'price_minor');
            }
            if (Schema::hasColumn('subscription_plans', 'annual_price_kobo')) {
                $table->renameColumn('annual_price_kobo', 'annual_price_minor');
            }
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_plans', 'daily_price_minor')) {
                $table->unsignedInteger('daily_price_minor')->nullable()->after('price_minor');
            }
            if (! Schema::hasColumn('subscription_plans', 'monthly_price_minor')) {
                $table->unsignedInteger('monthly_price_minor')->nullable()->after('daily_price_minor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['daily_price_minor', 'monthly_price_minor']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->renameColumn('price_minor', 'price_kobo');
            $table->renameColumn('annual_price_minor', 'annual_price_kobo');
        });
    }
};
