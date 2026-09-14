@extends('layouts.portal')

@section('title', __('billing_desk.create_title'))
@section('breadcrumb_home', __('billing_desk.title'))
@section('breadcrumb_home_url', route('portals.billing.index'))
@section('breadcrumb_section', __('billing_desk.create_title'))

@php use App\Services\Billing\Xaf; @endphp

@section('content')

<div class="page-header">
    <div><h1 class="page-title">{{ __('billing_desk.create_title') }}</h1></div>
    <a href="{{ route('portals.billing.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.back_to_list') }}</a>
</div>

@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

@if($errors->any())
<div class="alert alert-danger mb-6" role="alert">
    <i data-lucide="alert-circle"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
</div>
@endif

@if($patient === null)

    {{-- Step 1: exact Health ID only. No name search, no partial match —
         a professional must not be able to enumerate this platform's patients. --}}
    <div class="panel form-panel">
        <div class="panel-header"><h2 class="panel-title"><i data-lucide="search"></i> {{ __('billing_desk.find_patient') }}</h2></div>
        <div class="panel-body">
            @if($notFound)
                <div class="alert alert-danger mb-4" role="alert"><i data-lucide="user-x"></i><div>{{ __('billing_desk.not_found') }}</div></div>
            @endif
            <form method="GET" action="{{ route('portals.billing.create') }}">
                <div class="form-group mb-4">
                    <label class="form-label form-label-required" for="billing-health-id">{{ __('billing_desk.health_id') }}</label>
                    <input type="text" id="billing-health-id" name="health_id" class="form-control" value="{{ request('health_id') }}" required autocomplete="off" autofocus>
                    <div class="form-hint">{{ __('billing_desk.health_id_hint') }}</div>
                </div>
                <div class="row-actions-inline">
                    <button type="submit" class="btn btn-primary"><i data-lucide="search"></i> <span>{{ __('billing_desk.lookup') }}</span></button>
                    <a href="{{ route('portals.billing.index') }}" class="btn btn-secondary">{{ __('billing_desk.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

@else

    @php $pName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')); @endphp

    <div class="panel mb-6">
        <div class="panel-body row-actions-inline">
            <div style="flex:1">
                <span class="td-strong">{{ $pName !== '' ? $pName : '—' }}</span>
                <div class="td-muted">{{ $patient->health_id }}</div>
            </div>
            <a href="{{ route('portals.billing.create') }}" class="btn btn-secondary btn-sm"><i data-lucide="user-cog"></i> {{ __('billing_desk.change_patient') }}</a>
        </div>
    </div>

    <form method="POST" action="{{ route('portals.billing.store') }}">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">

        <div class="panel form-panel mb-6">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="list"></i> {{ __('billing_desk.lines_title') }}</h2></div>
            <div class="panel-body">
                <p class="form-hint mb-4">{{ __('billing_desk.lines_hint') }}</p>

                @if($catalogue->isEmpty())
                    <div class="alert alert-warning mb-4"><i data-lucide="info"></i><div>{{ __('billing_desk.catalogue_empty') }}</div></div>
                @endif

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('billing_desk.line_service') }}</th>
                                <th>{{ __('billing_desk.line_free') }}: {{ __('billing_desk.line_description') }}</th>
                                <th>{{ __('billing_desk.line_unit_price') }}</th>
                                <th>{{ __('billing_desk.line_quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @for($i = 0; $i < 8; $i++)
                            @php $old = old("lines.$i", []); @endphp
                            <tr>
                                <td data-label="{{ __('billing_desk.line_service') }}">
                                    <select name="lines[{{ $i }}][catalogue_item_id]" class="form-control" aria-label="{{ __('billing_desk.line_service') }}">
                                        <option value="">—</option>
                                        @foreach($catalogue as $item)
                                            <option value="{{ $item->id }}" @selected(($old['catalogue_item_id'] ?? '') === $item->id)>
                                                {{ $item->local_name ?: ($item->system.' '.$item->code) }} — {{ Xaf::format((int) $item->price_xaf) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td data-label="{{ __('billing_desk.line_description') }}">
                                    <input type="text" name="lines[{{ $i }}][description]" class="form-control" maxlength="250" value="{{ $old['description'] ?? '' }}" aria-label="{{ __('billing_desk.line_description') }}">
                                </td>
                                <td data-label="{{ __('billing_desk.line_unit_price') }}">
                                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="lines[{{ $i }}][unit_price_xaf]" class="form-control" value="{{ $old['unit_price_xaf'] ?? '' }}" aria-label="{{ __('billing_desk.line_unit_price') }}">
                                </td>
                                <td data-label="{{ __('billing_desk.line_quantity') }}">
                                    <input type="number" min="1" max="1000" step="1" name="lines[{{ $i }}][quantity]" class="form-control" value="{{ $old['quantity'] ?? 1 }}" aria-label="{{ __('billing_desk.line_quantity') }}">
                                </td>
                            </tr>
                        @endfor
                        </tbody>
                    </table>
                </div>

                <div class="form-group mt-4">
                    <label class="checkbox">
                        <input type="hidden" name="issue" value="0">
                        <input type="checkbox" name="issue" value="1" @checked(old('issue', '1') === '1')>
                        <span>{{ __('billing_desk.issue_now') }}</span>
                    </label>
                </div>

                <div class="row-actions-inline mt-4">
                    <button type="submit" class="btn btn-primary"><i data-lucide="file-check-2"></i> <span>{{ __('billing_desk.save_invoice') }}</span></button>
                    <a href="{{ route('portals.billing.index') }}" class="btn btn-secondary">{{ __('billing_desk.cancel') }}</a>
                </div>
            </div>
        </div>
    </form>

@endif

@endsection
