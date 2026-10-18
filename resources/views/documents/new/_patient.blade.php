{{-- Patient identity block shared by the new documents. Never invents: a blank column prints the dash. --}}
@php
    $pDash = __('doc_new_docs.not_recorded');
    $pDob  = $patient?->date_of_birth ? \Illuminate\Support\Carbon::parse($patient->date_of_birth)->translatedFormat('d M Y') : null;
    $pSex  = $patient?->sex instanceof \BackedEnum ? $patient->sex->value : $patient?->sex;
@endphp
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.patient') }}</td><td class="v"><strong>{{ $patient ? trim($patient->first_name . ' ' . $patient->last_name) : $pDash }}</strong></td></tr>
    <tr><td class="k">{{ __('doc_new_docs.health_id') }}</td><td class="v id">{{ $patient?->health_id ?? $pDash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.date_of_birth') }}</td><td class="v">{{ $pDob ?? $pDash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.sex') }}</td><td class="v">{{ filled($pSex) ? $pSex : $pDash }}</td></tr>
</table>
