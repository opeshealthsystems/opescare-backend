<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Insurance Providers (payer companies)
        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('country_code', 3)->default('CM');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('portal_url')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->string('status')->default('active'); // active, inactive, suspended
            $table->timestamps();
        });

        // Insurance Plans offered by providers
        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('insurance_provider_id');
            $table->string('name');
            $table->string('plan_code')->nullable();
            $table->string('plan_type')->nullable(); // nhia, private, employer, mutual
            $table->boolean('requires_preauthorization')->default(false);
            $table->boolean('cashless_available')->default(false);
            $table->decimal('copay_percentage', 5, 2)->nullable();
            $table->text('covered_services')->nullable(); // JSON list
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('insurance_provider_id')->references('id')->on('insurance_providers')->onDelete('cascade');
        });

        // Patient Insurance Policies (patient enrolled in a plan)
        Schema::create('patient_insurance_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('insurance_plan_id');
            $table->string('policy_number');
            $table->string('member_id')->nullable();
            $table->string('group_number')->nullable();
            $table->string('relationship_to_primary')->default('self'); // self, spouse, child, other
            $table->string('primary_member_name')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('pending'); // pending, active, inactive, expired, cancelled
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('insurance_plan_id')->references('id')->on('insurance_plans')->onDelete('restrict');
        });

        // Eligibility Checks
        Schema::create('eligibility_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_insurance_policy_id');
            $table->uuid('checked_by')->nullable(); // staff user id
            $table->string('status')->default('pending'); // pending, eligible, not_eligible, unknown, expired, failed
            $table->text('response_notes')->nullable();
            $table->string('source')->default('manual'); // manual, api
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_insurance_policy_id')->references('id')->on('patient_insurance_policies')->onDelete('cascade');
        });

        // Preauthorization Requests
        Schema::create('preauthorization_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_insurance_policy_id');
            $table->uuid('invoice_id')->nullable();
            $table->uuid('facility_id');
            $table->string('requested_by')->nullable(); // staff user id
            $table->string('service_description');
            $table->text('clinical_justification')->nullable();
            $table->decimal('estimated_amount', 12, 2)->nullable();
            $table->string('status')->default('draft');
            // draft, submitted, under_review, approved, rejected, more_information_required, expired, cancelled
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_insurance_policy_id')->references('id')->on('patient_insurance_policies')->onDelete('restrict');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('restrict');
        });

        // Preauthorization Decisions (by payer)
        Schema::create('preauthorization_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('preauthorization_request_id');
            $table->string('decided_by')->nullable();
            $table->string('decision'); // approved, rejected, more_information_required
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->text('reason')->nullable();
            $table->string('authorization_number')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->foreign('preauthorization_request_id')->references('id')->on('preauthorization_requests')->onDelete('cascade');
        });

        // Insurance Claims
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_insurance_policy_id');
            $table->uuid('invoice_id')->nullable();
            $table->uuid('preauthorization_request_id')->nullable();
            $table->uuid('facility_id');
            $table->string('claim_number')->unique();
            $table->string('status')->default('draft');
            // draft, submitted, under_review, more_information_required,
            // approved, partially_approved, rejected, paid, partially_paid, cancelled, disputed
            $table->decimal('claimed_amount', 12, 2)->default(0);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->string('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('submission_notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_insurance_policy_id')->references('id')->on('patient_insurance_policies')->onDelete('restrict');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('restrict');
        });

        // Claim Items (line items)
        Schema::create('claim_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('insurance_claim_id');
            $table->string('description');
            $table->string('service_code')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, partial
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('insurance_claim_id')->references('id')->on('insurance_claims')->onDelete('cascade');
        });

        // Claim Documents
        Schema::create('claim_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('insurance_claim_id');
            $table->string('document_type'); // invoice, lab_result, prescription, referral, other
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('insurance_claim_id')->references('id')->on('insurance_claims')->onDelete('cascade');
        });

        // Claim Decisions (by payer)
        Schema::create('claim_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('insurance_claim_id');
            $table->string('decided_by')->nullable();
            $table->string('decision'); // approved, partially_approved, rejected, more_information_required, disputed
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->text('reason');
            $table->text('missing_information')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->foreign('insurance_claim_id')->references('id')->on('insurance_claims')->onDelete('cascade');
        });

        // Claim Payments (payer to facility)
        Schema::create('claim_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('insurance_claim_id');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('bank_transfer');
            $table->string('reference_number')->nullable();
            $table->string('recorded_by')->nullable();
            $table->timestamp('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('insurance_claim_id')->references('id')->on('insurance_claims')->onDelete('restrict');
        });

        // Claim Messages (communication between facility and payer)
        Schema::create('claim_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('insurance_claim_id');
            $table->string('sender_type'); // facility, payer
            $table->string('sender_id')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->foreign('insurance_claim_id')->references('id')->on('insurance_claims')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_messages');
        Schema::dropIfExists('claim_payments');
        Schema::dropIfExists('claim_decisions');
        Schema::dropIfExists('claim_documents');
        Schema::dropIfExists('claim_items');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('preauthorization_decisions');
        Schema::dropIfExists('preauthorization_requests');
        Schema::dropIfExists('eligibility_checks');
        Schema::dropIfExists('patient_insurance_policies');
        Schema::dropIfExists('insurance_plans');
        Schema::dropIfExists('insurance_providers');
    }
};
