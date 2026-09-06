@extends('layouts.portal')

@section('title', __('family.inv_title') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.inv_breadcrumb'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')
<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('family.inv_title') }}</h1>
            <p class="pf-sub">{{ __('family.inv_subtitle') }}</p>
        </div>
        <a href="{{ route('portals.patient.family') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('public.portal.family_breadcrumb', [], $l) ?: 'My Family' }}
        </a>
    </div>

    @if(!$hasPatient)
        <div class="pf-alert pf-alert--warn">
            @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
            <p>{{ __('family.inv_no_patient') }}</p>
        </div>
    @elseif($invites->isEmpty())
        <div class="f-card" style="text-align:center;padding:40px 20px;" data-section="invitations-empty">
            <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
                @include('partials.finder.icon', ['name' => 'bell', 'size' => 24])
            </div>
            <p class="f-muted" style="margin:0;">{{ __('family.inv_empty') }}</p>
        </div>
    @else
        <div class="pf-stack" data-section="invitations">
            @foreach($invites as $inv)
            @php
                $who   = $inv->guardianUser?->name ?? $inv->guardianUser?->email ?? (__('public.portal.unknown_guardian', [], $l) ?: 'Unknown guardian');
                $rel   = __('family.relationship.' . $inv->relationship);
                $rel   = $rel === 'family.relationship.' . $inv->relationship ? ucfirst(str_replace('_', ' ', $inv->relationship)) : $rel;
                $level = $inv->access_level === 'full' ? __('family.access.full') : __('family.access.read_only');
            @endphp
            <div class="f-card" data-invitation="{{ $inv->id }}">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="pf-avatar">{{ strtoupper(substr($who, 0, 1)) }}</div>
                    <div style="flex:1;min-width:0;">
                        <p style="margin:0;color:var(--f-ink);">{{ __('family.inv_from', ['name' => $who, 'relationship' => mb_strtolower($rel), 'access' => mb_strtolower($level)]) }}</p>
                        @if($inv->invite_expires_at)
                        <p class="f-small f-muted" style="margin:4px 0 0;">{{ __('family.inv_expires', ['date' => $inv->invite_expires_at->translatedFormat('d M Y, H:i')]) }}</p>
                        @endif
                    </div>
                </div>
                <p class="f-small f-muted" style="margin:10px 0 0;">
                    {{ $inv->access_level === 'full' ? __('family.access_full_explained') : __('family.access_read_only_explained') }}
                </p>
                <div class="pf-appt__actions">
                    <form method="POST" action="{{ route('portals.patient.family.invitations.accept', $inv->id) }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="f-btn f-btn--primary">
                            @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
                            {{ __('family.inv_accept') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('portals.patient.family.invitations.decline', $inv->id) }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="f-btn pf-btn--danger-ghost">{{ __('family.inv_decline') }}</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
