<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('dispensing_pharmacy_name', 255)->nullable()->after('notes')
                ->comment('Name of pharmacy the prescription is routed to');
            $table->string('dispensing_pharmacy_address', 500)->nullable()->after('dispensing_pharmacy_name');
            $table->string('dispensing_pharmacy_phone', 30)->nullable()->after('dispensing_pharmacy_address');
            $table->string('dispensing_pharmacy_fax', 30)->nullable()->after('dispensing_pharmacy_phone');
            $table->string('pharmacy_routing_status', 30)->nullable()->after('dispensing_pharmacy_fax')
                ->comment('pending|sent|confirmed|rejected|dispensed');
            $table->timestamp('pharmacy_routing_sent_at')->nullable()->after('pharmacy_routing_status');
            $table->timestamp('pharmacy_confirmed_at')->nullable()->after('pharmacy_routing_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'dispensing_pharmacy_name',
                'dispensing_pharmacy_address',
                'dispensing_pharmacy_phone',
                'dispensing_pharmacy_fax',
                'pharmacy_routing_status',
                'pharmacy_routing_sent_at',
                'pharmacy_confirmed_at',
            ]);
        });
    }
};
