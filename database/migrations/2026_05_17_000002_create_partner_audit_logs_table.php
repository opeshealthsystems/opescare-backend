<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('actor_id')->nullable();
            $table->string('actor_role')->nullable();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->onDelete('cascade');
            $table->string('action');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_audit_logs');
    }
};
