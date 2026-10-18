{{--
    Signature / stamp block for the OpesCare Secure sheet.
    Part of the shared sheet interface — docs/specs/documents/00-secure-sheet.md.

    @include('pdf.layouts.secure-signature', [
        'name'         => ?string   the signatory as recorded (never invented),
        'role'         => ?string   profession / title,
        'registration' => ?string   professional registration / licence number,
        'date'         => ?Carbon   date of signing (defaults to nothing printed),
        'label'        => ?string   caption override (default: signature and stamp),
    ])

    A blank line is left for a wet signature and the facility stamp: the PDF
    states who is responsible, the ink is what makes a paper copy count.
--}}
@php
    // Blade includes inherit the parent's variables, so a parent's own
    // $date closure or $label loop variable must never be mistaken for a
    // parameter: only scalar / date values are accepted.
    $sigScalar = fn ($v) => is_string($v) || is_numeric($v) ? (string) $v : null;
    $sigName  = $sigScalar($name ?? null);
    $sigRole  = $sigScalar($role ?? null);
    $sigReg   = $sigScalar($registration ?? null);
    $sigDate  = ($date ?? null) instanceof \DateTimeInterface || is_string($date ?? null) ? $date : null;
    $sigLabel = $sigScalar($label ?? null) ?? __('doc_ex_sheet.signature_and_stamp');
    $sigNone  = __('doc_ex_sheet.not_recorded');
@endphp
<table class="sig">
    <tr>
        <td style="width: 55%;">
            <div><strong>{{ filled($sigName) ? $sigName : $sigNone }}</strong></div>
            @if(filled($sigRole))<div>{{ $sigRole }}</div>@endif
            <div>{{ __('doc_ex_sheet.registration_no') }}: {{ filled($sigReg) ? $sigReg : $sigNone }}</div>
            @if($sigDate)<div class="muted">{{ __('doc_ex_sheet.date') }}: {{ \Illuminate\Support\Carbon::parse($sigDate)->translatedFormat('d M Y') }}</div>@endif
        </td>
        <td style="width: 45%;">
            <div class="line"></div>
            <div class="cap">{{ $sigLabel }}</div>
        </td>
    </tr>
</table>
