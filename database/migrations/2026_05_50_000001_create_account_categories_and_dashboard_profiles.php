<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_categories')) {
            Schema::create('account_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('key')->unique();
                $table->string('name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('dashboard_profiles')) {
            Schema::create('dashboard_profiles', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('name');
                $table->string('portal_prefix');          // patient|staff|admin|insurance|developer|lite
                $table->string('landing_route');           // named Laravel route for post-login redirect
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // Add columns to roles if they don't exist
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'account_category_id')) {
                    $table->uuid('account_category_id')->nullable()->after('description');
                    $table->foreign('account_category_id')
                          ->references('id')->on('account_categories')
                          ->nullOnDelete();
                }
                if (!Schema::hasColumn('roles', 'dashboard_profile_key')) {
                    $table->string('dashboard_profile_key')->nullable()->after('account_category_id');
                    $table->foreign('dashboard_profile_key')
                          ->references('key')->on('dashboard_profiles')
                          ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropForeignIfExists(['account_category_id']);
                $table->dropForeignIfExists(['dashboard_profile_key']);
                $table->dropColumnIfExists('account_category_id');
                $table->dropColumnIfExists('dashboard_profile_key');
            });
        }
        Schema::dropIfExists('dashboard_profiles');
        Schema::dropIfExists('account_categories');
    }
};
