<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('provider_id')->index();
            $table->enum('credential_type', [
                'medical_license',
                'specialist_cert',
                'dea_registration',
                'board_certification',
                'cpr_cert',
                'hospital_privilege',
                'other',
            ]);
            $table->string('issuing_body', 255);
            $table->string('credential_number', 100);
            $table->date('issued_date');
            $table->date('expiry_date')->nullable();
            $table->enum('status', [
                'active',
                'expired',
                'suspended',
                'revoked',
                'pending_renewal',
            ])->default('active');
            $table->string('document_path', 500)->nullable();
            $table->uuid('verified_by')->nullable()->index();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('provider_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['provider_id', 'status']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_credentials');
    }
};
