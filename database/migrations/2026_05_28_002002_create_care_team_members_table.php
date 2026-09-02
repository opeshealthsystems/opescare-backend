<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_team_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->uuid('visit_id')->nullable();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)
                ->comment('attending|consulting|nursing|pharmacy|social_work|other');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('provider_id');
            $table->index('visit_id');
            $table->index(['patient_id', 'left_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_team_members');
    }
};
