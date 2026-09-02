<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('provider_shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('provider_shifts', 'is_on_call')) {
                $table->boolean('is_on_call')->default(false);
            }
            if (!Schema::hasColumn('provider_shifts', 'department')) {
                $table->string('department')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('provider_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('provider_shifts', 'is_on_call')) {
                $table->dropColumn('is_on_call');
            }
            if (Schema::hasColumn('provider_shifts', 'department')) {
                $table->dropColumn('department');
            }
        });
    }
};
