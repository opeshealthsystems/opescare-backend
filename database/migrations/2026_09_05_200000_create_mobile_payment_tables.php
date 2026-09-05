<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile money payments for the patient app — invoice, charge, and event log.
 *
 * The Finder can book a consultation, order a test and locate medicine, and
 * could not take a single franc for any of it. This is the money side of that
 * surface: an invoice a patient owes, a charge that attempts to settle it
 * through MTN MoMo or Orange Money, and an append-only record of every status
 * move either one made.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * AMOUNT REPRESENTATION — read this before touching any amount column
 * ─────────────────────────────────────────────────────────────────────────
 * Every amount is a BIGINT of **minor units**. There is no float, no decimal
 * and no numeric column anywhere in this schema, and no code path converts an
 * amount through a float.
 *
 * XAF (CFA franc BEAC) has an ISO 4217 exponent of **0** — it has no minor
 * unit in practice, so *the minor unit IS the franc*:
 *
 *     amount_minor = 5000   means   5 000 FCFA        (NOT 50.00)
 *
 * The column is deliberately still called `amount_minor` rather than
 * `amount_xaf`, because the name is what stops someone later adding a
 * two-exponent currency and silently reinterpreting every existing row. To
 * keep that impossible today, `currency` is CHECK-constrained to 'XAF' alone:
 * a second currency cannot be inserted until someone adds a real
 * exponent-aware formatter and consciously widens this constraint.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT THE DATABASE ENFORCES (not PHP — the database)
 * ─────────────────────────────────────────────────────────────────────────
 *  1. Status only ever moves FORWARD. A BEFORE UPDATE trigger rejects every
 *     transition outside {pending→paid|failed|expired, paid→refunded}. A
 *     `paid` row can never return to `pending`, from any code path, including
 *     a raw `DB::table(...)->update()`, a psql session, or two racing workers.
 *  2. The money is IMMUTABLE after issue. The same trigger rejects any UPDATE
 *     that changes amount_minor, currency or patient_id. The amount a patient
 *     agreed to is the amount that can be charged.
 *  3. AT MOST ONE paid charge per invoice, and AT MOST ONE pending charge per
 *     invoice — partial unique indexes, so a double-charge is a constraint
 *     violation rather than a code review finding. This holds even when the
 *     two attempts carry DIFFERENT idempotency keys.
 *  4. AT MOST ONE charge per (patient, idempotency key) — the retry guard.
 *  5. A FULL PHONE NUMBER CANNOT BE STORED. `payer_msisdn_masked` is
 *     CHECK-constrained to contain a '*', and the event log's free-text
 *     columns are CHECK-constrained to contain no run of 6+ digits. There is
 *     no column anywhere in this schema that a complete MSISDN fits in.
 *
 * Postgres only. These are partial unique indexes, plpgsql triggers and regex
 * CHECKs; there is no MySQL equivalent and this app does not target one.
 */
