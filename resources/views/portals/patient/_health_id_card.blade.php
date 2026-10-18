{{--
    The patient's Health ID credential — the web port of the app's
    SecureCredentialCard.tsx (same layers as public/landing.blade.php, .hc in
    public/css/od-patient.css), filled with the patient's OWN record.
    Only rendered on patient-facing pages (the patient, or their guardian):
    the date of birth shown here is never part of a staff view.
    Expects: $patient, $staticQrDataUri (nullable), $canWrite.
--}}
@php
    $hcName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
    $hcInitials = mb_strtoupper(mb_substr(trim((string) $patient->first_name), 0, 1) . mb_substr(trim((string) $patient->last_name), 0, 1)) ?: '—';
    $hcIdentity = $patient->identity_status instanceof \BackedEnum ? $patient->identity_status->value : (string) ($patient->identity_status ?? '');
    $hcVerified = in_array($hcIdentity, ['verified', 'verified_by_facility'], true);
    $hcDob = $patient->date_of_birth ? \Illuminate\Support\Carbon::parse($patient->date_of_birth)->translatedFormat('d M Y') : '—';
    $hcSexRaw = strtolower((string) ($patient->sex instanceof \BackedEnum ? $patient->sex->value : ($patient->sex ?? '')));
    $hcSex = in_array($hcSexRaw, ['male', 'female', 'other', 'unknown', 'm', 'f'], true) ? __('patient_ui.sex_' . $hcSexRaw) : ($hcSexRaw !== '' ? $hcSexRaw : '—');
@endphp
<figure class="hc-stage" aria-label="{{ __('patient_ui.hid_card_aria', ['name' => $hcName]) }}">
    <div class="hc">
        <div class="hc__sub" aria-hidden="true">
            <img class="hc__fill hc__o45" src="{{ asset('images/health-id/guilloche-texture.webp') }}" alt="" loading="lazy">
            <img class="hc__fill hc__o55" src="{{ asset('images/health-id/guilloche-mesh.webp') }}" alt="" loading="lazy">
            <img class="hc__helix" src="{{ asset('images/health-id/dna-helix.webp') }}" alt="" loading="lazy">
            <img class="hc__micro" src="{{ asset('images/health-id/dna-microdata.webp') }}" alt="" loading="lazy">
        </div>
        <span class="hc__corner hc__corner--tl" aria-hidden="true"></span><span class="hc__corner hc__corner--tr" aria-hidden="true"></span>
        <span class="hc__corner hc__corner--bl" aria-hidden="true"></span><span class="hc__corner hc__corner--br" aria-hidden="true"></span>

        <div class="hc__top">
            <div class="hc__brand">
                <span class="hc__logo" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 34 34"><circle cx="17" cy="17" r="15.5" fill="none" stroke="#0F4C81" stroke-width="2.6"/><path d="M17 9.5v15M9.5 17h15" stroke="#0F4C81" stroke-width="3.4" stroke-linecap="round"/></svg>
                </span>
                <span>
                    <span class="hc__word">Opes<b>Care</b></span>
                    <span class="hc__brandsub">{{ __('landing.v2.card.brand_sub') }}</span>
                </span>
            </div>
            <div class="hc__tag" aria-hidden="true">
                <span>{{ __('landing.v2.card.tagline') }}</span>
                <img src="{{ asset('images/health-id/africa.webp') }}" alt="" width="34" height="36" loading="lazy">
            </div>
        </div>

        <div class="hc__face">
            <div class="hc__holder">
                <span class="hc__avatar hc__avatar--initials" aria-hidden="true">{{ $hcInitials }}</span>
                <span class="hc__holdertext">
                    <span class="hc__name">{{ $hcName !== '' ? $hcName : '—' }}</span>
                    <span class="hc__label">{{ __('landing.v2.card.label') }}</span>
                </span>
                @if($hcVerified)
                    <span class="hc__badge"><i data-lucide="shield-check"></i>{{ __('landing.v2.card.verified') }}</span>
                @else
                    <span class="hc__badge hc__badge--pending"><i data-lucide="clock"></i>@enum(($patient->verification_status instanceof \BackedEnum ? $patient->verification_status->value : $patient->verification_status) ?? 'Active', 'verification')</span>
                @endif
            </div>
            <div>
                <span class="hc__label">{{ __('landing.v2.card.health_id') }}</span>
                <span class="hc__id">{{ $patient->health_id }}</span>
            </div>
            <div class="hc__body">
                <div class="hc__fields">
                    <div><span class="hc__label">{{ __('landing.v2.card.dob') }}</span><span class="hc__val">{{ $hcDob }}</span></div>
                    <div class="hc__row">
                        <div><span class="hc__label">{{ __('landing.v2.card.sex') }}</span><span class="hc__val">{{ $hcSex }}</span></div>
                        <div><span class="hc__label">{{ __('landing.v2.card.blood') }}</span><span class="hc__val">{{ $patient->blood_group ?: '—' }}</span></div>
                    </div>
                    <img class="hc__ecg" src="{{ asset('images/health-id/ecg-line.webp') }}" alt="" loading="lazy">
                </div>
                <div class="hc__qrcol">
                    <span class="hc__qr">
                        <img class="hc__qrframe" src="{{ asset('images/health-id/qr-frame.webp') }}" alt="" loading="lazy">
                        @if($staticQrDataUri)
                            <img class="hc__qrcode" src="{{ $staticQrDataUri }}" alt="{{ __('public.medical_id.health_id_qr_alt') }}">
                        @else
                            <span class="hc__qrcode hc__qrcode--locked" title="{{ $canWrite ? '' : __('family.banner_read_only') }}"><i data-lucide="{{ $canWrite ? 'qr-code' : 'lock' }}"></i></span>
                        @endif
                    </span>
                    <span class="hc__scan">{{ __('landing.v2.card.scan') }}</span>
                </div>
            </div>
        </div>

        <div class="hc__foot">
            <img class="hc__chip" src="{{ asset('images/health-id/chip.webp') }}" alt="" loading="lazy">
            <span class="hc__footcopy">
                <span class="hc__foot1">{{ __('landing.v2.card.foot_1') }}</span>
                <span class="hc__foot2">{{ __('landing.v2.card.foot_2') }}</span>
            </span>
        </div>
        <span class="hc__seal" aria-hidden="true">
            <img class="hc__ring" src="{{ asset('images/health-id/holo-ring.webp') }}" alt="" loading="lazy">
            <img class="hc__sealimg" src="{{ asset('images/health-id/seal.webp') }}" alt="" loading="lazy">
            <img class="hc__shield" src="{{ asset('images/health-id/verification-shield.webp') }}" alt="" loading="lazy">
        </span>
        <span class="hc__microtext" aria-hidden="true">{{ __('landing.v2.card.microtext') }}</span>
        <span class="hc__edge" aria-hidden="true">{{ __('landing.v2.card.edge') }}</span>
    </div>
</figure>
