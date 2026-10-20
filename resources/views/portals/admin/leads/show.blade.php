@extends('layouts.portal')
@section('title', ($lead->organization_name ?: $lead->name) . ' — ' . __('leads.admin.page_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@section('content')

@php
$statusBadge = match($lead->status){'new'=>'urgent','contacted'=>'pending','qualified'=>'normal','won'=>'ready','converted'=>'ready','lost'=>'neutral',default=>'neutral'};
$canConvert = ! $lead->isConverted() && in_array($lead->organization_type, ['facility', 'lab', 'pharmacy', 'other', null], true);
$gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
$gcLabel = $lead->organization_name ?: $lead->name;
$gcTone = $gcTones[crc32((string) $gcLabel) % count($gcTones)];
$gcInit = mb_strtoupper(collect(preg_split('/\s+/', trim((string) $gcLabel)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
@endphp
<div class="od-page">

<div class="od-card od-detail oda-gc-detail">
    <div class="od-detail__head">
        <span class="od-avatar od-avatar--lg od-tint--{{ $gcTone }}" aria-hidden="true">{{ $gcInit }}</span>
        <div class="od-detail__main">
            <h1 class="od-detail__name">{{ $lead->organization_name ?: $lead->name }}</h1>
            <div class="od-row">
                <span class="od-pill od-pill--dot od-pill--{{ $statusBadge }}">{{ __('leads.statuses.'.$lead->status) }}</span>
                <span class="od-pill od-pill--neutral">{{ $lead->organization_type ? __('leads.org_types.'.$lead->organization_type) : '—' }}</span>
                <span class="od-pill od-pill--neutral">{{ $lead->source }}</span>
                @if($lead->source === \App\Models\Lead::SOURCE_SOLUTIONS)
                @if($lead->request_kind)<span class="od-pill od-pill--{{ $lead->request_kind === 'donation' ? 'ready' : 'normal' }}">{{ __('solutions.admin.kind_'.$lead->request_kind) }}</span>@endif
                <span class="od-pill od-pill--neutral">{{ $lead->solution_slug ? __('solutions.systems.'.$lead->solution_slug.'.name') : __('solutions.admin.system_any') }}</span>
                @if($lead->facility_type)<span class="od-pill od-pill--neutral">{{ __('solutions.request.types.'.$lead->facility_type) }}</span>@endif
                @if($lead->city)<span class="od-pill od-pill--neutral">{{ $lead->city }}</span>@endif
                @if($lead->has_system !== null)<span class="od-pill od-pill--neutral">{{ $lead->has_system ? __('solutions.admin.has_system_yes') : __('solutions.admin.has_system_no') }}</span>@endif
                @endif
            </div>
        </div>
        <div class="od-row">
            <a href="{{ route('portals.admin.leads') }}" class="od-btn od-btn--ghost"><i data-lucide="arrow-left"></i> {{ __('admin_desk.back_to_list') }}</a>
            @if($lead->isConverted())
            <a href="{{ route('portals.admin.leads.convert', $lead) }}" class="od-btn od-btn--secondary"><i data-lucide="building-2"></i> {{ __('leads.conversion.action_view_tenant') }}</a>
            @elseif($canConvert)
            <a href="{{ route('portals.admin.leads.convert', $lead) }}" class="od-btn od-btn--primary"><i data-lucide="building-2"></i> {{ __('leads.conversion.action_convert') }}</a>
            @endif
        </div>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="od-grid od-grid--main">
<div class="od-stack">
    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="message-square"></i> {{ __('admin_desk.lead_message') }}</h2></div>
        <div class="od-card__body oda-gc-prewrap">{{ $lead->message ?: __('admin_desk.lead_no_message') }}</div>
    </section>

    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="notebook-pen"></i> {{ __('admin_desk.lead_notes') }}</h2></div>
        <div class="od-card__body oda-gc-prewrap">{{ $lead->notes ?: __('admin_desk.lead_no_notes') }}</div>
    </section>
</div>

<div class="od-stack">
    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="contact"></i> {{ __('leads.admin.col_contact') }}</h2></div>
        <div class="od-card__body">
            <dl class="oda-dl">
                <dt>{{ __('admin_desk.name') }}</dt><dd>{{ $lead->name }}</dd>
                <dt>{{ __('admin_desk.email') }}</dt><dd>{{ $lead->email ?: '—' }}</dd>
                <dt>{{ __('admin_desk.phone') }}</dt><dd>{{ $lead->phone ?: '—' }}</dd>
                <dt>{{ __('leads.admin.col_date') }}</dt><dd>{{ $lead->created_at?->isoFormat('lll') }}</dd>
                @if($tenant)
                <dt>{{ __('admin_desk.lead_tenant') }}</dt><dd><a href="{{ route('admin.facilities.show', $tenant->id) }}">{{ $tenant->name }}</a></dd>
                <dt>{{ __('admin_desk.lead_converted_at') }}</dt><dd>{{ $lead->converted_at?->isoFormat('lll') ?? '—' }}</dd>
                @endif
            </dl>
        </div>
    </section>

    @unless($lead->isConverted())
    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="pencil"></i> {{ __('leads.admin.modal_title') }}</h2></div>
        <div class="od-card__body">
            <form method="POST" action="{{ route('portals.admin.leads.status', $lead) }}" class="od-stack">
                @csrf
                <label class="oda-label" for="status">
                    <span class="oda-label__text">{{ __('leads.admin.modal_status') }}</span>
                    <select name="status" id="status" class="od-select">
                        @foreach(array_diff(\App\Models\Lead::STATUSES, ['converted']) as $s)
                        <option value="{{ $s }}" @selected($lead->status===$s)>{{ __('leads.statuses.'.$s) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="oda-label" for="note">
                    <span class="oda-label__text">{{ __('leads.admin.modal_note') }}</span>
                    <textarea name="note" id="note" rows="3" class="oda-textarea" maxlength="5000" placeholder="{{ __('leads.admin.modal_note_ph') }}"></textarea>
                </label>
                <div class="oda-form__actions"><button type="submit" class="od-btn od-btn--primary"><i data-lucide="save"></i> {{ __('leads.admin.modal_save') }}</button></div>
            </form>
        </div>
    </section>
    @endunless
</div>
</div>

</div>
@endsection
