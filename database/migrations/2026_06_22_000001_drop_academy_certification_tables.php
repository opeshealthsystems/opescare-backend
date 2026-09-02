<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the OpesCare Academy / certification tables.
 *
 * The Academy module was decommissioned — practitioner training and
 * certification moved to the main OPES Health Systems platform
 * (opeshealthsystems.com → "OPES Academy"). The module code and the original
 * create migration (2026_05_22_000000_create_academy_certification_tables) were
 * removed alongside it. Tables are dropped child-first (reverse FK order).
 *
 * This is a one-way migration: to restore Academy, re-introduce the module and
 * its create migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('academy_trainer_signoffs');
        Schema::dropIfExists('academy_competency_requirements');
        Schema::dropIfExists('academy_certificate_verification_events');
        Schema::dropIfExists('academy_certificate_tokens');
        Schema::dropIfExists('academy_certificates');
        Schema::dropIfExists('academy_simulation_attempts');
        Schema::dropIfExists('academy_quiz_attempts');
        Schema::dropIfExists('academy_lesson_progress');
        Schema::dropIfExists('academy_course_enrollments');
        Schema::dropIfExists('academy_quiz_questions');
        Schema::dropIfExists('academy_quizzes');
        Schema::dropIfExists('academy_lessons');
        Schema::dropIfExists('academy_course_modules');
        Schema::dropIfExists('academy_courses');
    }

    public function down(): void
    {
        // One-way: the Academy module and its create migration were removed.
    }
};
