<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // cnamgs_id and national_id_number already added by 2026_05_28_200002
            if (!Schema::hasColumn('patients', 'cnamgs_verified_at')) {
                $table->timestamp('cnamgs_verified_at')->nullable();
            }
            if (!Schema::hasColumn('patients', 'national_id_type')) {
                $table->enum('national_id_type', ['cni', 'passport', 'residence_permit', 'other'])->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'cnamgs_verified_at')) {
                $table->dropColumn('cnamgs_verified_at');
            }
            if (Schema::hasColumn('patients', 'national_id_type')) {
                $table->dropColumn('national_id_type');
            }
        });
    }
};
