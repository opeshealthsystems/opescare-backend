<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ussd_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session_id', 50)->unique();
            $table->string('phone_number', 20);
            $table->string('service_code', 20);
            $table->uuid('patient_id')->nullable()->index();
            $table->string('current_menu', 50)->default('MAIN');
            $table->json('menu_data')->nullable();
            $table->timestamp('initiated_at');
            $table->timestamp('last_active_at');
            $table->timestamps();
            $table->index(['phone_number', 'session_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('ussd_sessions'); }
};
