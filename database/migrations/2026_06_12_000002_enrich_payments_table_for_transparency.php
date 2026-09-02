<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Payment gateway / mobile money details
            $table->string('gateway')->nullable()->after('method');              // mtn_momo, orange_money, card, cash, insurance, bank_transfer, wallet
            $table->string('gateway_transaction_id')->nullable()->after('gateway'); // provider's own transaction ID (e.g. MTN txn ref)
            $table->string('gateway_status')->nullable()->after('gateway_transaction_id'); // raw status from gateway (SUCCESSFUL, PENDING, FAILED)
            $table->string('payer_phone')->nullable()->after('gateway_status'); // phone number used for mobile money
            $table->string('payer_name')->nullable()->after('payer_phone');    // name as registered with gateway / cardholder
            $table->string('service_type')->nullable()->after('payer_name');   // consultation, lab_test, pharmacy, radiology, admission, subscription, etc.

            // Device / session context
            $table->string('device_type')->nullable()->after('service_type');  // web, android, ios, pos_terminal, ussd
            $table->string('device_id')->nullable()->after('device_type');
            $table->string('user_agent', 500)->nullable()->after('device_id');
            $table->string('ip_address', 45)->nullable()->after('user_agent'); // IPv4 or IPv6

            // Timestamps for gateway lifecycle
            $table->timestampTz('initiated_at')->nullable()->after('ip_address');
            $table->timestampTz('confirmed_at')->nullable()->after('initiated_at');
            $table->timestampTz('failed_at')->nullable()->after('confirmed_at');

            // Currency
            $table->string('currency', 10)->default('XAF')->after('amount');

            // Extra gateway metadata (raw payload for audit)
            $table->json('gateway_metadata')->nullable()->after('failed_at');

            // Failure reason
            $table->string('failure_reason')->nullable()->after('gateway_metadata');

            // Indexes for fast filtering in admin reports
            $table->index('gateway');
            $table->index('service_type');
            $table->index('payer_phone');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['gateway']);
            $table->dropIndex(['service_type']);
            $table->dropIndex(['payer_phone']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropColumn([
                'gateway', 'gateway_transaction_id', 'gateway_status',
                'payer_phone', 'payer_name', 'service_type',
                'device_type', 'device_id', 'user_agent', 'ip_address',
                'initiated_at', 'confirmed_at', 'failed_at',
                'currency', 'gateway_metadata', 'failure_reason',
            ]);
        });
    }
};
