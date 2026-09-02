<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Central file asset registry
        Schema::create('file_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('original_name');          // original filename from upload
            $table->string('stored_name');            // UUID-based stored filename
            $table->string('disk')->default('local'); // local | s3
            $table->string('path');                   // relative path on disk
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum')->nullable();   // SHA-256 for integrity
            $table->string('uploaded_by')->nullable();
            $table->string('facility_id')->nullable()->index();
            $table->boolean('is_private')->default(true);
            $table->timestamps();
        });

        // Medical attachments linking file assets to clinical resources
        Schema::create('medical_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('file_asset_id')->index();
            $table->foreign('file_asset_id')->references('id')->on('file_assets')->cascadeOnDelete();
            $table->string('resource_type');   // patient | visit | triage_record | clinical_note | invoice | support_ticket
            $table->string('resource_id');     // UUID of the related resource
            $table->index(['resource_type', 'resource_id']);
            $table->string('category')->nullable(); // lab_result | imaging | consent | prescription | referral | other
            $table->string('description')->nullable();
            $table->string('attached_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_attachments');
        Schema::dropIfExists('file_assets');
    }
};
