<?php

/*
|--------------------------------------------------------------------------
| Subscription pricing — the screen that owns what the platform charges
|--------------------------------------------------------------------------
|
| Own file, same as mpi.php and caremap_claim.php: the EN/FR pair stays
| readable and `php scripts/i18n-audit.php` parity is obvious at a glance.
|
*/

return [
    'nav'      => 'Plans & pricing',
    'title'    => 'Plans & pricing',
    'subtitle' => 'What it costs to be on OpesCare. :count plans in the catalogue.',

    // The one thing a reader must not get wrong.
    'francs_note' => 'Every amount here is a whole number of francs. XAF has no centime, so 50 means fifty francs — never fifty hundredths.',

    'col_plan'        => 'Plan',
    'col_audience'    => 'Sold to',
    'col_daily'       => 'Per day',
    'col_monthly'     => 'Per month',
    'col_annual'      => 'Per year',
    'col_status'      => 'Status',
    'col_last_change' => 'Last changed',
    'col_actions'     => 'Actions',

    'interval_daily'   => 'daily',
    'interval_monthly' => 'monthly',
    'interval_annual'  => 'annual',

    'default_interval' => 'billed :interval by default',

    'audience_patient'   => 'Patients',
    'audience_household' => 'Households',
    'audience_facility'  => 'Facilities',
    'audience_insurer'   => 'Insurers',
    'audience_lab'       => 'Laboratories',
    'audience_pharmacy'  => 'Pharmacies',
    'audience_healthorg' => 'Health organizations',
    'audience_developer' => 'Developers',

    'not_sold'      => 'Not sold this way',
    'not_sold_hint' => 'This plan is not billed on this interval. The pricing screen sets prices; it does not change which intervals a plan is sold in.',
    'blank_means'   => 'Leave blank to stop selling this plan on that interval.',

    'badge_active'   => 'Active',
    'badge_inactive' => 'Inactive',

    'btn_edit'       => 'Edit prices',
    'btn_save'       => 'Save prices',
    'btn_activate'   => 'Activate',
    'btn_deactivate' => 'Deactivate',
    'btn_audit'      => 'Full change history',

    'last_change_never' => 'Never changed here',
    'last_change_by'    => ':name · :when',
    'actor_unknown'     => 'Unknown administrator',

    'empty_heading' => 'No plans in the catalogue',
    'empty_body'    => 'Nothing is priced yet. Seed the platform fee catalogue before pricing anything here.',

    'flash_prices_updated' => 'Prices updated for :plan.',
    'flash_prices_same'    => 'Nothing changed for :plan — the prices submitted were the ones already stored.',
    'flash_activated'      => ':plan is active and can be subscribed to.',
    'flash_deactivated'    => ':plan is inactive and can no longer be subscribed to.',

    'err_whole_francs'       => 'The :interval price for :plan must be a whole number of francs — no decimals, no separators. Leave it blank to stop selling that interval.',
    'err_zero_price'         => 'A :interval price of 0 cannot be stored for :plan. Money — the class that actually charges — only accepts a positive amount, so a zero-franc price would break every screen that reads this plan. Leave the field blank to stop selling that interval, or deactivate the plan.',
    'err_too_large'          => 'The :interval price for :plan is above the maximum a single charge may carry (:max).',
    'err_interval_not_offered' => ':plan is not sold on a :interval basis, so it cannot be given a :interval price here.',
    'err_last_interval'      => ':plan must keep at least one price. Deactivate the plan instead of clearing every interval.',
];
