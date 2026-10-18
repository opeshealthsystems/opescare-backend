@extends('layouts.portal')

@section('title', __('prescriber.create_title'))

@section('breadcrumb_home', __('prescriber.title'))
@section('breadcrumb_home_url', route('portals.prescriber.index'))
@section('breadcrumb_section', __('prescriber.create_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('prescriber.create_title') }}</h1>
        <p class="od-page__sub">{{ __('prescriber.create_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.prescriber.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> <span>{{ __('prescriber.back') }}</span></a>
</div>

@if(session('error'))
<div class="odc-note odc-note--err" role="alert"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

@if($errors->any())
<div class="odc-note odc-note--err" role="alert"><i data-lucide="triangle-alert"></i>
    <div><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
</div>
@endif

@if($patient === null)

    {{-- ── Step 1: find the patient ────────────────────────────────────────
         Exact Health ID only — patient names are deliberately not searchable. --}}
    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('prescriber.find_patient') }}</h2></header>
        <div class="od-card__body">
            @if($notFound)
            <div class="odc-note odc-note--err odc-note--inset" role="alert"><i data-lucide="user-x"></i><div>{{ __('prescriber.not_found') }}</div></div>
            @endif

            <form method="GET" action="{{ route('portals.prescriber.create') }}" class="odc-form">
                <div class="odc-grid odc-grid--1">
                    <div class="odc-field">
                        <label class="odc-label odc-label--req" for="rx-health-id">{{ __('prescriber.health_id') }}</label>
                        <input type="text" id="rx-health-id" name="health_id" class="od-input" value="{{ request('health_id') }}" required autocomplete="off" autofocus>
                        <p class="odc-hint">{{ __('prescriber.health_id_hint') }}</p>
                    </div>
                </div>
                <div class="odc-actions">
                    <a href="{{ route('portals.prescriber.index') }}" class="od-btn od-btn--secondary">{{ __('prescriber.cancel') }}</a>
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="search"></i> <span>{{ __('prescriber.lookup') }}</span></button>
                </div>
            </form>
        </div>
    </section>

@else

    @php
        // Rebuild the submitted lines on a validation bounce; otherwise start with
        // a single empty line. Always an array of arrays.
        $oldItems = old('items');
        $itemRows = (is_array($oldItems) && count($oldItems) > 0)
            ? array_values(array_map(fn ($r) => (array) $r, $oldItems))
            : [[]];

        $patientName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
        $patientInit = mb_strtoupper(mb_substr(trim((string) ($patient->first_name ?? '')), 0, 1) . mb_substr(trim((string) ($patient->last_name ?? '')), 0, 1));
        $patientTone = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($patientName !== '' ? $patientName : (string) $patient->id) % 6];
        // Never print a date of birth: an age band only.
        $patientAge  = $patient->date_of_birth ? \App\Support\ResultFlagResolver::ageBand($patient->date_of_birth) : null;
    @endphp

    {{-- ── Selected patient ──────────────────────────────────────────────── --}}
    <section class="od-card">
        <div class="od-card__head">
            <div class="odc-patient">
                <span class="od-avatar od-avatar--lg od-tint--{{ $patientTone }}" aria-hidden="true">{{ $patientInit !== '' ? $patientInit : '—' }}</span>
                <div class="odc-patient__main">
                    <div class="od-detail__name">{{ $patientName !== '' ? $patientName : '—' }}</div>
                    <div class="od-detail__facts">
                        <span class="od-detail__fact"><i data-lucide="id-card"></i> {{ $patient->health_id }}</span>
                        @if($patientAge)<span class="od-detail__fact"><i data-lucide="calendar"></i> {{ __('pharmacy_desk.age_bands.' . $patientAge) }}</span>@endif
                        @if($patient->sex)<span class="od-detail__fact"><i data-lucide="user"></i> @enum($patient->sex)</span>@endif
                    </div>
                </div>
            </div>
            <a href="{{ route('portals.prescriber.create') }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="repeat"></i> <span>{{ __('prescriber.change_patient') }}</span></a>
        </div>
    </section>

    {{-- ── The prescription ──────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('portals.prescriber.store') }}" id="rx-form" class="odc-form">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">

        <section class="od-card">
            <header class="od-card__head">
                <h2 class="od-card__title">{{ __('prescriber.medicines') }}</h2>
                <button type="button" class="od-btn od-btn--secondary od-btn--sm" id="rx-add"><i data-lucide="plus"></i> <span>{{ __('prescriber.add_item') }}</span></button>
            </header>
            <div class="od-card__body">
                <div class="odc-rx-rows" id="rx-rows">
                    @foreach($itemRows as $i => $row)
                    <div class="odc-rx-row rx-row" data-index="{{ $i }}">
                        <div class="odc-rx-row__head">
                            <span class="odc-rx-row__num rx-row__num">{{ $i + 1 }}</span>
                            <button type="button" class="od-btn od-btn--ghost od-btn--sm odc-rx-remove rx-remove"><i data-lucide="trash-2"></i> <span>{{ __('prescriber.remove_item') }}</span></button>
                        </div>

                        <div class="odc-field">
                            <label class="odc-label odc-label--req">{{ __('prescriber.drug_name') }}</label>
                            <input type="text" class="od-input rx-drug" name="items[{{ $i }}][drug_name]" value="{{ $row['drug_name'] ?? '' }}" list="rx-medicine-list" maxlength="200" required autocomplete="off" aria-label="{{ __('prescriber.drug_name') }}">
                            {{-- Filled by JS only when the typed text matches a catalogue entry exactly.
                                 Empty stays empty: off-catalogue drugs post name-only. --}}
                            <input type="hidden" class="rx-medicine-id" name="items[{{ $i }}][medicine_id]" value="{{ $row['medicine_id'] ?? '' }}">
                        </div>

                        <div class="odc-grid">
                            <div class="odc-field">
                                <label class="odc-label">{{ __('doc_ex_rx.strength') }}</label>
                                <input type="text" class="od-input" name="items[{{ $i }}][strength]" value="{{ $row['strength'] ?? '' }}" maxlength="100" placeholder="{{ __('doc_ex_rx.strength_ph') }}" aria-label="{{ __('doc_ex_rx.strength') }}">
                            </div>
                            <div class="odc-field">
                                <label class="odc-label">{{ __('doc_ex_rx.form') }}</label>
                                <input type="text" class="od-input" name="items[{{ $i }}][dosage_form]" value="{{ $row['dosage_form'] ?? '' }}" maxlength="100" placeholder="{{ __('doc_ex_rx.form_ph') }}" aria-label="{{ __('doc_ex_rx.form') }}">
                            </div>
                        </div>
                        <div class="odc-grid odc-grid--3">
                            <div class="odc-field">
                                <label class="odc-label">{{ __('prescriber.dose') }}</label>
                                <input type="text" class="od-input" name="items[{{ $i }}][dose]" value="{{ $row['dose'] ?? '' }}" maxlength="100" placeholder="{{ __('prescriber.dose_ph') }}" aria-label="{{ __('prescriber.dose') }}">
                            </div>
                            <div class="odc-field">
                                <label class="odc-label">{{ __('prescriber.frequency') }}</label>
                                <input type="text" class="od-input" name="items[{{ $i }}][frequency]" value="{{ $row['frequency'] ?? '' }}" maxlength="100" placeholder="{{ __('prescriber.frequency_ph') }}" aria-label="{{ __('prescriber.frequency') }}">
                            </div>
                            <div class="odc-field">
                                <label class="odc-label">{{ __('prescriber.route') }}</label>
                                <select class="od-select" name="items[{{ $i }}][route]" aria-label="{{ __('prescriber.route') }}">
                                    <option value=""></option>
                                    @foreach($routes as $administrationRoute)
                                        <option value="{{ $administrationRoute }}" @selected(($row['route'] ?? '') === $administrationRoute)>{{ $administrationRoute }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="odc-field">
                                <label class="odc-label">{{ __('prescriber.duration_days') }}</label>
                                <input type="number" class="od-input" name="items[{{ $i }}][duration_days]" value="{{ $row['duration_days'] ?? '' }}" min="1" max="365" step="1" inputmode="numeric" aria-label="{{ __('prescriber.duration_days') }}">
                            </div>
                            <div class="odc-field">
                                <label class="odc-label">{{ __('prescriber.quantity') }}</label>
                                <input type="number" class="od-input" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? '' }}" min="1" max="1000" step="1" inputmode="numeric" aria-label="{{ __('prescriber.quantity') }}">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Catalogue autocomplete. Free text is still accepted. --}}
        <datalist id="rx-medicine-list">
            @foreach($medicines as $medicine)
                <option data-id="{{ $medicine->id }}" value="{{ trim($medicine->name . ' ' . ($medicine->strength ?? '')) }}"></option>
            @endforeach
        </datalist>

        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('prescriber.notes') }}</h2></header>
            <div class="od-card__body odc-grid">
                <div class="odc-field odc-field--full">
                    <label class="odc-label" for="rx-notes">{{ __('prescriber.notes') }}</label>
                    <textarea id="rx-notes" name="notes" rows="3" class="odc-textarea" maxlength="1000" placeholder="{{ __('prescriber.notes_ph') }}">{{ old('notes') }}</textarea>
                </div>
                <div class="odc-field">
                    <label class="odc-label" for="rx-validity">{{ __('prescriber.validity_days') }}</label>
                    <input type="number" id="rx-validity" name="validity_days" class="od-input" value="{{ old('validity_days') }}" min="1" max="365" step="1" inputmode="numeric">
                </div>
            </div>
        </section>

        {{-- Drug-interaction warnings (imported CDSS). Advisory only: the
             prescription saves whether or not the box is ticked, and both the
             warnings shown and the acknowledgement are audited on save. --}}
        <section class="od-card" id="rx-interactions" data-check-url="{{ route('portals.prescriber.interaction_check') }}" hidden>
            <header class="od-card__head"><h2 class="od-card__title">{{ __('cdss.panel_title') }}</h2></header>
            <div class="od-card__body">
                <p class="odc-hint">{{ __('cdss.panel_hint') }}</p>
                <ul class="odc-ia-list" id="rx-interactions-list"></ul>
                <label class="odc-check">
                    <input type="hidden" name="interactions_acknowledged" value="0">
                    <input type="checkbox" name="interactions_acknowledged" value="1" id="rx-interactions-ack">
                    <span>{{ __('cdss.acknowledge') }}</span>
                </label>
            </div>
        </section>
        <p class="odc-hint">{{ __('cdss.coverage_note') }}</p>

        <div class="odc-actions">
            <a href="{{ route('portals.prescriber.index') }}" class="od-btn od-btn--secondary">{{ __('prescriber.cancel') }}</a>
            <button type="submit" class="od-btn od-btn--primary"><i data-lucide="file-plus-2"></i> <span>{{ __('prescriber.issue') }}</span></button>
        </div>
    </form>

