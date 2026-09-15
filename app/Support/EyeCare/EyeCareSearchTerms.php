<?php

namespace App\Support\EyeCare;

/**
 * The words a patient types for an eye clinic, EN and FR.
 *
 * Eye clinic audit 2 (docs/audits/EYE_CLINIC_DASHBOARD_AUDIT_2.md): on prod a
 * directory search for "ophthalmologist", "optometrist" or "eye doctor"
 * returned 0 of the 23 `eye_clinic` listings, "ophtalmologie" returned 1 (by
 * name only) and "Eye" returned fewer than "eye" (case-sensitive type match).
 *
 * Only words that name the KIND of place or practitioner. No conditions
 * ("cataracte") and no optical shops ("opticien"): an optician is not an eye
 * clinic, and a disease name is a guess about where to go. Lower-case, trimmed;
 * CareMapSearchService::typeForQuery() compares the whole query against these.
 */
final class EyeCareSearchTerms
{
    public const FACILITY_TYPE = 'eye_clinic';

    public const WORDS = [
        // English
        'eye', 'eyes', 'eye clinic', 'eye clinics', 'eye doctor', 'eye doctors',
        'eye care', 'eye hospital', 'eye specialist',
        'ophthalmology', 'ophthalmologist', 'ophthalmologists', 'ophthalmic clinic',
        'optometry', 'optometrist', 'optometrists',
        // French
        'yeux', 'ophtalmologie', 'ophtalmologue', 'ophtalmologues', 'ophtalmo',
        'ophtalmologiste', 'clinique ophtalmologique', 'centre ophtalmologique',
        'cabinet ophtalmologique', "clinique de l'oeil", 'médecin des yeux',
        'optométrie', 'optométriste', 'optometriste',
    ];
}
