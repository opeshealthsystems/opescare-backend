<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use App\Modules\Broadcasts\Models\Broadcast;
use App\Modules\Broadcasts\Services\BroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * AdminBroadcastController — compose, publish and manage broadcasts/announcements.
 *
 * Imported from the platform (docs/PLATFORM_IMPORTS.md group B). Platform-tier:
 * routes live under /admin/* so RequirePlatformAdmin holds.
 *
 * Finder adaptations: an audience can name facilities (facility_staff) or
 * roles (role) through target_ids; publish_at in the future schedules the
 * broadcast (it reaches staff feeds only from then); an acknowledgement list
 * per broadcast. Nothing is deleted — a broadcast is cancelled.
 */
class AdminBroadcastController extends Controller
{
    private const TYPES        = ['announcement', 'alert', 'maintenance', 'policy', 'outage'];
    private const TARGET_TYPES = ['all_staff', 'all_users', 'all_patients', 'facility_staff', 'role'];
    private const PRIORITIES   = ['normal', 'high', 'urgent'];

    public function __construct(private readonly BroadcastService $broadcasts) {}

    public function index(Request $request): View
    {
        $items = Broadcast::query()
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->withCount('acknowledgements')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('portals.admin.broadcasts.index', [
            'items'       => $items,
            'types'       => self::TYPES,
            'targetTypes' => self::TARGET_TYPES,
            'priorities'  => self::PRIORITIES,
            'status'      => $request->input('status'),
            'facilities'  => Facility::query()->orderBy('name')->limit(500)->get(['id', 'name']),
            'roles'       => Role::query()->where('name', '!=', 'patient')->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'                    => 'required|string|max:160',
            'body'                     => 'required|string|max:5000',
            'broadcast_type'           => 'required|in:'.implode(',', self::TYPES),
            'target_type'              => 'required|in:'.implode(',', self::TARGET_TYPES),
            'facility_ids'             => 'required_if:target_type,facility_staff|array',
            'facility_ids.*'           => 'uuid|exists:facilities,id',
            'role_names'               => 'required_if:target_type,role|array',
            'role_names.*'             => 'string|exists:roles,name',
            'priority'                 => 'nullable|in:'.implode(',', self::PRIORITIES),
            'language'                 => 'nullable|in:en,fr',
            'requires_acknowledgement' => 'nullable|boolean',
            'publish_at'               => 'nullable|date|after:now',
            'expires_at'               => 'nullable|date|after:now',
            'publish_now'              => 'nullable|boolean',
        ]);

        $targetIds = match ($data['target_type']) {
            'facility_staff' => array_values($data['facility_ids'] ?? []),
            'role'           => array_values($data['role_names'] ?? []),
            default          => [],
        };

        $broadcast = $this->broadcasts->create([
            'broadcast_type'           => $data['broadcast_type'],
            'title'                    => $data['title'],
            'body'                     => $data['body'],
            'target_type'              => $data['target_type'],
            'target_ids'               => $targetIds,
            'priority'                 => $data['priority'] ?? 'normal',
            'language'                 => $data['language'] ?? app()->getLocale(),
            'requires_acknowledgement' => $request->boolean('requires_acknowledgement'),
            'publish_at'               => $data['publish_at'] ?? null,
            'expires_at'               => $data['expires_at'] ?? null,
        ], (string) Auth::id());

        if ($request->boolean('publish_now')) {
            $this->broadcasts->publish($broadcast);
            return redirect()->route('portals.admin.broadcasts')->with('success', __('broadcasts.published_flash'));
        }

        return redirect()->route('portals.admin.broadcasts')->with('success', __('broadcasts.draft_flash'));
    }

    public function show(string $uuid): View
    {
        $broadcast = Broadcast::where('uuid', $uuid)->firstOrFail();
        $acks = $broadcast->acknowledgements()->orderByDesc('acknowledged_at')->get();

        return view('portals.admin.broadcasts.show', [
            'broadcast' => $broadcast,
            'acks'      => $acks,
            'users'     => User::whereIn('id', $acks->pluck('user_id')->all())->get(['id', 'name'])->keyBy(fn ($u) => (string) $u->id),
            'facilities'=> Facility::whereIn('id', $acks->pluck('facility_id')->filter()->all())->pluck('name', 'id'),
        ]);
    }

    public function publish(string $uuid): RedirectResponse
    {
        $broadcast = Broadcast::where('uuid', $uuid)->firstOrFail();

        try {
            $this->broadcasts->publish($broadcast);
        } catch (\DomainException) {
            return back()->with('error', __('broadcasts.not_draft'));
        }

        return back()->with('success', __('broadcasts.published_flash'));
    }

    public function cancel(string $uuid): RedirectResponse
    {
        $broadcast = Broadcast::where('uuid', $uuid)->firstOrFail();

        try {
            $this->broadcasts->cancel($broadcast);
        } catch (\DomainException) {
            return back()->with('error', __('broadcasts.already_cancelled'));
        }

        return back()->with('success', __('broadcasts.cancelled_flash'));
    }
}
