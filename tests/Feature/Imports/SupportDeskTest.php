<?php

namespace Tests\Feature\Imports;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Staff Help desk + platform support desk (routes/imports_tasks.php). */
class SupportDeskTest extends TestCase
{
    use ImportsGroupBHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_staff_raise_a_ticket_admin_replies_staff_sees_the_reply(): void
    {
        [$pharmacist, $facility] = $this->staffAt(null, 'pharmacist');

        $this->actingAtFacility($pharmacist, $facility)->get(route('portals.staff.support'))->assertOk();

        $this->actingAtFacility($pharmacist, $facility)->post(route('portals.staff.support.store'), [
            'subject' => 'Printer will not print labels',
            'category' => 'technical',
            'priority' => 'high',
            'description' => 'Since this morning the label printer is offline. Call +237 699 000 000.',
        ])->assertRedirect();

        $ticket = SupportTicket::where('subject', 'Printer will not print labels')->firstOrFail();
        $this->assertSame((string) $facility->id, (string) $ticket->facility_id);
        $this->assertSame((string) $pharmacist->id, (string) $ticket->requester_id);
        $this->assertStringContainsString('[REDACTED_PHONE]', $ticket->description_redacted);

        $admin = $this->platformAdmin();
        $this->actingAsAdmin($admin)->get(route('portals.admin.support.index'))->assertOk()->assertSee('Printer will not print labels');
        $this->actingAsAdmin($admin)->get(route('portals.admin.support.show', $ticket->id))->assertOk();

        $this->actingAsAdmin($admin)->post(route('portals.admin.support.reply', $ticket->id), ['body' => 'Please restart the print spooler.'])->assertRedirect();
        $this->actingAsAdmin($admin)->post(route('portals.admin.support.reply', $ticket->id), ['body' => 'Internal: known driver bug', 'internal' => '1'])->assertRedirect();
        $this->actingAsAdmin($admin)->post(route('portals.admin.support.assign', $ticket->id), ['assignee_id' => $admin->id])->assertRedirect();
        $this->assertSame('assigned', $ticket->fresh()->status);

        $this->actingAtFacility($pharmacist, $facility)
            ->get(route('portals.staff.support.show', $ticket->id))
            ->assertOk()
            ->assertSee('Please restart the print spooler.')
            ->assertDontSee('Internal: known driver bug');

        $this->actingAsAdmin($admin)->post(route('portals.admin.support.close', $ticket->id), ['resolution_note' => 'Fixed'])->assertRedirect();
        $this->assertSame('resolved', $ticket->fresh()->status);
        $this->assertSame(2, TicketMessage::where('support_ticket_id', $ticket->id)->count());
    }

    public function test_another_facilitys_ticket_is_404(): void
    {
        [$nurse, $facility] = $this->staffAt();
        [$other, $otherFacility] = $this->staffAt();

        $this->actingAtFacility($other, $otherFacility)->post(route('portals.staff.support.store'), [
            'subject' => 'Foreign ticket',
            'category' => 'account',
            'priority' => 'normal',
            'description' => 'Cannot log in on the ward tablet.',
        ]);
        $ticket = SupportTicket::where('subject', 'Foreign ticket')->firstOrFail();

        $this->actingAtFacility($nurse, $facility)->get(route('portals.staff.support.show', $ticket->id))->assertNotFound();
        $this->actingAtFacility($nurse, $facility)->post(route('portals.staff.support.reply', $ticket->id), ['body' => 'hello'])->assertNotFound();
        $this->actingAtFacility($nurse, $facility)->post(route('portals.staff.support.close', $ticket->id))->assertNotFound();
        $this->actingAtFacility($nurse, $facility)->get(route('portals.staff.support'))->assertOk()->assertDontSee('Foreign ticket');
        $this->assertSame('open', $ticket->fresh()->status);
    }

    public function test_admin_support_is_403_for_facility_admin(): void
    {
        [$admin, $facility] = $this->staffAt(null, 'facility_admin');

        $this->actingAtFacility($admin, $facility)->get(route('portals.admin.support.index'))->assertForbidden();
        $this->actingAtFacility($admin, $facility)->get(route('portals.staff.support'))->assertOk();
    }
}
