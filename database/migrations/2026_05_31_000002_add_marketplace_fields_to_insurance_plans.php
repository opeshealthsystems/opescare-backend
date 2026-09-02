<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->text('description')->nullable()->after('plan_type');
            $table->string('logo_url')->nullable()->after('description');
            $table->decimal('monthly_premium', 12, 2)->nullable()->after('logo_url');
            $table->decimal('annual_premium', 12, 2)->nullable()->after('monthly_premium');
            $table->decimal('deductible', 12, 2)->nullable()->after('annual_premium');
            $table->boolean('is_purchasable')->default(false)->after('deductible');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'logo_url',
                'monthly_premium',
                'annual_premium',
                'deductible',
                'is_purchasable',
            ]);
        });
    }
};
