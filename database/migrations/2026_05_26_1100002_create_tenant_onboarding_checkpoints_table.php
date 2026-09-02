<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tenant_onboarding_checkpoints')) {
            Schema::create('tenant_onboarding_checkpoints', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('facility_id', 36)->index();
                $table->string('step_key');
                $table->string('step_label');
                $table->unsignedTinyInteger('step_order')->default(0);
                $table->boolean('completed')->default(false);
                $table->boolean('required')->default(true);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique(['facility_id', 'step_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_onboarding_checkpoints');
    }
};
