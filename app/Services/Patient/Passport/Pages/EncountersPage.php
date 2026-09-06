<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\CareFacility;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\Patient\Passport\PageSource;
use Illuminate\Support\Collection;

/**
 * Page 08 — Visits & Encounters.
 *
 * The sheet for page 04 carries this page in miniature as its RECENT KEY
 * ENCOUNTERS block — Date, Type, Facility, Reason. This is the full version of
 * that block: the whole visit history rather than the last handful, plus the
 * two things the record holds that a reader of a longitudinal passport asks for
 * next — who attended, and how the encounter concluded.
 *
 * ── WHY THE HISTORY IS NOT TRUNCATED ──────────────────────────────────────
 *
 * Other pages take the eight most recent rows, because on a summary a long tail
 * is noise. This page IS the tail: it is the one place in the passport that
 * answers "where have I been seen, and when". A row limit here would also put
 * the counted tile at the top into open disagreement with the table under it,
 * and a clinician reading two numbers that do not match reads it as an error in
 * the record rather than in the passport. Every recorded encounter is listed,
 * newest first, and the tile is that same count.
 *
 * ── WHAT IS SHOWN AND WHAT IS NOT ─────────────────────────────────────────
 *
 *  • TYPE is `encounter_type` as recorded — outpatient, inpatient, emergency,
 *    telemedicine, referral. The sheet's artwork shows "Consultation" and
 *    "Laboratory"; those are display categories this schema does not carry, and
 *    re-labelling an outpatient encounter as a "consultation" would be this
 *    page inventing a classification nobody recorded.
 *  • A CANCELLED ENCOUNTER STAYS ON THE PAGE, with its status shown. It is care
 *    that was arranged and did not happen, and a reader who cannot see it reads
 *    a shorter history and believes it complete — the same reasoning that keeps
 *    withdrawn allergies visible on page 05.
 *  • OUTCOME is `discharge_disposition`: what the clinician recorded as the
 *    conclusion of the encounter. It is sparse — most encounters carry none —
 *    so it is a block of its own that appears only when something was actually
 *    recorded, rather than a column of blanks running down the main table.
 *  • DURATION is derivable (`Encounter::durationMinutes()`) and deliberately not
 *    shown: minutes in a consulting room is an operations metric, not something
 *    a patient or a receiving clinician reads a passport for.
 *  • The sheet has no NOTES source here either — `encounters` records a reason
 *    and a disposition, and nothing that could stand in for a clinical note.
 */
class EncountersPage extends PageSource
{
    public function build(Patient $patient): array
    {
        $encounters = Encounter::where('patient_id', $patient->id)
            ->orderByDesc('started_at')
            ->get();

        if ($encounters->isEmpty()) {
            return [];
        }

        $facilities = $this->facilityNames($encounters->pluck('facility_id')->all());
        $providers  = $this->providerNames($encounters->pluck('attending_provider_id')->all());

        $sections = [
            // The sheet opens its pages on a tile row. Only one of these tiles
            // has a source: the number of encounters on the record. It is the
            // same count page 04 puts on its "Visits" tile, read the same way,
            // so the two pages cannot disagree.
            $this->stats(
                __('passport.sections.summary'),
                [['value' => $encounters->count(), 'label' => __('passport.stats.encounters')]],
                'blue',
                'calendar-check'
            ),
            $this->table(
                __('passport.sections.encounters'),
                [
                    __('passport.columns.date'),
                    __('passport.columns.type'),
                    __('passport.columns.facility'),
                    __('passport.columns.provider'),
                    __('passport.columns.reason'),
                    __('passport.columns.status'),
                ],
                $encounters->map(fn (Encounter $e) => [
                    $this->cell($this->date($e->started_at)),
                    $this->cell($this->statusLabel($e->encounter_type)),
                    $this->cell((string) ($facilities[(string) $e->facility_id] ?? '')),
                    $this->cell((string) ($providers[(string) $e->attending_provider_id] ?? '')),
                    $this->cell((string) $e->reason_for_encounter),
                    $this->cell($this->statusLabel($e->status), $this->statusTone($e->status)),
                ])->all(),
                'blue',
                'calendar-check'
            ),
        ];

        $outcomes = $this->outcomeRows($encounters, $facilities);

        if ($outcomes !== []) {
            $sections[] = $this->table(
                // There is no `sections` key for "how the encounter ended".
                // `columns.outcome` is that word exactly and is already 1:1 in
                // EN/FR, so it is borrowed rather than a new key added.
                __('passport.columns.outcome'),
                [
                    __('passport.columns.date'),
                    __('passport.columns.type'),
                    __('passport.columns.facility'),
                    __('passport.columns.outcome'),
                ],
                $outcomes,
                'teal',
                'check-circle'
            );
        }

        return $sections;
    }

