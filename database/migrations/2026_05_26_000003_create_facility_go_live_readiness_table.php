<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_go_live_readiness', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->unique();
            $table->json('checklist_json');
            $table->string('status')->default('pending');
            $table->boolean('can_go_live')->default(false);
            $table->uuid('approved_by')->nullable()->index();
            $table->timestampTz('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_go_live_readiness');
    }
};
