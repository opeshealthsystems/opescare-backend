<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The push receipt ledger — one row per message handed to Expo's push API.
 *
 * Expo answers a send with a TICKET, which only means "queued". Whether the
 * handset was actually reached is known from the RECEIPT, published a few
 * minutes later and kept for about a day. Until this table existed the ticket
 * ids were returned to the caller and dropped, so `DeviceNotRegistered` at
 * receipt time — the one signal that an app was uninstalled — was never read,
 * and a dead token was pushed to forever.
 *
 * Each row records who was pushed (patient + device row), what kind of push it
 * was (data.type, never the copy), the ticket, and what Expo eventually said.
 * `push:check-receipts` (every 15 minutes) moves rows from `pending` to `ok`
 * or `error`, retiring the device token when the error is DeviceNotRegistered.
 *
 * `patient_id` cascades with the patient (account deletion takes the ledger
 * with it); the device row link is nulled rather than cascaded so a purged
 * token does not erase the evidence that a push was attempted.
 *
 * Also adds `mobile_app_settings.push_messages`: the app's five push toggles
 * (appointments, lab results, prescriptions, billing, consent requests) had no
 * column for the `message` type, so a patient who did not want message pushes
 * had nothing to switch off. Default true — nobody is silenced by the
 * migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->nullable()->index();
            $table->uuid('push_device_token_id')->nullable()->index();
            $table->string('platform', 20)->nullable();   // ios|android|web, copied from the device row
            $table->string('type', 40)->nullable();       // data.type — the kind of push, never its copy
            $table->string('ticket_id', 64)->nullable()->index();
            $table->string('status', 16)->default('pending'); // pending|ok|error
            $table->string('error_code', 64)->nullable();     // DeviceNotRegistered, InvalidCredentials, MessageTooBig, ...
            $table->json('error_details')->nullable();
            $table->timestampTz('sent_at');
            $table->timestampTz('receipt_checked_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'sent_at']);
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('push_device_token_id')->references('id')->on('push_device_tokens')->nullOnDelete();
        });

        if (Schema::hasTable('mobile_app_settings') && ! Schema::hasColumn('mobile_app_settings', 'push_messages')) {
            Schema::table('mobile_app_settings', function (Blueprint $table) {
                $table->boolean('push_messages')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_deliveries');

        if (Schema::hasTable('mobile_app_settings') && Schema::hasColumn('mobile_app_settings', 'push_messages')) {
            Schema::table('mobile_app_settings', function (Blueprint $table) {
                $table->dropColumn('push_messages');
            });
        }
    }
};
