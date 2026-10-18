@extends('layouts.portal')
@section('title', __('backups.title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_hlth_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('backups.title'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('backups.title') }}</h1>
        <p class="od-page__sub">{{ __('backups.subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.cc.backups') }}" class="od-btn od-btn--secondary">
            <i data-lucide="refresh-cw"></i> {{ __('backups.refresh') }}
        </a>
    </div>
</div>

@php
    $state = $status['state'];
    $verdict = match ($state) {
        'off_host'      => ['oda-ga-alert--ok',     'shield-check'],
        'configured'    => ['',                     'clock'],
        'misconfigured' => ['oda-ga-alert--danger', 'triangle-alert'],
        default         => ['oda-ga-alert--warn',   'hard-drive'],
    };
    $when = fn (?int $ts) => $ts ? \Illuminate\Support\Carbon::createFromTimestamp($ts)->timezone(config('app.timezone'))->isoFormat('D MMM YYYY, HH:mm') : null;
    $mb = fn (?int $bytes) => $bytes === null ? null : number_format($bytes / 1048576, 1) . ' MB';
    $staleLocal = $status['local']['latest_at']
        && (now()->timestamp - $status['local']['latest_at']) > \App\Support\BackupStatus::STALE_AFTER_HOURS * 3600;
@endphp

{{-- The verdict leads, because it is the one thing the owner needs to know. --}}
<div class="oda-ga-alert {{ $verdict[0] }}">
    <i data-lucide="{{ $verdict[1] }}"></i>
    <div class="oda-ga-alert__body">
        <strong>{{ __('backups.state_' . $state) }}</strong>
        <div>{{ __('backups.state_' . $state . '_body') }}</div>
    </div>
</div>

<div class="od-grid od-grid--2">
    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title">{{ __('backups.section_config') }}</h2></div>
        <div class="od-card__body">
            <p class="oda-help">{{ __('backups.config_hint') }}</p>
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
            <dl class="oda-dl">
            @foreach($rows as [$label, $ok, $kind])
                <dt class="oda-dl__k">{{ __('backups.' . $label) }}</dt>
                <dd class="oda-dl__v">
                    @if($ok)
                        <span class="od-pill od-pill--ready">{{ $kind === 'yesno' ? __('backups.yes') : __('backups.set') }}</span>
                    @elseif($kind === 'optional')
                        <span class="oda-ga-muted">{{ __('backups.optional_aws') }}</span>
                    @else
                        <span class="od-pill od-pill--urgent">{{ $kind === 'yesno' ? __('backups.no') : __('backups.missing') }}</span>
                    @endif
                </dd>
            @endforeach
            </dl>
        </div>
    </section>

    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title">{{ __('backups.section_archives') }}</h2></div>
        <div class="od-card__body">
            <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr><th></th><th>{{ __('backups.on_this_server') }}</th><th>{{ __('backups.off_site') }}</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="od-table__name">{{ __('backups.copies') }}</span></td>
                        <td>{{ $status['local']['error'] ? '—' : $status['local']['count'] }}</td>
                        <td>{{ $status['remote'] === null ? __('backups.not_configured') : ($status['remote']['error'] ? '—' : $status['remote']['count']) }}</td>
                    </tr>
                    <tr>
                        <td><span class="od-table__name">{{ __('backups.latest') }}</span></td>
                        <td>{{ $when($status['local']['latest_at']) ?? __('backups.none_yet') }}</td>
                        <td>{{ $status['remote'] === null ? '—' : ($when($status['remote']['latest_at']) ?? __('backups.none_yet')) }}</td>
                    </tr>
                    <tr>
                        <td><span class="od-table__name">{{ __('backups.size') }}</span></td>
                        <td>{{ $mb($status['local']['latest_size']) ?? '—' }}</td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
            </div>
            @if($status['local']['error'])
                <p class="oda-ga-alerttext">{{ __('backups.read_error', ['error' => $status['local']['error']]) }}</p>
            @endif
            @if(($status['remote']['error'] ?? null))
                <p class="oda-ga-alerttext">{{ __('backups.read_error', ['error' => $status['remote']['error']]) }}</p>
            @endif
            @if($staleLocal)
                <p class="oda-ga-warntext">{{ __('backups.stale', ['hours' => \App\Support\BackupStatus::STALE_AFTER_HOURS]) }}</p>
            @endif
        </div>
    </section>
</div>

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('backups.section_steps') }}</h2></div>
    <div class="od-card__body">
        <p class="oda-ga-prose">{{ __('backups.steps_intro') }}</p>

        {{-- These commands are real: the Finder's path and user on this host.
             Values in CAPITALS after the = sign are placeholders to replace. --}}
        <ol class="oda-ga-steps">
            <li>
                <span class="oda-ga-steps__num">0</span>
                <div>
                    <h3>{{ __('backups.step0_title') }}</h3>
                    <p>{{ __('backups.step0_body') }}</p>
<div class="oda-ga-cmd">cd /home/finder/htdocs/finder.opescare.cloud
sudo -u finder composer require league/flysystem-aws-s3-v3 --no-interaction</div>
                </div>
            </li>
            <li>
                <span class="oda-ga-steps__num">1</span>
                <div>
                    <h3>{{ __('backups.step1_title') }}</h3>
                    <p>{{ __('backups.step1_body') }}</p>
                </div>
            </li>
            <li>
                <span class="oda-ga-steps__num">2</span>
                <div>
                    <h3>{{ __('backups.step2_title') }}</h3>
                    <p>{{ __('backups.step2_body') }}</p>
<div class="oda-ga-cmd">nano /home/finder/htdocs/finder.opescare.cloud/.env</div>
                    <p>{{ __('backups.step2_after') }}</p>
<div class="oda-ga-cmd">BACKUP_S3_ENABLED=true
AWS_ACCESS_KEY_ID=YOUR_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=YOUR_SECRET_KEY
AWS_DEFAULT_REGION=YOUR_REGION
AWS_BUCKET=YOUR_BUCKET_NAME
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false</div>
                    <p class="oda-ga-muted">{{ __('backups.step2_other_providers') }}</p>
                </div>
            </li>
            <li>
                <span class="oda-ga-steps__num">3</span>
                <div>
                    <h3>{{ __('backups.step3_title') }}</h3>
                    <p>{{ __('backups.step3_body') }}</p>
<div class="oda-ga-cmd">cd /home/finder/htdocs/finder.opescare.cloud
sudo -u finder /usr/bin/php8.3 artisan config:cache</div>
                </div>
            </li>
            <li>
                <span class="oda-ga-steps__num">4</span>
                <div>
                    <h3>{{ __('backups.step4_title') }}</h3>
                    <p>{{ __('backups.step4_body') }}</p>
<div class="oda-ga-cmd">sudo -u finder /usr/bin/php8.3 artisan backup:run
sudo -u finder /usr/bin/php8.3 artisan backup:list</div>
                </div>
            </li>
            <li>
                <span class="oda-ga-steps__num">5</span>
                <div>
                    <h3>{{ __('backups.step5_title') }}</h3>
                    <p>{{ __('backups.step5_body') }}</p>
                </div>
            </li>
        </ol>
    </div>
</section>

<div class="oda-ga-alert oda-ga-alert--warn">
    <i data-lucide="key-round"></i>
    <div class="oda-ga-alert__body">
        <strong>{{ __('backups.password_title') }}</strong>
        <div>{{ __('backups.password_body') }}</div>
    </div>
</div>
</div>
@endsection
