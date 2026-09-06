<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Admission;
use App\Models\Patient;
use App\Models\User;
use App\Services\Patient\Passport\PageSource;
use Illuminate\Support\Facades\DB;

/**
 * Page 19 — Discharge Summary.
 *
 * WHAT THIS PAGE IS FOR, AND HOW IT DIFFERS FROM PAGE 09. Page 09 lists the
 * stays: admitted, discharged, facility, ward, why the patient came in, and
 * whether the stay is still running. This page is about how each stay *ended*.
 * The two read the same table and must not tell the same story twice, so the
 * columns are deliberately disjoint: page 09 keeps the stay's own facts and
 * this page takes the conclusion — the destination page 09 explicitly reserved
 * for it (`passport.columns.outcome`), what the stay achieved, and who signed
 * it off. `discharged` is the one heading they share, because it is the date a
 * reader uses to line one page up against the other.
 *
 * ONLY STAYS THAT ENDED. The row set is `discharged_at IS NOT NULL` — the same
 * test `PatientDocumentService::admission()` applies before it will render the
 * downloadable discharge summary, so the page and the sheet a patient can
 * download agree on which stays have a discharge summary at all. A stay still
 * in progress is not listed here in any form: a discharge summary naming a
 * patient who has not been discharged reads as a discharge, and someone will
 * act on it. The status word is not used as the filter — `discharged_at` is the
 * timestamp of the ending itself, and a stay that ended by transfer out ended
 * just as truly as one that ended by discharge home. A patient currently in a
 * bed and never previously admitted therefore gets an empty page, which is the
 * true statement about their record.
 *
 * WHAT THE OUTCOME COLUMN CARRIES. `admissions.discharge_destination` is a
 * short coded word (`home | referral | ama | deceased`) and it is the outcome
 * of the stay in the only sense the record codes; `discharge_reason` is the
 * free-text clinical line and sits under "Detail" beside it. The destination is
 * the one value on this page that colour can usefully carry, so it takes a
 * pill: the shared `statusTone()` is asked first, so that a word the rest of
 * the passport already renders is rendered the same way here, and only when it
 * has nothing to say does the destination vocabulary — which no other page
 * reads — supply its own. Anything outside that vocabulary gets no pill rather
 * than a guessed one.
 *
 * WHO SIGNED IT OFF. `attending_physician_id` is a bare staff uuid. Page 09
 * declined to render it because a stay does not need it; a discharge summary
 * does — it is the name a receiving clinician rings. It is looked up in both
 * registers that could hold it and left blank when neither does. A blank there
 * means the record does not say who; it is never filled with the facility, the
 * admitting clerk, or anyone else who happens to be on the row.
 *
 * WHAT THIS PAGE DOES NOT SHOW. No length of stay: the record holds two
 * timestamps and not a duration, and page 09 already declined to fold them into
 * one. No facility: that is the stay's own fact and page 09 carries it, and
 * repeating it here would make a four-column phone table out of a three-column
 * story. No admitting diagnosis: the admission reason is free text written at
 * the door, and pairing a discharge with a condition nobody attached to it
 * would be a fabricated clinical link on exactly the page where it would be
 * believed.
 */
class DischargeSummaryPage extends PageSource
{
    public function build(Patient $patient): array
    {
        // Newest ending first — the discharge a reader is looking for is
        // almost always the last one.
        $discharges = Admission::where('patient_id', $patient->id)
            ->whereNotNull('discharged_at')
            ->orderByDesc('discharged_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($discharges->isEmpty()) {
            return [];
        }

        $clinicians = $this->clinicianNames($discharges->pluck('attending_physician_id')->all());

        return [
            $this->table(
                __('passport.sections.discharge'),
                [
                    __('passport.columns.discharged'),
                    __('passport.columns.outcome'),
                    __('passport.columns.detail'),
                    __('passport.columns.provider'),
                ],
                $discharges->map(fn (Admission $a) => [
                    $this->cell($this->date($a->discharged_at)),
                    $this->destination($a->discharge_destination),
                    $this->cell((string) ($a->discharge_reason ?: '')),
                    $this->cell((string) ($clinicians[(string) $a->attending_physician_id] ?? '')),
                ])->all(),
                'green',
                'folder-plus'
            ),
        ];
    }

    /**
     * Where the patient went, with the pill the sheet gives a coded outcome.
     *
     * The shared status vocabulary is consulted first so a word the rest of the
     * passport knows keeps its colour here. `discharge_destination` is its own
     * vocabulary though — no other page reads it — and the four values the
     * table documents carry real weight for a reader glancing at the page, so
     * they are coloured here. A fifth value some deployment invents falls
     * through to no pill: an uncoloured word is merely plain, a wrongly
     * coloured one is a false reassurance.
     */
    private function destination(?string $destination): array
    {
        $word = strtolower(trim((string) $destination));

        if ($word === '') {
            return $this->cell('');
        }

        return $this->cell(
            $this->statusLabel($word),
            $this->statusTone($word) ?? match ($word) {
                'home'                            => 'success',
                'referral'                        => 'neutral',
                'ama', 'against_medical_advice'   => 'warning',
                'deceased'                        => 'danger',
                default                           => null,
            }
        );
    }

    /**
     * Names for a set of attending-physician ids.
     *
     * The column holds a staff uuid and the deployments differ on which table
     * that uuid belongs to, so both are asked: the account (`users`) first,
     * because that is where a clinician's display name is maintained, then the
     * employment record (`staff_profiles`). An id that neither register knows
     * yields no entry, and the cell renders the honest blank rather than a
     * uuid, a job title, or a stand-in name.
     *
     * @param  list<mixed>  $ids
     * @return array<string,string>
     */
    private function clinicianNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));

        if ($ids === []) {
            return [];
        }

        $names = [];

        try {
            $names = User::whereIn('id', $ids)->pluck('name', 'id')->all();
        } catch (\Throwable) {
            $names = [];
        }

        $missing = array_values(array_diff($ids, array_keys($names)));

        if ($missing !== []) {
            try {
                foreach (DB::table('staff_profiles')->whereIn('id', $missing)->get() as $staff) {
                    $name = trim(((string) $staff->first_name) . ' ' . ((string) $staff->last_name));

                    if ($name !== '') {
                        $names[(string) $staff->id] = $name;
                    }
                }
            } catch (\Throwable) {
                // One register being unavailable must not cost the reader the
                // whole discharge; the name falls back to the honest blank.
            }
        }

        return array_map('strval', $names);
    }
}
