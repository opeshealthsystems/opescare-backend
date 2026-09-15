<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\SupportTicket;
use App\Modules\Support\Services\SupportService;
use App\Services\Portal\DeskFacilityContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * StaffSupportController — the staff "Help" desk: my tickets, a new ticket,
 * the ticket thread, and the knowledge base (read-only).
 *
 * Imported from the platform's StaffPortalController support* methods
 * (support, supportStore, supportReply, supportClose) into its own controller
 * so the Finder's StaffPortalController is untouched
 * (docs/PLATFORM_IMPORTS.md group B).
 *
 * Finder adaptations:
 *  - facility ONLY from DeskFacilityContext; a ticket at another facility, or
 *    raised by someone else, is a 404;
 *  - staff see their OWN tickets and only non-internal messages;
 *  - supportEscalate / supportAssign stay on the admin desk (/admin/support);
 *  - nothing is deleted: "close" resolves the ticket via SupportService.
 */
class StaffSupportController extends Controller
{
    public const CATEGORIES = ['technical', 'account', 'data', 'billing', 'other'];

    public function __construct(private readonly SupportService $svc) {}

    private function facilityId(): string
    {
        $facilityId = app(DeskFacilityContext::class)->resolve();
        abort_unless($facilityId, 403);

        return $facilityId;
    }

    private function mine(): Builder
    {
        return SupportTicket::query()
            ->where('facility_id', $this->facilityId())
            ->where('requester_type', 'staff')
            ->where('requester_id', Auth::id());
    }

    private function ticket(string $id): SupportTicket
    {
        abort_unless(Str::isUuid($id), 404);
        $ticket = $this->mine()->whereKey($id)->first();
        abort_unless($ticket, 404);

        return $ticket;
    }

    public function index(Request $request): View
    {
        $query = $this->mine()->withCount('messages')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', (string) $request->input('priority'));
        }

        $articles = KnowledgeBaseArticle::query()
            ->where('status', 'published')
            ->whereIn('audience', ['all', 'staff'])
            ->orderByDesc('view_count')
            ->limit(10)
            ->get(['id', 'title', 'slug']);

        return view('portals.staff.support.index', [
            'tickets'    => $query->limit(100)->get(),
            'articles'   => $articles,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'subject'     => 'required|string|max:200',
            'category'    => 'required|in:'.implode(',', self::CATEGORIES),
            'priority'    => 'required|in:normal,high,urgent,critical',
            'description' => 'required|string|min:10|max:2000',
        ]);

        $ticket = $this->svc->createTicket([
            'requester_type' => 'staff',
            'requester_id'   => (string) Auth::id(),
            'facility_id'    => $this->facilityId(),
            'category'       => $request->input('category'),
            'priority'       => $request->input('priority'),
            'subject'        => $request->input('subject'),
            'description'    => $request->input('description'),
        ], (string) Auth::id());

        return redirect()->route('portals.staff.support.show', $ticket->id)
            ->with('success', __('support.ticket_created'));
    }

    public function show(string $id): View
    {
        $ticket = $this->ticket($id);

        return view('portals.staff.support.show', [
            'ticket'   => $ticket,
            'messages' => $ticket->messages()->where('internal', false)->orderBy('created_at')->get(),
        ]);
    }

    public function reply(Request $request, string $id): RedirectResponse
    {
        $request->validate(['body' => 'required|string|min:2|max:2000']);
        $ticket = $this->ticket($id);

        $this->svc->addMessage($ticket, [
            'sender_type' => 'staff',
            'sender_id'   => (string) Auth::id(),
            'body'        => $request->input('body'),
            'internal'    => false,
        ], (string) Auth::id());

        return back()->with('success', __('support.reply_sent'));
    }

    public function close(Request $request, string $id): RedirectResponse
    {
        $request->validate(['resolution_note' => 'nullable|string|max:1000']);
        $ticket = $this->ticket($id);
        $this->svc->resolveTicket($ticket, (string) Auth::id(), $request->input('resolution_note'));

        return back()->with('success', __('support.ticket_closed'));
    }

    public function article(string $slug): View
    {
        $article = KnowledgeBaseArticle::query()
            ->where('status', 'published')
            ->whereIn('audience', ['all', 'staff'])
            ->where('slug', $slug)
            ->firstOrFail();

        $article = $this->svc->recordArticleView($article, (string) Auth::id());

        return view('portals.staff.support.article', ['article' => $article]);
    }
}
