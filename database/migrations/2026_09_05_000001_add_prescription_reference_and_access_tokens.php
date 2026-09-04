<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The paper-to-digital bridge for a prescription.
 *
 * Two things were missing. A prescription had no human-quotable identity — only
 * a UUID, which nobody reads down a phone line or off a printed sheet — so a
 * pharmacy could look a patient up by Health ID but could never be handed "the
 * prescription number". And there was no way to verify a printed script at all:
 * the Health ID has a scan-to-verify page, a prescription had nothing.
 *
 * `reference` gives every prescription a short, unique, unambiguous handle.
 * `prescription_access_tokens` lets one be presented — printed or on a phone —
 * and verified by whoever holds it, on a revocable, expiring token, following
 * exactly the split-token shape health_id_qr_tokens already uses: the raw token
 * is `rx_<lookupId>_<secret>`, only the secret's hash is stored, and lookup is
 * O(1) on the indexed lookup_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            // Nullable: existing rows are backfilled below, and a prescription
            // issued by an older code path must not fail to save.
            $table->string('reference', 20)->nullable()->after('id');
        });

        // Backfill before the unique index, so the index cannot fail on NULLs
        // colliding under some drivers, and so old prescriptions are quotable too.
        foreach (DB::table('prescriptions')->select('id')->orderBy('id')->cursor() as $row) {
            DB::table('prescriptions')->where('id', $row->id)->update([
                'reference' => self::referenceFromId($row->id),
            ]);
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->unique('reference');
        });

        Schema::create('prescription_access_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prescription_id');

            // The half that identifies the row (indexed, not secret) and the
            // hash of the half that proves possession. Never store the secret.
            $table->string('lookup_id', 24)->unique();
            $table->string('token_hash');

            $table->string('status', 20)->default('active');   // active|revoked
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            // Presenting a script is a disclosure; the patient is entitled to see
            // that it happened, so every scan is counted and timestamped.
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedInteger('use_count')->default(0);

            // Who minted it — the prescriber, the patient from their app, or a
            // pharmacist reprinting at the counter.
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('prescription_id')->references('id')->on('prescriptions')->cascadeOnDelete();
            $table->index(['prescription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_access_tokens');

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn('reference');
        });
    }

    /**
     * A stable, readable handle derived from the UUID: RX-XXXX-XXXX.
     *
     * Deterministic on purpose — re-running the backfill cannot renumber a
     * prescription a patient is already holding on paper. Crockford's alphabet
     * (no I, L, O or U) so a reference read aloud or copied by hand does not
     * turn a 1 into an I or a 0 into an O.
     */
    private static function referenceFromId(string $id): string
    {
        $hex = preg_replace('/[^0-9a-f]/i', '', $id);
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $out = '';

        for ($i = 0; $i < 8; $i++) {
            $chunk = hexdec(substr($hex, $i * 4, 4) ?: '0');
            $out .= $alphabet[$chunk % 32];
        }

        return 'RX-' . substr($out, 0, 4) . '-' . substr($out, 4, 4);
    }
};
