<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Admission;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Services\Patient\Passport\PageSource;

/**
 * Page 09 — Hospital Admissions.
 *
 * WHAT THIS PAGE IS FOR. An inpatient stay is the heaviest thing in a record:
 * it says the patient was ill enough to be kept in, where, for how long, and
 * why. Someone reading this page is usually deciding whether tonight's
 * presentation is a repeat of a stay this patient has already had. So each row
 * is a stay, and the two dates are given as recorded rather than folded into a
 * "length of stay" the record does not itself hold.
 *
 * THE TABLE, AND WHY IT ENDS WHERE IT DOES. `admissions` records the stay
 * (facility, bed, reason, status, the two timestamps) and, separately, how it
 * concluded (`discharge_reason`, `discharge_destination`). The conclusion is
 * page 19's subject, not this one's — page 19 lists only the stays that ended
 * and says how. Repeating the destination here would put the same fact on two
 * pages under two headings and invite a reader to treat them as two events, so
 * `passport.columns.outcome` is deliberately left to page 19.
 *
 * WHAT AN UNDISCHARGED ROW SHOWS. A stay still in progress has no
 * `discharged_at`, and the column reads as the honest blank rather than being
 * filled with today's date or an em dash that could be mistaken for one. Its
 * status carries the fact that it is still open.
 *
 * A NOTE ON THE STATUS PILL. `admissions.status` is `active | discharged |
 * transferred`. The shared PageSource::statusTone() knows `active` and returns
 * nothing for the other two, so a concluded stay renders with no pill. That is
 * left exactly as the shared helper returns it: this page must not invent a
 * colour language of its own for a word every other page would render
 * differently. The gap is reported rather than patched here.
 *
 * WHAT THE RECORD CANNOT SUPPLY. There is no admitting-diagnosis link and no
 * attending-clinician name on this table: `attending_physician_id` is a bare
 * staff uuid with no guarantee of a readable profile, and the admission reason
 * is free text written at the door, not a coded diagnosis. Neither is guessed
 * at from the problem list — a stay paired with a condition nobody attached to
 * it is a fabricated clinical link, and this is exactly the page on which such
 * a link would be believed.
 */
class HospitalAdmissionsPage extends PageSource
{
    public function build(Patient $patient): array
    {
        // Newest first, which is the order a stay is looked for in. An open
        // stay is by construction the most recent one, so it is never the row
        // the limit pushes off the end.
        $admissions = Admission::with('bed.ward')
            ->where('patient_id', $patient->id)
            ->orderByDesc('admitted_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($admissions->isEmpty()) {
            return [];
        }

        $facilities = $this->facilityNames($admissions->pluck('facility_id')->all());

        return [
            $this->stats(
                __('passport.sections.summary'),
                [['value' => $admissions->count(), 'label' => __('passport.stats.admissions')]],
                'blue',
                'building'
            ),

            $this->table(
                __('passport.sections.admissions'),
                [
                    __('passport.columns.admitted'),
                    __('passport.columns.discharged'),
                    __('passport.columns.facility'),
                    __('passport.columns.ward'),
                    __('passport.columns.reason'),
                    __('passport.columns.status'),
                ],
                $admissions->map(fn (Admission $a) => [
                    $this->cell($this->date($a->admitted_at)),
                    // Blank while a stay is still open — never today's date.
                    $this->cell($a->discharged_at ? $this->date($a->discharged_at) : ''),
                    $this->cell((string) ($facilities[(string) $a->facility_id] ?? __('passport.a_facility'))),
                    $this->cell((string) ($a->bed?->ward?->name ?? '')),
                    $this->cell((string) ($a->admission_reason ?: '')),
                    $this->cell($this->statusLabel((string) $a->status), $this->statusTone((string) $a->status)),
                ])->all(),
                'blue',
                'building'
            ),
        ];
    }

    /**
     * Facility names for a set of ids.
     *
     * Two registers carry them — `facilities` (the tenants that record care)
     * and `care_facilities` (the national directory) — and an admission may
     * point at either, so both are asked in that order.
     *
     * The demo-isolation scope is lifted on purpose. It hides a facility whose
     * `is_demo` flag does not match the running environment, and the flag
     * describes the environment the row was created in, not the institution:
     * with it on, a stay at a real hospital renders with a blank facility
     * because the hospital's row is flagged real while the app runs in demo
     * mode. The admission already names the facility by id; refusing to read
     * its name back would hide a fact the record states plainly. Names are
     * public directory data, not patient data.
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
                $names += CareFacility::whereIn('id', $missing)->pluck('facility_name', 'id')->all();
            } catch (\Throwable) {
                // One register being unavailable must not cost the reader the
                // whole stay; the name falls back to "a facility".
            }
        }

        return array_map('strval', $names);
    }
}
