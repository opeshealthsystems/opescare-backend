<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'pharmacy_route_id')) {
                $table->foreignUuid('pharmacy_route_id')
                    ->nullable()
                    ->constrained('pharmacy_routes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'pharmacy_route_id')) {
                $table->dropForeign(['pharmacy_route_id']);
                $table->dropColumn('pharmacy_route_id');
            }
        });
    }
};
