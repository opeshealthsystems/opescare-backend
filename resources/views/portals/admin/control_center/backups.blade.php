@extends('layouts.portal')
@section('title', __('backups.title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_hlth_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('backups.title'))

@section('content')
<style>
    .bk-cmd { background:#0b1f33; color:#e6eef6; border-radius:8px; padding:12px 14px; margin:8px 0 4px;
              font:13px/1.55 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; overflow-x:auto; white-space:pre; }
    .bk-steps { list-style:none; margin:0; padding:0; display:grid; gap:18px; }
    .bk-steps > li { display:grid; grid-template-columns:32px 1fr; gap:12px; }
    .bk-num { width:28px; height:28px; border-radius:50%; background:#0F4C81; color:#fff;
              display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; }
    .bk-steps h3 { margin:2px 0 4px; font-size:15px; }
    .bk-steps p { margin:0 0 4px; max-width:72ch; }
    .bk-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px; }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('backups.title') }}</h1>
        <p class="page-subtitle">{{ __('backups.subtitle') }}</p>
    </div>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.admin.cc.backups') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="refresh-cw"></i> {{ __('backups.refresh') }}
    </a>
</div>

@php
    $state = $status['state'];
    $verdict = match ($state) {
        'off_host'      => ['alert-success', 'shield-check'],
        'configured'    => ['alert-info',    'clock'],
        'misconfigured' => ['alert-danger',  'triangle-alert'],
        default         => ['alert-warning', 'hard-drive'],
    };
    $when = fn (?int $ts) => $ts ? \Illuminate\Support\Carbon::createFromTimestamp($ts)->timezone(config('app.timezone'))->isoFormat('D MMM YYYY, HH:mm') : null;
    $mb = fn (?int $bytes) => $bytes === null ? null : number_format($bytes / 1048576, 1) . ' MB';
    $staleLocal = $status['local']['latest_at']
        && (now()->timestamp - $status['local']['latest_at']) > \App\Support\BackupStatus::STALE_AFTER_HOURS * 3600;
@endphp

{{-- The verdict leads, because it is the one thing the owner needs to know. --}}
<div class="alert {{ $verdict[0] }} mb-6">
    <i data-lucide="{{ $verdict[1] }}"></i>
    <div>
        <strong>{{ __('backups.state_' . $state) }}</strong>
        <div>{{ __('backups.state_' . $state . '_body') }}</div>
    </div>
</div>

<div class="bk-grid mb-6">
    <div class="panel">
        <div class="panel-header"><span class="panel-title">{{ __('backups.section_config') }}</span></div>
        <div style="padding:4px 20px 14px;">
            <p class="text-muted text-sm" style="margin:10px 0;">{{ __('backups.config_hint') }}</p>
            <table class="data-table">
                <tbody>
                @php
                    $rows = [
                        ['row_s3_enabled', $status['s3_enabled'], 'yesno'],
                        ['row_access_key', $status['key_present'], 'set'],
                        ['row_secret', $status['secret_present'], 'set'],
                        ['row_bucket', $status['bucket_present'], 'set'],
                        ['row_region', $status['region_present'], 'set'],
                        ['row_endpoint', $status['endpoint_present'], 'optional'],
                        ['row_encryption', $status['encryption_password_present'], 'set'],
                    ];
                @endphp
                @foreach($rows as [$label, $ok, $kind])
                    <tr>
                        <td>{{ __('backups.' . $label) }}</td>
                        <td style="text-align:right;white-space:nowrap;">
                            @if($ok)
                                <span class="badge badge-success">{{ $kind === 'yesno' ? __('backups.yes') : __('backups.set') }}</span>
                            @elseif($kind === 'optional')
                                <span class="text-muted text-sm">{{ __('backups.optional_aws') }}</span>
                            @else
                                <span class="badge badge-danger">{{ $kind === 'yesno' ? __('backups.no') : __('backups.missing') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><span class="panel-title">{{ __('backups.section_archives') }}</span></div>
        <div style="padding:4px 20px 14px;">
            <table class="data-table">
                <thead>
                    <tr><th></th><th>{{ __('backups.on_this_server') }}</th><th>{{ __('backups.off_site') }}</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('backups.copies') }}</td>
                        <td>{{ $status['local']['error'] ? '—' : $status['local']['count'] }}</td>
                        <td>{{ $status['remote'] === null ? __('backups.not_configured') : ($status['remote']['error'] ? '—' : $status['remote']['count']) }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('backups.latest') }}</td>
                        <td>{{ $when($status['local']['latest_at']) ?? __('backups.none_yet') }}</td>
                        <td>{{ $status['remote'] === null ? '—' : ($when($status['remote']['latest_at']) ?? __('backups.none_yet')) }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('backups.size') }}</td>
                        <td>{{ $mb($status['local']['latest_size']) ?? '—' }}</td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
            @if($status['local']['error'])
                <p class="text-sm" style="color:#b42318;margin-top:10px;">{{ __('backups.read_error', ['error' => $status['local']['error']]) }}</p>
            @endif
            @if(($status['remote']['error'] ?? null))
                <p class="text-sm" style="color:#b42318;margin-top:10px;">{{ __('backups.read_error', ['error' => $status['remote']['error']]) }}</p>
            @endif
            @if($staleLocal)
                <p class="text-sm" style="color:#b54708;margin-top:10px;">{{ __('backups.stale', ['hours' => \App\Support\BackupStatus::STALE_AFTER_HOURS]) }}</p>
            @endif
        </div>
    </div>
