<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The institutional code dictionary: the reference vocabularies themselves.
 *
 * ── Why this is a different table to `code_system_mappings` ───────────────
 * `code_system_mappings` answers "what does OUR local code mean in a standard
 * system" — it is a per-deployment, human-approved crosswalk, and it holds
 * only the couple of dozen codes this deployment happens to use.
 *
 * THIS table is the standard system itself: every WHO ICD-10 category, every
 * LOINC term. It is not a mapping and it is not approved by anybody here —
 * it is published reference data, imported verbatim from the publisher, and
 * the only honest way to hold it is to record where each row came from and
 * which release it belongs to.
 *
 * Nothing in here is ever hand-typed. Every row arrives through an importer
 * that names its source and its version, so `source` + `source_version` is
 * the provenance of every single code and a re-import can replace a release
 * wholesale without guessing what was local and what was published.
 *
 * ── `is_common` is an opinion, and it says whose ─────────────────────────
 * "Codes commonly used in Africa" is not a set anybody publishes. What DOES
 * exist is publisher-defined subsets (LOINC's own Universal Lab Orders) and
 * epidemiologically-defined ones (the WHO AFRO / IDSR priority conditions).
 * `is_common` marks membership of such a subset and `common_reason` names
 * WHICH one, so the claim is auditable rather than asserted. A row with
 * is_common = false is not "rare" — it is simply not in a named subset.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_code_dictionary', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // 'ICD-10' | 'LOINC'. Matches code_system_mappings.standard_system.
            $table->string('system', 20);
            $table->string('code', 40);

            // The publisher's own display text. Never edited, never localised
            // in place — a translation goes in `display_fr`, leaving the
            // published English intact and comparable against the release.
            $table->text('display');
            $table->text('display_fr')->nullable();
            $table->string('short_name', 200)->nullable();

            // ICD-10: 'chapter' | 'block' | 'category'. LOINC: 'term'.
            // A chapter and a block are NOT diagnosable codes; storing the
            // kind is what stops a chapter heading being offered as a
            // diagnosis on a form.
            $table->string('kind', 20)->default('term');
            $table->string('parent_code', 40)->nullable();
            $table->string('chapter', 20)->nullable();

            // LOINC's six axes, as far as the source exposes them.
            $table->string('component', 300)->nullable();
            $table->string('property', 40)->nullable();
            $table->string('scale', 20)->nullable();
            $table->string('specimen', 120)->nullable();
            $table->string('example_units', 60)->nullable();

            // Free-text synonyms, searched alongside the display.
            $table->text('synonyms')->nullable();

            $table->boolean('is_common')->default(false);
            $table->string('common_reason', 120)->nullable();

            // A code withdrawn by its publisher stays in the table, inactive.
            // Historical records still point at it and must still resolve.
            $table->boolean('is_active')->default(true);

            $table->string('source', 80);
            $table->string('source_version', 40)->nullable();
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();

            $table->unique(['system', 'code']);
            $table->index(['system', 'is_common']);
            $table->index(['system', 'kind']);
            $table->index('parent_code');
            $table->index('chapter');
        });

        // Substring search over 100k+ rows is a sequential scan without this.
        // pg_trgm ships with Postgres but is not enabled by default; if the
        // role cannot create the extension the import still works and only
        // search is slower, so this must not abort the migration.
        try {
            $this->enableTrigramSearch();
        } catch (\Throwable $e) {
            // Reported, not fatal. See down() — the indexes simply won't exist.
            logger()->warning('clinical_code_dictionary: pg_trgm unavailable, search will be slower', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists("clinical_code_dictionary");
    }

    /**
     * Enable pg_trgm and build the trigram indexes that make "find me the
     * code for cholera" fast over 100k+ rows. Without them every substring
     * search is a sequential scan.
     */
    private function enableTrigramSearch(): void
    {
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm");
        DB::statement(
            "CREATE INDEX IF NOT EXISTS clinical_code_dictionary_display_trgm
             ON clinical_code_dictionary USING gin (lower(display) gin_trgm_ops)"
        );
        DB::statement(
            "CREATE INDEX IF NOT EXISTS clinical_code_dictionary_synonyms_trgm
             ON clinical_code_dictionary USING gin (lower(synonyms) gin_trgm_ops)"
        );
    }
};
