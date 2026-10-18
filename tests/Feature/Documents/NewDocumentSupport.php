<?php

namespace Tests\Feature\Documents;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;

/**
 * Shared fixtures for the missing-documents tests (docs/specs/documents/20-33).
 *
 * dompdf subsets and compresses fonts, so the PDF bytes cannot be searched
 * for text. The view data handed to dompdf is captured instead and the same
 * view is rendered to HTML, which is exactly what dompdf laid out.
 */
trait NewDocumentSupport
{
    /** @var array<string, array<string, mixed>> */
    private array $captured = [];

    protected function seedBasics(): void
    {
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    protected function capture(string $view): void
    {
        View::composer($view, function ($v) use ($view) {
            $this->captured[$view] = $v->getData();
        });
    }

    /** The HTML dompdf received for $view during the last request. */
    protected function renderedHtml(string $view): string
    {
        $this->assertArrayHasKey($view, $this->captured, "{$view} was not rendered");

        return html_entity_decode(strip_tags(view($view, $this->captured[$view])->render()));
    }

    protected function assertPdfResponse(TestResponse $response): string
    {
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('cache-control'));
        $body = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF-', $body);

        return $body;
    }

    /** @return array{0: User, 1: Facility} */
    protected function staff(string $roleName, ?Facility $facility = null, string $type = 'hospital'): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => $type, 'is_demo' => (bool) config('demo.enabled')]);

        $user = User::factory()->create([
            'role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active',
            'is_demo' => (bool) config('demo.enabled'),
        ]);

        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    protected function at(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id, 'mfa.verified' => true]);
    }

    protected function patient(array $attrs = []): Patient
    {
        return Patient::factory()->create($attrs + [
            'is_demo'             => (bool) config('demo.enabled'),
            'privacy_preferences' => ['require_consent_for_full_record' => false],
        ]);
    }
}
