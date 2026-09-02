@extends('layouts.portal')
@section('title', __('caremap_claim.edit_title'))
@section('breadcrumb_section', __('caremap_claim.edit_title'))

@php
    use App\Http\Controllers\CareMap\FacilityListingController as Listing;

    $dayLabels  = Listing::dayLabels();
    $categories = Listing::serviceCategories();
    $statuses   = Listing::availabilityStatuses();

    // 'N/A' is a placeholder the registry left behind, not a number. It must
    // never be pre-filled into a claimant's form as though it were theirs.
    $phonePrimary = \App\Models\CareFacility::realValue($listing->phone_primary);
@endphp

@section('head')
<style>
  .lst-badges{display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:var(--p-space-3)}
  .lst-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:999px;font-size:.72rem;font-weight:700}
  .lst-badge i{width:12px;height:12px}
  .lst-badge--claimed{background:rgba(15,76,129,.1);color:#0F4C81;border:1px solid rgba(15,76,129,.25)}
  .lst-badge--unverified{background:#F1F5F9;color:#64748B;border:1px solid #E2E8F0}
  .lst-note{font-size:.8rem;color:var(--p-text-muted);line-height:1.55;margin-bottom:var(--p-space-4)}
  .lst-hours-table td{padding:.4rem .5rem;vertical-align:middle}
  .lst-hours-table .form-control{padding:.35rem .5rem;font-size:.8rem}
  .lst-check{display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--p-text-2);white-space:nowrap}
  .lst-svc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--p-space-4)}
  .lst-svc-flags{display:flex;gap:1rem;flex-wrap:wrap;margin-top:var(--p-space-3)}
</style>
@endsection

@section('content')

