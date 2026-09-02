@extends('layouts.portal')
@section('title', __('leads.admin.page_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('leads.admin.breadcrumb'))
@section('content')

<div class="page-head">
    <h2>{{ __('leads.admin.heading') }}</h2>
    <div class="page-head__spacer"></div>
</div>
<p class="td-muted mb-6">{{ __('leads.admin.description') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary"><div class="stat-card__head"><i data-lucide="inbox"></i></div><div class="stat-card__label">{{ __('leads.admin.stat_total') }}</div><div class="stat-card__value">{{ $totalCount }}</div></div>
    <div class="stat-card stat-card--danger"><div class="stat-card__head"><i data-lucide="sparkles"></i></div><div class="stat-card__label">{{ __('leads.statuses.new') }}</div><div class="stat-card__value">{{ $counts['new'] ?? 0 }}</div></div>
    <div class="stat-card stat-card--warning"><div class="stat-card__head"><i data-lucide="phone"></i></div><div class="stat-card__label">{{ __('leads.statuses.contacted') }}</div><div class="stat-card__value">{{ $counts['contacted'] ?? 0 }}</div></div>
    <div class="stat-card stat-card--success"><div class="stat-card__head"><i data-lucide="trophy"></i></div><div class="stat-card__label">{{ __('leads.statuses.won') }}</div><div class="stat-card__value">{{ $counts['won'] ?? 0 }}</div></div>
</div>

<form method="GET" action="{{ route('portals.admin.leads') }}" class="filter-bar">
    <select name="status" class="filter-select" aria-label="{{ __('leads.admin.filter_label') }}">
        <option value="">{{ __('leads.admin.filter_all') }}</option>
        @foreach(\App\Models\Lead::STATUSES as $s)
        <option value="{{ $s }}" @selected($activeStatus===$s)>{{ __('leads.statuses.'.$s) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('leads.admin.filter_apply') }}</button>
    <a href="{{ route('portals.admin.leads') }}" class="btn btn-ghost btn-sm">{{ __('leads.admin.filter_reset') }}</a>
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('leads.admin.col_organization') }}</th>
                    <th>{{ __('leads.admin.col_type') }}</th>
                    <th>{{ __('leads.admin.col_contact') }}</th>
                    <th>{{ __('leads.admin.col_source') }}</th>
                    <th>{{ __('leads.admin.col_status') }}</th>
                    <th>{{ __('leads.admin.col_date') }}</th>
                    <th class="row-actions">{{ __('leads.admin.col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                @php
                $statusBadge = match($lead->status){'new'=>'badge-danger','contacted'=>'badge-warning','qualified'=>'badge-primary','won'=>'badge-success','lost'=>'badge-neutral',default=>'badge-neutral'};
                @endphp
                <tr>
                    <td data-label="{{ __('leads.admin.col_organization') }}"><span class="td-strong">{{ $lead->organization_name ?: '—' }}</span></td>
                    <td data-label="{{ __('leads.admin.col_type') }}"><span class="badge badge-neutral">{{ $lead->organization_type ? __('leads.org_types.'.$lead->organization_type) : '—' }}</span></td>
                    <td data-label="{{ __('leads.admin.col_contact') }}">
                        <div class="td-strong">{{ $lead->name }}</div>
                        <div class="td-muted">{{ $lead->email }}</div>
                    </td>
                    <td data-label="{{ __('leads.admin.col_source') }}"><span class="badge badge-neutral">{{ $lead->source }}</span></td>
                    <td data-label="{{ __('leads.admin.col_status') }}"><span class="badge {{ $statusBadge }}">{{ __('leads.statuses.'.$lead->status) }}</span></td>
                    <td data-label="{{ __('leads.admin.col_date') }}">{{ $lead->created_at?->format('d M Y') }}</td>
                    <td class="row-actions" data-label="{{ __('leads.admin.col_actions') }}">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="opOpenModal('lead-{{ $lead->id }}')"><i data-lucide="pencil"></i> {{ __('leads.admin.action_update') }}</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="td-muted empty-cell">{{ __('leads.admin.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leads->hasPages())
    <div class="panel-body">{{ $leads->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Status update modals --}}
@foreach($leads as $lead)
<div id="lead-{{ $lead->id }}" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="lead-{{ $lead->id }}-title">
        <h3 class="modal__title" id="lead-{{ $lead->id }}-title"><i data-lucide="pencil"></i> {{ __('leads.admin.modal_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.leads.status', $lead) }}">
            @csrf
            <div class="modal__body">
                <p class="td-strong mb-2">{{ $lead->organization_name ?: $lead->name }}</p>
                <label class="demo-label" for="status-{{ $lead->id }}">{{ __('leads.admin.modal_status') }}</label>
                <select name="status" id="status-{{ $lead->id }}" class="filter-select" style="width:100%;margin-bottom:1rem;">
                    @foreach(\App\Models\Lead::STATUSES as $s)
                    <option value="{{ $s }}" @selected($lead->status===$s)>{{ __('leads.statuses.'.$s) }}</option>
                    @endforeach
                </select>
                <label class="demo-label" for="note-{{ $lead->id }}">{{ __('leads.admin.modal_note') }}</label>
                <textarea name="note" id="note-{{ $lead->id }}" rows="3" class="filter-select" style="width:100%;" placeholder="{{ __('leads.admin.modal_note_ph') }}"></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('lead-{{ $lead->id }}')">{{ __('leads.admin.modal_cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('leads.admin.modal_save') }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
