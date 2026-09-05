<?php

namespace Tests\Feature\Admin;

use App\Models\MpiCandidate;
use App\Models\PatientMerge;
use App\Models\User;
use App\Modules\MasterPatientIndex\Services\MasterPatientIndexService;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Mpi\MpiTestCase;

/** The desk: reachable, gated, and every action lands. */
class IdentityReviewDeskTest extends MpiTestCase
{
    public function test_every_action_has_a_route(): void
    {
        $named = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter()->all();

        foreach ([
            'portals.admin.mpi.index',
            'portals.admin.mpi.detect',
            'portals.admin.mpi.candidates.merge',
            'portals.admin.mpi.candidates.reject',
            'portals.admin.mpi.merges.unmerge',
        ] as $name) {
            $this->assertContains($name, $named, "route {$name} is not registered");
        }
    }

    public function test_the_desk_is_platform_gated(): void
    {
        $this->platformAdmin();
        $outsider = User::factory()->create(['status' => 'active']);

        $this->actingAs($outsider)->get(route('portals.admin.mpi.index'))->assertForbidden();
        $this->actingAs($outsider)->post(route('portals.admin.mpi.detect'))->assertForbidden();
    }

    public function test_the_desk_lists_a_pair_side_by_side_with_its_evidence(): void
    {
        $admin = $this->platformAdmin();
        $a = $this->patient();
        $b = $this->patient();
        app(MasterPatientIndexService::class)->detectDuplicates();

        $response = $this->actingAs($admin)->get(route('portals.admin.mpi.index'));

        $response->assertOk();
        $response->assertSee($a->health_id);
        $response->assertSee($b->health_id);
        $response->assertSee(__('mpi.reason_name_match'));
        $response->assertSee(__('mpi.reason_dob_match'));
        $response->assertSee(__('mpi.btn_merge'));
        $response->assertSee(__('mpi.btn_reject'));
    }

    public function test_the_scan_button_queues_candidates(): void
    {
        $admin = $this->platformAdmin();
        $this->patient();
        $this->patient();

        $this->actingAs($admin)->post(route('portals.admin.mpi.detect'))
            ->assertRedirect(route('portals.admin.mpi.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, MpiCandidate::count());
    }

    public function test_merging_from_the_desk_needs_a_survivor_and_a_reason(): void
    {
        $admin = $this->platformAdmin();
        $a = $this->patient();
        $b = $this->patient();
        app(MasterPatientIndexService::class)->detectDuplicates();
        $c = MpiCandidate::firstOrFail();

        // No reason → refused at validation, nothing changes.
        $this->actingAs($admin)
            ->post(route('portals.admin.mpi.candidates.merge', $c->id), ['survivor_patient_id' => $a->id])
            ->assertSessionHasErrors('reason');
        $this->assertSame('pending_review', $c->fresh()->status);

        // With both → merged, reviewer recorded.
        $this->actingAs($admin)
            ->from(route('portals.admin.mpi.index'))
            ->post(route('portals.admin.mpi.candidates.merge', $c->id), [
                'survivor_patient_id' => $a->id,
                'reason'              => 'Patient confirmed both Health IDs are theirs.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $c->refresh();
        $this->assertSame('merged', $c->status);
        $this->assertSame((string) $admin->id, (string) $c->reviewed_by);
        $this->assertTrue($this->reload($b)->isMerged());
        $this->assertSame(1, PatientMerge::count());
    }

    public function test_rejecting_from_the_desk(): void
    {
        $admin = $this->platformAdmin();
        $this->patient();
        $this->patient();
        app(MasterPatientIndexService::class)->detectDuplicates();
        $c = MpiCandidate::firstOrFail();

        $this->actingAs($admin)
            ->post(route('portals.admin.mpi.candidates.reject', $c->id), ['reason' => 'Twins.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $c->refresh();
        $this->assertSame('rejected', $c->status);
        $this->assertSame('Twins.', $c->review_reason);
    }

    public function test_unmerging_from_the_desk(): void
    {
        $admin = $this->platformAdmin();
        $a = $this->patient();
        $b = $this->patient();
        app(MasterPatientIndexService::class)->detectDuplicates();
        $c = MpiCandidate::firstOrFail();
        $merge = app(MasterPatientIndexService::class)->confirmMatch($c, $admin->id, (string) $a->id, 'same person');

        $this->actingAs($admin)
            ->post(route('portals.admin.mpi.merges.unmerge', $merge->id), ['reason' => 'Reviewer misread the year of birth.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(PatientMerge::STATUS_UNMERGED, $merge->fresh()->status);
        $this->assertFalse($this->reload($b)->isMerged());
        $this->assertSame('pending_review', $c->fresh()->status);
    }

    public function test_a_decided_pair_cannot_be_decided_again(): void
    {
        $admin = $this->platformAdmin();
        $a = $this->patient();
        $this->patient();
        app(MasterPatientIndexService::class)->detectDuplicates();
        $c = MpiCandidate::firstOrFail();

        $this->actingAs($admin)->post(route('portals.admin.mpi.candidates.reject', $c->id));

        $this->actingAs($admin)
            ->post(route('portals.admin.mpi.candidates.merge', $c->id), ['survivor_patient_id' => $a->id, 'reason' => 'changed my mind about it'])
            ->assertSessionHas('error', __('mpi.error_not_pending'));

        $this->assertSame('rejected', $c->fresh()->status);
    }
}
