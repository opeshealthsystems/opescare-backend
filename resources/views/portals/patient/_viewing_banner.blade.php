{{--
    "You are viewing {name}'s record — switch back"

    Rendered at the top of every patient-portal page while a guardian has a
    dependent's record open (request attributes set by GuardianAccessMiddleware).
    Renders nothing otherwise, so every view can include it unconditionally.

    Optional: ['viewingNote' => '…'] adds a second line — a page-specific
    reminder of whose data is on screen (the inbox pages use it to say these
    are the dependent's conversations / notifications, not the guardian's own).
--}}
@php
    $vbPatient = request()->attributes->get('viewing_patient');
    $vbLink    = request()->attributes->get('guardian_link');
    $vbName    = $vbPatient ? trim(($vbPatient->first_name ?? '') . ' ' . ($vbPatient->last_name ?? '')) : '';
    $vbRead    = \App\Http\Middleware\GuardianAccessMiddleware::isReadOnly($vbLink);
    $viewingNote = $viewingNote ?? null;
@endphp
@if($vbPatient)
<div class="pf-viewing" role="status" data-viewing-banner>
    <style>
        .pf-viewing{background:#0F4C81;color:#fff;padding:.65rem var(--p-space-6,1.25rem);font-size:.875rem}
        .pf-viewing__row{display:flex;align-items:center;justify-content:space-between;gap:.75rem 1rem;flex-wrap:wrap}
        .pf-viewing__who{display:flex;align-items:center;gap:.5rem;min-width:0;flex-wrap:wrap}
        .pf-viewing__who svg{width:1rem;height:1rem;flex:none}
        .pf-viewing__id{font-family:ui-monospace,Menlo,Consolas,monospace;opacity:.85}
        .pf-viewing__level{display:inline-flex;align-items:center;gap:.3rem;border-radius:999px;padding:.1rem .6rem;font-size:.75rem;font-weight:700;background:rgba(255,255,255,.18)}
        .pf-viewing__level svg{width:.85rem;height:.85rem}
        .pf-viewing__back{background:#fff;color:#0F4C81;border:0;border-radius:6px;padding:.35rem .8rem;font-weight:700;font-size:.8125rem;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;min-height:36px}
        .pf-viewing__back svg{width:.9rem;height:.9rem}
        .pf-viewing__note{margin:.45rem 0 0;font-size:.8125rem;opacity:.9;display:flex;align-items:flex-start;gap:.4rem}
        .pf-viewing__note svg{width:.9rem;height:.9rem;flex:none;margin-top:.1rem}
    </style>
    <div class="pf-viewing__row">
        <div class="pf-viewing__who">
            <i data-lucide="eye" aria-hidden="true"></i>
            <span>{{ __('family.banner_viewing', ['name' => $vbName]) }}</span>
            <span class="pf-viewing__id">{{ $vbPatient->health_id }}</span>
            <span class="pf-viewing__level">
                <i data-lucide="{{ $vbRead ? 'lock' : 'pencil' }}" aria-hidden="true"></i>
                {{ $vbRead ? __('family.banner_read_only') : __('family.banner_full') }}
            </span>
        </div>
        <form class="ods-1386d50" method="POST" action="{{ route('portals.patient.family.switch.back') }}">
            @csrf
            <button type="submit" class="pf-viewing__back">
                <i data-lucide="undo-2" aria-hidden="true"></i>
                {{ __('family.banner_switch_back') }}
            </button>
        </form>
    </div>
    @if($viewingNote)
        <p class="pf-viewing__note">
            <i data-lucide="info" aria-hidden="true"></i>
            <span>{{ $viewingNote }}</span>
        </p>
    @endif
</div>
@endif
