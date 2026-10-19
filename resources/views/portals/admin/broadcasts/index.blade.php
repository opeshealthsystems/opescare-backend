@extends('layouts.portal')

@section('title', __('broadcasts.title') . ' — OpesCare')

@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@php $l = app()->getLocale(); @endphp

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('broadcasts.title') }}</h1>
        <p class="od-page__sub">{{ __('broadcasts.subtitle') }}</p>
    </div>
    <div class="od-row">
        <button type="button" class="od-btn od-btn--primary" data-toggle-hidden="new-bc">
            <i data-lucide="megaphone"></i> {{ __('broadcasts.new') }}
        </button>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="od-card" id="new-bc" {{ $errors->any() ? '' : 'hidden' }}>
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="megaphone"></i> {{ __('broadcasts.new') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.admin.broadcasts.store') }}" class="oda-form">
            @csrf
            <label class="oda-label oda-form__full" for="title"><span class="oda-label__text">{{ __('broadcasts.field_title') }}</span>
                <input type="text" id="title" name="title" class="od-input" maxlength="160" value="{{ old('title') }}" required></label>
            <label class="oda-label" for="broadcast_type"><span class="oda-label__text">{{ __('broadcasts.field_type') }}</span>
                <select id="broadcast_type" name="broadcast_type" class="od-select" required>
                    @foreach($types as $t)<option value="{{ $t }}" @selected(old('broadcast_type')===$t)>{{ __('broadcasts.type_' . $t) }}</option>@endforeach
                </select></label>
            <label class="oda-label" for="target_type"><span class="oda-label__text">{{ __('broadcasts.field_audience') }}</span>
                <select id="target_type" name="target_type" class="od-select" required>
                    @foreach($targetTypes as $tt)<option value="{{ $tt }}" @selected(old('target_type')===$tt)>{{ __('broadcasts.audience_' . $tt) }}</option>@endforeach
                </select></label>
            {{-- Finder: name the facilities (audience facility_staff) or roles (audience role). --}}
            <label class="oda-label" for="facility_ids"><span class="oda-label__text">{{ __('broadcasts.field_facilities') }}</span>
                <select id="facility_ids" name="facility_ids[]" class="od-select oda-gd-multi" multiple size="4">
                    @foreach($facilities as $f)<option value="{{ $f->id }}" @selected(in_array($f->id, old('facility_ids', []), true))>{{ $f->name }}</option>@endforeach
                </select></label>
            <label class="oda-label" for="role_names"><span class="oda-label__text">{{ __('broadcasts.field_roles') }}</span>
                <select id="role_names" name="role_names[]" class="od-select oda-gd-multi" multiple size="4">
                    @foreach($roles as $r)<option value="{{ $r }}" @selected(in_array($r, old('role_names', []), true))>{{ $r }}</option>@endforeach
                </select></label>
            <label class="oda-label" for="publish_at"><span class="oda-label__text">{{ __('broadcasts.field_publish_at') }}</span>
                <input type="datetime-local" id="publish_at" name="publish_at" class="od-input" value="{{ old('publish_at') }}"></label>
            <label class="oda-label" for="priority"><span class="oda-label__text">{{ __('broadcasts.field_priority') }}</span>
                <select id="priority" name="priority" class="od-select">
                    @foreach($priorities as $p)<option value="{{ $p }}" @selected(old('priority')===$p)>@enum($p, 'priority')</option>@endforeach
                </select></label>
            <label class="oda-label" for="language"><span class="oda-label__text">{{ __('broadcasts.field_language') }}</span>
                <select id="language" name="language" class="od-select">
                    <option value="en" @selected(old('language')==='en')>{{ __('broadcasts.lang_en') }}</option>
                    <option value="fr" @selected(old('language')==='fr')>{{ __('broadcasts.lang_fr') }}</option>
                </select></label>
            <label class="oda-label" for="expires_at"><span class="oda-label__text">{{ __('broadcasts.field_expires') }}</span>
                <input type="datetime-local" id="expires_at" name="expires_at" class="od-input" value="{{ old('expires_at') }}"></label>
            <label class="oda-label oda-form__full" for="body"><span class="oda-label__text">{{ __('broadcasts.field_body') }}</span>
                <textarea id="body" name="body" class="oda-textarea" rows="4" maxlength="5000" required>{{ old('body') }}</textarea></label>
            <div class="oda-form__full od-row">
                <label class="oda-check"><input type="checkbox" name="requires_acknowledgement" value="1"> {{ __('broadcasts.field_ack') }}</label>
                <label class="oda-check"><input type="checkbox" name="publish_now" value="1"> {{ __('broadcasts.field_publish_now') }}</label>
            </div>
            <div class="oda-form__actions"><button type="submit" class="od-btn od-btn--primary"><i data-lucide="send"></i> {{ __('broadcasts.save') }}</button></div>
        </form>
    </div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="radio"></i> {{ __('broadcasts.title') }}</h2>
        <span class="od-card__count">{{ method_exists($items, 'total') ? $items->total() : $items->count() }}</span>
        <form method="GET" class="od-card__actions"><select name="status" class="od-select" data-autosubmit aria-label="{{ __('broadcasts.col_status') }}">
            <option value="">{{ __('broadcasts.all_statuses') }}</option>
            @foreach(['draft','published','cancelled'] as $st)<option value="{{ $st }}" @selected($status===$st)>@enum($st)</option>@endforeach
        </select></form>
    </div>
    @if($items->isEmpty())
        <div class="od-card__body"><div class="od-empty"><span class="od-empty__icon"><i data-lucide="megaphone"></i></span><h3 class="od-empty__title">{{ __('broadcasts.empty_title') }}</h3><p class="od-empty__text">{{ __('broadcasts.empty_body') }}</p></div></div>
    @else
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('broadcasts.field_title') }}</th><th>{{ __('broadcasts.field_audience') }}</th>
                    <th>{{ __('broadcasts.field_type') }}</th><th>{{ __('broadcasts.col_status') }}</th>
                    <th>{{ __('broadcasts.col_acks') }}</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach($items as $bc)
                    <tr>
                        <td data-label="{{ __('broadcasts.field_title') }}"><span class="od-table__name">{{ $bc->title }}</span>
                            @if(($bc->priority ?? 'normal')!=='normal')<span class="od-pill od-pill--pending">@enum($bc->priority, 'priority')</span>@endif</td>
                        <td data-label="{{ __('broadcasts.field_audience') }}"><span class="od-table__sub">{{ __('broadcasts.audience_' . $bc->target_type) }}</span></td>
                        <td data-label="{{ __('broadcasts.field_type') }}"><span class="od-pill od-pill--neutral">{{ __('broadcasts.type_' . $bc->broadcast_type) }}</span></td>
                        <td data-label="{{ __('broadcasts.col_status') }}">
                            @php $sc = ['published'=>'ready','cancelled'=>'neutral','expired'=>'neutral'][$bc->status] ?? 'pending'; @endphp
                            <span class="od-pill od-pill--{{ $sc }}">@enum($bc->status)</span></td>
                        <td data-label="{{ __('broadcasts.col_acks') }}">{{ $bc->requires_acknowledgement ? $bc->acknowledgements_count : '—' }}</td>
                        <td>
                            <div class="od-table__actions">
                                <a href="{{ route('portals.admin.broadcasts.show', $bc->uuid) }}" class="od-btn od-btn--row"><i data-lucide="list-checks"></i> {{ __('broadcasts.view_acks') }}</a>
                                @if($bc->status === 'draft')
                                <form method="POST" action="{{ route('portals.admin.broadcasts.publish', $bc->uuid) }}" class="oda-inline-form">@csrf
                                    <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="send"></i> {{ __('broadcasts.publish') }}</button></form>
                                @endif
                                @if(in_array($bc->status, ['draft','published']))
                                <form method="POST" action="{{ route('portals.admin.broadcasts.cancel', $bc->uuid) }}" data-confirm="{{ __('broadcasts.cancel_confirm') }}" class="oda-inline-form">@csrf
                                    <button type="submit" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="x-circle"></i> {{ __('broadcasts.cancel') }}</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="od-card__foot">{{ $items->links() }}</div>
    @endif
</div>

</div>
@endsection
