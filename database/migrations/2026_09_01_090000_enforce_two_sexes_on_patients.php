<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The platform records two sexes: male and female.
 *
 * Validation, portal forms, the mobile app, the test factory and the FHIR
 * mapper were all narrowed to those two already. What survived was the layer
 * underneath: `patients.sex` is a plain nullable string with no constraint, so
 * anything at all could still be written to it, and one row was still carrying
 * 'other' from before the rule existed.
 *
 * This closes both halves.
 *
 * The stale row is set to NULL rather than guessed at. We do not know that
 * person's sex, and inventing one to satisfy a constraint would be a worse
 * error than an absent value — the column is nullable precisely so "not
 * recorded" can be represented honestly.
 *
 * NOT touched, deliberately:
 *   - lab_reference_ranges.gender still permits 'all'. That is not a third
 *     sex; it marks a reference range valid for both, and removing it would
 *     break every unisex range.
 *   - FhirPatientMapper still emits 'unknown' for an unrecorded sex. FHIR R4
 *     binds Patient.gender to a REQUIRED value set (male | female | other |
 *     unknown) with no way to express absence, so a patient with no recorded
 *     sex has to map to 'unknown' or the resource fails validation at every
 *     partner. That code is a statement about missing data, not about sex.
 */
return new class extends Migration
{
    public function up(): void
    {
        $cleared = DB::table('patients')
            ->whereNotNull('sex')
            ->whereNotIn('sex', ['male', 'female'])
            ->update(['sex' => null, 'updated_at' => now()]);

        if ($cleared > 0) {
            // Visible in the migration output rather than silent, because this
            // edits real patient demographics.
            echo "  cleared sex on {$cleared} patient row(s) holding a value outside male|female\n";
        }

        DB::statement(<<<'SQL'
            ALTER TABLE patients
            ADD CONSTRAINT patients_sex_two_values
            CHECK (sex IS NULL OR sex IN ('male', 'female'))
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE patients DROP CONSTRAINT IF EXISTS patients_sex_two_values');
    }
};
