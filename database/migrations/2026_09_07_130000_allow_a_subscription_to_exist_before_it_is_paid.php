<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let a subscription exist without a paid-for period.
 *
 * ── Why ────────────────────────────────────────────────────────────────────
 * Taking a subscription and paying for it are two events separated by a
 * handset prompt that the subscriber may never answer. Between them there is a
 * row that says "this person asked for the Patient plan, monthly, and owes
 * 1 500 FCFA for it" — and that row must NOT say they have a period of access.
 *
 * `current_period_start` / `current_period_end` were NOT NULL, which left only
 * two ways to write that row: invent a period before the money arrives (a
 * failed payment then leaves someone marked as paid — the exact outcome this
 * work is required to prevent), or invent a sentinel date and hope every
 * reader remembers what it means. Both are worse than a null.
 *
 * A null period is the honest representation of "nothing has been paid for
 * yet", and it makes the guarantee structural rather than procedural: a
 * subscription that was never paid for has no period to check, so any future
 * gate that asks "is this period current?" answers no by construction, from
 * every code path, including one written by someone who never read this file.
 *
 * The period is written exactly once, by SubscriptionSettlementService, from
 * the interval that was actually paid for, and only after the invoice reaches
 * `paid`.
 *
 * Nothing is dropped and no existing row changes: every subscription that
 * already carries a period keeps it. Postgres only (this app does not target
 * MySQL); `->change()` here relaxes a constraint, so it is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_subscriptions', function (Blueprint $table) {
            $table->date('current_period_start')->nullable()->change();
            $table->date('current_period_end')->nullable()->change();
        });
    }

    /**
     * Deliberately partial, and it says so rather than pretending.
     *
     * Re-imposing NOT NULL would fail on any unpaid subscription this feature
     * has since created, and the only way to make it succeed would be to
     * fabricate a period for someone who never paid — which is the bug. So the
     * down path fills nothing in: it restores the constraint only when it can
     * do so truthfully, and otherwise leaves the column nullable and says why.
     */
    public function down(): void
    {
        $unpaid = \Illuminate\Support\Facades\DB::table('organization_subscriptions')
            ->whereNull('current_period_start')
            ->orWhereNull('current_period_end')
            ->count();

        if ($unpaid > 0) {
            throw new RuntimeException(
                "Refusing to re-impose NOT NULL on organization_subscriptions.current_period_* : {$unpaid} "
                .'subscription(s) have no paid period. Reversing this migration would require inventing a '
                .'period for a subscriber who has not paid. Delete or settle those rows deliberately first.'
            );
        }

        Schema::table('organization_subscriptions', function (Blueprint $table) {
            $table->date('current_period_start')->nullable(false)->change();
            $table->date('current_period_end')->nullable(false)->change();
        });
    }
};
