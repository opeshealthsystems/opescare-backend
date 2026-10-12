@extends('layouts.portal')

@section('title', __('billing_desk.session.title'))
@section('breadcrumb_home', __('billing_desk.title'))
@section('breadcrumb_home_url', route('portals.billing.index'))
@section('breadcrumb_section', __('billing_desk.session.title'))

@php use App\Services\Billing\Xaf; @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('billing_desk.session.title') }}</h1>
        <p class="page-subtitle">{{ __('billing_desk.session.subtitle') }}</p>
    </div>
    <a href="{{ route('portals.billing.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.back_to_list') }}</a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="alert alert-danger mb-6" role="alert"><i data-lucide="alert-circle"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
</div>
@endif

@if($open === null)
<div class="panel form-panel mb-6">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="wallet"></i> {{ __('billing_desk.session.open_title') }}</h2></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.billing.sessions.open') }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="session-float">{{ __('billing_desk.session.float_label') }}</label>
                <input type="text" inputmode="numeric" pattern="[0-9]*" id="session-float" name="float_xaf" class="form-control" value="{{ old('float_xaf', '0') }}" required>
                <div class="form-hint">{{ __('billing_desk.session.float_hint') }}</div>
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="unlock"></i> {{ __('billing_desk.session.open_button') }}</button>
        </form>
    </div>
</div>
@else
@php $float = Xaf::int($open->opening_float_amount); @endphp
<div class="panel form-panel mb-6">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="wallet"></i> {{ __('billing_desk.session.current_title') }}</h2>
        <span class="badge badge-success">{{ __('billing_desk.session.status_open') }}</span></div>
    <div class="panel-body">
        <div class="table-wrapper mb-4">
            <table class="data-table">
                <tr><td class="td-muted">{{ __('billing_desk.session.opened_at') }}</td><td>{{ $open->opened_at?->translatedFormat('d M Y H:i') }}</td></tr>
                <tr><td class="td-muted">{{ __('billing_desk.session.float') }}</td><td>{{ Xaf::format($float) }}</td></tr>
                <tr><td class="td-muted">{{ __('billing_desk.session.cash_taken') }}</td><td>{{ Xaf::format($cashSoFar) }}</td></tr>
                <tr><td class="td-muted">{{ __('billing_desk.session.expected') }}</td><td class="td-strong">{{ Xaf::format($float + $cashSoFar) }}</td></tr>
            </table>
        </div>
        <form method="POST" action="{{ route('portals.billing.sessions.close', $open->id) }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="session-counted">{{ __('billing_desk.session.counted_label') }}</label>
                <input type="text" inputmode="numeric" pattern="[0-9]*" id="session-counted" name="counted_xaf" class="form-control" value="{{ old('counted_xaf') }}" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="session-note">{{ __('billing_desk.session.note_label') }}</label>
                <input type="text" id="session-note" name="note" class="form-control" maxlength="500" value="{{ old('note') }}">
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="lock"></i> {{ __('billing_desk.session.close_button') }}</button>
        </form>
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="history"></i> {{ __('billing_desk.session.history_title') }}</h2></div>
    @if($sessions->isEmpty())
        <div class="panel-body td-muted">{{ __('billing_desk.session.empty') }}</div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('billing_desk.session.col_cashier') }}</th>
                    <th>{{ __('billing_desk.col_status') }}</th>
                    <th>{{ __('billing_desk.session.col_opened') }}</th>
                    <th>{{ __('billing_desk.session.col_closed') }}</th>
                    <th>{{ __('billing_desk.session.col_float') }}</th>
                    <th>{{ __('billing_desk.session.col_cash') }}</th>
                    <th>{{ __('billing_desk.session.col_expected') }}</th>
                    <th>{{ __('billing_desk.session.col_counted') }}</th>
                    <th>{{ __('billing_desk.session.col_variance') }}</th>
                </tr></thead>
                <tbody>
                @foreach($sessions as $s)
                    @php $variance = $s->variance_amount === null ? null : Xaf::int($s->variance_amount); @endphp
                    <tr>
                        <td data-label="{{ __('billing_desk.session.col_cashier') }}">{{ $cashiers[$s->cashier_id]->name ?? '—' }}</td>
                        <td data-label="{{ __('billing_desk.col_status') }}"><span class="badge badge-{{ $s->status === 'open' ? 'success' : 'neutral' }}">{{ $s->status === 'open' ? __('billing_desk.session.status_open') : __('billing_desk.session.status_closed') }}</span></td>
                        <td data-label="{{ __('billing_desk.session.col_opened') }}" class="td-muted">{{ $s->opened_at?->translatedFormat('d M Y H:i') }}</td>
                        <td data-label="{{ __('billing_desk.session.col_closed') }}" class="td-muted">{{ $s->closed_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('billing_desk.session.col_float') }}">{{ Xaf::format(Xaf::int($s->opening_float_amount)) }}</td>
                        <td data-label="{{ __('billing_desk.session.col_cash') }}">{{ Xaf::format(Xaf::int($s->cash_total_amount)) }}</td>
                        <td data-label="{{ __('billing_desk.session.col_expected') }}">{{ $s->expected_cash_amount === null ? '—' : Xaf::format(Xaf::int($s->expected_cash_amount)) }}</td>
                        <td data-label="{{ __('billing_desk.session.col_counted') }}">{{ $s->counted_cash_amount === null ? '—' : Xaf::format(Xaf::int($s->counted_cash_amount)) }}</td>
                        <td data-label="{{ __('billing_desk.session.col_variance') }}">
                            @if($variance === null) — @else
                                <span class="badge badge-{{ $variance === 0 ? 'success' : 'danger' }}">{{ Xaf::format($variance) }}</span>
                            @endif
                            @if($s->closing_note)<div class="td-muted">{{ $s->closing_note }}</div>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())<div class="panel-body">{{ $sessions->links() }}</div>@endif
    @endif
</div>

@endsection