</div>

<div class="panel mb-6">
    <div class="panel-header"><span class="panel-title">{{ __('backups.section_steps') }}</span></div>
    <div style="padding:16px 20px 20px;">
        <p style="margin:0 0 18px;max-width:72ch;">{{ __('backups.steps_intro') }}</p>

        {{-- These commands are real: the Finder's path and user on this host.
             Values in CAPITALS after the = sign are placeholders to replace. --}}
        <ol class="bk-steps">
            <li>
                <span class="bk-num">1</span>
                <div>
                    <h3>{{ __('backups.step1_title') }}</h3>
                    <p>{{ __('backups.step1_body') }}</p>
                </div>
            </li>
            <li>
                <span class="bk-num">2</span>
                <div>
                    <h3>{{ __('backups.step2_title') }}</h3>
                    <p>{{ __('backups.step2_body') }}</p>
<div class="bk-cmd">nano /home/finder/htdocs/finder.opescare.cloud/.env</div>
                    <p>{{ __('backups.step2_after') }}</p>
<div class="bk-cmd">BACKUP_S3_ENABLED=true
AWS_ACCESS_KEY_ID=YOUR_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=YOUR_SECRET_KEY
AWS_DEFAULT_REGION=YOUR_REGION
AWS_BUCKET=YOUR_BUCKET_NAME
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false</div>
                    <p class="text-muted text-sm">{{ __('backups.step2_other_providers') }}</p>
                </div>
            </li>
            <li>
                <span class="bk-num">3</span>
                <div>
                    <h3>{{ __('backups.step3_title') }}</h3>
                    <p>{{ __('backups.step3_body') }}</p>
<div class="bk-cmd">cd /home/finder/htdocs/finder.opescare.cloud
sudo -u finder /usr/bin/php8.3 artisan config:cache</div>
                </div>
            </li>
            <li>
                <span class="bk-num">4</span>
                <div>
                    <h3>{{ __('backups.step4_title') }}</h3>
                    <p>{{ __('backups.step4_body') }}</p>
<div class="bk-cmd">sudo -u finder /usr/bin/php8.3 artisan backup:run
sudo -u finder /usr/bin/php8.3 artisan backup:list</div>
                </div>
            </li>
            <li>
                <span class="bk-num">5</span>
                <div>
                    <h3>{{ __('backups.step5_title') }}</h3>
                    <p>{{ __('backups.step5_body') }}</p>
                </div>
            </li>
        </ol>
    </div>
</div>

<div class="alert alert-warning">
    <i data-lucide="key-round"></i>
    <div>
        <strong>{{ __('backups.password_title') }}</strong>
        <div>{{ __('backups.password_body') }}</div>
    </div>
</div>
@endsection
