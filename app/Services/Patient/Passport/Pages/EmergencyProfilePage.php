<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\AllergyRecord;
use App\Models\ClinicalAlert;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\Patient\Passport\PageSource;

/**
 * Page 03 — Emergency Profile.
 *
 * The page somebody reads when the patient cannot speak for themselves. That
 * changes what "good" means here: every other page is allowed to be a complete
 * account of its domain, and this one has to be readable in seconds and right
 * the first time. Three consequences, and they outrank the design sheet.
 *
 *  1. IT IS SELF-CONTAINED. Blood group, allergies, standing alerts, the
 *     active problem list, current medicines and who to telephone all appear
 *     here even though pages 02, 05, 06 and 07 carry them in fuller form.
 *     Nobody leafs through a passport during a resuscitation, so a summary
 *     that sends the reader to another page is not a summary.
 *
 *  2. NOTHING SAFETY-CRITICAL IS TRUNCATED. The RECENT_LIMIT window that keeps
 *     the history pages readable is not applied to allergies, active
 *     conditions or current medicines. An allergen or an anticoagulant hidden
 *     behind a row limit is not noise; it is the one line the reader needed.
 *     Those lists are bounded by being current, not by a count.
 *
 *  3. NO WORD IS UPGRADED OR DEFAULTED. Severities and statuses are printed as
 *     the record holds them and take the sheet's colour only through
 *     severityTone()/statusTone(), which return no pill for a word they do not
 *     recognise. On this page a confident-looking colour nobody recorded would
 *     read as clinical reassurance.
 *
 * Allergies are ordered severest-first rather than newest-first — the one
 * place this passport reorders a list for the reader, and what the approved
 * sheet does, because the first line read is the one that matters most. The
 * values are identical to page 05; only the order differs.
 *
 * ── WHAT THE APPROVED SHEETS SHOW THAT THIS DEPLOYMENT CANNOT ────────────
 *
 * The sheets carry an allergy "Reaction" column ("rash, breathing
 * difficulty"): `allergy_records` records only the substance, its severity and
 * its status, so the column is left out rather than filled in. They carry an
 * "Indication"/"Reason" against each medicine; `prescription_items` records
 * drug, dose, frequency, route, duration and quantity, and no indication, so
 * that column is left out too. The emergency contact card shows an alternate
 * phone, an email and an address; the free-form `patients.emergency_contact`
 * json is written with a name, a relationship and a phone and nothing else, so
 * only those three lines are shown. There is no "preferred facility" and no
 * "next of kin" separate from the emergency contact — `patients.facility_id`
 * is an undocumented column with no writer in this codebase, and next of kin
 * IS the emergency contact in this schema. "Nationality", the separate
 * "Patient ID" and "Passport ID", the "Important notes" / "Special
 * considerations" / "Additional information" prose cards and "Last updated by"
 * have no source at all and are absent. The document ID, barcode, QR, seal and
 * signature belong to the minted-credential machinery on page 24.
 */
class EmergencyProfilePage extends PageSource
{
    /** Severest first: how an emergency reader scans, and what the sheet shows. */
    private const SEVERITY_ORDER = [
        'life_threatening' => 0,
        'critical'         => 0,
        'severe'           => 0,
        'high'             => 0,
        'moderate'         => 1,
        'medium'           => 1,
        'mild'             => 2,
        'low'              => 2,
    ];

    public function build(Patient $patient): array
    {
        $bloodGroup = trim((string) $patient->blood_group);
        $allergies  = $this->allergyRows($patient);
        $alerts     = $this->alertRows($patient);
        $conditions = $this->conditionRows($patient);
        $medicines  = $this->medicineRows($patient);
        $contact    = $this->contactItems($patient);

        // A name and a Health ID exist on every patient row, so they cannot be
        // what makes this page worth opening. With no blood group, no allergy,
        // no standing alert, no active condition, no current medicine and
        // nobody to telephone, there is nothing here a clinician could act on,
        // and the honest answer is that nothing has been recorded.
        if ($bloodGroup === '' && $allergies === [] && $alerts === []
            && $conditions === [] && $medicines === [] && $contact === []) {
            return [];
        }

        $sections = [
            $this->callout(
                __('passport.blocks.in_case_of_emergency'),
                __('passport.blocks.emergency_note'),
                'red',
                'alert-triangle'
            ),
            $this->identity($patient, $bloodGroup),
        ];

        if ($allergies !== []) {
            $sections[] = $this->table(
                __('passport.sections.allergies'),
                [
                    __('passport.columns.allergen'),
                    __('passport.columns.severity'),
                    __('passport.columns.status'),
                ],
                $allergies,
                'red',
                'alert-triangle'
            );
        }

        if ($alerts !== []) {
            $sections[] = $this->table(
                __('passport.sections.critical_alerts'),
                [
                    __('passport.columns.detail'),
                    __('passport.columns.severity'),
                ],
                $alerts,
                'red',
                'first-aid'
            );
        }

        if ($conditions !== []) {
            $sections[] = $this->table(
                __('passport.sections.active_conditions'),
                [
                    __('passport.columns.condition'),
                    __('passport.columns.status'),
                ],
                $conditions,
                'amber',
                'activity'
            );
        }

        if ($medicines !== []) {
            $sections[] = $this->table(
                __('passport.sections.medications'),
                [
                    __('passport.columns.medication'),
                    __('passport.columns.dose'),
                    __('passport.columns.frequency'),
                ],
                $medicines,
                'blue',
                'pill'
            );
        }

        if ($contact !== []) {
            $sections[] = $this->section(
                __('passport.sections.emergency_contacts'),
                $contact,
                'red',
                'users'
            );
        }

        return $sections;
    }

