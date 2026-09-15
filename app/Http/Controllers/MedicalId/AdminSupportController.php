<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequirePlatformAdmin;
use App\Models\Facility;
use App\Models\SupportTicket;
use App\Models\User;
use App\Modules\Support\Services\SupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * AdminSupportController — the platform support desk: every ticket, assign,
 * reply, close, reopen.
 *
 * Imported from the platform (docs/PLATFORM_IMPORTS.md group B). Routes live
 * under /admin/* so RequirePlatformAdmin holds.
 *
 * Finder adaptations: assign/close/reply go through SupportService (audited,
 * assignment history kept, PII redacted) rather than raw updates; a reply
 * action was added (the platform desk had none); the platform's destroy()
 * is NOT imported — nothing is hard-deleted, a ticket is resolved. The search
 * no longer LIKE-matches the uuid primary key (a Postgres type error).
 */
class AdminSupportController extends Controller
{
    public function __construct(private readonly SupportService $svc) {}

    public function index(Request $request): View
    {
        $query = SupportTicket::query()->withCount('messages');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', (string) $request->input('priority'));
        }
        if ($request->filled('search')) {
            $query->where('subject', 'ilike', '%'.addcslashes((string) $request->input('search'), '%_\\').'%');
        }

        $stats = [
            'total'    => SupportTicket::count(),
            'open'     => SupportTicket::whereIn('status', ['open', 'assigned', 'escalated'])->count(),
            'resolved' => SupportTicket::where('status', 'resolved')->count(),
        ];

        $tickets = $query->latest()->paginate(25)->withQueryString();

        return view('portals.admin.support.index', [
            'tickets'    => $tickets,
            'stats'      => $stats,
            'facilities' => Facility::whereIn('id', $tickets->getCollection()->pluck('facility_id')->filter()->all())->pluck('name', 'id'),
        ]);
    }

    private function find(string $id): SupportTicket
    {
        abort_unless(Str::isUuid($id), 404);

        return SupportTicket::findOrFail($id);
    }

    public function show(string $id): View
    {
        $ticket = $this->find($id);
        $messages = $ticket->messages()->orderBy('created_at')->get();

        $assignees = User::with('role')->whereNotNull('role_id')->orderBy('name')->limit(500)->get()
            ->filter(fn (User $u) => RequirePlatformAdmin::isPlatformTier($u))
            ->values();

        return view('portals.admin.support.show', [
            'ticket'    => $ticket,
            'messages'  => $messages,
            'people'    => User::whereIn('id', $messages->pluck('sender_id')->push($ticket->requester_id)->push($ticket->assigned_to)->filter()->all())->pluck('name', 'id'),
            'facility'  => $ticket->facility_id ? Facility::find($ticket->facility_id) : null,
            'assignees' => $assignees,
        ]);
    }

    public function assign(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate(['assignee_id' => 'required|uuid|exists:users,id']);
        $this->svc->assignTicket($this->find($id), $validated['assignee_id'], (string) Auth::id());

        return back()->with('success', __('support.ticket_assigned'));
    }

    public function reply(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'body'     => 'required|string|min:2|max:2000',
            'internal' => 'nullable|boolean',
        ]);

        $this->svc->addMessage($this->find($id), [
            'sender_type' => 'support',
            'sender_id'   => (string) Auth::id(),
            'body'        => $validated['body'],
            'internal'    => $request->boolean('internal'),
        ], (string) Auth::id());

        return back()->with('success', __('support.reply_sent'));
    }

    public function close(Request $request, string $id): RedirectResponse
    {
        $request->validate(['resolution_note' => 'nullable|string|max:1000']);
        $this->svc->resolveTicket($this->find($id), (string) Auth::id(), $request->input('resolution_note'));

        return back()->with('success', __('support.ticket_closed'));
    }

    public function reopen(string $id): RedirectResponse
    {
        $this->find($id)->update(['status' => 'open', 'resolved_at' => null]);

        return back()->with('success', __('support.ticket_reopened'));
    }
}
