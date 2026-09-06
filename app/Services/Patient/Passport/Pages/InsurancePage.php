<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Services\Patient\Passport\PageSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

/**
 * Page 22 — Insurance & Coverage.
 *
 * WHAT THIS PAGE IS FOR. It is the page somebody actually presents at a
 * cashier's window before they are treated, so it answers three questions in
 * the order the window asks them: who pays for this, what else stands behind
 * it, and what does that cover.
 *
 * ── AN EXPIRED POLICY MUST NEVER READ AS COVER ───────────────────────────
 *
 * This is the whole safety argument of the page. `patient_insurance_policies`
 * carries BOTH a `status` word and an `expiry_date`, and the two go out of step
 * constantly: nobody goes back to flip a row to `expired` on the morning it
 * lapses, so a policy that ran out eighteen months ago is still sitting there
 * saying `active`. Printing that word would put a green "Active" pill in front
 * of a cashier for cover that does not exist, and the patient would be admitted
 * against a payer who will refuse the claim.
 *
 * So the status shown here is not the stored word. It is the stored word read
 * against the calendar (see `effectiveStatus()`):
 *
 *   - a status the record itself has ended — cancelled, revoked, inactive —
 *     always wins, whatever the dates say;
 *   - otherwise a past `expiry_date` makes the policy read `Expired`, in the
 *     danger pill, no matter what the `status` column claims;
 *   - otherwise an `effective_date` still in the future reads `Pending`,
 *     because cover that starts next month does not pay for today;
 *   - only what survives all three reads with its recorded status.
 *
 * The same test decides which policies contribute to the benefits block, so a
 * lapsed plan's benefit list can never be read as a live entitlement.
 *
 * ── WHICH POLICY IS "PRIMARY" ────────────────────────────────────────────
 *
 * The approved sheet splits cover into a primary block and an additional one.
 * This schema has NO `is_primary` column — nothing in the record marks one
 * policy as the first payer — so the split is derived, and derived from
 * recorded columns only:
 *
 *   1. cover that is in force outranks cover that is not, always;
 *   2. then by the plan's own recorded `plan_type`, in the order a Cameroonian
 *      counter actually bills: the statutory/social scheme (CNAMGS and the
 *      like), then a mutuelle, then an employer scheme, then private
 *      complementary cover;
 *   3. then the most recently effective.
 *
 * The first policy after that sort fills the primary block; every other policy
 * — expired ones included — is listed in Additional coverage, so nothing is
 * hidden by the split and no policy is dropped.
 *
 * ── WHAT THE SHEET SHOWS THAT THIS RECORD CANNOT ─────────────────────────
 *
 * The sheet's benefits table has four columns: Service Type, Covered, Coverage
 * Limit and Notes. This schema holds only the first. `insurance_plans` records
 * `covered_services` — a JSON list of service names the plan covers — and
 * nothing per service beyond it: no limit, no percentage, no note. There IS a
 * plan-level `copay_percentage`, and turning it into a per-service "80%" column
 * would be arithmetic presented as a benefit schedule; a patient refused at a
 * counter would have been refused holding this page. So the block keeps the one
 * column the record can defend — what the plan says it covers, and which plan
 * says it — and the three invented columns are simply absent.
 *
 * The sheet also prints the insurer's e-mail and website, and a coverage level
 * ("Individual"). `insurance_providers` holds a `portal_url` and a
 * `contact_email`, but the passport vocabulary has no key for either and none
 * may be added here; and nothing in this schema records a coverage level at
 * all — `relationship_to_primary` says whether the patient is the principal
 * member or a dependant, which is a different fact and is shown as that.
 */
class InsurancePage extends PageSource
{
    /**
     * Statuses the record has itself ended. These outrank the calendar: a
     * policy cancelled last week is not cover, however far off its expiry date
     * still is.
     */
    private const ENDED = ['cancelled', 'revoked', 'terminated', 'expired', 'inactive', 'lapsed', 'suspended'];

    /**
     * The order a counter bills cover in. Read off `insurance_plans.plan_type`,
     * which this schema records as nhia / mutual / employer / private. Anything
     * else sorts last rather than being guessed at.
     */
    private const SCHEME_ORDER = ['nhia' => 0, 'mutual' => 1, 'employer' => 2, 'private' => 3];

    public function build(Patient $patient): array
    {
        $policies = PatientInsurancePolicy::with('plan.provider')
            ->where('patient_id', $patient->id)
            ->orderByDesc('effective_date')
            ->limit(12)
            ->get();

        if ($policies->isEmpty()) {
            return [];
        }

        $ranked = $this->ranked($policies);
        $rest   = $ranked;

        $sections = [$this->primary(array_shift($rest))];

        if ($rest !== []) {
            $sections[] = $this->additional($rest);
        }

        // Read in the same order, so the cover that pays first lists what it
        // covers first.
        if ($benefits = $this->benefits($ranked)) {
            $sections[] = $benefits;
        }

        return $sections;
    }

