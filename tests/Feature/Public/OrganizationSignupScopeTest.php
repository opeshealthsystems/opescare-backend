<?php

namespace Tests\Feature\Public;

use Tests\TestCase;

/**
 * Insurance is out of Finder's scope (CLAUDE.md "Deliberately absent"): the
 * organisation sign-up must not offer insurer services or insurance billing.
 */
class OrganizationSignupScopeTest extends TestCase
{
    public function test_the_organisation_signup_offers_no_insurance_services(): void
    {
        foreach (['en', 'fr'] as $locale) {
            app()->setLocale($locale);
            $html = $this->withSession(['locale' => $locale])->get('/signup/organization')->assertOk()->getContent();

            $this->assertStringNotContainsString(__('ui_sweep.register.svc_billing'), $html);
            $this->assertStringNotContainsString('value="billing"', $html);
            $this->assertStringNotContainsString('services-insurer', $html);
            $this->assertStringNotContainsString('value="claims"', $html);
        }
    }
}