    // ── Reading the record ───────────────────────────────────────────────

    /**
     * The encounters that recorded a conclusion, in the same order as the table
     * above so a reader can walk the two blocks together.
     *
     * Date, type and facility repeat the main table's leading columns on
     * purpose. One day can hold two encounters at one hospital — a clinic
     * review and the admission it led to — and an outcome the reader cannot
     * pin to an encounter is worse than one they have to look up.
     *
     * @param  Collection<int,Encounter>  $encounters
     * @param  array<string,string>  $facilities
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function outcomeRows(Collection $encounters, array $facilities): array
    {
        return $encounters
            ->filter(fn (Encounter $e) => trim((string) $e->discharge_disposition) !== '')
            ->map(fn (Encounter $e) => [
                $this->cell($this->date($e->started_at)),
                $this->cell($this->statusLabel($e->encounter_type)),
                $this->cell((string) ($facilities[(string) $e->facility_id] ?? '')),
                $this->cell(trim((string) $e->discharge_disposition)),
            ])
            ->values()
            ->all();
    }

    // ── Resolving the names behind the ids ───────────────────────────────

    /**
     * Facility names for a set of ids.
     *
     * `encounters.facility_id` points at the operational `facilities` register
     * since the 2026-09-06 repoint migration; older rows on a deployment that
     * has not run it still hold a `care_facilities` id. Both registers are
     * asked, in that order, exactly as page 04 asks them — a facility that
     * names itself on the medical summary must not be blank here.
     *
     * The demo-isolation scope is lifted because this is a lookup BY ID of a
     * facility already named on the patient's own encounter, not a search: with
     * the scope on, a real hospital would lose its name whenever the app runs
     * in demo mode, and a demo one whenever it does not.
     *
     * @param  list<mixed>  $ids
     * @return array<string,string>
     */
    private function facilityNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));

        if ($ids === []) {
            return [];
        }

        $names = [];

        try {
            $names = Facility::withoutGlobalScope('isolate_demo')
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all();
        } catch (\Throwable) {
            $names = [];
        }

        $missing = array_values(array_diff($ids, array_keys($names)));

        if ($missing !== []) {
            try {
                $names += CareFacility::whereIn('id', $missing)
                    ->pluck('facility_name', 'id')
                    ->all();
            } catch (\Throwable) {
                // One register being unavailable must not cost the reader the
                // whole encounter row; the name simply stays blank.
            }
        }

        return array_map('strval', $names);
    }

    /**
     * The attending clinician's name, for the encounters that record one.
     *
     * `attending_provider_id` carries no foreign key, so an id that answers to
     * nothing leaves the cell honestly blank rather than printing a raw UUID.
     *
     * @param  list<mixed>  $ids
     * @return array<string,string>
     */
    private function providerNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));

        if ($ids === []) {
            return [];
        }

        try {
            return array_map('strval', User::withoutGlobalScope('isolate_demo')
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all());
        } catch (\Throwable) {
            return [];
        }
    }
}
