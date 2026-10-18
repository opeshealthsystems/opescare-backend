@extends('layouts.portal')
@section('title', ($lead->organization_name ?: $lead->name) . ' — ' . __('leads.admin.page_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('leads.admin.breadcrumb'))
@section('breadcrumb_home_url', route('portals.admin.leads'))
@section('breadcrumb_section', __('admin_desk.lead_detail'))
@section('content')

@php
$statusBadge = match($lead->status){'new'=>'badge-danger','contacted'=>'badge-warning','qualified'=>'badge-primary','won'=>'badge-success','converted'=>'badge-success','lost'=>'badge-neutral',default=>'badge-neutral'};
$canConvert = ! $lead->isConverted() && in_array($lead->organization_type, ['facility', 'lab', 'pharmacy', 'other', null], true);
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $lead->organization_name ?: $lead->name }}</h1>
        <p class="page-subtitle">
            <span class="badge {{ $statusBadge }}">{{ __('leads.statuses.'.$lead->status) }}</span>
            <span class="badge badge-neutral">{{ $lead->organization_type ? __('leads.org_types.'.$lead->organization_type) : '—' }}</span>
            <span class="badge badge-neutral">{{ $lead->source }}</span>
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.admin.leads') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('admin_desk.back_to_list') }}</a>
        @if($lead->isConverted())
        <a href="{{ route('portals.admin.leads.convert', $lead) }}" class="btn btn-secondary"><i data-lucide="building-2"></i> {{ __('leads.conversion.action_view_tenant') }}</a>
        @elseif($canConvert)
        <a href="{{ route('portals.admin.leads.convert', $lead) }}" class="btn btn-primary"><i data-lucide="building-2"></i> {{ __('leads.conversion.action_convert') }}</a>
        @endif
    </div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="adm-detail-grid">
<div>
    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="message-square"></i> {{ __('admin_desk.lead_message') }}</h3></div>
        <div class="panel-body adm-pre">{{ $lead->message ?: __('admin_desk.lead_no_message') }}</div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="notebook-pen"></i> {{ __('admin_desk.lead_notes') }}</h3></div>
        <div class="panel-body adm-pre">{{ $lead->notes ?: __('admin_desk.lead_no_notes') }}</div>
    </div>
</div>

<div>
    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="contact"></i> {{ __('leads.admin.col_contact') }}</h3></div>
        <div class="panel-body">
            <table class="kv-table">
                <tr><td>{{ __('admin_desk.name') }}</td><td class="kv-strong">{{ $lead->name }}</td></tr>
                <tr><td>{{ __('admin_desk.email') }}</td><td class="kv-strong">{{ $lead->email ?: '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.phone') }}</td><td class="kv-strong">{{ $lead->phone ?: '—' }}</td></tr>
                <tr><td>{{ __('leads.admin.col_date') }}</td><td class="kv-strong">{{ $lead->created_at?->isoFormat('lll') }}</td></tr>
                @if($tenant)
                <tr><td>{{ __('admin_desk.lead_tenant') }}</td><td class="kv-strong"><a href="{{ route('admin.facilities.show', $tenant->id) }}" class="link-action">{{ $tenant->name }}</a></td></tr>
                <tr><td>{{ __('admin_desk.lead_converted_at') }}</td><td class="kv-strong">{{ $lead->converted_at?->isoFormat('lll') ?? '—' }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    @unless($lead->isConverted())
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="pencil"></i> {{ __('leads.admin.modal_title') }}</h3></div>
        <div class="panel-body">
            <form method="POST" action="{{ route('portals.admin.leads.status', $lead) }}">
                @csrf
                <label class="form-label" for="status">{{ __('leads.admin.modal_status') }}</label>
                <select name="status" id="status" class="form-control mb-4">
                    @foreach(array_diff(\App\Models\Lead::STATUSES, ['converted']) as $s)
                    <option value="{{ $s }}" @selected($lead->status===$s)>{{ __('leads.statuses.'.$s) }}</option>
                    @endforeach
                </select>
                <label class="form-label" for="note">{{ __('leads.admin.modal_note') }}</label>
                <textarea name="note" id="note" rows="3" class="form-control" maxlength="5000" placeholder="{{ __('leads.admin.modal_note_ph') }}"></textarea>
                <div class="adm-mt-2"><button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('leads.admin.modal_save') }}</button></div>
            </form>
        </div>
    </div>
    @endunless
</div>
</div>

@endsection
