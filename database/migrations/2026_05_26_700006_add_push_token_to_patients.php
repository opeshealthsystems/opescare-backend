<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'push_token')) {
                $table->string('push_token')->nullable();
            }
            if (!Schema::hasColumn('patients', 'push_platform')) {
                $table->enum('push_platform', ['android', 'ios', 'web'])->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'push_token')) {
                $table->dropColumn('push_token');
            }
            if (Schema::hasColumn('patients', 'push_platform')) {
                $table->dropColumn('push_platform');
            }
        });
    }
};
