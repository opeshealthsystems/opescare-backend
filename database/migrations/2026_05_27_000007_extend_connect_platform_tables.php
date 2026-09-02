<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend integration_clients with more admin fields
        Schema::table('integration_clients', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('description')->nullable()->after('name');
            $table->string('contact_email')->nullable()->after('description');
            $table->string('created_by')->nullable()->after('environment');
            $table->timestamp('approved_at')->nullable()->after('created_by');
            $table->string('approved_by')->nullable()->after('approved_at');
            $table->timestamp('last_used_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('request_count')->default(0)->after('last_used_at');
        });

        // SDK tokens (separate from OAuth client credentials)
        Schema::create('sdk_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('client_id')->index();
            $table->string('token_hash');           // SHA-256 of actual token
            $table->string('token_prefix', 16);     // First 8 chars for display
            $table->jsonb('scopes')->nullable();
            $table->string('environment')->default('sandbox');
            $table->string('label')->nullable();    // "Production Backend", "Mobile App"
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('revoked_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Rate limit profiles per integration client
        Schema::create('rate_limit_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('client_id')->unique()->index();
            $table->unsignedInteger('requests_per_minute')->default(60);
            $table->unsignedInteger('requests_per_hour')->default(1000);
            $table->unsignedInteger('requests_per_day')->default(10000);
            $table->string('burst_allowance')->default('normal'); // normal | high | restricted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limit_profiles');
        Schema::dropIfExists('sdk_tokens');
        Schema::table('integration_clients', function (Blueprint $table) {
            $table->dropColumn(['name','description','contact_email','created_by',
                                'approved_at','approved_by','last_used_at','request_count']);
        });
    }
};
