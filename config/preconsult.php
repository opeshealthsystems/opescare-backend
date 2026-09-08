<?php

/**
 * THE PRE-CONSULT QUESTIONNAIRE DEFINITION.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THE DEFINITION IS CONFIG AND NOT A DATABASE TABLE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * A questionnaire has two halves: the QUESTIONS (this file) and the ANSWERS
 * (`preconsult_submissions` / `preconsult_answers`). Only the second half is
 * per-patient data. The first is clinical content, and this platform's law is
 * that every user-facing string goes through `__()` against lang/en and
 * lang/fr, which `php scripts/i18n-audit.php` enforces in CI.
 *
 * A question stored as a row of text cannot be audited that way. It ships
 * whatever a writer typed, in whatever language they typed it, past every
 * gate — which is exactly how App\Modules\...\SurveyService ended up with its
 * English question strings hardcoded and not one `__()` call in the file.
 * Rows also change silently: edit the text of question 3 and every answer
 * already given to the old wording is now filed under the new one, with
 * nothing recording that the question changed.
 *
 * So the questions live here, under code review, and carry only TRANSLATION
 * KEYS — never literal text. `version` below is stamped onto every submission,
 * so an answer is always interpretable against the definition it was given
 * under; bump it whenever a question is added, removed or re-meant.
 *
 * The cost of this choice is real and accepted: a facility cannot author its
 * own questions. That is deliberate for v1 — a bilingual, clinically reviewed,
 * deliberately SHORT national intake set is worth more than per-facility forms
 * nobody translates.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT THE ANSWERS ARE, AND ARE NOT
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Everything a patient types here is PATIENT-REPORTED CONTEXT. It is not a
 * clinical finding, and this platform already draws that line structurally
 * rather than as a label a writer chooses — see App\Enums\VitalProvenance.
 * The same rule applies here:
 *
 *   - answers land ONLY in `preconsult_answers`, whose `provenance` column is
 *     `patient_reported` and is not writable to anything else;
 *   - nothing in this feature writes to `triage_vital_signs`, `vital_signs`,
 *     `diagnoses` or `allergy_records`;
 *   - a stated allergy is a REASON TO ASK, never an entry on the allergy list.
 *     `allergy_status` therefore offers "not sure" as a first-class answer,
 *     and a blank field is ABSENT — never "no known allergies", which is the
 *     single most dangerous thing a chart can say without being told it.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * CARE IS NEVER GATED ON THIS
 * ─────────────────────────────────────────────────────────────────────────
 *
 * A patient who skips this, cannot read, or has no phone is seen exactly the
 * same. No appointment is blocked, cancelled, reordered or flagged for an
 * unanswered questionnaire. Held by
 * tests/Feature/Architecture/CareIsNeverGatedOnPreconsultTest.php.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * KEEPING IT SHORT IS A CLINICAL DECISION
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Six questions. A long form is abandoned, and an abandoned form teaches the
 * clinician nothing while costing the patient their waiting-room time. The set
 * below is the history a consultation actually opens with: what is wrong, how
 * long, how bad, what you are already taking, what has ever reacted on you,
 * and — where it applies — whether you might be pregnant, because that one
 * answer changes what may be prescribed and what may be imaged.
 */

