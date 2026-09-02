<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks which users have acknowledged each broadcast.
 * Required for broadcasts where requires_acknowledgement = true.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('broadcasts')->cascadeOnDelete();
            $table->uuid('user_id');
            $table->string('facility_id', 36)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('acknowledged_at')->useCurrent();
            $table->timestamps();

            $table->unique(['broadcast_id', 'user_id']); // one acknowledgement per user per broadcast
            $table->index(['broadcast_id', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_acknowledgements');
    }
};
