<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\AllergyRecord;
use App\Models\ClinicalAlert;
use App\Models\Facility;
use App\Models\Patient;
use App\Services\Patient\Passport\PageSource;

/**
 * Page 05 — Allergies & Alerts.
 *
 * The page a clinician is meant to read before they prescribe, so it is the
 * one page where a comfortable-looking guess would do real harm. Three rules
 * follow from that and they outrank the design:
 *
 *  1. NO SEVERITY IS EVER DEFAULTED. The table shows the word the record
 *     holds. `severityTone()` gives it the sheet's colour only when it
 *     recognises the word; an unrecognised severity is printed plainly with no
 *     pill, because a wrong colour on this page reads as clinical reassurance
 *     that nobody actually gave.
 *
 *  2. THE ALLERGY LIST IS NEVER TRUNCATED. Other pages take the eight most
 *     recent rows because a long history is noise. An allergen hidden behind a
 *     row limit is not noise — it is the one line the reader needed. Every
 *     recorded allergy is listed.
 *
 *  3. NOTHING IS DROPPED FOR BEING INACTIVE. A withdrawn or entered-in-error
 *     allergy stays visible with its status shown, so the reader can see that
 *     it was considered and set aside, rather than reading a shorter list and
 *     believing it complete.
 *
 * WHAT THE PRINTED SHEET SHOWS THAT THIS DEPLOYMENT CANNOT. The sheet's
 * allergy table carries a "Reaction" column; `allergy_records` records only the
 * substance, its severity and its status, so the column is left out rather
 * than filled with something plausible. The sheet's "Alert instructions" and
 * "Emergency response" cards are printed safety guidance, not a patient's
 * record, and have no source here either.
 */
class AllergiesAlertsPage extends PageSource
{
    public function build(Patient $patient): array
    {
        $allergies = $this->allergyRows($patient);
        $alerts    = $this->alertRows($patient);

        // Nothing recorded is a fact about the record. Say it plainly rather
        // than opening a safety banner over an empty page.
        if ($allergies === [] && $alerts === []) {
            return [];
        }

        $sections = [
            $this->callout(
                __('passport.blocks.in_case_of_emergency'),
                __('passport.blocks.emergency_note'),
                'red',
                'alert-triangle'
            ),
        ];

        if ($allergies !== []) {
            $sections[] = $this->table(
                __('passport.sections.allergies'),
                [
                    __('passport.columns.allergen'),
                    __('passport.columns.severity'),
                    __('passport.columns.date'),
                    __('passport.columns.provider'),
                    __('passport.columns.status'),
                ],
                $allergies,
                'red',
                'pill'
            );
        }

        if ($alerts !== []) {
            $sections[] = $this->table(
                __('passport.sections.critical_alerts'),
                [
                    __('passport.columns.type'),
                    __('passport.columns.detail'),
                    __('passport.columns.severity'),
                    __('passport.columns.date'),
                    __('passport.columns.facility'),
                    __('passport.columns.status'),
                ],
                $alerts,
                'red',
                'alert-triangle'
            );
        }

        return $sections;
    }

    /**
     * Every allergy on the record, newest first — no row limit, no filter.
     *
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function allergyRows(Patient $patient): array
    {
        return AllergyRecord::with('provider')
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (AllergyRecord $a) => [
                $this->cell((string) $a->substance),
                $this->cell($this->statusLabel($a->severity), $this->severityTone($a->severity)),
                $this->cell($this->date($a->created_at)),
                $this->cell((string) ($a->provider->name ?? '')),
                $this->cell($this->statusLabel($a->status), $this->statusTone($a->status)),
            ])
            ->values()
            ->all();
    }

    /**
     * Clinical alerts that still stand against this patient.
     *
     * Alerts a clinician resolved or dismissed are left off: unlike an allergy,
     * a closed alert is a finished event, and carrying it here would push the
     * standing ones down the page. Overridden alerts DO stay — an override is a
     * decision taken in spite of the alert, not a retraction of it.
     *
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function alertRows(Patient $patient): array
    {
        $alerts = ClinicalAlert::where('patient_id', $patient->id)
            ->whereIn('status', ['active', 'acknowledged', 'overridden'])
            ->orderByDesc('triggered_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($alerts->isEmpty()) {
            return [];
        }

        $facilities = Facility::whereIn('id', $alerts->pluck('facility_id')->filter()->unique())
            ->pluck('name', 'id');

        return $alerts
            ->map(fn (ClinicalAlert $a) => [
                $this->cell($this->statusLabel($a->alert_type)),
                $this->cell((string) $a->alert_message),
                $this->cell($this->statusLabel($a->severity), $this->severityTone($a->severity)),
                $this->cell($this->date($a->triggered_at)),
                $this->cell((string) ($facilities[$a->facility_id] ?? '')),
                $this->cell($this->statusLabel($a->status), $this->statusTone($a->status)),
            ])
            ->values()
            ->all();
    }
}
