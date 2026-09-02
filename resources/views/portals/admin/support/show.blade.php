@extends('layouts.portal')
@section('title', 'Ticket #' . ($ticket->ticket_number ?? $ticket->id))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_sup_idx_breadcrumb_section'))
@section('content')

@php
$statusBadge=match($ticket->status??'open'){'open'=>'badge-danger','pending'=>'badge-warning','resolved'=>'badge-success','closed'=>'badge-neutral',default=>'badge-neutral'};
$prioBadge=match($ticket->priority??'medium'){'urgent'=>'badge-danger','high'=>'badge-warning','medium'=>'badge-primary','low'=>'badge-neutral',default=>'badge-neutral'};
@endphp

<div class="breadcrumb">
    <a href="{{ route('portals.admin.support.index') }}">{{ __('public.adm_sup_show_breadcrumb_tickets') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>#{{ $ticket->ticket_number ?? $ticket->id }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="ticket"></i></div>
    <div>
        <h2 class="entity-head__title">{{ $ticket->subject }}</h2>
        <div class="entity-head__sub">
            <span class="td-muted">#{{ $ticket->ticket_number ?? $ticket->id }}</span>
            <span class="badge {{ $statusBadge }}">@enum($ticket->status ?? 'open')</span>
            <span class="badge {{ $prioBadge }}">@enum($ticket->priority ?? 'medium', 'priority') {{ __('public.adm_sup_show_priority_label') }}</span>
        </div>
    </div>
    <div class="entity-head__spacer"></div>
    @if(!in_array($ticket->status??'',['closed','resolved']))
    <button type="button" class="btn btn-warning btn-sm" onclick="opOpenModal('close-modal')"><i data-lucide="x-circle"></i> {{ __('public.adm_sup_show_btn_close') }}</button>
    @endif
    @if(in_array($ticket->status??'',['closed','resolved']))
    <button type="button" class="btn btn-success btn-sm" onclick="opOpenModal('reopen-modal')"><i data-lucide="refresh-cw"></i> {{ __('public.adm_sup_show_btn_reopen') }}</button>
    @endif
    <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('delete-modal')"><i data-lucide="trash-2"></i> {{ __('public.adm_sup_show_btn_delete') }}</button>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="field-grid">
    <div>
        <div class="panel mb-6">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="message-square"></i> {{ __('public.adm_sup_show_panel_desc') }}</h3></div>
            <div class="panel-body prose-wrap">{{ $ticket->description ?? $ticket->body ?? __('public.adm_sup_show_no_desc') }}</div>
        </div>

        <div class="panel">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="messages-square"></i> {{ __('public.adm_sup_show_panel_replies') }} @if(isset($ticket->replies))<span class="td-muted">({{ $ticket->replies->count() }})</span>@endif</h3></div>
            @if(isset($ticket->replies) && $ticket->replies->count())
            @foreach($ticket->replies as $reply)
            <div class="thread-item {{ !$loop->last ? 'thread-item--bordered' : '' }}">
                <div class="thread-item__head">
                    <span class="list-row__main">
                        <span class="thread-avatar">{{ strtoupper(substr($reply->author?->name ?? 'S', 0, 1)) }}</span>
                        <span class="kv-strong">{{ $reply->author?->name ?? 'Staff' }}</span>
                        @if($reply->is_staff_reply ?? false)<span class="badge badge-primary">{{ __('public.adm_sup_show_badge_staff') }}</span>@endif
                    </span>
                    <span class="td-muted">{{ $reply->created_at?->format('d M Y, H:i') }}</span>
                </div>
                <div class="thread-item__body">{{ $reply->body ?? $reply->message }}</div>
            </div>
            @endforeach
            @else
            <div class="empty-state"><p>{{ __('public.adm_sup_show_no_replies') }}</p></div>
            @endif
        </div>
    </div>

    <div>
        <div class="panel mb-6">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="info"></i> {{ __('public.adm_sup_show_panel_details') }}</h3></div>
            <div class="panel-body">
                <table class="kv-table">
                    <tr><td>{{ __('public.adm_sup_show_kv_status') }}</td><td><span class="badge {{ $statusBadge }}">@enum($ticket->status ?? 'open')</span></td></tr>
                    <tr><td>{{ __('public.adm_sup_show_kv_priority') }}</td><td><span class="badge {{ $prioBadge }}">@enum($ticket->priority ?? 'medium', 'priority')</span></td></tr>
                    <tr><td>{{ __('public.adm_sup_show_kv_category') }}</td><td class="kv-strong">{{ ucfirst($ticket->category ?? 'General') }}</td></tr>
                    <tr><td>{{ __('public.adm_sup_show_kv_submitted_by') }}</td><td class="kv-strong">{{ $ticket->submittedBy?->name ?? $ticket->user?->name ?? 'Unknown' }}</td></tr>
                    <tr><td>{{ __('public.adm_sup_show_kv_assignee') }}</td><td class="kv-strong">{{ $ticket->assignee?->name ?? 'Unassigned' }}</td></tr>
                    <tr><td>{{ __('public.adm_sup_show_kv_created') }}</td><td class="kv-strong">{{ $ticket->created_at?->format('d M Y') }}</td></tr>
                    @if($ticket->resolved_at)
                    <tr><td>{{ __('public.adm_sup_show_kv_resolved') }}</td><td class="kv-strong">{{ $ticket->resolved_at?->format('d M Y') }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="user-check"></i> {{ __('public.adm_sup_show_panel_assign') }}</h3></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('portals.admin.support.assign', $ticket) }}">
                    @csrf @method('PATCH')
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sup_show_lbl_assign_to') }}</label>
                        <select name="assignee_id" class="form-control">
                            <option value="">{{ __('public.adm_sup_show_opt_unassigned') }}</option>
                            @foreach($staffUsers ?? [] as $staff)
                            <option value="{{ $staff->id }}" @selected(($ticket->assignee_id??null)==$staff->id)>{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('public.adm_sup_show_btn_update_assignee') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Confirm modals --}}
