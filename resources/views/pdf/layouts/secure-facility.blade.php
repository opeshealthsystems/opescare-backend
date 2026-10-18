{{--
    Issuing-facility block for the OpesCare Secure sheet.
    Part of the shared sheet interface — docs/specs/documents/00-secure-sheet.md.

    @include('pdf.layouts.secure-facility', ['facility' => Facility|CareFacility|null])

    Prints name, licence / registration number, address and phone from
    whichever of those columns the row holds. The care-map registry row that
    claimed the facility supplies address/phone when the tenant row has none.
    A missing licence prints "not recorded" — a blank is itself information.
--}}
@php
    $fac      = $facility ?? null;
    $facNone  = __('doc_ex_sheet.not_recorded');
    $facName  = $fac ? ($fac->name ?? ($fac->facility_name ?? null)) : null;
    $facLic   = $fac ? ($fac->license_number ?? ($fac->registration_number ?? null)) : null;
    $facReg   = null;
    if ($fac instanceof \App\Models\Facility) {
        $facReg = \App\Models\FacilityRegistry::where('claimed_facility_id', $fac->id)->first();
    }
    $facAddr  = trim(implode(', ', array_filter([
        (string) ($fac->address ?? ($facReg->address ?? '')),
        (string) ($fac->city ?? ($facReg->city ?? '')),
        (string) ($fac->region ?? ($facReg->region ?? '')),
    ], fn ($v) => is_string($v) && trim($v) !== '')));
    $facPhone = (string) ($fac->phone ?? ($facReg->phone ?? ''));
    $facCode  = (string) ($facReg->ministry_code ?? '');
@endphp
<table class="kv">
    <tr><td class="k">{{ __('doc_ex_sheet.facility') }}</td><td class="v"><strong>{{ filled($facName) ? $facName : $facNone }}</strong></td></tr>
    <tr><td class="k">{{ __('doc_ex_sheet.facility_licence') }}</td><td class="v">{{ filled($facLic) ? $facLic : $facNone }}</td></tr>
    @if($facCode !== '')<tr><td class="k">{{ __('doc_ex_sheet.ministry_code') }}</td><td class="v">{{ $facCode }}</td></tr>@endif
    @if($facAddr !== '')<tr><td class="k">{{ __('doc_ex_sheet.address') }}</td><td class="v">{{ $facAddr }}</td></tr>@endif
    @if(trim($facPhone) !== '')<tr><td class="k">{{ __('doc_ex_sheet.phone') }}</td><td class="v">{{ $facPhone }}</td></tr>@endif
</table>