<div class="page-head">
    <h2>{{ __('caremap_claim.edit_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('public.care-map.profile', $listing->id) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">
        <i data-lucide="external-link"></i> {{ __('caremap_claim.view_listing') }}
    </a>
</div>

<div class="lst-badges">
    <span class="lst-badge lst-badge--claimed"><i data-lucide="shield-check"></i> {{ __('caremap_claim.badge_claimed') }}</span>
    <span class="lst-badge lst-badge--unverified"><i data-lucide="circle-dashed"></i> {{ __('caremap_claim.badge_not_verified') }}</span>
</div>
<p class="lst-note">{{ __('caremap_claim.edit_subtitle', ['facility' => $listing->facility_name]) }}</p>
<p class="lst-note">{{ __('caremap_claim.not_verified_note') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- ══ Contact details ═══════════════════════════════════════════════════ --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="phone"></i> {{ __('caremap_claim.section_contact') }}</h3>
    </div>
    <div class="panel-body">
        <p class="lst-note">{{ __('caremap_claim.audit_note') }}</p>

        <form method="POST" action="{{ route('portals.listing.update') }}">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required" for="phone_primary">{{ __('caremap_claim.label_phone_primary') }}</label>
                    <input type="tel" id="phone_primary" name="phone_primary" class="form-control" maxlength="40"
                           value="{{ old('phone_primary', $phonePrimary) }}">
                    @if($phonePrimary === null)
                        <div class="form-hint">{{ __('caremap_claim.hint_phone_placeholder') }}</div>
                    @endif
                    @error('phone_primary')<div class="form-hint" style="color:var(--p-danger)">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone_secondary">{{ __('caremap_claim.label_phone_secondary') }}</label>
                    <input type="tel" id="phone_secondary" name="phone_secondary" class="form-control" maxlength="40"
                           value="{{ old('phone_secondary', \App\Models\CareFacility::realValue($listing->phone_secondary)) }}">
                    @error('phone_secondary')<div class="form-hint" style="color:var(--p-danger)">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-row" style="margin-top:var(--p-space-4)">
                <div class="form-group">
                    <label class="form-label" for="email">{{ __('caremap_claim.label_email') }}</label>
                    <input type="email" id="email" name="email" class="form-control" maxlength="150"
                           value="{{ old('email', $listing->email) }}">
                    @error('email')<div class="form-hint" style="color:var(--p-danger)">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="website">{{ __('caremap_claim.label_website') }}</label>
                    <input type="url" id="website" name="website" class="form-control" maxlength="255"
                           value="{{ old('website', $listing->website) }}" placeholder="https://">
                    @error('website')<div class="form-hint" style="color:var(--p-danger)">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group" style="margin-top:var(--p-space-4)">
                <label class="form-label" for="description">{{ __('caremap_claim.label_description') }}</label>
                <textarea id="description" name="description" class="form-control" maxlength="2000">{{ old('description', $listing->description) }}</textarea>
                <div class="form-hint">{{ __('caremap_claim.hint_description') }}</div>
                @error('description')<div class="form-hint" style="color:var(--p-danger)">{{ $message }}</div>@enderror
            </div>

            <div style="margin-top:var(--p-space-5)">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> {{ __('caremap_claim.btn_save') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══ Opening hours ═════════════════════════════════════════════════════ --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clock"></i> {{ __('caremap_claim.section_hours') }}</h3>
    </div>
    <div class="panel-body">
        <p class="lst-note">{{ __('caremap_claim.hours_intro') }}</p>

        <form method="POST" action="{{ route('portals.listing.hours.update') }}">
            @csrf
            <div class="table-wrapper">
                <table class="data-table lst-hours-table">
                    <thead>
                        <tr>
                            <th>{{ __('caremap_claim.hours_day') }}</th>
                            <th>{{ __('caremap_claim.hours_open') }}</th>
                            <th>{{ __('caremap_claim.hours_close') }}</th>
                            <th>{{ __('caremap_claim.hours_24') }}</th>
                            <th>{{ __('caremap_claim.hours_closed') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($dayLabels as $day => $label)
                        @php $h = $hours->get($day); @endphp
                        <tr>
                            <td data-label="{{ __('caremap_claim.hours_day') }}" class="td-strong">{{ $label }}</td>
                            <td data-label="{{ __('caremap_claim.hours_open') }}">
                                <input type="time" name="hours[{{ $day }}][opens_at]" class="form-control"
                                       value="{{ $h && $h->opens_at ? substr($h->opens_at, 0, 5) : '' }}">
                            </td>
                            <td data-label="{{ __('caremap_claim.hours_close') }}">
                                <input type="time" name="hours[{{ $day }}][closes_at]" class="form-control"
                                       value="{{ $h && $h->closes_at ? substr($h->closes_at, 0, 5) : '' }}">
                            </td>
                            <td data-label="{{ __('caremap_claim.hours_24') }}">
                                <label class="lst-check">
                                    <input type="checkbox" name="hours[{{ $day }}][is_24_hours]" value="1" @checked($h?->is_24_hours)>
                                </label>
                            </td>
                            <td data-label="{{ __('caremap_claim.hours_closed') }}">
                                <label class="lst-check">
                                    <input type="checkbox" name="hours[{{ $day }}][is_closed]" value="1" @checked($h?->is_closed)>
                                </label>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:var(--p-space-5)">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> {{ __('caremap_claim.btn_save_hours') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══ Services and specialties ══════════════════════════════════════════ --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="stethoscope"></i> {{ __('caremap_claim.section_services') }}</h3>
    </div>
    <div class="panel-body">
        <p class="lst-note">{{ __('caremap_claim.svc_intro') }}</p>

        @if($services->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="list-plus"></i></div>
                <p>{{ __('caremap_claim.empty_services') }}</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('caremap_claim.svc_name') }}</th>
                            <th>{{ __('caremap_claim.svc_category') }}</th>
                            <th>{{ __('caremap_claim.svc_specialty') }}</th>
                            <th>{{ __('caremap_claim.svc_availability') }}</th>
                            <th class="row-actions">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($services as $svc)
                        <tr>
                            <td data-label="{{ __('caremap_claim.svc_name') }}">
                                <span class="td-strong">{{ $svc->service_name }}</span>
                                <div class="td-muted">
                                    @if($svc->appointment_required) <span>{{ __('caremap_claim.svc_appointment') }}</span> @endif
                                    @if($svc->walk_in_allowed) <span>&bull; {{ __('caremap_claim.svc_walkin') }}</span> @endif
                                    @if($svc->telemedicine_available) <span>&bull; {{ __('caremap_claim.svc_telemedicine') }}</span> @endif
                                </div>
                            </td>
                            <td data-label="{{ __('caremap_claim.svc_category') }}" class="td-muted">
                                {{ $categories[$svc->service_category] ?? $svc->service_category }}
                            </td>
                            <td data-label="{{ __('caremap_claim.svc_specialty') }}" class="td-muted">{{ $svc->specialty ?: '—' }}</td>
                            <td data-label="{{ __('caremap_claim.svc_availability') }}" class="td-muted">
                                {{ $statuses[$svc->availability_status] ?? $svc->availability_status }}
                            </td>
                            <td class="row-actions">
                                <form method="POST" action="{{ route('portals.listing.services.destroy', $svc->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm">
                                        <i data-lucide="trash-2"></i> {{ __('caremap_claim.btn_remove') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('portals.listing.services.store') }}" style="margin-top:var(--p-space-5)">
            @csrf
            <div class="lst-svc-grid">
                <div class="form-group">
                    <label class="form-label form-label-required" for="service_name">{{ __('caremap_claim.svc_name') }}</label>
                    <input type="text" id="service_name" name="service_name" class="form-control" maxlength="150" required>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="service_category">{{ __('caremap_claim.svc_category') }}</label>
                    <select id="service_category" name="service_category" class="form-control" required>
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="specialty">{{ __('caremap_claim.svc_specialty') }}</label>
                    <input type="text" id="specialty" name="specialty" class="form-control" maxlength="120">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="availability_status">{{ __('caremap_claim.svc_availability') }}</label>
                    <select id="availability_status" name="availability_status" class="form-control" required>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="lst-svc-flags">
                <label class="lst-check"><input type="checkbox" name="appointment_required" value="1"> {{ __('caremap_claim.svc_appointment') }}</label>
                <label class="lst-check"><input type="checkbox" name="walk_in_allowed" value="1" checked> {{ __('caremap_claim.svc_walkin') }}</label>
                <label class="lst-check"><input type="checkbox" name="telemedicine_available" value="1"> {{ __('caremap_claim.svc_telemedicine') }}</label>
            </div>

            <div style="margin-top:var(--p-space-5)">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="plus"></i> {{ __('caremap_claim.btn_add_service') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