    // ── The blocks ───────────────────────────────────────────────────────

    /**
     * The cover that pays first, as the sheet's label/value block.
     *
     * The identity spine — insurer, plan, policy number, member id, dates,
     * status — is always drawn, because a blank member id on the page is itself
     * what the cashier needs to know. The rest appears only where the record
     * holds it, so the block is not padded with rows that say nothing.
     */
    private function primary(PatientInsurancePolicy $policy): array
    {
        $plan     = $policy->plan;
        $provider = $plan?->provider;

        $items = [
            // Scheme is the insurer, Plan is the product they sold — the same
            // two words in the same two roles everywhere on this page. The
            // insurer is NOT filed under `columns.provider`: that key reads
            // "Professionnel" in French, which a reader would take for the
            // clinician who treated them, on the one page where the question is
            // who pays.
            $this->item(__('passport.columns.scheme'), (string) ($provider?->name ?? '')),
            $this->item(__('passport.columns.plan'), (string) ($plan?->name ?? '')),
            $this->item(__('passport.columns.policy_number'), (string) $policy->policy_number),
            $this->item(__('passport.columns.member_id'), (string) $policy->member_id),
        ];

        if (($type = $this->word($plan?->plan_type)) !== '') {
            $items[] = $this->item(__('passport.columns.type'), $this->planTypeLabel($type));
        }

        // Only worth a row when the patient is NOT the principal member: "Self"
        // tells the counter nothing, whereas cover held in somebody else's name
        // is the fact that decides whether the claim goes through.
        if (($relationship = $this->word($policy->relationship_to_primary)) !== '' && $relationship !== 'self') {
            $items[] = $this->item(__('passport.columns.role'), $this->statusLabel($relationship));

            if (trim((string) $policy->primary_member_name) !== '') {
                $items[] = $this->item(__('passport.columns.who'), trim((string) $policy->primary_member_name));
            }
        }

        $items[] = $this->item(__('passport.columns.started'), $this->date($policy->effective_date));
        $items[] = $this->item(__('passport.columns.expires'), $this->date($policy->expiry_date));

        $status   = $this->effectiveStatus($policy);
        $items[]  = $this->item(
            __('passport.columns.status'),
            $this->statusLabel($status),
            $this->statusTone($status)
        );

        if ($provider && trim((string) $provider->contact_phone) !== '') {
            $items[] = $this->item(__('passport.columns.contact'), trim((string) $provider->contact_phone));
        }

        return $this->section(
            __('passport.sections.primary_insurance'),
            $items,
            // Green is this design's "in order". Cover that has lapsed or been
            // cancelled must not carry it, or the page reads as reassurance
            // before a word of it has been read.
            $this->inForce($policy) ? 'green' : 'amber',
            'shield-check'
        );
    }

    /**
     * Everything else the patient holds, in the same rank order.
     *
     * Lapsed policies stay in this list rather than being filtered out. A
     * patient who believes they still have private cover needs to see the row
     * that says it ended, and a cashier needs to know the top-up they were told
     * about is not there any more.
     *
     * @param  list<PatientInsurancePolicy>  $policies
     */
    private function additional(array $policies): array
    {
        // The Type column is drawn only when some plan records one, rather than
        // standing empty down the page.
        $withType = false;

        foreach ($policies as $policy) {
            if ($this->word($policy->plan?->plan_type) !== '') {
                $withType = true;
                break;
            }
        }

        $columns = [__('passport.columns.plan')];

        if ($withType) {
            $columns[] = __('passport.columns.type');
        }

        $columns[] = __('passport.columns.policy_number');
        $columns[] = __('passport.columns.expires');
        $columns[] = __('passport.columns.status');

        $rows = [];

        foreach ($policies as $policy) {
            $status = $this->effectiveStatus($policy);
            $type   = $this->word($policy->plan?->plan_type);

            $row = [$this->cell((string) ($policy->plan?->name ?? ''))];

            if ($withType) {
                // A plain string, not cell(): a plan with no recorded type has
                // nothing missing from it, and "Not recorded" repeated down an
                // optional column reads as a gap where there is none.
                $row[] = $type === '' ? '' : $this->planTypeLabel($type);
            }

            $row[] = $this->cell((string) $policy->policy_number);
            $row[] = $this->cell($this->date($policy->expiry_date));
            $row[] = $this->cell($this->statusLabel($status), $this->statusTone($status));

            $rows[] = $row;
        }

        return $this->table(
            __('passport.sections.additional_cover'),
            $columns,
            $rows,
            'blue',
            'credit-card'
        );
    }

