<?php

namespace Tests\Feature\Portal;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The login page's one-click demo panel must offer every demo account the
 * seeders create — a reviewer should never have to type a demo email — and
 * every button it offers must be an account that actually signs in.
 */
class LoginDemoPanelTest extends TestCase
{
    use RefreshDatabase;

    /** Every one-click account the Finder demo seeds, by tab. */
    private const DEMO_EMAILS = [
        'demo.doctor@opescare.test',
        'demo.nurse@opescare.test',
        'demo.pharmacist@opescare.test',
        'demo.receptionist@opescare.test',
        'demo.facility.admin@opescare.test',
        'demo.facility.ceo@opescare.test',
        'demo.patient@opescare.test',
        'demo.reviewer@opescare.test',
        'demo.admin@opescare.test',
        'demo.developer@opescare.test',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('demo.enabled', true);
        Config::set('demo.public_enabled', true);
    }

    public function test_login_page_offers_one_click_access_for_every_seeded_demo_account(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        foreach (self::DEMO_EMAILS as $email) {
            $this->assertStringContainsString('data-demo-email="' . $email . '"', $html, "No one-click button for {$email}.");
        }

        // The two patients are told apart by sex and identity state, and the
        // labels are the translated text — never a raw lang key. Literal
        // strings on purpose: comparing __() to itself would pass on a typo.
        $this->assertStringContainsString('Female patient', $html);
        $this->assertStringContainsString('Male patient', $html);
        $this->assertStringContainsString('<small>Doctor</small>', $html);
        $this->assertStringContainsString('<small>Nurse</small>', $html);
        $this->assertStringNotContainsString('public.demo.', $html, 'A demo label rendered as a raw translation key.');
    }

    public function test_the_panel_is_absent_outside_demo_mode(): void
    {
        Config::set('demo.enabled', false);

        $this->get('/login')->assertOk()->assertDontSee('data-demo-email', false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('clinicalStaff')]
    public function test_one_click_login_signs_the_demo_clinician_in(string $email, string $role): void
    {
        $user = User::factory()->create([
            'email'   => $email,
            'is_demo' => true,
            'role_id' => Role::firstOrCreate(['name' => $role], ['display_name' => ucfirst($role)])->id,
        ]);

        $this->post('/demo-access/login-as', ['role' => $role, 'email' => $email, 'mode' => 'public'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public static function clinicalStaff(): array
    {
        return [
            'doctor' => ['demo.doctor@opescare.test', 'doctor'],
            'nurse'  => ['demo.nurse@opescare.test', 'nurse'],
        ];
    }
}
