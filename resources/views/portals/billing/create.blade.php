@extends('layouts.portal')

@section('title', __('billing_desk.create_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php use App\Services\Billing\Xaf; @endphp

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div><h1 class="od-page__title">{{ __('billing_desk.create_title') }}</h1></div>
    <a href="{{ route('portals.billing.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.back_to_list') }}</a>
</div>

@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>
@endif

@if($patient === null)

    {{-- Step 1: exact Health ID only. No name search, no partial match —
         a professional must not be able to enumerate this platform's patients. --}}
    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('billing_desk.find_patient') }}</h2></header>
        <div class="od-card__body">
            @if($notFound)
                <div class="odc-note odc-note--err odc-note--inset" role="alert"><i data-lucide="user-x"></i><div>{{ __('billing_desk.not_found') }}</div></div>
            @endif
            <form method="GET" action="{{ route('portals.billing.create') }}" class="odc-form">
                <div class="odc-grid odc-grid--1">
                    <div class="odc-field">
                        <label class="odc-label odc-label--req" for="billing-health-id">{{ __('billing_desk.health_id') }}</label>
                        <input type="text" id="billing-health-id" name="health_id" class="od-input" value="{{ request('health_id') }}" required autocomplete="off" autofocus>
                        <p class="odc-hint">{{ __('billing_desk.health_id_hint') }}</p>
                    </div>
                </div>
                <div class="odc-actions">
                    <a href="{{ route('portals.billing.index') }}" class="od-btn od-btn--secondary">{{ __('billing_desk.cancel') }}</a>
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="search"></i> <span>{{ __('billing_desk.lookup') }}</span></button>
                </div>
            </form>
        </div>
    </section>

@else

    @php
        $pName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
        $pInit = mb_strtoupper(mb_substr(trim((string) ($patient->first_name ?? '')), 0, 1) . mb_substr(trim((string) ($patient->last_name ?? '')), 0, 1));
        $pTone = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($pName !== '' ? $pName : (string) $patient->id) % 6];
    @endphp

    <section class="od-card">
        <div class="od-card__head">
            <div class="odc-patient">
                <span class="od-avatar od-avatar--lg od-tint--{{ $pTone }}" aria-hidden="true">{{ $pInit !== '' ? $pInit : '—' }}</span>
                <div class="odc-patient__main">
                    <div class="od-detail__name">{{ $pName !== '' ? $pName : '—' }}</div>
                    <div class="od-detail__ref"><span>{{ $patient->health_id }}</span></div>
                </div>
            </div>
            <a href="{{ route('portals.billing.create') }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="user-cog"></i> {{ __('billing_desk.change_patient') }}</a>
        </div>
    </section>

    <form method="POST" action="{{ route('portals.billing.store') }}" class="odc-form">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">

        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('billing_desk.lines_title') }}</h2></header>
            <div class="od-card__body odc-form">
                <p class="odc-hint">{{ __('billing_desk.lines_hint') }}</p>

                @if($catalogue->isEmpty())
                    <div class="odc-note odc-note--warn odc-note--inset"><i data-lucide="info"></i><div>{{ __('billing_desk.catalogue_empty') }}</div></div>
                @endif

                <div class="od-table-wrap">
                    <table class="od-table">
                        <thead><tr>
                            <th class="od-table__num">#</th>
                            <th>{{ __('billing_desk.line_service') }}</th>
                            <th>{{ __('billing_desk.line_free') }}: {{ __('billing_desk.line_description') }}</th>
                            <th>{{ __('billing_desk.line_unit_price') }}</th>
                            <th>{{ __('billing_desk.line_quantity') }}</th>
                        </tr></thead>
                        <tbody>
                        @for($i = 0; $i < 8; $i++)
                            @php $old = old("lines.$i", []); @endphp
                            <tr>
                                <td class="od-table__num">{{ $i + 1 }}</td>
                                <td>
                                    <select name="lines[{{ $i }}][catalogue_item_id]" class="od-select" aria-label="{{ __('billing_desk.line_service') }}">
                                        <option value="">—</option>
                                        @foreach($catalogue as $item)
                                            <option value="{{ $item->id }}" @selected(($old['catalogue_item_id'] ?? '') === $item->id)>
                                                {{ $item->local_name ?: ($item->system.' '.$item->code) }} — {{ Xaf::format((int) $item->price_xaf) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="lines[{{ $i }}][description]" class="od-input" maxlength="250" value="{{ $old['description'] ?? '' }}" aria-label="{{ __('billing_desk.line_description') }}"></td>
                                <td><input type="text" inputmode="numeric" pattern="[0-9]*" name="lines[{{ $i }}][unit_price_xaf]" class="od-input" value="{{ $old['unit_price_xaf'] ?? '' }}" aria-label="{{ __('billing_desk.line_unit_price') }}"></td>
                                <td><input type="number" min="1" max="1000" step="1" name="lines[{{ $i }}][quantity]" class="od-input" value="{{ $old['quantity'] ?? 1 }}" aria-label="{{ __('billing_desk.line_quantity') }}"></td>
                            </tr>
                        @endfor
                        </tbody>
                    </table>
                </div>

                <label class="odc-check">
                    <input type="hidden" name="issue" value="0">
                    <input type="checkbox" name="issue" value="1" @checked(old('issue', '1') === '1')>
                    <span>{{ __('billing_desk.issue_now') }}</span>
                </label>
            </div>
        </section>

        <div class="odc-actions">
            <a href="{{ route('portals.billing.index') }}" class="od-btn od-btn--secondary">{{ __('billing_desk.cancel') }}</a>
            <button type="submit" class="od-btn od-btn--primary"><i data-lucide="file-check-2"></i> <span>{{ __('billing_desk.save_invoice') }}</span></button>
        </div>
    </form>

@endif

</div>
@endsection