    /**
     * What the cover actually covers — the sheet's benefits block, reduced to
     * the one column this schema can defend.
     *
     * Built ONLY from policies that are in force. The services listed against a
     * plan whose policy expired last year are not entitlements, and a table
     * that mixed them with live ones would be read as one list.
     *
     * @param  list<PatientInsurancePolicy>  $policies
     */
    private function benefits(array $policies): ?array
    {
        $rows = [];
        $seen = [];

        foreach ($policies as $policy) {
            if (! $this->inForce($policy)) {
                continue;
            }

            $planName = trim((string) ($policy->plan?->name ?? ''));

            foreach ($this->coveredServices($policy->plan?->covered_services) as $service) {
                $key = strtolower($service . '|' . $planName);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $rows[]     = [$this->cell($service), $this->cell($planName)];
            }
        }

        if ($rows === []) {
            return null;
        }

        return $this->table(
            __('passport.sections.cover_benefits'),
            [__('passport.columns.cover'), __('passport.columns.plan')],
            $rows,
            'teal',
            'check-circle'
        );
    }

    // ── Reading the record ───────────────────────────────────────────────

    /**
     * The status this policy actually has today.
     *
     * See the class docblock: the stored word is trusted to END cover, never to
     * keep it alive past its own expiry date.
     */
    private function effectiveStatus(PatientInsurancePolicy $policy): string
    {
        $recorded = $this->word($policy->status);

        if (in_array($recorded, self::ENDED, true)) {
            return $recorded;
        }

        if ($this->hasLapsed($policy)) {
            return 'expired';
        }

        if ($this->notYetStarted($policy)) {
            return 'pending';
        }

        return $recorded !== '' ? $recorded : 'pending';
    }

    /** Cover that pays for care today: not ended, not lapsed, already started. */
    private function inForce(PatientInsurancePolicy $policy): bool
    {
        return $this->effectiveStatus($policy) === 'active';
    }

    /**
     * Past its expiry date. A policy expiring TODAY is still cover today, so
     * the comparison is against the start of today and not against now.
     */
    private function hasLapsed(PatientInsurancePolicy $policy): bool
    {
        $expiry = $this->day($policy->expiry_date);

        return $expiry !== null && $expiry->lt(Carbon::today());
    }

    private function notYetStarted(PatientInsurancePolicy $policy): bool
    {
        $effective = $this->day($policy->effective_date);

        return $effective !== null && $effective->gt(Carbon::today());
    }

    /**
     * The services a plan records itself as covering.
     *
     * `insurance_plans.covered_services` is a text column holding a JSON list
     * on every row this deployment writes, but it is not cast and older rows
     * could hold a plain comma-separated line. Both are read; anything else is
     * left alone rather than guessed at.
     *
     * @return list<string>
     */
    private function coveredServices($raw): array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        $values = match (true) {
            is_array($decoded)  => $decoded,
            is_string($decoded) => [$decoded],
            default             => explode(',', $raw),
        };

        $services = [];

        foreach ($values as $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $service = trim((string) $value);

            if ($service !== '') {
                $services[] = $service;
            }
        }

        return $services;
    }

    // ── Ranking ──────────────────────────────────────────────────────────

    /**
     * The policies in billing order — see the class docblock for why the order
     * is derived rather than read off a column that does not exist.
     *
     * @param  Collection<int,PatientInsurancePolicy>  $policies
     * @return list<PatientInsurancePolicy>
     */
    private function ranked(Collection $policies): array
    {
        $ordered = $policies->all();

        usort($ordered, fn (PatientInsurancePolicy $a, PatientInsurancePolicy $b) => $this->rank($a) <=> $this->rank($b));

        return array_values($ordered);
    }

    /** @return array{0:int,1:int,2:int} */
    private function rank(PatientInsurancePolicy $policy): array
    {
        $type = $this->word($policy->plan?->plan_type);

        return [
            $this->inForce($policy) ? 0 : 1,
            self::SCHEME_ORDER[$type] ?? count(self::SCHEME_ORDER),
            // Most recently effective first, so a renewal outranks the policy
            // it replaced.
            -$this->startedAt($policy),
        ];
    }

    private function startedAt(PatientInsurancePolicy $policy): int
    {
        return $this->day($policy->effective_date)?->getTimestamp() ?? 0;
    }

    // ── Small readings ───────────────────────────────────────────────────

    /** A date value as a day, or null when it is missing or unparseable. */
    private function day($value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * A plan type in words somebody at a cashier's window recognises.
     *
     * `insurance_plans.plan_type` holds a coded token — nhia, mutual, employer,
     * private — and the shared status tidier turned `nhia` into "Nhia", a word
     * that names nothing in either language. Those four are translated properly.
     * A token this schema has not met falls back to the tidier rather than being
     * dropped: an untranslated type is still a fact the record holds.
     */
    private function planTypeLabel(string $type): string
    {
        $key = 'passport.plan_type_' . $type;

        return Lang::has($key) ? __($key) : $this->statusLabel($type);
    }

    /** A recorded word, lowered and trimmed, for comparison against a list. */
    private function word($value): string
    {
        return strtolower(trim((string) $value));
    }
}
