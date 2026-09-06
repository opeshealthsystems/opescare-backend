<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\ReferralCase;
use App\Services\Patient\Passport\PageSource;
use Illuminate\Support\Collection;

/**
 * Page 17 — Referrals: where each referral has got to.
 *
 * A referral has a life. It is raised by one clinician, sent to another
 * facility, accepted or declined there, and eventually completed — or it
 * quietly lapses because nobody acted on it before its window closed. "I was
 * referred to cardiology in August; has anything happened?" is one of the
 * commonest reasons a patient opens their record at all, so this page is built
 * around the answer to that question rather than around the list.
 *
 * The sheet's treatment (page 21) is followed: block one sets out in full the
 * one thing the reader came for, as a list of facts; block two is the ruled
 * table of everything else.
 *
 *   1. REFERRED TO — the referral that is still live, with its reason, its
 *      destination and the date its window closes. Shown only when exactly one
 *      referral still stands; see below.
 *   2. REFERRALS — the register: every other referral, newest first, each with
 *      where it got to and, where one was recorded, the explanation of why it
 *      ended there.
 *
 * ── A REFERRAL THAT IS OVER MUST NEVER READ AS STILL PENDING ──────────────
 *
 * This is the failure that would matter. A patient who reads "Sent" beside a
 * referral raised eleven months ago goes on waiting for a call that is never
 * coming, when what they needed to know was that it lapsed and must be raised
 * again.
 *
 * `referral_cases` records the ending twice over: in the `status` column, and
 * in `expires_at`. Nothing in this deployment sweeps expired cases and rewrites
 * their status — the schema's own list of statuses includes `expired`, but a
 * row only reaches it if something writes it — so the status column goes stale
 * while the date beside it says the referral is finished. The model already
 * settles the disagreement in `isActive()`: sent or accepted, AND not past its
 * expiry. This page reads standing through that same method rather than through
 * the status word alone, so a referral cannot be live at the counter and live
 * on this page for different reasons, and prints the derived word "Expired"
 * where the two disagree.
 *
 * Deriving that word is not inventing one. Both columns are recorded; reading
 * them together is what the model does, and printing the stale one alone would
 * be the invention.
 *
 * The pill for a lapsed referral is NEUTRAL, deliberately, and not the red that
 * `statusTone()` gives the word "expired". A window that closed is not an
 * alarm — it is simply no longer live — and page 21 made the same choice for a
 * care-team member who has left. Declined and cancelled referrals do get red:
 * there the record is saying that someone decided against it, and the patient
 * has to do something about it.
 *
 * ── A DRAFT IS NOT A REFERRAL, AND IS NOT SHOWN ───────────────────────────
 *
 * `referral_cases.status` defaults to `draft`: a clinician's unsent working
 * copy. Printing one here would tell a patient they have been referred to
 * cardiology when nothing has left the building — the identical mistake the
 * `patient_referral_requests` migration was written to prevent, in its own
 * words: "`draft` reads as 'nearly sent'". Drafts are therefore left off the
 * page entirely. Everything that WAS issued stays, including the declined and
 * the cancelled: a referral that happened is care that happened, and a reader
 * given a shorter list believes it complete.
 *
 * ── WHAT THIS PAGE DOES NOT SHOW, AND WHY ─────────────────────────────────
 *
 *  • THE PATIENT'S OWN REFERRAL REQUESTS (`patient_referral_requests`). A
 *    request the patient submitted from the app is not a referral, and its
 *    table's migration is explicit that the two must never be read as the same
 *    thing. The shared passport vocabulary has no heading that separates a
 *    request from an issued referral, so listing them under "Referrals" would
 *    do exactly what that migration forbids. The missing key is named in the
 *    handover instead of a wrong one borrowed here.
 *  • THE CLINICAL SUMMARY (`clinical_summary`). It is clinician-to-clinician
 *    prose — several paragraphs of history, medication and allergy detail
 *    already set out, in the patient's own words, on pages 04 to 06. Repeating
 *    it in a table cell would bury this page's answer without adding a fact.
 *  • WHAT WAS SHARED (`included_record_types`). Which parts of the record
 *    travelled with the referral is a consent question, and page 23 is where
 *    the passport answers consent questions.
 *  • A CLINICIAN'S CONTACT DETAILS. Page 21 settled this and the reasoning is
 *    unchanged: nothing on a document the patient carries, photographs and
 *    hands across a counter should publish a clinician's personal number or
 *    their login address. The receiving clinician is named — that name is
 *    written on the referral itself — and nothing more.
 *  • URGENCY is carried under `columns.urgency` ("Urgency" / "Degré d’urgence").
 *    It is triage, and deliberately NOT `columns.flag`: a flag is what a
 *    laboratory says about a result, and the two must not share a heading.
 *    The column appears only when at least one referral is not routine — a
 *    column of "Routine" tells the reader nothing.
 */