return [

    /** Definition identity, stamped onto every submission. */
    'key'     => 'general_intake',
    'version' => 1,

    /** Hard limit on any free-text answer, enforced by the service and the API. */
    'max_text_length' => 1000,

    /**
     * How far ahead of an appointment the questionnaire opens, and how long
     * after it closes. Outside this window the API answers `closed` — it does
     * not refuse the appointment, it simply stops collecting.
     */
    'opens_days_before'  => 30,
    'closes_hours_after' => 24,

    /**
     * The questions, in the order they are asked.
     *
     * type:
     *   'text'          free text, capped at max_text_length
     *   'single_choice' exactly one of `options`
     *
     * NONE of them is required. `optional_hint_key` is the string the client
     * shows to say so out loud, on every question, in the patient's language.
     */
    'questions' => [

        [
            'key'          => 'presenting_complaint',
            'type'         => 'text',
            'label_key'    => 'preconsult.questions.presenting_complaint.label',
            'help_key'     => 'preconsult.questions.presenting_complaint.help',
            'clinical_use' => 'chief_complaint',
        ],

        [
            'key'       => 'symptom_duration',
            'type'      => 'single_choice',
            'label_key' => 'preconsult.questions.symptom_duration.label',
            'help_key'  => 'preconsult.questions.symptom_duration.help',
            'options'   => [
                ['value' => 'today',        'label_key' => 'preconsult.options.symptom_duration.today'],
                ['value' => 'days_1_3',     'label_key' => 'preconsult.options.symptom_duration.days_1_3'],
                ['value' => 'days_4_7',     'label_key' => 'preconsult.options.symptom_duration.days_4_7'],
                ['value' => 'weeks_1_4',    'label_key' => 'preconsult.options.symptom_duration.weeks_1_4'],
                ['value' => 'over_a_month', 'label_key' => 'preconsult.options.symptom_duration.over_a_month'],
            ],
            'clinical_use' => 'symptom_onset',
        ],

        [
            'key'       => 'symptom_severity',
            'type'      => 'single_choice',
            'label_key' => 'preconsult.questions.symptom_severity.label',
            'help_key'  => 'preconsult.questions.symptom_severity.help',
            'options'   => [
                ['value' => 'mild',     'label_key' => 'preconsult.options.symptom_severity.mild'],
                ['value' => 'moderate', 'label_key' => 'preconsult.options.symptom_severity.moderate'],
                ['value' => 'severe',   'label_key' => 'preconsult.options.symptom_severity.severe'],
            ],
            'clinical_use' => 'severity_self_rated',
        ],

        [
            'key'          => 'current_medicines',
            'type'         => 'text',
            'label_key'    => 'preconsult.questions.current_medicines.label',
            'help_key'     => 'preconsult.questions.current_medicines.help',
            'clinical_use' => 'medication_history',
        ],

        /*
         * Two fields, deliberately. The status is what the clinician reads at
         * a glance; the detail is what they ask about. "not_sure" exists so a
         * patient is never forced to assert "none" they do not know, and a
         * blank stays blank — absent is not "no known allergies".
         */
        [
            'key'       => 'allergy_status',
            'type'      => 'single_choice',
            'label_key' => 'preconsult.questions.allergy_status.label',
            'help_key'  => 'preconsult.questions.allergy_status.help',
            'options'   => [
                ['value' => 'none_known', 'label_key' => 'preconsult.options.allergy_status.none_known'],
                ['value' => 'yes',        'label_key' => 'preconsult.options.allergy_status.yes'],
                ['value' => 'not_sure',   'label_key' => 'preconsult.options.allergy_status.not_sure'],
            ],
            'clinical_use' => 'allergy_context',
        ],

        [
            'key'          => 'allergy_details',
            'type'         => 'text',
            'label_key'    => 'preconsult.questions.allergy_details.label',
            'help_key'     => 'preconsult.questions.allergy_details.help',
            'shown_when'   => ['question' => 'allergy_status', 'equals' => 'yes'],
            'clinical_use' => 'allergy_context',
        ],

        /*
         * "where applicable" is the PATIENT's call, not a rule this platform
         * derives from a recorded sex or age. `not_applicable` is an offered
         * option, and the question is never hidden on anyone's behalf.
         */
        [
            'key'       => 'pregnancy_status',
            'type'      => 'single_choice',
            'label_key' => 'preconsult.questions.pregnancy_status.label',
            'help_key'  => 'preconsult.questions.pregnancy_status.help',
            'options'   => [
                ['value' => 'not_applicable', 'label_key' => 'preconsult.options.pregnancy_status.not_applicable'],
                ['value' => 'no',             'label_key' => 'preconsult.options.pregnancy_status.no'],
                ['value' => 'yes',            'label_key' => 'preconsult.options.pregnancy_status.yes'],
                ['value' => 'not_sure',       'label_key' => 'preconsult.options.pregnancy_status.not_sure'],
            ],
            'clinical_use' => 'pregnancy_context',
        ],
    ],
];
