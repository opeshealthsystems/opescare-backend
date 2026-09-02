<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pen_test_engagements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->string('vendor_name', 255);
            $table->enum('engagement_type', [
                'black_box',
                'white_box',
                'grey_box',
                'red_team',
                'social_engineering',
            ]);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('scope');
            $table->string('report_path', 500)->nullable();
            $table->enum('status', [
                'planned',
                'in_progress',
                'completed',
                'remediation_in_progress',
                'closed',
            ])->default('planned');
            $table->integer('total_findings')->default(0);
            $table->integer('critical_findings')->default(0);
            $table->integer('high_findings')->default(0);
            $table->integer('medium_findings')->default(0);
            $table->integer('low_findings')->default(0);
            $table->integer('informational_findings')->default(0);
            $table->uuid('created_by')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pen_test_engagements');
    }
};
