<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('security_audit_logs')) {
            return;
        }
        Schema::create('security_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('audit_type', [
                'penetration_test', 'vulnerability_scan', 'code_review',
                'compliance_audit', 'soc2_assessment', 'iso27001_audit', 'other',
            ]);
            $table->string('vendor_name');
            $table->date('audit_date');
            $table->text('scope');
            $table->unsignedInteger('findings_count')->default(0);
            $table->unsignedInteger('critical_count')->default(0);
            $table->unsignedInteger('high_count')->default(0);
            $table->unsignedInteger('medium_count')->default(0);
            $table->unsignedInteger('low_count')->default(0);
            $table->enum('status', ['in_progress', 'in_remediation', 'completed', 'closed'])->default('in_progress');
            $table->string('report_url')->nullable();
            $table->date('next_assessment')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
    }
};