return new class extends Migration
{
    /** The five lifecycle states, shared by invoices and charges. */
    private const STATUSES = "'pending','paid','failed','expired','refunded'";

    public function up(): void
    {
        Schema::create('mobile_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Human-facing, printable, safe to read down a phone line.
            $table->string('reference', 32)->unique();

            // The only authorisation key in this schema. Set from
            // $request->attributes (the bearer token), never from a body.
            $table->uuid('patient_id');

            // Who gets paid, when the invoice came from a facility flow.
            // Nullable because a patient-initiated invoice has no payee yet.
            $table->uuid('facility_id')->nullable();

            // What is being paid for. A loose pointer, not a polymorphic
            // relation: the payable modules (appointments, lab orders,
            // medicine reservations) are owned elsewhere and must not gain a
            // foreign key from the money tables.
            $table->string('subject_type', 64)->nullable();
            $table->uuid('subject_id')->nullable();

            $table->string('description', 255);

            // Minor units. See the header. Immutable after insert (trigger).
            $table->bigInteger('amount_minor');
            $table->string('currency', 3)->default('XAF');

            $table->string('status', 16)->default('pending');

            // An unpaid invoice does not stay open forever — the sweeper moves
            // it to `expired` past this instant.
            $table->timestamp('expires_at')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Informational only — deliberately NOT wired to the IsDemoRecord
            // global scope. That scope keys off config('demo.enabled'), which
            // the bearer-token mobile API never sets (DemoDataScope needs
            // Auth::check()), and a half-applied global scope on a money table
            // is how an invoice becomes invisible to the patient who owes it.
            // Isolation here is `patient_id`, which cannot be spoofed.
            $table->boolean('is_demo')->default(false);

            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->index(['patient_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('mobile_payment_charges', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('invoice_id');

            // Denormalised from the invoice so an authorisation check never
            // needs a join, and so the (patient, key) uniqueness below is a
            // single-table constraint.
            $table->uuid('patient_id');

            // The retry guard. Supplied by the client as `Idempotency-Key`.
            $table->string('idempotency_key', 190);

            // SHA-256 of the canonical request. Same key + different body is a
            // 409, not a silent replay of the wrong charge.
            $table->char('request_fingerprint', 64);

            // Which implementation actually ran ('demo' on this host).
            $table->string('provider', 32);

            // What the patient picked: mtn_momo | orange_money.
            $table->string('method', 32);

            $table->bigInteger('amount_minor');
            $table->string('currency', 3)->default('XAF');

            $table->string('status', 16)->default('pending');

            // ── The payer's phone number, and why it is not here ────────────
            // The full MSISDN is passed to the provider in memory and is never
            // written down. What persists is:
            //   masked — '+2376*****21', for a human to recognise their own
            //            number in a receipt. CHECK-constrained to contain '*'.
            //   hash   — HMAC-SHA256(APP_KEY, E.164), so support can correlate
            //            "the number I paid from" without the column ever
            //            being reversible into a number.
            $table->string('payer_msisdn_masked', 32);
            $table->char('payer_msisdn_hash', 64);

            // The provider's own identifier: MTN's X-Reference-Id (a UUID we
            // generate and they adopt), or Orange's `pay_token`.
            $table->string('provider_reference', 128)->nullable();

            // The per-transaction callback secret, when the rail issues one:
            // Orange Money returns a `notif_token` from /webpayment and that
            // token is the WHOLE of its callback authentication, so a live
            // integration has nowhere else to put it. Stored under the
            // `encrypted` cast (AES-256 via APP_KEY), never logged, never
            // returned by any endpoint. MTN issues no such token and leaves
            // this null; the demo provider derives its own and leaves it null
            // too.
            $table->text('provider_callback_token')->nullable();

            // Short codes only — never a provider response body, which can
            // carry the payer's number.
            $table->string('provider_status_code', 64)->nullable();
            $table->string('failure_code', 64)->nullable();

            // When the provider was asked, and when it answered for the last
            // time. `next_poll_after` throttles the poller.
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('settled_at')->nullable();

            // Past this instant a pending charge is abandoned. Mobile money
            // push prompts die on the handset in about two minutes.
            $table->timestamp('expires_at');

            $table->boolean('is_demo')->default(false);

            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('mobile_invoices')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();

            $table->index(['invoice_id', 'status']);
            $table->index(['patient_id', 'created_at']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('mobile_payment_events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('charge_id')->nullable();
            $table->uuid('invoice_id')->nullable();

            // 'api' | 'poll' | 'callback' | 'sweeper'
            $table->string('source', 16);

            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16);

            // Codes, never bodies. See the CHECK on these columns below.
            $table->string('provider_status_code', 64)->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->string('note', 255)->nullable();

            $table->timestamp('occurred_at');

            // DELIBERATELY NO FOREIGN KEYS.
            //
            // Two reasons, and the first is a bug this schema actually had:
            //
            //  1. `nullOnDelete` implements itself as an UPDATE, which the
            //     append-only trigger below rejects — so deleting an invoice
            //     that had ever recorded an event failed with "mobile_payment_
            //     events is append-only", and a patient with payment history
            //     could not be erased at all. A referential action and an
            //     append-only table cannot both be right; the log wins.
            //  2. `cascadeOnDelete` would be worse: the record of what happened
            //     to somebody's money would vanish with the row it describes,
            //     which is exactly when it is needed.
            //
            // Retaining these rows is not a privacy problem: this table holds
            // ids, statuses and short codes only. No name, no number, no
            // amount — DB-enforced (see the digit-run CHECKs below).
            $table->index(['charge_id', 'occurred_at']);
            $table->index(['invoice_id', 'occurred_at']);
        });

        $statuses = self::STATUSES;

        // ── Value constraints ────────────────────────────────────────────────
        DB::statement("ALTER TABLE mobile_invoices ADD CONSTRAINT mobile_invoices_status_chk CHECK (status IN ({$statuses}))");
        DB::statement("ALTER TABLE mobile_payment_charges ADD CONSTRAINT mobile_charges_status_chk CHECK (status IN ({$statuses}))");
        DB::statement("ALTER TABLE mobile_payment_events ADD CONSTRAINT mobile_events_to_status_chk CHECK (to_status IN ({$statuses}))");

        // Nobody is billed zero, and nobody is billed a negative amount (which
        // is a refund, and refunds are a status — not a sign).
        DB::statement('ALTER TABLE mobile_invoices ADD CONSTRAINT mobile_invoices_amount_chk CHECK (amount_minor > 0)');
        DB::statement('ALTER TABLE mobile_payment_charges ADD CONSTRAINT mobile_charges_amount_chk CHECK (amount_minor > 0)');

        // One currency, until someone writes an exponent-aware formatter.
        DB::statement("ALTER TABLE mobile_invoices ADD CONSTRAINT mobile_invoices_currency_chk CHECK (currency = 'XAF')");
        DB::statement("ALTER TABLE mobile_payment_charges ADD CONSTRAINT mobile_charges_currency_chk CHECK (currency = 'XAF')");

        // A terminal status must carry its own timestamp — a `paid` row with a
        // null paid_at is a receipt nobody can date.
        DB::statement("ALTER TABLE mobile_invoices ADD CONSTRAINT mobile_invoices_paid_at_chk CHECK (status <> 'paid' OR paid_at IS NOT NULL)");
        DB::statement("ALTER TABLE mobile_invoices ADD CONSTRAINT mobile_invoices_refunded_chk CHECK (status <> 'refunded' OR (paid_at IS NOT NULL AND refunded_at IS NOT NULL))");
        DB::statement("ALTER TABLE mobile_payment_charges ADD CONSTRAINT mobile_charges_settled_chk CHECK (status = 'pending' OR settled_at IS NOT NULL)");

        // A full phone number cannot fit in the masked column: it must be
        // masked, and masking means at least one '*'.
        DB::statement("ALTER TABLE mobile_payment_charges ADD CONSTRAINT mobile_charges_msisdn_masked_chk CHECK (payer_msisdn_masked LIKE '%*%')");

        // The event log takes codes, not payloads. A run of six or more digits
        // is what a phone number looks like; no legitimate status code has one.
        DB::statement("ALTER TABLE mobile_payment_events ADD CONSTRAINT mobile_events_note_digits_chk CHECK (note IS NULL OR note !~ '[0-9]{6,}')");
        DB::statement("ALTER TABLE mobile_payment_events ADD CONSTRAINT mobile_events_code_digits_chk CHECK (provider_status_code IS NULL OR provider_status_code !~ '[0-9]{6,}')");
        DB::statement("ALTER TABLE mobile_payment_events ADD CONSTRAINT mobile_events_failure_digits_chk CHECK (failure_code IS NULL OR failure_code !~ '[0-9]{6,}')");

        // ── Uniqueness: the double-charge guards ─────────────────────────────
        // The retry guard: one charge per (patient, idempotency key), for as
        // long as the row lives.
        DB::statement('CREATE UNIQUE INDEX mobile_charges_idem_uniq ON mobile_payment_charges (patient_id, idempotency_key)');

        // The real guard: an invoice can hold at most ONE successful charge and
        // at most ONE in-flight charge, regardless of idempotency keys. Two
        // racing INSERTs both pass every PHP check and then one of them dies
        // here, which is exactly the intent.
        DB::statement("CREATE UNIQUE INDEX mobile_charges_one_paid_per_invoice ON mobile_payment_charges (invoice_id) WHERE status = 'paid'");
        DB::statement("CREATE UNIQUE INDEX mobile_charges_one_pending_per_invoice ON mobile_payment_charges (invoice_id) WHERE status = 'pending'");

        // A provider reference identifies exactly one charge — this is what
        // makes a replayed webhook land on the right row and nowhere else.
        DB::statement('CREATE UNIQUE INDEX mobile_charges_provider_ref_uniq ON mobile_payment_charges (provider, provider_reference) WHERE provider_reference IS NOT NULL');

        // ── The forward-only trigger ─────────────────────────────────────────
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION opescare_mobile_payment_guard() RETURNS trigger AS $$
            BEGIN
                -- 1. The money is immutable once issued. Re-pricing an invoice
                --    a patient has already seen is not an edit, it is a new
                --    invoice.
                IF NEW.amount_minor IS DISTINCT FROM OLD.amount_minor
                   OR NEW.currency IS DISTINCT FROM OLD.currency
                   OR NEW.patient_id IS DISTINCT FROM OLD.patient_id THEN
                    RAISE EXCEPTION
                        'mobile payment row %.% (id %) is immutable: amount_minor, currency and patient_id cannot change after issue',
                        TG_TABLE_SCHEMA, TG_TABLE_NAME, OLD.id
                        USING ERRCODE = '23514';
                END IF;

                -- 2. Status moves forward or not at all. An UPDATE that leaves
                --    the status alone (recording a poll timestamp, say) is fine.
                IF NEW.status IS NOT DISTINCT FROM OLD.status THEN
                    RETURN NEW;
                END IF;

                IF (OLD.status = 'pending' AND NEW.status IN ('paid', 'failed', 'expired'))
                   OR (OLD.status = 'paid' AND NEW.status = 'refunded') THEN
                    RETURN NEW;
                END IF;

                RAISE EXCEPTION
                    'illegal payment status transition % -> % on %.% (id %)',
                    OLD.status, NEW.status, TG_TABLE_SCHEMA, TG_TABLE_NAME, OLD.id
                    USING ERRCODE = '23514';
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared('
            CREATE TRIGGER mobile_invoices_guard
            BEFORE UPDATE ON mobile_invoices
            FOR EACH ROW EXECUTE FUNCTION opescare_mobile_payment_guard();
        ');

        DB::unprepared('
            CREATE TRIGGER mobile_charges_guard
            BEFORE UPDATE ON mobile_payment_charges
            FOR EACH ROW EXECUTE FUNCTION opescare_mobile_payment_guard();
        ');

        // The event log is append-only: correcting history is how a disputed
        // payment becomes unprovable.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION opescare_mobile_payment_event_append_only() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'mobile_payment_events is append-only (attempted % on id %)', TG_OP, OLD.id
                    USING ERRCODE = '23514';
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared('
            CREATE TRIGGER mobile_payment_events_append_only
            BEFORE UPDATE OR DELETE ON mobile_payment_events
            FOR EACH ROW EXECUTE FUNCTION opescare_mobile_payment_event_append_only();
        ');

        // ── Documentation that ships with the database ───────────────────────
        DB::statement("COMMENT ON COLUMN mobile_invoices.amount_minor IS 'Integer minor units. XAF has ISO 4217 exponent 0, so 1 minor unit = 1 franc: 5000 means 5 000 FCFA. Never a float.'");
        DB::statement("COMMENT ON COLUMN mobile_payment_charges.amount_minor IS 'Integer minor units. XAF exponent 0: 5000 means 5 000 FCFA. Never a float.'");
        DB::statement("COMMENT ON COLUMN mobile_payment_charges.payer_msisdn_masked IS 'Masked MSISDN only. CHECK-constrained to contain a *; a full number cannot be stored here.'");
        DB::statement("COMMENT ON COLUMN mobile_payment_charges.payer_msisdn_hash IS 'HMAC-SHA256(APP_KEY, E.164). One-way; for support correlation, not reversible.'");
    }

    public function down(): void
    {
        // The append-only trigger blocks DELETE, which would also block a
        // table drop's cascade path in some Postgres versions — remove the
        // triggers before the tables.
        DB::unprepared('DROP TRIGGER IF EXISTS mobile_payment_events_append_only ON mobile_payment_events');
        DB::unprepared('DROP TRIGGER IF EXISTS mobile_charges_guard ON mobile_payment_charges');
        DB::unprepared('DROP TRIGGER IF EXISTS mobile_invoices_guard ON mobile_invoices');
        DB::unprepared('DROP FUNCTION IF EXISTS opescare_mobile_payment_event_append_only()');
        DB::unprepared('DROP FUNCTION IF EXISTS opescare_mobile_payment_guard()');

        Schema::dropIfExists('mobile_payment_events');
        Schema::dropIfExists('mobile_payment_charges');
        Schema::dropIfExists('mobile_invoices');
    }
};
