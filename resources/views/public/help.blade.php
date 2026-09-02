@extends('layouts.public')

@section('title', __('public.help_page.page_title'))
@section('meta_description', __('public.help_page.meta_description'))

@section('content')

    {{-- Hero --}}
    <header class="content-header">
        <div class="container">
            <div class="badge" style="background:rgba(20,184,166,.15);color:#0F766E;margin-bottom:1rem;">{{ __('public.help_page.badge') }}</div>
            <h1>{{ __('public.help_page.hero_title') }}</h1>
            <p class="text-muted" style="max-width:680px;margin:0 auto;font-size:1.2rem;">
                {{ __('public.help_page.hero_subtitle') }}
            </p>
            {{-- Search bar --}}
            <div style="margin-top:2.5rem;max-width:560px;margin-left:auto;margin-right:auto;">
                <form action="{{ route('public.contact') }}" method="GET" style="display:flex;gap:.5rem;">
                    <input type="text" name="q" placeholder="{{ __('public.help_page.search_placeholder') }}"
                           style="flex:1;height:3rem;padding:0 1.25rem;border:1px solid #e2e8f0;border-radius:.75rem;font-size:.9375rem;outline:none;background:#fff;"
                           aria-label="{{ __('public.help_page.search_placeholder') }}">
                    <button type="submit" class="btn btn-primary" style="height:3rem;padding:0 1.5rem;">{{ __('public.help_page.search_btn') }}</button>
                </form>
            </div>
        </div>
    </header>

    {{-- Audience cards --}}
    <section class="content-body">
        <div class="container">
            <div class="section-header">
                <h2>{{ __('public.help_page.browse_title') }}</h2>
                <p class="text-muted">{{ __('public.help_page.browse_subtitle') }}</p>
            </div>
            <div class="card-grid">
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.1);color:#0F4C81;"><i data-lucide="user"></i></div>
                    <h3>{{ __('public.help_page.for_patients') }}</h3>
                    <p>{{ __('public.help_page.for_patients_desc') }}</p>
                    <ul style="list-style:none;padding:0;margin:1.25rem 0 0;display:grid;gap:.5rem;font-size:.875rem;">
                        <li><a href="{{ route('public.solutions.patients') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_what_is_hid') }}</a></li>
                        <li><a href="{{ route('register.patient') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_how_register') }}</a></li>
                        <li><a href="{{ route('public.consent') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_manage_consent') }}</a></li>
                        <li><a href="{{ route('public.privacy') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_data_privacy') }}</a></li>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.1);color:#0F4C81;"><i data-lucide="hospital"></i></div>
                    <h3>{{ __('public.help_page.for_institutions') }}</h3>
                    <p>{{ __('public.help_page.for_institutions_desc') }}</p>
                    <ul style="list-style:none;padding:0;margin:1.25rem 0 0;display:grid;gap:.5rem;font-size:.875rem;">
                        <li><a href="{{ route('public.solutions.hospitals') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_hospital_guide') }}</a></li>
                        <li><a href="{{ route('public.solutions.laboratories') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_lab_guide') }}</a></li>
                        <li><a href="{{ route('public.solutions.pharmacies') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_pharmacy_guide') }}</a></li>
                        <li><a href="{{ route('register.organization') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_register_facility') }}</a></li>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-icon" style="background:rgba(15,76,129,.1);color:#0F4C81;"><i data-lucide="code-2"></i></div>
                    <h3>{{ __('public.help_page.for_developers') }}</h3>
                    <p>{{ __('public.help_page.for_developers_desc') }}</p>
                    <ul style="list-style:none;padding:0;margin:1.25rem 0 0;display:grid;gap:.5rem;font-size:.875rem;">
                        <li><a href="{{ route('public.developers') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_dev_portal') }}</a></li>
                        <li><a href="{{ route('public.developers') }}#sdk" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_sdk_docs') }}</a></li>
                        <li><a href="{{ route('public.interoperability') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_interop') }}</a></li>
                        <li><a href="{{ route('public.status') }}" style="color:#0F4C81;text-decoration:none;">{{ __('public.help_page.lnk_api_status') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Popular topics --}}
    <section class="section" style="background:#F0F9FF;">
        <div class="container">
            <div class="section-header">
                <h2>{{ __('public.help_page.popular_title') }}</h2>
                <p class="text-muted">{{ __('public.help_page.popular_subtitle') }}</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.25rem;max-width:960px;margin:0 auto;">
                @php
                $topics = [
                    ['icon'=>'id-card','title'=>__('public.help_page.topic_health_id_title'),'desc'=>__('public.help_page.topic_health_id_desc'),'link'=>route('register.patient')],
                    ['icon'=>'qr-code','title'=>__('public.help_page.topic_qr_title'),'desc'=>__('public.help_page.topic_qr_desc'),'link'=>route('portals.patient')],
                    ['icon'=>'shield-check','title'=>__('public.help_page.topic_consent_title'),'desc'=>__('public.help_page.topic_consent_desc'),'link'=>route('public.consent')],
                    ['icon'=>'history','title'=>__('public.help_page.topic_timeline_title'),'desc'=>__('public.help_page.topic_timeline_desc'),'link'=>route('portals.patient')],
                    ['icon'=>'building-2','title'=>__('public.help_page.topic_facility_title'),'desc'=>__('public.help_page.topic_facility_desc'),'link'=>route('register.organization')],
                    ['icon'=>'key','title'=>__('public.help_page.topic_password_title'),'desc'=>__('public.help_page.topic_password_desc'),'link'=>route('password.request')],
                    ['icon'=>'webhook','title'=>__('public.help_page.topic_webhooks_title'),'desc'=>__('public.help_page.topic_webhooks_desc'),'link'=>route('public.developers').'#webhooks'],
                    ['icon'=>'phone','title'=>__('public.help_page.topic_contact_title'),'desc'=>__('public.help_page.topic_contact_desc'),'link'=>route('public.contact')],
                ];
                @endphp
                @foreach($topics as $topic)
                <a href="{{ $topic['link'] }}" style="display:flex;gap:1rem;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.5rem;text-decoration:none;transition:box-shadow .15s;" onmouseover="this.style.boxShadow='0 4px 20px rgba(15,76,129,.12)'" onmouseout="this.style.boxShadow='none'">
                    <div style="width:2.25rem;height:2.25rem;background:rgba(15,76,129,.08);color:#0F4C81;border-radius:.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="{{ $topic['icon'] }}" style="width:1rem;height:1rem;"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;color:#0F2744;font-size:.9375rem;margin-bottom:.25rem;">{{ $topic['title'] }}</div>
                        <div style="font-size:.8125rem;color:#64748b;">{{ $topic['desc'] }}</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Still need help --}}
    <section class="section" style="background:#0F2744;color:#fff;text-align:center;">
        <div class="container" style="max-width:640px;">
            <i data-lucide="headset" style="width:3rem;height:3rem;color:#14B8A6;margin-bottom:1.5rem;"></i>
            <h2 style="color:#fff;margin-bottom:1rem;">{{ __('public.help_page.still_help_title') }}</h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:2rem;">{{ __('public.help_page.still_help_body') }}</p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:1rem;">
                <a href="{{ route('public.contact') }}" class="btn btn-primary">{{ __('public.help_page.btn_contact') }}</a>
                <a href="{{ route('public.faq') }}" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25);">{{ __('public.help_page.btn_faq') }}</a>
            </div>
        </div>
    </section>

@endsection
