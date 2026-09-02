<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeBaseArticleResource;
use App\Http\Resources\TicketMessageResource;
use App\Models\KnowledgeBaseArticle;
use App\Models\SupportTicket;
use App\Modules\Support\Services\KnowledgeBaseService;
use App\Modules\Support\Services\SupportService;
use App\Modules\Support\Services\TicketAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $tickets = SupportTicket::query()
            ->where('facility_id', $facilityId)
            ->when($request->query('requester_type'), fn ($query, $type) => $query->where('requester_type', $type))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))

            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $tickets->map(fn (SupportTicket $ticket) => $this->serializeTicket($ticket))->values()]);
    }

    public function store(Request $request, SupportService $service): JsonResponse
    {
        $validated = $request->validate([
            'requester_type' => ['required', 'in:patient,facility,developer,staff'],
            'requester_id' => ['nullable', 'uuid'],
            'facility_id' => ['nullable', 'uuid'],
            'category' => ['required', 'string', 'max:80'],
            'priority' => ['nullable', 'in:low,normal,high,urgent,critical'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        $ticket = $service->createTicket($validated, $validated['actor_id'] ?? null);

        return response()->json(['data' => $this->serializeTicket($ticket)], 201);
    }

    public function addMessage(SupportTicket $ticket, Request $request, SupportService $service): JsonResponse
    {
        $validated = $request->validate([
            'sender_type' => ['required', 'in:patient,facility,developer,staff,agent'],
            'sender_id' => ['nullable', 'uuid'],
            'body' => ['required', 'string', 'max:5000'],
            'internal' => ['nullable', 'boolean'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        $message = $service->addMessage($ticket, $validated, $validated['actor_id'] ?? null);

        return response()->json(['data' => TicketMessageResource::make($message)], 201);
    }

    public function assign(SupportTicket $ticket, Request $request, SupportService $service): JsonResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['required', 'uuid'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        return response()->json([
            'data' => $this->serializeTicket($service->assignTicket($ticket, $validated['assigned_to'], $validated['actor_id'] ?? null)),
        ]);
    }

    public function escalate(SupportTicket $ticket, Request $request, SupportService $service): JsonResponse
    {
        $validated = $request->validate([
            'level' => ['required', 'string', 'max:80'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        return response()->json([
            'data' => $this->serializeTicket($service->escalateTicket($ticket, $validated['level'], $validated['actor_id'] ?? null, $validated['reason'] ?? null)),
        ]);
    }

    public function resolve(SupportTicket $ticket, Request $request, SupportService $service): JsonResponse
    {
        $validated = $request->validate([
            'resolution_note' => ['required', 'string', 'max:2000'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        return response()->json([
            'data' => $this->serializeTicket($service->resolveTicket($ticket, $validated['actor_id'] ?? null, $validated['resolution_note'])),
        ]);
    }

    public function createIncident(SupportTicket $ticket, Request $request, SupportService $service): JsonResponse
    {
        $validated = $request->validate([
            'severity' => ['nullable', 'in:low,medium,high,critical'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        $incident = $service->createIncidentFromTicket($ticket, $validated['actor_id'] ?? null, $validated['severity'] ?? 'medium');

        return response()->json(['data' => $incident], 201);
    }

    // ── Knowledge Base (KnowledgeBaseService) ────────────────────────────

    /**
     * Browse and search knowledge base articles.
     * ?q=search term  OR  ?category=category_slug
     * ?role=staff|patient — role-scoped results (optional)
     */
    public function listArticles(Request $request, KnowledgeBaseService $kb): JsonResponse
    {
        $q        = $request->query('q');
        $category = $request->query('category');
        $role     = $request->query('role');

        if ($q) {
            $articles = $kb->search($q, $role);
        } elseif ($category) {
            $articles = $kb->getByCategory($category);
        } else {
            // No filter — return all published (default browse)
            $articles = KnowledgeBaseArticle::where('is_published', true)
                ->orderByDesc('published_at')
                ->get();
        }

        return response()->json([
            'count' => $articles->count(),
            'data'  => KnowledgeBaseArticleResource::collection($articles),
        ]);
    }

    /**
     * Publish an existing knowledge base article.
     * Body: { published_by: uuid }
     */
    public function publishKbArticle(KnowledgeBaseArticle $article, Request $request, KnowledgeBaseService $kb): JsonResponse
    {
        $validated = $request->validate([
            'published_by' => ['required', 'uuid'],
        ]);

        $updated = $kb->publish($article->id, $validated['published_by']);

        return response()->json(['message' => __('api.article_published'), 'data' => KnowledgeBaseArticleResource::make($updated)]);
    }

    /**
     * Unpublish a knowledge base article.
     */
    public function unpublishKbArticle(KnowledgeBaseArticle $article, KnowledgeBaseService $kb): JsonResponse
    {
        $updated = $kb->unpublish($article->id);

        return response()->json(['message' => __('api.article_unpublished'), 'data' => KnowledgeBaseArticleResource::make($updated)]);
    }

    public function publishArticle(Request $request, SupportService $service): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'audience' => ['nullable', 'string', 'max:80'],
            'body' => ['required', 'string'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        return response()->json([
            'data' => $service->publishKnowledgeBaseArticle($validated, $validated['actor_id'] ?? null),
        ], 201);
    }

    public function viewArticle(KnowledgeBaseArticle $article, Request $request, SupportService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->recordArticleView($article, $request->input('actor_id')),
        ]);
    }

    /**
     * List unassigned open support tickets.
     * Used by the support queue dashboard to surface tickets needing assignment.
     */
    public function unassigned(TicketAssignmentService $service): JsonResponse
    {
        $tickets = $service->getUnassignedTickets();

        return response()->json([
            'count' => $tickets->count(),
            'data'  => $tickets->map(fn ($t) => $this->serializeTicket($t))->values(),
        ]);
    }

    private function serializeTicket(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'requester_type' => $ticket->requester_type,
            'requester_id' => $ticket->requester_id,
            'facility_id' => $ticket->facility_id,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'subject' => $ticket->subject,
            'description_redacted' => $ticket->description_redacted,
            'pii_redaction_summary' => $ticket->pii_redaction_summary,
            'assigned_to' => $ticket->assigned_to,
            'escalation_level' => $ticket->escalation_level,
            'sla_due_at' => optional($ticket->sla_due_at)->toISOString(),
            'resolved_at' => optional($ticket->resolved_at)->toISOString(),
            'incident_id' => $ticket->incident_id,
        ];
    }
}
