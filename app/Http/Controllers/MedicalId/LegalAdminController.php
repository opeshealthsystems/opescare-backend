<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AccountClosureRequest;
use App\Models\Facility;
use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Models\MinorTransitionReview;
use App\Models\PrivacyComplaint;
use App\Modules\Legal\Services\LegalDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin portal for legal document management and patient rights workflows.
 */
class LegalAdminController extends Controller
{
    public function __construct(private readonly LegalDocumentService $legalService) {}

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-admin';
    }

    // ------------------------------------------------------------------
    // Legal Documents
    // ------------------------------------------------------------------

    /**
     * List all legal documents (admin view).
     */
    public function index(): View
    {
        $documents = LegalDocument::with(['versions' => fn ($q) => $q->where('is_current', true)])
            ->orderBy('document_type')
            ->get();

        $stats = $this->legalService->getAdminStats();

        return view('portals.admin.legal.index', compact('documents', 'stats'));
    }

    /**
     * Show a specific legal document with all versions.
     */
    public function show(LegalDocument $document): View
    {
        $versions = $document->versions()
            ->orderByDesc('published_at')
            ->get();

        return view('portals.admin.legal.show', compact('document', 'versions'));
    }

    /**
     * Publish a new version of a legal document.
     */
    public function publishVersion(Request $request, LegalDocument $document): RedirectResponse
    {
        $data = $request->validate([
            'version'               => 'required|string|max:20',
            'content_html'          => 'required|string|min:50',
            'requires_reacceptance' => 'nullable|boolean',
            'change_summary'        => 'nullable|string|max:300',
            'effective_at'          => 'nullable|date',
        ]);

        // Check version uniqueness
        if ($document->versions()->where('version', $data['version'])->exists()) {
            return back()->withErrors(['version' => __('flash.document_version_exists')]);
        }

        $this->legalService->publishVersion(
            document:             $document,
            version:              $data['version'],
            contentHtml:          $data['content_html'],
            publishedBy:          $this->demoActorId(),
            requiresReacceptance: (bool) ($data['requires_reacceptance'] ?? false),
            changeSummary:        $data['change_summary'] ?? '',
            effectiveAt:          $data['effective_at'] ?? null,
        );

        return back()->with('success', __('flash.legal_version_published', ['version' => $data['version']]));
    }

    /**
     * Create a new legal document.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slug'                 => 'required|string|max:80|unique:legal_documents,slug',
            'title'                => 'required|string|max:200',
            'document_type'        => 'required|in:terms,privacy,consent,dpa,facility_agreement,api_terms',
            'language'             => 'nullable|in:en,fr,ha,yo,ig',
            'requires_acceptance'  => 'nullable|boolean',
        ]);

        LegalDocument::create([
            ...$data,
            'language'            => $data['language'] ?? 'en',
            'requires_acceptance' => (bool) ($data['requires_acceptance'] ?? true),
            'is_active'           => true,
            'created_by'          => $this->demoActorId(),
        ]);

        return back()->with('success', __('flash.legal_document_created', ['title' => $data['title']]));
    }

    // ------------------------------------------------------------------
    // Patient Rights — Account Closure
    // ------------------------------------------------------------------

    public function closureRequests(): View
    {
        $requests = AccountClosureRequest::with('patient')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('portals.admin.legal.closures', compact('requests'));
    }

    public function reviewClosure(Request $request, AccountClosureRequest $closure): RedirectResponse
    {
        $data = $request->validate([
            'action'      => 'required|in:approve,reject',
            'review_note' => 'nullable|string|max:500',
        ]);

        $status = $data['action'] === 'approve' ? 'approved' : 'rejected';

        $closure->update([
            'status'      => $status,
            'reviewed_by' => $this->demoActorId(),
            'review_note' => $data['review_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', __('flash.closure_request_' . $status));
    }

    // ------------------------------------------------------------------
    // Privacy Complaints
    // ------------------------------------------------------------------

    public function privacyComplaints(): View
    {
        $complaints = PrivacyComplaint::with('patient')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('portals.admin.legal.complaints', compact('complaints'));
    }

    public function resolveComplaint(Request $request, PrivacyComplaint $complaint): RedirectResponse
    {
        $data = $request->validate([
            'resolution' => 'required|string|min:10|max:1000',
        ]);

        $complaint->update([
            'status'      => 'resolved',
            'resolution'  => $data['resolution'],
            'resolved_at' => now(),
        ]);

        return back()->with('success', __('flash.privacy_complaint_resolved'));
    }

    // ------------------------------------------------------------------
    // Minor Transitions
    // ------------------------------------------------------------------

    public function minorTransitions(): View
    {
        $transitions = MinorTransitionReview::with('patient')
            ->orderBy('turns_18_on')
            ->paginate(20);

        return view('portals.admin.legal.minor_transitions', compact('transitions'));
    }

    // ------------------------------------------------------------------
    // Public-facing legal centre (read-only)
    // ------------------------------------------------------------------

    /**
     * GET /legal — public legal centre listing all documents.
     */
    public function publicIndex(): View
    {
        $documents = $this->legalService->getPublicDocuments();

        return view('public.legal.index', compact('documents'));
    }

    /**
     * GET /legal/{slug} — public document view.
     */
    public function publicShow(string $slug): View
    {
        $document = LegalDocument::where('slug', $slug)
            ->where('is_active', true)
            ->with(['versions' => fn ($q) => $q->where('is_current', true)])
            ->firstOrFail();

        $currentVersion = $document->versions->first();

        return view('public.legal.show', compact('document', 'currentVersion'));
    }
}
