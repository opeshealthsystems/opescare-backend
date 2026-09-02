<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();                 // sandbox|growth|scale
            $table->string('name');
            $table->unsignedInteger('rate_limit_per_min')->default(60);
            $table->unsignedBigInteger('monthly_request_quota')->nullable(); // null = unlimited
            $table->unsignedBigInteger('price_xaf')->default(0);             // monthly, XAF
            $table->decimal('overage_price_xaf', 10, 4)->default(0);         // per request over quota, XAF
            $table->string('support_level')->default('community');          // community|business|priority
            $table->json('features')->nullable();                            // array of i18n feature keys
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Every integration client is on a plan (defaults to sandbox).
        Schema::table('integration_clients', function (Blueprint $table) {
            $table->string('api_plan_key')->default('sandbox');
        });

        // Seed the reference plans (idempotent reference data, not a one-off).
        $now = now();
        $rows = [
            ['key' => 'sandbox', 'name' => 'Sandbox', 'rate_limit_per_min' => 60,   'monthly_request_quota' => 10000,  'price_xaf' => 0,      'overage_price_xaf' => 0,   'support_level' => 'community', 'features' => ['feat_full_api', 'feat_sandbox', 'feat_webhooks', 'feat_community_support'],                          'is_public' => true, 'sort' => 1],
            ['key' => 'growth',  'name' => 'Growth',  'rate_limit_per_min' => 300,  'monthly_request_quota' => 500000, 'price_xaf' => 75000,  'overage_price_xaf' => 0.5, 'support_level' => 'business',  'features' => ['feat_production', 'feat_webhooks', 'feat_business_support', 'feat_sla_995'],                              'is_public' => true, 'sort' => 2],
            ['key' => 'scale',   'name' => 'Scale',   'rate_limit_per_min' => 1200, 'monthly_request_quota' => null,   'price_xaf' => 350000, 'overage_price_xaf' => 0,   'support_level' => 'priority',  'features' => ['feat_production', 'feat_unlimited', 'feat_webhooks', 'feat_priority_support', 'feat_sla_999', 'feat_dedicated'], 'is_public' => true, 'sort' => 3],
        ];
        foreach ($rows as $r) {
            $r['features'] = json_encode($r['features']);
            $r['created_at'] = $now;
            $r['updated_at'] = $now;
            DB::table('api_plans')->insert($r);
        }
    }

    public function down(): void
    {
        Schema::table('integration_clients', function (Blueprint $table) {
            $table->dropColumn('api_plan_key');
        });
        Schema::dropIfExists('api_plans');
    }
};
