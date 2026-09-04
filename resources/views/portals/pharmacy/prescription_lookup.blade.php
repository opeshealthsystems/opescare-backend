@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('prescriber.counter_title', [], $l) ?: 'Dispense at the counter')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('prescriber.counter_title', [], $l) ?: 'Dispense at the counter' }}</h1>
        <p class="page-subtitle">{{ __('prescriber.counter_sub', [], $l) ?: 'Enter the Health ID the patient presents to see their active prescriptions.' }}</p>
    </div>
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-secondary">
        <i data-lucide="list"></i>
        <span>{{ __('prescriber.title', [], $l) ?: 'Prescriptions' }}</span>
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Health ID lookup — exact match only; patient names are never searchable. --}}
<div class="panel">
    <div class="panel-body">
        <form method="GET" action="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="form-row">
            <div class="form-group" style="flex:1 1 260px">
                <label class="form-label" for="health_id">{{ __('prescriber.health_id', [], $l) ?: 'Health ID' }}</label>
                <input type="text" id="health_id" name="health_id" class="form-control"
                       value="{{ $healthId }}" autocomplete="off" required
                       placeholder="CM-HID-XXXX-XXXX-XXXX">
                <p class="form-hint">{{ __('prescriber.health_id_hint', [], $l) ?: 'Enter the exact Health ID — patient names are not searchable.' }}</p>
            </div>
            <div class="form-group" style="align-self:flex-start">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="search"></i>
                    <span>{{ __('prescriber.counter_lookup', [], $l) ?: 'Find prescriptions' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

@if($searched && ! $patient)
    <div class="alert alert-danger">{{ __('prescriber.counter_nopat', [], $l) ?: 'No patient matches that Health ID.' }}</div>
@endif

@if($patient)
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">{{ $patient->first_name }} {{ $patient->last_name }}</div>
            <span class="badge badge-neutral">{{ $patient->health_id }}</span>
        </div>

        @if($results->isEmpty())
            <div class="panel-body">
                <div class="empty-state">
                    <i data-lucide="file-x" style="width:32px;height:32px"></i>
                    <p>{{ __('prescriber.counter_none', [], $l) ?: 'No active prescriptions on that Health ID.' }}</p>
                </div>
            </div>
        @else
            @foreach($results as $rx)
                <div class="panel-body" style="border-top:1px solid var(--p-border,#e5e7eb)">
                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
                        <div style="min-width:0">
                            <div style="font-weight:600">
                                {{ optional($rx->prescribed_at)->format('d M Y') ?: '—' }}
                                <span class="badge badge-info" style="margin-left:6px">
                                    {{ __('prescriber.st_' . $rx->status, [], $l) ?: $rx->status }}
                                </span>
                            </div>
                            <div class="td-muted" style="font-size:.85rem">
                                {{ __('prescriber.counter_from', [], $l) ?: 'Issued at' }}:
                                {{ $rx->facility->name ?? '—' }}
                            </div>
                            @if($rx->notes)
                                <div class="td-muted" style="font-size:.85rem">{{ $rx->notes }}</div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('portals.pharmacy.dispense', $rx->id) }}"
                              style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
                            @csrf
                            <input type="text" name="dispense_notes" class="form-control" style="min-width:180px"
                                   placeholder="{{ __('prescriber.counter_notes', [], $l) ?: 'Dispense notes' }}">
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="check"></i>
                                <span>{{ __('prescriber.counter_dispense', [], $l) ?: 'Dispense' }}</span>
                            </button>
                        </form>
                    </div>

                    <div class="table-wrapper" style="margin-top:10px">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>{{ __('prescriber.drug_name', [], $l) ?: 'Medicine' }}</th>
                                    <th>{{ __('prescriber.dose', [], $l) ?: 'Dose' }}</th>
                                    <th>{{ __('prescriber.frequency', [], $l) ?: 'Frequency' }}</th>
                                    <th>{{ __('prescriber.quantity', [], $l) ?: 'Quantity' }}</th>
                                    <th>{{ __('prescriber.item_status', [], $l) ?: 'Line status' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rx->items as $item)
                                    <tr>
                                        <td class="td-strong">{{ $item->drug_name }}</td>
                                        <td>{{ $item->dose ?: '—' }}</td>
                                        <td>{{ $item->frequency ?: '—' }}</td>
                                        <td>{{ $item->quantity ?: '—' }}</td>
                                        <td>
                                            <span class="badge {{ $item->status === 'dispensed' ? 'badge-success' : 'badge-neutral' }}">
                                                {{ __('prescriber.st_' . $item->status, [], $l) ?: $item->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endif
@endsection
