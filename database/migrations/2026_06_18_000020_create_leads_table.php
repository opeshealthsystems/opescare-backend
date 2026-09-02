<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lead & Demo pipeline (B2B sales funnel).
 *
 * Captures "Request a demo" interest from the public marketing site so admins
 * can manage it from an inbox. UUID primary key to match the platform-wide
 * HasUuids convention.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('organization_name')->nullable();
            // facility|insurer|lab|pharmacy|developer|other
            $table->string('organization_type')->nullable();
            $table->text('message')->nullable();
            // pricing|request_demo|contact|solutions
            $table->string('source')->default('request_demo');
            // new|contacted|qualified|won|lost
            $table->string('status')->default('new');
            $table->string('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('organization_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
