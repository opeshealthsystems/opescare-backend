<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('data_retention_policies')) {
            return;
        }
        Schema::create('data_retention_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('table_name')->unique();
            $table->unsignedInteger('retention_days');
            $table->enum('purge_action', ['delete', 'anonymise', 'archive'])->default('delete');
            $table->string('legal_basis')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedBigInteger('last_run_purged')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_retention_policies');
    }
};