@endif

</div>
@endsection

@section('scripts')
@if($patient !== null)
<script @cspNonce>
(function () {
    var list   = document.getElementById('rx-rows');
    var addBtn = document.getElementById('rx-add');
    if (!list || !addBtn) return;

    function rows() {
        return Array.prototype.slice.call(list.querySelectorAll('.rx-row'));
    }

    // Single source of truth for the items[] indexes: after any add or remove,
    // every row is renumbered from 0 so the server always receives a dense array.
    function reindex() {
        var all = rows();
        var only = all.length <= 1;

        all.forEach(function (row, i) {
            row.setAttribute('data-index', String(i));

            var num = row.querySelector('.rx-row__num');
            if (num) num.textContent = String(i + 1);

            row.querySelectorAll('[name]').forEach(function (el) {
                var name = el.getAttribute('name') || '';
                el.setAttribute('name', name.replace(/^items\[[^\]]*\]/, 'items[' + i + ']'));
            });

            var btn = row.querySelector('.rx-remove');
            if (btn) {
                btn.disabled = only;
                btn.setAttribute('aria-disabled', only ? 'true' : 'false');
            }
        });
    }

    addBtn.addEventListener('click', function () {
        var all = rows();
        if (!all.length) return;

        var copy = all[0].cloneNode(true);
        copy.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else {
                el.value = '';
            }
        });

        list.appendChild(copy);
        reindex();

        if (typeof lucide !== 'undefined') lucide.createIcons();

        var focusTarget = copy.querySelector('.rx-drug');
        if (focusTarget) focusTarget.focus();
    });

    list.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('.rx-remove') : null;
        if (!btn || btn.disabled) return;
        if (rows().length <= 1) return;   // never leave the form with zero lines

        var row = btn.closest('.rx-row');
        if (row && row.parentNode) row.parentNode.removeChild(row);
        reindex();
    });

    // Map catalogue label to medicine id so an exact match records the link.
    var catalogue = {};
    var datalist  = document.getElementById('rx-medicine-list');
    if (datalist) {
        datalist.querySelectorAll('option').forEach(function (opt) {
            catalogue[(opt.value || '').trim().toLowerCase()] = opt.getAttribute('data-id') || '';
        });
    }

    list.addEventListener('input', function (e) {
        var el = e.target;
        if (!el || !el.classList || !el.classList.contains('rx-drug')) return;

        var row    = el.closest('.rx-row');
        var hidden = row ? row.querySelector('.rx-medicine-id') : null;
        if (hidden) hidden.value = catalogue[(el.value || '').trim().toLowerCase()] || '';
        scheduleCheck();
    });

    // ── Drug-interaction check (warning only, never blocks submit) ─────────
    var panel   = document.getElementById('rx-interactions');
    var listEl  = document.getElementById('rx-interactions-list');
    var form    = document.getElementById('rx-form');
    var timer   = null;

    function scheduleCheck() {
        if (!panel) return;
        clearTimeout(timer);
        timer = setTimeout(runCheck, 400);
    }

    function runCheck() {
        var drugs = rows().map(function (row) {
            var el = row.querySelector('.rx-drug');
            return el ? el.value : '';
        }).filter(function (v) { return v && v.trim() !== ''; });

        if (!drugs.length) { panel.hidden = true; return; }

        var token = form.querySelector('input[name="_token"]');
        fetch(panel.getAttribute('data-check-url'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.value : ''
            },
            body: JSON.stringify({
                patient_id: form.querySelector('input[name="patient_id"]').value,
                drugs: drugs
            })
        }).then(function (r) { return r.ok ? r.json() : { data: [] }; })
          .then(function (json) {
              var alerts = (json && json.data) || [];
              listEl.innerHTML = '';
              alerts.forEach(function (a) {
                  var li = document.createElement('li');
                  var strong = document.createElement('strong');
                  strong.textContent = a.severity_label + ' — ' + a.drug_a + ' + ' + a.drug_b + ': ';
                  li.appendChild(strong);
                  li.appendChild(document.createTextNode(a.message));
                  listEl.appendChild(li);
              });
              panel.hidden = alerts.length === 0;
              if (typeof lucide !== 'undefined') lucide.createIcons();
          })
          .catch(function () { /* advisory only — a failed check never blocks */ });
    }

    list.addEventListener('click', function () { scheduleCheck(); });

    reindex();
    scheduleCheck();
})();
</script>
@endif
@endsection
