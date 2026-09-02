<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('api_usage_logs')) {
            Schema::create('api_usage_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('integration_client_id', 100)->nullable()->index();
                $table->string('endpoint');
                $table->string('method', 10);
                $table->unsignedSmallInteger('response_status');
                $table->unsignedInteger('response_time_ms')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('facility_id', 36)->nullable()->index();
                $table->timestamp('logged_at')->useCurrent();
                $table->index(['integration_client_id', 'logged_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};
