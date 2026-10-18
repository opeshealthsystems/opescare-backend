@extends('layouts.portal')
@section('title', __('leads.admin.page_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('leads.admin.breadcrumb'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = fn ($s) => $gcTones[crc32((string) $s) % count($gcTones)];
    $gcInit = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
@endphp
@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('leads.admin.heading') }}</h1>
        <p class="od-page__sub">{{ __('leads.admin.description') }}</p>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-kpis">
    <div class="od-kpi"><span class="od-kpi__icon od-tint--blue"><i data-lucide="inbox"></i></span><div class="od-kpi__body"><span class="od-kpi__label">{{ __('leads.admin.stat_total') }}</span><span class="od-kpi__value">{{ $totalCount }}</span></div></div>
    <div class="od-kpi"><span class="od-kpi__icon od-tint--rose"><i data-lucide="sparkles"></i></span><div class="od-kpi__body"><span class="od-kpi__label">{{ __('leads.statuses.new') }}</span><span class="od-kpi__value">{{ $counts['new'] ?? 0 }}</span></div></div>
    <div class="od-kpi"><span class="od-kpi__icon od-tint--amber"><i data-lucide="phone"></i></span><div class="od-kpi__body"><span class="od-kpi__label">{{ __('leads.statuses.contacted') }}</span><span class="od-kpi__value">{{ $counts['contacted'] ?? 0 }}</span></div></div>
    <div class="od-kpi"><span class="od-kpi__icon od-tint--green"><i data-lucide="trophy"></i></span><div class="od-kpi__body"><span class="od-kpi__label">{{ __('leads.statuses.won') }}</span><span class="od-kpi__value">{{ $counts['won'] ?? 0 }}</span></div></div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('leads.admin.heading') }} <span class="od-card__count">({{ $leads->total() }})</span></h2>
    </div>

    <form method="GET" action="{{ route('portals.admin.leads') }}" class="oda-toolbar">
        <select name="status" class="od-select" aria-label="{{ __('leads.admin.filter_label') }}">
            <option value="">{{ __('leads.admin.filter_all') }}</option>
            @foreach(\App\Models\Lead::STATUSES as $s)
            <option value="{{ $s }}" @selected($activeStatus===$s)>{{ __('leads.statuses.'.$s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="filter"></i> {{ __('leads.admin.filter_apply') }}</button>
        <a href="{{ route('portals.admin.leads') }}" class="od-btn od-btn--ghost">{{ __('leads.admin.filter_reset') }}</a>
    </form>

    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th>{{ __('leads.admin.col_organization') }}</th>
                    <th>{{ __('leads.admin.col_type') }}</th>
                    <th>{{ __('leads.admin.col_contact') }}</th>
                    <th>{{ __('leads.admin.col_source') }}</th>
                    <th>{{ __('leads.admin.col_status') }}</th>
                    <th>{{ __('leads.admin.col_date') }}</th>
                    <th class="od-table__actions">{{ __('leads.admin.col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                @php
                $statusBadge = match($lead->status){'new'=>'urgent','contacted'=>'pending','qualified'=>'normal','won'=>'ready','converted'=>'ready','lost'=>'neutral',default=>'neutral'};
                @endphp
                <tr>
                    <td data-label="{{ __('leads.admin.col_organization') }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-tint--{{ $gcTone($lead->organization_name ?: $lead->name) }}" aria-hidden="true">{{ $gcInit($lead->organization_name ?: $lead->name) }}</span>
                            <a href="{{ route('portals.admin.leads.show', $lead) }}" class="od-table__name">{{ $lead->organization_name ?: $lead->name }}</a>
                        </div>
                    </td>
                    <td data-label="{{ __('leads.admin.col_type') }}"><span class="od-pill od-pill--neutral">{{ $lead->organization_type ? __('leads.org_types.'.$lead->organization_type) : '—' }}</span></td>
                    <td data-label="{{ __('leads.admin.col_contact') }}">
                        <span class="od-table__name">{{ $lead->name }}</span>
                        <span class="od-table__sub">{{ $lead->email }}</span>
                    </td>
                    <td data-label="{{ __('leads.admin.col_source') }}"><span class="od-pill od-pill--neutral">{{ $lead->source }}</span></td>
                    <td data-label="{{ __('leads.admin.col_status') }}"><span class="od-pill od-pill--dot od-pill--{{ $statusBadge }}">{{ __('leads.statuses.'.$lead->status) }}</span></td>
                    <td data-label="{{ __('leads.admin.col_date') }}" class="oda-gc-muted">{{ $lead->created_at?->translatedFormat('d M Y') }}</td>
                    <td class="od-table__actions" data-label="{{ __('leads.admin.col_actions') }}">
                        <div class="oda-gc-actions">
                        @if($lead->isConverted())
                        <a href="{{ route('portals.admin.leads.convert', $lead) }}" class="od-btn od-btn--row"><i data-lucide="building-2"></i> {{ __('leads.conversion.action_view_tenant') }}</a>
                        @else
                        <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-call="opOpenModal" data-args="[&quot;lead-{{ $lead->id }}&quot;]"><i data-lucide="pencil"></i> {{ __('leads.admin.action_update') }}</button>
                        @if(in_array($lead->organization_type, ['facility', 'lab', 'pharmacy', 'other', null], true))
                        <a href="{{ route('portals.admin.leads.convert', $lead) }}" class="od-btn od-btn--row"><i data-lucide="building-2"></i> {{ __('leads.conversion.action_convert') }}</a>
                        @endif
                        @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="od-empty">
                        <span class="od-empty__icon"><i data-lucide="inbox"></i></span>
                        <p class="od-empty__text">{{ __('leads.admin.empty') }}</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leads->hasPages())
    <div class="od-card__foot">{{ $leads->withQueryString()->links() }}</div>
    @endif
</section>

{{-- Status update modals --}}
@foreach($leads as $lead)
<div id="lead-{{ $lead->id }}" class="modal-backdrop" hidden>
    <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="lead-{{ $lead->id }}-title">
        <div class="od-card__head"><h3 class="od-card__title" id="lead-{{ $lead->id }}-title"><i data-lucide="pencil"></i> {{ __('leads.admin.modal_title') }}</h3></div>
        <form method="POST" action="{{ route('portals.admin.leads.status', $lead) }}" class="od-card__body od-stack">
            @csrf
            <p class="od-table__name">{{ $lead->organization_name ?: $lead->name }}</p>
            <label class="oda-label" for="status-{{ $lead->id }}">
                <span class="oda-label__text">{{ __('leads.admin.modal_status') }}</span>
                <select name="status" id="status-{{ $lead->id }}" class="od-select">
                    @foreach(array_diff(\App\Models\Lead::STATUSES, ['converted']) as $s)
                    <option value="{{ $s }}" @selected($lead->status===$s)>{{ __('leads.statuses.'.$s) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="oda-label" for="note-{{ $lead->id }}">
                <span class="oda-label__text">{{ __('leads.admin.modal_note') }}</span>
                <textarea name="note" id="note-{{ $lead->id }}" rows="3" class="oda-textarea" placeholder="{{ __('leads.admin.modal_note_ph') }}"></textarea>
            </label>
            <div class="oda-form__actions">
                <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;lead-{{ $lead->id }}&quot;]">{{ __('leads.admin.modal_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--primary">{{ __('leads.admin.modal_save') }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

</div>
@endsection
@section('scripts')
<script @cspNonce>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
