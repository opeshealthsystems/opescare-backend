<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * What it costs to be on the platform.
 *
 * Every figure here is the OWNER'S, set on 2026-09-07, and every one of them is
 * editable in the admin panel afterwards — this seeder establishes the plans,
 * it does not own the prices. It is written to create a plan that is missing
 * and to leave an existing one alone, so re-running it after someone has
 * adjusted a price does not quietly undo them.
 *
 * ── Amounts are whole francs ───────────────────────────────────────────────
 * XAF has an ISO 4217 exponent of 0, so `*_minor` columns hold francs, not
 * hundredths. 50 here is fifty francs. See App\Services\Payments\Domain\Money,
 * which is the class that actually charges, and the migration that renamed
 * these columns away from "kobo" precisely so this cannot be misread.
 *
 * ── Only a patient chooses their interval ──────────────────────────────────
 * A patient may pay by day, by month or by year; a facility is billed yearly.
 * That is expressed by which price columns are non-null, so the choice a
 * subscriber is offered comes from the plan itself rather than from a rule
 * repeated in every screen that sells one.
 *
 * The monthly and yearly patient prices are exact multiples of the daily rate
 * (30 and 365 days). No discount for paying up front is invented here — if one
 * is wanted, it is a price change in the admin panel, not a formula in code.
 */
class PlatformFeeSeeder extends Seeder
{
    /**
     * slug => [name, audience, default interval, daily, monthly, annual]
     *
     * A null price means the plan is not sold for that interval at all.
     */
    private const PLANS = [
        'patient' => [
            'name'     => 'Patient',
            'audience' => 'patient',
            'cycle'    => 'daily',
            'daily'    => 50,
            'monthly'  => 1_500,      // 30 × 50
            'annual'   => 18_250,     // 365 × 50
            'blurb'    => 'Your Health ID, your record, and everything the app does with it.',
        ],
        'pharmacy' => [
            'name'     => 'Pharmacy',
            'audience' => 'facility',
            'cycle'    => 'annual',
            'daily'    => null,
            'monthly'  => null,
            'annual'   => 25_000,
            'blurb'    => 'Dispensing desk, prescription lookup and stock visibility.',
        ],
        'health_center' => [
            'name'     => 'Health centre',
            'audience' => 'facility',
            'cycle'    => 'annual',
            'daily'    => null,
            'monthly'  => null,
            'annual'   => 50_000,
            'blurb'    => 'Front desk, clinical records and the lab and pharmacy desks.',
        ],
        'clinic' => [
            'name'     => 'Clinic',
            'audience' => 'facility',
            'cycle'    => 'annual',
            'daily'    => null,
            'monthly'  => null,
            'annual'   => 100_000,
            'blurb'    => 'Everything a health centre has, for a larger clinical team.',
        ],
        'hospital' => [
            'name'     => 'Hospital',
            'audience' => 'facility',
            'cycle'    => 'annual',
            'daily'    => null,
            'monthly'  => null,
            'annual'   => 200_000,
            'blurb'    => 'Wards, admissions and the full clinical record across departments.',
        ],
        'laboratory' => [
            'name'     => 'Laboratory',
            'audience' => 'facility',
            'cycle'    => 'annual',
            'daily'    => null,
            'monthly'  => null,
            'annual'   => 40_000,
            'blurb'    => 'Order queue, result entry and release into the patient record.',
        ],
        'health_organization' => [
            'name'     => 'Health organisation',
            'audience' => 'facility',
            'cycle'    => 'annual',
            'daily'    => null,
            'monthly'  => null,
            'annual'   => 300_000,
            'blurb'    => 'Group oversight across the facilities you own, each on its own plan.',
        ],
        'insurance' => [
            'name'     => 'Insurer',
            'audience' => 'facility',
            'cycle'    => 'annual',
            'daily'    => null,
            'monthly'  => null,
            'annual'   => 500_000,
            'blurb'    => 'Coverage and claims across every facility your members attend.',
        ],
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::PLANS as $slug => $plan) {
            $order++;

            $existing = SubscriptionPlan::where('slug', $slug)->first();

            if ($existing) {
                $this->command?->line(sprintf(
                    '  kept  %-14s %s (already priced — the admin panel owns it now)',
                    $slug,
                    $existing->priceFormatted()
                ));

                continue;
            }

            $created = SubscriptionPlan::create([
                'name'                 => $plan['name'],
                'slug'                 => $slug,
                'audience'             => $plan['audience'],
                'billing_cycle'        => $plan['cycle'],
                // `price_minor` is the legacy single-price column; it carries the
                // default interval's price so anything still reading it agrees
                // with what the subscriber is shown.
                'price_minor'          => $plan[$plan['cycle'] === 'annual' ? 'annual' : $plan['cycle']],
                'daily_price_minor'    => $plan['daily'],
                'monthly_price_minor'  => $plan['monthly'],
                'annual_price_minor'   => $plan['annual'],
                'currency'             => 'XAF',
                'description'          => $plan['blurb'],
                'is_active'            => true,
                'is_public'            => true,
                'trial_days'           => 0,
                'sort_order'           => $order,
            ]);

            $this->command?->info(sprintf(
                '  set   %-14s %s / %s   [%s]',
                $slug,
                $created->priceFormatted(),
                $created->defaultInterval(),
                implode(', ', $created->intervalsOffered())
            ));
        }

        $this->command?->comment(
            'Still unpriced on purpose: `developer` is already sold through api_plans '
            . '(Sandbox free / Growth 75 000 a month / Scale 350 000), and pricing it twice would '
            . 'give one customer two contradictory prices; `lite` and `health_org` are single demo '
            . 'rows whose meaning nobody has settled.'
        );
    }
}
