@extends('layouts.portal')
@section('title', $certification->integration_name . ' — Certification')
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.certifications.index') }}">{{ __('public.adm_cert_show_breadcrumb_parent') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $certification->integration_name }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="shield-check"></i></div>
    <div>
        <h2 class="entity-head__title">{{ $certification->integration_name }}</h2>
        <div class="entity-head__sub">
            <span class="badge {{ $certification->statusBadgeClass() }} badge-sm">@enum($certification->status)</span>
            <span class="badge badge--info badge-sm">{{ strtoupper($certification->integration_type) }}</span>
            @if($certification->certification_level)
            <span class="badge {{ $certification->levelBadgeClass() }} badge-sm">{{ ucfirst($certification->certification_level) }}</span>
            @endif
        </div>
    </div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="grid-2">

    {{-- Left column: details + badge --}}
    <div>
        {{-- Details --}}
        <div class="panel mb-6">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="info"></i> {{ __('public.adm_cert_show_panel_details') }}</h3></div>
            <div class="panel-body">
                <table class="kv-table">
                    <tr><td>{{ __('public.adm_cert_show_kv_vendor') }}</td><td class="kv-strong">{{ $certification->vendor_name ?? '—' }}</td></tr>
                    <tr><td>{{ __('public.adm_cert_show_kv_contact') }}</td><td class="kv-strong">{{ $certification->vendor_contact ?? '—' }}</td></tr>
                    <tr><td>{{ __('public.adm_cert_show_kv_version') }}</td><td class="kv-strong">{{ $certification->version ?? '—' }}</td></tr>
                    <tr><td>{{ __('public.adm_cert_show_kv_submitted') }}</td><td class="kv-strong">{{ $certification->submitted_at?->format('d M Y') ?? '—' }}</td></tr>
                    <tr><td>{{ __('public.adm_cert_show_kv_certified') }}</td><td class="kv-strong">{{ $certification->certified_at?->format('d M Y') ?? '—' }}</td></tr>
                    <tr><td>{{ __('public.adm_cert_show_kv_expires') }}</td><td class="kv-strong">
                        {{ $certification->expires_at?->format('d M Y') ?? __('public.adm_cert_show_kv_no_expiry') }}
                        @if($certification->isExpiringSoon())
                            <span class="badge badge-warning badge-sm"><i data-lucide="alert-triangle"></i> {{ __('public.adm_cert_show_kv_expiring_soon') }}</span>
                        @endif
                    </td></tr>
                </table>
                @if($certification->scope_description)
                <div class="detail-divider">
                    <div class="kv-strong mb-3">{{ __('public.adm_cert_show_kv_scope') }}</div>
                    <div class="td-muted">{{ $certification->scope_description }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Badge --}}
        @if($certification->badge)
        <div class="panel">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="award"></i> {{ __('public.adm_cert_show_panel_active_badge') }}</h3></div>
            <div class="panel-body">
                <div class="badge-showcase">
                    <i data-lucide="{{ $certification->badge->levelIcon() }}" class="badge-showcase__icon" style="color: {{ $certification->badge->levelColor() }};"></i>
                    <div class="badge-showcase__level">{{ ucfirst($certification->badge->certification_level) }}</div>
                    <div class="mono code-token">{{ $certification->badge->badge_code }}</div>
                    <div class="td-muted text-sm">
                        {{ __('public.adm_cert_show_badge_issued') }} {{ $certification->badge->issued_at->format('d M Y') }}
                        @if($certification->badge->expires_at) {{ __('public.adm_cert_show_badge_expires') }} {{ $certification->badge->expires_at->format('d M Y') }} @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('portals.admin.certifications.badge.revoke', $certification->badge) }}"
                      class="mt-6" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('public.adm_cert_show_js_confirm_revoke')) }})">
                    @csrf
                    <div class="form-group mb-3">
                        <input type="text" name="revoke_reason" class="form-control" placeholder="{{ __('public.adm_cert_show_ph_revoke_reason') }}" required>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block">{{ __('public.adm_cert_show_btn_revoke') }}</button>
                </form>
            </div>
        </div>
        @elseif($certification->isPassed())
        <div class="panel">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="award"></i> {{ __('public.adm_cert_show_panel_issue_badge') }}</h3></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('portals.admin.certifications.badge.issue', $certification) }}">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('public.adm_cert_show_lbl_level') }}</label>
                        <select name="certification_level" class="form-control" required>
                            <option value="bronze">@enum('bronze', 'level')</option>
                            <option value="silver">@enum('silver', 'level')</option>
                            <option value="gold">@enum('gold', 'level')</option>
                            <option value="platinum">@enum('platinum', 'level')</option>
                        </select>
                    </div>
                    <div class="form-group mb-4">
                        <label class="form-label">{{ __('public.adm_cert_show_lbl_valid_months') }}</label>
                        <input type="number" name="expires_months" value="12" min="1" max="36" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">{{ __('public.adm_cert_show_btn_issue') }}</button>
                </form>
            </div>
        </div>
        @endif
    </div>

    {{-- Right column: test runs + record new run --}}
    <div>
        {{-- Record test run --}}
        <div class="panel mb-6">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="clipboard-check"></i> {{ __('public.adm_cert_show_panel_record_run') }}</h3></div>
            <div class="panel-body">
                @if($requirements->isEmpty())
                <p class="td-muted">
                    {{ __('public.adm_cert_show_no_requirements') }}
                    <a href="{{ route('portals.admin.certifications.seed') }}" onclick="this.closest('form')?.submit();return false;">{{ __('public.adm_cert_show_seed_link') }}</a> {{ __('public.adm_cert_show_seed_first') }}
                </p>
                @else
                <form method="POST" action="{{ route('portals.admin.certifications.test_run', $certification) }}">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('public.adm_cert_show_lbl_run_label') }}</label>
                        <input type="text" name="run_label" class="form-control" placeholder="{{ __('public.adm_cert_show_ph_run_label') }}">
                    </div>
                    <div class="scroll-table mb-3">
                        <table class="data-table">
                            <thead><tr>
                                <th>{{ __('public.adm_cert_show_col_requirement') }}</th>
                                <th>{{ __('public.adm_cert_show_col_result') }}</th>
                            </tr></thead>
                            <tbody>
                            @foreach($requirements as $i => $req)
                            <tr>
                                <td data-label="{{ __('public.adm_cert_show_col_requirement') }}">
                                    <input type="hidden" name="results[{{ $i }}][requirement_id]" value="{{ $req->id }}">
                                    <span class="td-strong">{{ $req->name }}</span>
                                    <span class="badge {{ $req->severityBadgeClass() }} badge-sm">{{ $req->severity }}</span>
                                </td>
                                <td data-label="{{ __('public.adm_cert_show_col_result') }}">
                                    <select name="results[{{ $i }}][result]" class="form-control">
                                        <option value="passed">{{ __('public.adm_cert_show_opt_pass') }}</option>
                                        <option value="failed">{{ __('public.adm_cert_show_opt_fail') }}</option>
                                        <option value="skipped">{{ __('public.adm_cert_show_opt_skip') }}</option>
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('public.adm_cert_show_lbl_run_notes') }}</label>
                        <textarea name="run_notes" rows="2" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">{{ __('public.adm_cert_show_btn_record') }}</button>
                </form>
                @endif
            </div>
        </div>

        {{-- Test run history --}}
        @if($certification->testRuns->isNotEmpty())
        <div class="panel">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> {{ __('public.adm_cert_show_panel_history') }}</h3></div>
            <div class="panel-body panel-body--flush">
                <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('public.adm_cert_show_col_run') }}</th><th>{{ __('public.adm_cert_show_col_pass_rate') }}</th><th>{{ __('public.adm_cert_show_col_status') }}</th><th>{{ __('public.adm_cert_show_col_date') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($certification->testRuns->sortByDesc('started_at') as $run)
                    <tr>
                        <td data-label="{{ __('public.adm_cert_show_col_run') }}">{{ $run->run_label ?: __('public.adm_cert_show_run_default') }}</td>
                        <td data-label="{{ __('public.adm_cert_show_col_pass_rate') }}">
                            <span class="badge {{ $run->isPassed() ? 'badge-success' : 'badge-danger' }} badge-sm">{{ $run->passRate() }}%</span>
                            <span class="td-muted code-muted">({{ $run->passed_count }}/{{ $run->total_requirements }})</span>
                        </td>
                        <td data-label="{{ __('public.adm_cert_show_col_status') }}"><span class="badge {{ $run->statusBadgeClass() }} badge-sm">@enum($run->status)</span></td>
                        <td data-label="{{ __('public.adm_cert_show_col_date') }}" class="td-muted">{{ $run->started_at?->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        @endif
    </div>

</div>

@endsection