    // ── Who this is, and the one demographic that changes treatment ──────

    /**
     * The identity strip, blood group first.
     *
     * Page 02 lists the same facts with the blood group last, because there it
     * is one demographic among several. Here it leads: it is the line that
     * decides what can be hung on a patient who cannot answer the question.
     * Same labels, same values, same source — only the reading order differs.
     */
    private function identity(Patient $patient, string $bloodGroup): array
    {
        $items = [];

        if ($bloodGroup !== '') {
            $items[] = $this->item(__('passport.blood_group'), $bloodGroup);
        }

        $items[] = $this->item(__('passport.common_name'), $this->displayName($patient));
        $items[] = $this->item(__('passport.health_id'), (string) $patient->health_id);

        if ($patient->date_of_birth) {
            // An estimated date is still the recorded date, and it is what any
            // age-based dosing gets worked out from — so it is shown in full,
            // toned to say it was reconstructed rather than registered.
            $items[] = $this->item(
                __('passport.dob'),
                $this->date($patient->date_of_birth),
                $patient->is_dob_estimated ? 'caution' : null
            );
        }

        if (filled($patient->sex)) {
            $items[] = $this->item(__('passport.sex'), $this->sexLabel((string) $patient->sex));
        }

        return $this->section(__('passport.sections.identity'), $items, 'blue', 'id-card');
    }

    // ── The dangerous things ─────────────────────────────────────────────

    /**
     * Every recorded allergy, severest first.
     *
     * Nothing is dropped for being inactive or entered-in-error: the status is
     * shown instead, so the reader can see a substance was considered and set
     * aside rather than reading a shorter list and believing it complete. That
     * is the rule page 05 holds; this page only reorders it.
     *
     * The status word carries no pill here, though page 05 gives it one.
     * statusTone() paints "active" green, and a green badge beside a severe
     * penicillin allergy is the single most dangerous thing this page could
     * print — an active allergy is precisely the one to worry about. The
     * severity pill carries the colour; the status carries the word.
     *
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function allergyRows(Patient $patient): array
    {
        return AllergyRecord::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get()
            // Severest first, and a standing allergy ahead of a withdrawn one
            // of the same severity — a retracted line must never be the first
            // thing read. Newest first inside a tie, as the query returned it.
            ->sortBy(fn (AllergyRecord $a) => $this->severityRank($a->severity) * 10
                + (strtolower(trim((string) $a->status)) === 'active' ? 0 : 1))
            ->values()
            ->map(fn (AllergyRecord $a) => [
                $this->cell((string) $a->substance),
                $this->cell($this->statusLabel($a->severity), $this->severityTone($a->severity)),
                $this->cell($this->statusLabel($a->status)),
            ])
            ->all();
    }

    /**
     * Clinical alerts that still stand against this patient.
     *
     * Resolved and dismissed alerts are finished events and would push the
     * standing ones down a page read in a hurry. An overridden alert stays: an
     * override is a decision taken in spite of the alert, not a retraction of
     * it. Critical before warning, newest first within a severity — the same
     * rows page 05 lists in full, narrowed to the two columns that decide
     * whether the reader has to act.
     *
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function alertRows(Patient $patient): array
    {
        return ClinicalAlert::where('patient_id', $patient->id)
            ->whereIn('status', ['active', 'acknowledged', 'overridden'])
            ->orderByDesc('triggered_at')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->sortBy(fn (ClinicalAlert $a) => $this->severityRank($a->severity))
            ->values()
            ->map(fn (ClinicalAlert $a) => [
                $this->cell((string) $a->alert_message),
                $this->cell($this->statusLabel($a->severity), $this->severityTone($a->severity)),
            ])
            ->all();
    }

    /** Where a recorded severity word sorts; anything unrecognised goes last. */
    private function severityRank(?string $severity): int
    {
        return self::SEVERITY_ORDER[strtolower(trim((string) $severity))] ?? 3;
    }

