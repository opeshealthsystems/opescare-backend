<?php

namespace App\Http\Controllers;

use App\Services\Compliance\AccountDeletionService;
use Illuminate\View\View;

/**
 * The publicly reachable "how to delete your account" page.
 *
 * Google Play requires the deletion route to be documented at a URL a reviewer
 * — or a former user who has already uninstalled the app — can open without
 * signing in or installing anything. Apple accepts the in-app flow alone, but
 * the page costs one route and removes a whole class of review back-and-forth.
 *
 * Deliberately NOT a web deletion form. Deleting a medical account needs the
 * account holder authenticated, and a public form that accepts a phone number
 * is an enumeration oracle plus a denial-of-service against other people's
 * records. The page explains the in-app route and the assisted route, and the
 * numbers on it are read from the same service the app uses, so the page and
 * the app can never quote different grace periods.
 */
class AccountDeletionPageController extends Controller
{
    public function __construct(
        private readonly AccountDeletionService $deletion,
    ) {
    }

    public function show(): View
    {
        return view('public.account-deletion', [
            'graceDays'     => AccountDeletionService::GRACE_PERIOD_DAYS,
            // The default-country retention period, sourced from the same
            // CountryPolicyService the executor stamps onto each request —
            // never a number typed into the template.
            'retentionYears' => $this->deletion->retentionYears(
                config('opescare.health_id.default_country', 'CM')
            ),
            'contactEmail'  => 'privacy@opeshealthsystems.com',
            'lastUpdated'   => '5 September 2026',
        ]);
    }
}
