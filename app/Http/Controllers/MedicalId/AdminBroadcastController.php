<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Modules\Broadcasts\Models\Broadcast;
use App\Modules\Broadcasts\Services\BroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * AdminBroadcastController — compose, publish and manage broadcasts/announcements.
 *
 * Surfaces the Broadcasts module (BroadcastService + Broadcast), which had a full
 * backend + API but no composer UI. Platform-tier (lives under /portals/admin/*).
 */
class AdminBroadcastController extends Controller
{
    private const TYPES        = ['announcement', 'alert', 'maintenance', 'policy', 'outage'];
    private const TARGET_TYPES = ['all_patients', 'all_staff', 'all_users', 'facility_staff', 'facility_patients'];
    private const PRIORITIES   = ['normal', 'high', 'urgent'];

    public function __construct(private readonly BroadcastService $broadcasts) {}

    public function index(Request $request): View
    {
        $items = Broadcast::query()
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('portals.admin.broadcasts.index', [
            'items'       => $items,
            'types'       => self::TYPES,
            'targetTypes' => self::TARGET_TYPES,
            'priorities'  => self::PRIORITIES,
            'status'      => $request->input('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'                    => 'required|string|max:160',
            'body'                     => 'required|string|max:5000',
            'broadcast_type'           => 'required|in:'.implode(',', self::TYPES),
            'target_type'              => 'required|in:'.implode(',', self::TARGET_TYPES),
            'priority'                 => 'nullable|in:'.implode(',', self::PRIORITIES),
            'language'                 => 'nullable|in:en,fr',
            'requires_acknowledgement' => 'nullable|boolean',
            'expires_at'               => 'nullable|date|after:now',
            'publish_now'              => 'nullable|boolean',
        ]);

        $broadcast = $this->broadcasts->create([
            'broadcast_type'           => $data['broadcast_type'],
            'title'                    => $data['title'],
            'body'                     => $data['body'],
            'target_type'              => $data['target_type'],
            'target_ids'               => [],
            'priority'                 => $data['priority'] ?? 'normal',
            'language'                 => $data['language'] ?? app()->getLocale(),
            'requires_acknowledgement' => $request->boolean('requires_acknowledgement'),
            'expires_at'               => $data['expires_at'] ?? null,
        ], (string) Auth::id());

        if ($request->boolean('publish_now')) {
            $this->broadcasts->publish($broadcast);
            return redirect()->route('portals.admin.broadcasts')->with('success', __('broadcasts.published_flash'));
        }

        return redirect()->route('portals.admin.broadcasts')->with('success', __('broadcasts.draft_flash'));
    }

    public function publish(string $uuid): RedirectResponse
    {
        $broadcast = Broadcast::where('uuid', $uuid)->firstOrFail();

        try {
            $this->broadcasts->publish($broadcast);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('broadcasts.published_flash'));
    }

    public function cancel(string $uuid): RedirectResponse
    {
        $broadcast = Broadcast::where('uuid', $uuid)->firstOrFail();
        $this->broadcasts->cancel($broadcast);

        return back()->with('success', __('broadcasts.cancelled_flash'));
    }
}
