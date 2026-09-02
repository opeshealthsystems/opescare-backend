<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('problem_lists', function (Blueprint $table) {
            if (!Schema::hasColumn('problem_lists', 'snomed_code')) {
                $table->string('snomed_code', 30)->nullable();
                $table->string('snomed_display')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('problem_lists', function (Blueprint $table) {
            if (Schema::hasColumn('problem_lists', 'snomed_code')) {
                $table->dropColumn(['snomed_code', 'snomed_display']);
            }
        });
    }
};