    // ── The standing picture ─────────────────────────────────────────────

    /**
     * The problem list a clinician has to treat around.
     *
     * `diagnoses.status` carries `active`, `chronic` and `confirmed` in this
     * deployment; resolved and entered-in-error rows are history and are left
     * to page 07. The recorded word is shown rather than collapsed into
     * "chronic", because the sheet's "chronic" is a claim about the condition
     * that only the record can make. No pill: statusTone() would paint an
     * active diagnosis green, and there is nothing successful about one.
     *
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function conditionRows(Patient $patient): array
    {
        return Diagnosis::where('patient_id', $patient->id)
            ->whereNotIn('status', ['resolved', 'inactive', 'entered-in-error', 'entered_in_error'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Diagnosis $d) => [
                $this->cell((string) ($d->display_name ?: $d->snomed_display ?: $d->code ?: '')),
                $this->cell($this->statusLabel($d->status)),
            ])
            ->all();
    }

    /**
     * What the patient is on right now.
     *
     * The same prescription window page 06 reads, so the two pages cannot
     * disagree about what "current" means. Repeats are folded together — the
     * same drug at the same dose and frequency, re-prescribed across several
     * scripts, is one medicine to the reader and three lines of noise on a
     * page meant to be scanned. A different dose or a different frequency is a
     * different line and is kept.
     *
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function medicineRows(Patient $patient): array
    {
        $prescriptions = Prescription::with('items')
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['active', 'partially_dispensed'])
            ->orderByDesc('prescribed_at')
            ->get();

        $rows = [];

        foreach ($prescriptions as $prescription) {
            foreach ($prescription->items as $line) {
                /** @var PrescriptionItem $line */
                $drug = trim((string) $line->drug_name);

                if ($drug === '') {
                    continue;
                }

                $dose      = trim((string) $line->dose);
                $frequency = trim((string) $line->frequency);
                $key       = mb_strtolower($drug . '|' . $dose . '|' . $frequency);

                if (isset($rows[$key])) {
                    continue;
                }

                $rows[$key] = [
                    $this->cell($drug),
                    $this->cell($dose),
                    $this->cell($frequency),
                ];
            }
        }

        return array_values($rows);
    }

    // ── Who to telephone ─────────────────────────────────────────────────

    /**
     * The emergency contact.
     *
     * `patients.emergency_contact` is free-form json across the estate and is
     * written two ways: as an array by the portal, and as an already-encoded
     * json STRING by the mobile sign-up, which the array cast then encodes a
     * second time and hands back as a string. Both are read here, under the
     * field spellings actually in use, and a field the record does not hold is
     * simply absent — page 02 reads the same three fields the same way.
     *
     * @return list<array{label:string,value:string,tone?:string}>
     */
    private function contactItems(Patient $patient): array
    {
        $raw = $patient->emergency_contact;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw     = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($raw) || $raw === []) {
            return [];
        }

        // Some writers store a single contact, others a list of them.
        $contacts = array_is_list($raw) ? $raw : [$raw];
        $items    = [];

        foreach ($contacts as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            $name     = $this->pick($contact, ['name', 'full_name', 'contact_name']);
            $relation = $this->pick($contact, ['relation', 'relationship', 'relation_to_patient']);
            $phone    = $this->pick($contact, ['phone', 'phone_number', 'contact_phone', 'telephone']);

            if ($name === null && $relation === null && $phone === null) {
                continue;
            }

            if ($name !== null) {
                $items[] = $this->item(__('passport.common_name'), $name);
            }

            if ($relation !== null) {
                $items[] = $this->item(__('passport.columns.role'), $relation);
            }

            if ($phone !== null) {
                $items[] = $this->item(__('passport.columns.contact'), $phone);
            }
        }

        return $items;
    }

    /** The first of the several spellings the estate uses for one field, or none. */
    private function pick(array $contact, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($contact[$key]) && is_scalar($contact[$key]) && trim((string) $contact[$key]) !== '') {
                return trim((string) $contact[$key]);
            }
        }

        return null;
    }
}
