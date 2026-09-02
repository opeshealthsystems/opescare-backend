@extends('layouts.public')

@section('title', __('public.consent_page.page_title'))
@section('meta_description', __('public.consent_page.meta_description'))

@section('content')

    {{-- Hero --}}
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background-color:rgba(20,184,166,.15);color:#0F766E;margin-bottom:1rem;">{{ __('public.consent_page.badge') }}</div>
            <h1>{{ __('public.consent_page.hero_title') }}</h1>
            <p class="text-muted" style="max-width:760px;margin:0 auto;font-size:1.2rem;">
                {{ __('public.consent_page.hero_subtitle') }}
            </p>
            <div style="margin-top:2.5rem;display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('register.patient') }}" class="btn btn-primary">{{ __('public.consent_page.btn_get_hid') }}</a>
                <a href="{{ route('public.security') }}" class="btn btn-secondary">{{ __('public.consent_page.btn_security') }}</a>
            </div>
        </div>
    </header>

    {{-- Core pillars --}}
    <section class="content-body">
        <div class="container">
            <div class="section-header">
                <h2>{{ __('public.consent_page.pillars_title') }}</h2>
                <p class="text-muted">{{ __('public.consent_page.pillars_subtitle') }}</p>
            </div>

            <div class="card-grid">
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.1);color:#0F4C81;"><i data-lucide="clipboard-list"></i></div>
                    <h3>{{ __('public.consent_page.pillar_consent_title') }}</h3>
                    <p>{{ __('public.consent_page.pillar_consent_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.1);color:#0F4C81;"><i data-lucide="sliders-horizontal"></i></div>
                    <h3>{{ __('public.consent_page.pillar_scoped_title') }}</h3>
                    <p>{{ __('public.consent_page.pillar_scoped_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.1);color:#0F4C81;"><i data-lucide="eye"></i></div>
                    <h3>{{ __('public.consent_page.pillar_logs_title') }}</h3>
                    <p>{{ __('public.consent_page.pillar_logs_desc') }}</p>
                </div>
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.1);color:#0F4C81;"><i data-lucide="shield-off"></i></div>
                    <h3>{{ __('public.consent_page.pillar_revoke_title') }}</h3>
                    <p>{{ __('public.consent_page.pillar_revoke_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- How consent flow works --}}
    <section class="section" style="background:#F0F9FF;">
        <div class="container">
            <div class="section-header">
                <h2>{{ __('public.consent_page.flow_title') }}</h2>
                <p class="text-muted">{{ __('public.consent_page.flow_subtitle') }}</p>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2rem;counter-reset:step;">
                @php
                $steps = [
                    ['icon'=>'send','title'=>__('public.consent_page.step_submit_title'),'desc'=>__('public.consent_page.step_submit_desc')],
                    ['icon'=>'bell','title'=>__('public.consent_page.step_notify_title'),'desc'=>__('public.consent_page.step_notify_desc')],
                    ['icon'=>'check-circle-2','title'=>__('public.consent_page.step_approve_title'),'desc'=>__('public.consent_page.step_approve_desc')],
                    ['icon'=>'download','title'=>__('public.consent_page.step_access_title'),'desc'=>__('public.consent_page.step_access_desc')],
                    ['icon'=>'scroll-text','title'=>__('public.consent_page.step_log_title'),'desc'=>__('public.consent_page.step_log_desc')],
                    ['icon'=>'shield-off','title'=>__('public.consent_page.step_revoke_title'),'desc'=>__('public.consent_page.step_revoke_desc')],
                ];
                @endphp
                @foreach($steps as $i => $step)
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:1.25rem;padding:1.75rem;position:relative;">
                    <div style="width:2.25rem;height:2.25rem;background:#0F4C81;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.875rem;margin-bottom:1rem;">{{ $i+1 }}</div>
                    <i data-lucide="{{ $step['icon'] }}" style="width:1.5rem;height:1.5rem;color:#14B8A6;margin-bottom:.75rem;display:block;"></i>
                    <h4 style="margin:0 0 .5rem;">{{ $step['title'] }}</h4>
                    <p class="text-muted" style="font-size:.875rem;margin:0;">{{ $step['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Emergency access --}}
    <section class="section">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="badge" style="background:rgba(239,68,68,.1);color:#dc2626;margin-bottom:1rem;">{{ __('public.consent_page.emergency_badge') }}</div>
                    <h2>{{ __('public.consent_page.emergency_title') }}</h2>
                    <p class="text-muted" style="margin-bottom:1.5rem;">{{ __('public.consent_page.emergency_body') }}</p>
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.75rem;">
                        <li style="display:flex;gap:.75rem;align-items:flex-start;">
                            <i data-lucide="alert-triangle" style="width:1.1rem;height:1.1rem;color:#dc2626;flex-shrink:0;margin-top:.15rem;"></i>
                            <span>{{ __('public.consent_page.emergency_audit') }}</span>
                        </li>
                        <li style="display:flex;gap:.75rem;align-items:flex-start;">
                            <i data-lucide="file-pen-line" style="width:1.1rem;height:1.1rem;color:#dc2626;flex-shrink:0;margin-top:.15rem;"></i>
                            <span>{{ __('public.consent_page.emergency_reason') }}</span>
                        </li>
                        <li style="display:flex;gap:.75rem;align-items:flex-start;">
                            <i data-lucide="users" style="width:1.1rem;height:1.1rem;color:#dc2626;flex-shrink:0;margin-top:.15rem;"></i>
                            <span>{{ __('public.consent_page.emergency_notify') }}</span>
                        </li>
                        <li style="display:flex;gap:.75rem;align-items:flex-start;">
                            <i data-lucide="eye" style="width:1.1rem;height:1.1rem;color:#dc2626;flex-shrink:0;margin-top:.15rem;"></i>
                            <span>{{ __('public.consent_page.emergency_limited') }}</span>
                        </li>
                    </ul>
                </div>
                <div class="hero-visual">
                    <div style="background:#0F2744;border-radius:1.5rem;padding:2rem;color:#fff;">
                        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;">
                            <i data-lucide="siren" style="width:1.5rem;height:1.5rem;color:#f87171;"></i>
                            <span style="font-weight:700;font-size:.875rem;text-transform:uppercase;letter-spacing:.05em;color:#f87171;">{{ __('public.consent_page.emergency_profile') }}</span>
                        </div>
                        <div style="display:grid;gap:.75rem;font-size:.875rem;">
                            <div style="background:rgba(255,255,255,.07);border-radius:.75rem;padding:1rem;">
                                <div style="color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;">{{ __('public.consent_page.emerg_identity') }}</div>
                                <div style="font-weight:600;">Full Name &amp; Blood Group</div>
                            </div>
                            <div style="background:rgba(255,255,255,.07);border-radius:.75rem;padding:1rem;">
                                <div style="color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;">{{ __('public.consent_page.emerg_allergies') }}</div>
                                <div style="font-weight:600;color:#f87171;">Penicillin — Contraindicated</div>
                            </div>
                            <div style="background:rgba(255,255,255,.07);border-radius:.75rem;padding:1rem;">
                                <div style="color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;">{{ __('public.consent_page.emerg_conditions') }}</div>
                                <div style="font-weight:600;">Type 2 Diabetes, Hypertension</div>
                            </div>
                            <div style="background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);border-radius:.75rem;padding:.75rem;font-size:.75rem;color:#fca5a5;">
                                <i data-lucide="alert-circle" style="width:.875rem;height:.875rem;display:inline;vertical-align:middle;margin-right:.25rem;"></i>
                                {{ __('public.consent_page.emergency_logged') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Rights summary --}}
    <section class="section" style="background:#F8FAFC;">
        <div class="container">
            <div class="section-header">
                <h2>{{ __('public.consent_page.rights_title') }}</h2>
                <p class="text-muted">{{ __('public.consent_page.rights_subtitle') }}</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;max-width:960px;margin:0 auto;">
                @php
                $rights = [
                    ['icon'=>'eye','title'=>__('public.consent_page.right_know_title'),'desc'=>__('public.consent_page.right_know_desc')],
                    ['icon'=>'hand','title'=>__('public.consent_page.right_refuse_title'),'desc'=>__('public.consent_page.right_refuse_desc')],
                    ['icon'=>'shield-off','title'=>__('public.consent_page.right_revoke_title'),'desc'=>__('public.consent_page.right_revoke_desc')],
                    ['icon'=>'file-check','title'=>__('public.consent_page.right_review_title'),'desc'=>__('public.consent_page.right_review_desc')],
                    ['icon'=>'file-edit','title'=>__('public.consent_page.right_correct_title'),'desc'=>__('public.consent_page.right_correct_desc')],
                    ['icon'=>'phone','title'=>__('public.consent_page.right_support_title'),'desc'=>__('public.consent_page.right_support_desc')],
                ];
                @endphp
                @foreach($rights as $right)
                <div style="display:flex;gap:1rem;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.5rem;">
                    <div style="width:2.5rem;height:2.5rem;background:rgba(15,76,129,.08);color:#0F4C81;border-radius:.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="{{ $right['icon'] }}" style="width:1.1rem;height:1.1rem;"></i>
                    </div>
                    <div>
                        <h4 style="margin:0 0 .4rem;font-size:1rem;">{{ $right['title'] }}</h4>
                        <p class="text-muted" style="font-size:.875rem;margin:0;">{{ $right['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="section" style="background:#0F2744;color:#fff;text-align:center;">
        <div class="container" style="max-width:640px;">
            <i data-lucide="shield-check" style="width:3rem;height:3rem;color:#14B8A6;margin-bottom:1.5rem;"></i>
            <h2 style="color:#fff;margin-bottom:1rem;">{{ __('public.consent_page.cta_title') }}</h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:2rem;">{{ __('public.consent_page.cta_body') }}</p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('register.patient') }}" class="btn btn-primary">{{ __('public.consent_page.btn_register') }}</a>
                <a href="{{ route('public.privacy') }}" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);">{{ __('public.consent_page.btn_privacy') }}</a>
            </div>
        </div>
    </section>

@endsection
