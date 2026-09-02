@extends('layouts.portal')
@section('title', __('public.adm_sup_idx_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('admin_extra.breadcrumb_admin', [], app()->getLocale()) ?: 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_sup_idx_breadcrumb_section'))
@section('content')

<div class="page-head">
    <h2>{{ __('public.adm_sup_idx_heading') }}</h2>
    <div class="page-head__spacer"></div>
</div>
<p class="td-muted mb-6">{{ __('public.adm_sup_idx_desc') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary"><div class="stat-card__head"><i data-lucide="ticket"></i></div><div class="stat-card__label">{{ __('public.adm_sup_idx_stat_total') }}</div><div class="stat-card__value">{{ $stats['total'] ?? 0 }}</div></div>
    <div class="stat-card stat-card--danger"><div class="stat-card__head"><i data-lucide="alert-circle"></i></div><div class="stat-card__label">{{ __('public.adm_sup_idx_stat_open') }}</div><div class="stat-card__value">{{ $stats['open'] ?? 0 }}</div></div>
    <div class="stat-card stat-card--warning"><div class="stat-card__head"><i data-lucide="clock"></i></div><div class="stat-card__label">{{ __('public.adm_sup_idx_stat_pending') }}</div><div class="stat-card__value">{{ $stats['pending'] ?? 0 }}</div></div>
    <div class="stat-card stat-card--success"><div class="stat-card__head"><i data-lucide="check-circle"></i></div><div class="stat-card__label">{{ __('public.adm_sup_idx_stat_resolved') }}</div><div class="stat-card__value">{{ $stats['resolved'] ?? 0 }}</div></div>
</div>

<form method="GET" action="{{ route('portals.admin.support.index') }}" class="filter-bar">
    <select name="status" class="filter-select" aria-label="{{ __('public.aria_status') }}">
        <option value="">{{ __('public.adm_sup_idx_filter_all_statuses') }}</option>
        @foreach(['open','pending','resolved','closed'] as $s)
        <option value="{{ $s }}" @selected(request('status')===$s)>@enum($s)</option>
        @endforeach
    </select>
    <select name="priority" class="filter-select" aria-label="{{ __('public.aria_priority') }}">
        <option value="">{{ __('public.adm_sup_idx_filter_all_priorities') }}</option>
        @foreach(['low','medium','high','urgent'] as $p)
        <option value="{{ $p }}" @selected(request('priority')===$p)>@enum($p, 'priority')</option>
        @endforeach
    </select>
    <select name="category" class="filter-select" aria-label="{{ __('public.aria_category') }}">
        <option value="">{{ __('public.adm_sup_idx_filter_all_categories') }}</option>
        @foreach($categories ?? [] as $cat)
        <option value="{{ $cat }}" @selected(request('category')===$cat)>{{ ucfirst($cat) }}</option>
        @endforeach
    </select>
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="{{ __('public.adm_sup_idx_ph_search') }}" value="{{ request('search') }}" aria-label="{{ __('public.aria_search') }}">
    </label>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_sup_idx_btn_filter') }}</button>
    <a href="{{ route('portals.admin.support.index') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_sup_idx_btn_reset') }}</a>
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_sup_idx_col_ticket_num') }}</th>
                    <th>{{ __('public.adm_sup_idx_col_subject') }}</th>
                    <th>{{ __('public.adm_sup_idx_col_category') }}</th>
                    <th>{{ __('public.adm_sup_idx_col_priority') }}</th>
                    <th>{{ __('public.adm_sup_idx_col_status') }}</th>
                    <th>{{ __('public.adm_sup_idx_col_assignee') }}</th>
                    <th>{{ __('public.adm_sup_idx_col_created') }}</th>
                    <th class="row-actions">{{ __('public.adm_sup_idx_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets ?? [] as $ticket)
                @php
                $statusBadge=match($ticket->status??'open'){'open'=>'badge-danger','pending'=>'badge-warning','resolved'=>'badge-success','closed'=>'badge-neutral',default=>'badge-neutral'};
                $prioBadge=match($ticket->priority??'medium'){'urgent'=>'badge-danger','high'=>'badge-warning','medium'=>'badge-primary','low'=>'badge-neutral',default=>'badge-neutral'};
                @endphp
                <tr>
                    <td data-label="{{ __('admin_extra.sup_col_ticket', [], app()->getLocale()) ?: 'Ticket #' }}"><span class="td-muted td-strong">#{{ $ticket->ticket_number ?? $ticket->id }}</span></td>
                    <td data-label="{{ __('admin_extra.sup_col_subject', [], app()->getLocale()) ?: 'Subject' }}"><a href="{{ route('portals.admin.support.show', $ticket) }}" class="td-strong">{{ Str::limit($ticket->subject, 55) }}</a></td>
                    <td data-label="{{ __('admin_extra.sup_col_category', [], app()->getLocale()) ?: 'Category' }}"><span class="badge badge-neutral">{{ ucfirst($ticket->category ?? (__('admin_extra.sup_fallback_general', [], app()->getLocale()) ?: 'General')) }}</span></td>
                    <td data-label="{{ __('admin_extra.sup_col_priority', [], app()->getLocale()) ?: 'Priority' }}"><span class="badge {{ $prioBadge }}">@enum($ticket->priority ?? 'medium', 'priority')</span></td>
                    <td data-label="{{ __('admin_extra.sup_col_status', [], app()->getLocale()) ?: 'Status' }}"><span class="badge {{ $statusBadge }}">@enum($ticket->status ?? 'open')</span></td>
                    <td data-label="{{ __('admin_extra.sup_col_assignee', [], app()->getLocale()) ?: 'Assignee' }}">{{ $ticket->assignee?->name ?? '—' }}</td>
                    <td data-label="{{ __('admin_extra.sup_col_created', [], app()->getLocale()) ?: 'Created' }}">{{ $ticket->created_at?->format('d M Y') }}</td>
                    <td class="row-actions" data-label="{{ __('admin_extra.sup_col_actions', [], app()->getLocale()) ?: 'Actions' }}">
                        <a href="{{ route('portals.admin.support.show', $ticket) }}" class="icon-btn" aria-label="{{ __('public.aria_view_ticket') }}" title="{{ __('admin_extra.title_view', [], app()->getLocale()) ?: 'View' }}"><i data-lucide="eye"></i></a>
                        @if(!in_array($ticket->status??'',['closed','resolved']))
                        <button type="button" class="btn btn-danger btn-sm" title="{{ __('public.aria_close') }}" onclick="opOpenModal('close-{{ $ticket->id }}')"><i data-lucide="x-circle"></i></button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="td-muted empty-cell">{{ __('public.adm_sup_idx_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($tickets) && $tickets->hasPages())
    <div class="panel-body">{{ $tickets->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Close confirm modals --}}
@foreach($tickets ?? [] as $ticket)
    @if(!in_array($ticket->status??'',['closed','resolved']))
    <div id="close-{{ $ticket->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="close-{{ $ticket->id }}-title">
            <h3 class="modal__title" id="close-{{ $ticket->id }}-title"><i data-lucide="x-circle"></i> {{ __('public.adm_sup_idx_modal_close_title') }}</h3>
            <form method="POST" action="{{ route('portals.admin.support.close', $ticket) }}">
                @csrf @method('PATCH')
                <div class="modal__body"><p>{{ __('public.adm_sup_idx_modal_close_title') }} <strong>#{{ $ticket->ticket_number ?? $ticket->id }}</strong>?</p></div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('close-{{ $ticket->id }}')">{{ __('public.adm_sup_idx_modal_close_btn_cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('public.adm_sup_idx_modal_close_btn_close') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
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
