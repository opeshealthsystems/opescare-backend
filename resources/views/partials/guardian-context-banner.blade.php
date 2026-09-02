@php $viewingPatient = request()->attributes->get('viewing_patient'); @endphp
@if($viewingPatient)
<div style="background:#0E7490;color:#fff;padding:0.6rem var(--p-space-6);display:flex;align-items:center;justify-content:space-between;font-size:0.875rem;gap:1rem;">
    <div style="display:flex;align-items:center;gap:0.5rem;">
        <i data-lucide="eye" style="width:1rem;height:1rem;"></i>
        <span>
            {{ __('public.portal.viewing_as', [], app()->getLocale()) ?: 'Viewing:' }}
            <strong>{{ $viewingPatient->first_name }} {{ $viewingPatient->last_name }}</strong>
            &nbsp;({{ $viewingPatient->health_id }})
        </span>
    </div>
    <form method="POST" action="{{ route('portals.patient.family.switch.back') }}" style="margin:0;">
        @csrf
        <button type="submit" style="background:rgba(255,255,255,.2);border:none;color:#fff;padding:0.25rem 0.75rem;border-radius:4px;cursor:pointer;font-size:0.8125rem;">
            {{ __('public.portal.switch_back', [], app()->getLocale()) ?: 'Switch Back' }}
        </button>
    </form>
</div>
@endif