class ReferralsPage extends PageSource
{
    /**
     * How many referrals the register shows.
     *
     * A referral history is short — even a complicated one, over a lifetime,
     * does not reach two dozen — so this is a guard against a corrupted or
     * duplicated record building a page of thousands of rows, not a summary.
     * The page is meant to be the whole history.
     */
    private const WINDOW = 24;

    /** Not a referral yet: a clinician's unsent working copy. */
    private const NOT_ISSUED = ['draft'];

    /** The statuses in which a referral is still waiting on somebody. */
    private const STILL_STANDING = ['sent', 'accepted'];

    public function build(Patient $patient): array
    {
        $referrals = ReferralCase::query()
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::WINDOW)
            ->get()
            ->reject(fn (ReferralCase $r) => in_array($this->word($r->status), self::NOT_ISSUED, true))
            ->values();

        if ($referrals->isEmpty()) {
            return [];
        }

        $facilities = $this->facilities($referrals);
        $sections   = [];

        // The one the reader came for, set out in full — but only when there
        // is exactly one still live. With two open referrals, singling one out
        // would leave the other looking like history; they then share the
        // register, where each carries its own standing and neither is
        // privileged.
        $standing = $referrals->filter(fn (ReferralCase $r) => $this->stands($r))->values();
        $live     = $standing->count() === 1 ? $standing->first() : null;

        if ($live !== null) {
            $sections[] = $this->liveBlock($live, $facilities);
        }

        $rest = $live === null
            ? $referrals
            : $referrals->reject(fn (ReferralCase $r) => (string) $r->getKey() === (string) $live->getKey())->values();

        if ($rest->isNotEmpty()) {
            $sections[] = $this->register($rest, $facilities);
        }

