<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('patient_access_tokens')) {
            Schema::create('patient_access_tokens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('patient_id')->index();
                $table->string('token_hash')->unique();
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('patient_access_tokens');
    }
};