@if(!in_array($ticket->status??'',['closed','resolved']))
<div id="close-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="close-modal-title">
        <h3 class="modal__title" id="close-modal-title"><i data-lucide="x-circle"></i> {{ __('public.adm_sup_show_modal_close_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.support.close', $ticket) }}">
            @csrf @method('PATCH')
            <div class="modal__body"><p>{{ __('public.adm_sup_show_modal_close_body') }}</p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('close-modal')">{{ __('public.adm_sup_show_modal_close_btn_cancel') }}</button>
                <button type="submit" class="btn btn-warning">{{ __('public.adm_sup_show_modal_close_btn_close') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@if(in_array($ticket->status??'',['closed','resolved']))
<div id="reopen-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="reopen-modal-title">
        <h3 class="modal__title" id="reopen-modal-title"><i data-lucide="refresh-cw"></i> {{ __('public.adm_sup_show_modal_reopen_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.support.reopen', $ticket) }}">
            @csrf @method('PATCH')
            <div class="modal__body"><p>{{ __('public.adm_sup_show_modal_reopen_body') }}</p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('reopen-modal')">{{ __('public.adm_sup_show_modal_reopen_btn_cancel') }}</button>
                <button type="submit" class="btn btn-success">{{ __('public.adm_sup_show_modal_reopen_btn_reopen') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

<div id="delete-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <h3 class="modal__title" id="delete-modal-title"><i data-lucide="trash-2"></i> {{ __('public.adm_sup_show_modal_delete_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.support.destroy', $ticket) }}">
            @csrf @method('DELETE')
            <div class="modal__body"><p>{{ __('public.adm_sup_show_modal_delete_body') }}</p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-modal')">{{ __('public.adm_sup_show_modal_delete_btn_cancel') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('public.adm_sup_show_modal_delete_btn_delete') }}</button>
            </div>
        </form>
    </div>
</div>

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