        return $sections;
    }

    // ── The blocks ───────────────────────────────────────────────────────

    /**
     * Block 1 — the referral that is still live, as a list of facts.
     *
     * Destination, reason, date and standing are always shown: a blank in any
     * of them is itself worth reading. The rest appear only when recorded,
     * because a facts block half of which says "Not recorded" tells the reader
     * less than a shorter one that is entirely true — the judgement page 21
     * made for its primary provider.
     *
     * There is no `sections` key for "the referral you are waiting on".
     * `columns.referred_to` is that idea in the vocabulary's own words and is
     * already 1:1 in EN/FR, so it is borrowed rather than a key added — the
     * same choice pages 08 and 21 made for their opening blocks.
     *
     * @param  array<string,string>  $facilities
     */
    private function liveBlock(ReferralCase $referral, array $facilities): array
    {
        $items = [];

        if (($specialty = trim((string) $referral->receiving_specialty)) !== '') {
            $items[] = $this->item(__('passport.columns.specialty'), $specialty);
        }

        $items[] = $this->item(__('passport.columns.referred_to'), $this->destination($referral, $facilities));

        if (($urgency = $this->urgency($referral)) !== null) {
            $items[] = $this->item(__('passport.columns.urgency'), $urgency['label'], $urgency['tone']);
        }

        $items[] = $this->item(__('passport.columns.reason'), trim((string) $referral->reason));

        // Where it was raised. Named after the destination and immediately
        // before the date it was raised, so the two read together as "raised
        // at X on Y" and cannot be mistaken for the place being referred to.
        if (($from = $facilities[(string) $referral->referring_facility_id] ?? '') !== '') {
            $items[] = $this->item(__('passport.columns.facility'), $from);
        }

        $items[] = $this->item(__('passport.columns.date'), $this->date($referral->created_at));

        [$word, $tone] = $this->standing($referral);
        $items[] = $this->item(__('passport.columns.status'), $this->statusLabel($word), $tone);

        // The date the window closes. On a live referral this is always in the
        // future — a past one would have made it lapsed — so it reads as the
        // deadline it is: after this, it has to be raised again.
        if ($referral->expires_at) {
            $items[] = $this->item(__('passport.columns.expires'), $this->date($referral->expires_at));
        }

        return $this->section(__('passport.columns.referred_to'), $items, 'blue', 'arrow-right');
    }

    /**
     * Block 2 — the register, newest referral first.
     *
     * Two columns come and go with the record. FLAG carries the clinical
     * triage and appears only when something is not routine. NOTES carries the
     * recorded explanation of how a referral ended — why it was declined, why
     * it was cancelled, what came back from the receiving clinician — and
     * appears only when at least one referral has one. An empty NOTES cell is
     * left empty rather than filled with "Not recorded": there is nothing to
     * record about a referral that is still in flight.
     *
     * @param  Collection<int,ReferralCase>  $referrals
     * @param  array<string,string>  $facilities
     */
    private function register(Collection $referrals, array $facilities): array
    {
        $showsFlag  = $referrals->contains(fn (ReferralCase $r) => $this->urgency($r) !== null);
        $showsNotes = $referrals->contains(fn (ReferralCase $r) => $this->note($r) !== '');

        $columns = [
            __('passport.columns.date'),
            __('passport.columns.specialty'),
            __('passport.columns.referred_to'),
        ];

        if ($showsFlag) {
            $columns[] = __('passport.columns.urgency');
        }

        // Never dropped, however uniform the rows look: it is the only thing
        // separating a referral somebody is still working on from one that is
        // finished, declined or lapsed.
        $columns[] = __('passport.columns.status');

        if ($showsNotes) {
            $columns[] = __('passport.columns.notes');
        }

        $rows = $referrals->map(function (ReferralCase $r) use ($facilities, $showsFlag, $showsNotes) {
            $row = [
                $this->cell($this->date($r->created_at)),
                $this->cell(trim((string) $r->receiving_specialty)),
                $this->cell($this->destination($r, $facilities)),
            ];

            if ($showsFlag) {
                // Once the column is there every row carries its recorded
                // triage, routine included; only the ones that demand
                // attention carry a pill.
                $urgency = $this->urgency($r) ?? ['label' => $this->statusLabel((string) $r->urgency), 'tone' => null];
                $row[]   = $this->cell($urgency['label'], $urgency['tone']);
            }

            [$word, $tone] = $this->standing($r);
            $row[] = $this->cell($this->statusLabel($word), $tone);

            if ($showsNotes) {
                $row[] = $this->note($r);
            }

            return $row;
        })->all();

        return $this->table(__('passport.sections.referrals'), $columns, $rows, 'blue', 'arrow-right');
    }

    // ── Reading one referral ─────────────────────────────────────────────

    /**
     * Is anybody still expected to act on this referral?
     *
     * Read through the model's own `isActive()` — sent or accepted, and not
     * past its expiry — so that a referral is live on this page for exactly
     * the same reason it is live anywhere else in the platform.
     */
    private function stands(ReferralCase $referral): bool
    {
        try {
            return $referral->isActive();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Where this referral has got to, as a word and a pill colour.
     *
     * The recorded status is printed as recorded, with one exception, and that
     * exception is the point of the page: a referral still marked sent or
     * accepted whose window has closed reads as EXPIRED, because the record —
     * taken whole, both columns of it — says it is over. See the note at the
     * head of this class on why that is a reading and not an invention, and on
     * why the pill is neutral rather than red.
     *
     * @return array{0:string,1:?string}
     */
    private function standing(ReferralCase $referral): array
    {
        $status = $this->word($referral->status);

        if (in_array($status, self::STILL_STANDING, true) && $this->hasLapsed($referral)) {
            return ['expired', 'neutral'];
        }

        $tone = match ($status) {
            // Waiting on the receiving facility to answer. Amber, because this
            // is the state a patient is asking about when they open the page.
            'sent'                             => 'warning',
            'accepted', 'completed'            => 'success',
            // Somebody decided against it, and the patient has to act.
            'rejected', 'declined', 'cancelled' => 'danger',
            // A window that closed is not an alarm, it is simply not live.
            'expired'                          => 'neutral',
            default                            => $this->statusTone($status),
        };

        return [$status, $tone];
    }

    /** Has the referral's window closed? Asked of the model, never re-derived here. */
    private function hasLapsed(ReferralCase $referral): bool
    {
        try {
            return $referral->isExpired();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * The clinical triage, when it is worth printing.
     *
     * Returns nothing for a routine referral — which is most of them — so that
     * the column disappears from a register where nothing is urgent. The tones
     * are chosen here rather than looked up because urgency is triage, not a
     * status word: `severityTone()` does not know these words and would give
     * an urgent referral no pill at all.
     *
     * @return array{label:string,tone:?string}|null
     */
    private function urgency(ReferralCase $referral): ?array
    {
        $tone = match ($this->word($referral->urgency)) {
            'emergency', 'immediate', 'stat' => 'danger',
            'urgent', 'soon'                 => 'warning',
            default                          => null,
        };

        if ($tone === null) {
            return null;
        }

        return ['label' => $this->statusLabel((string) $referral->urgency), 'tone' => $tone];
    }

    /**
     * The recorded explanation of how a referral ended, if there is one.
     *
     * Read in the order the record can only hold one of them: why it was
     * declined, why it was cancelled, and otherwise whatever came back from
     * the receiving clinician. Printed as recorded — a clipped clinical
     * sentence is worse than a long one.
     */
    private function note(ReferralCase $referral): string
    {
        foreach ([$referral->rejection_reason, $referral->cancellation_reason, $referral->feedback] as $text) {
            if (($text = trim((string) $text)) !== '') {
                return $text;
            }
        }

        return '';
    }

    /**
     * Where the patient was sent, and to whom.
     *
     * The receiving facility, with the named receiving clinician in
     * parentheses when the referral records one — which is how a referral is
     * spoken about at a desk ("CHU, Dr Sow"). With no facility on the record
     * the clinician's name stands alone; a referral to a named specialist with
     * no facility yet chosen is a real and common state, and saying only "Not
     * recorded" would throw away the half that IS known.
     *
     * @param  array<string,string>  $facilities
     */
    private function destination(ReferralCase $referral, array $facilities): string
    {
        $facility  = $facilities[(string) $referral->receiving_facility_id] ?? '';
        $clinician = trim((string) $referral->receiving_provider_name);

        if ($facility === '') {
            return $clinician;
        }

        return $clinician === '' ? $facility : $facility . ' (' . $clinician . ')';
    }

    // ── Resolving what sits behind the ids ───────────────────────────────

    /**
     * The names of both ends of every referral on the page.
     *
     * `referral_cases` points at `facilities`, the operational register that
     * records care, and that is asked first. This platform also carries the
     * national directory (`care_facilities`) and the two are only partly
     * linked, so a facility the operational register cannot name is looked for
     * there before the page gives up on it — the same two-step pages 08, 14
     * and 21 make.
     *
     * The demo-isolation scope is lifted because this is a lookup BY ID of a
     * facility already named on the patient's own referral, not a search: with
     * the scope on, a real facility would lose its name whenever the app runs
     * in demo mode, and a demo one whenever it does not.
     *
     * @param  Collection<int,ReferralCase>  $referrals
     * @return array<string,string>
     */
    private function facilities(Collection $referrals): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            'strval',
            $referrals->pluck('referring_facility_id')
                ->merge($referrals->pluck('receiving_facility_id'))
                ->all()
        ))));

        if ($ids === []) {
            return [];
        }

        $names = [];

        try {
            foreach (Facility::withoutGlobalScope('isolate_demo')->whereIn('id', $ids)->get(['id', 'name']) as $facility) {
                if (($name = trim((string) $facility->name)) !== '') {
                    $names[(string) $facility->id] = $name;
                }
            }
        } catch (\Throwable) {
            $names = [];
        }

        $missing = array_values(array_diff($ids, array_keys($names)));

        if ($missing === []) {
            return $names;
        }

        try {
            // Two ways a directory row answers to an id here: it is the
            // directory entry FOR an operational facility (facility_id), or
            // the referral pointed straight at the directory row (id).
            $directory = CareFacility::query()
                ->whereIn('facility_id', $missing)
                ->orWhereIn('id', $missing)
                ->get(['id', 'facility_id', 'facility_name']);

            foreach ($directory as $row) {
                foreach ([(string) $row->facility_id, (string) $row->id] as $key) {
                    if ($key === '' || ! in_array($key, $missing, true) || isset($names[$key])) {
                        continue;
                    }

                    if (($name = trim((string) $row->facility_name)) !== '') {
                        $names[$key] = $name;
                    }
                }
            }
        } catch (\Throwable) {
            // One register being unavailable must not cost the reader the
            // whole page; the facility simply goes unnamed.
        }

        return $names;
    }

    /** A recorded word, lowered and trimmed, for comparison against a list. */
    private function word($value): string
    {
        return strtolower(trim((string) $value));
    }
}
