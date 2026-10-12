@extends('layouts.portal')

@section('title', __('prescriber.create_title'))

@section('breadcrumb_home', __('prescriber.title'))
@section('breadcrumb_home_url', route('portals.prescriber.index'))
@section('breadcrumb_section', __('prescriber.create_title'))

@section('head')
<style>
    /* Repeatable medicine lines. Each line is a self-contained card so a row can
       be added or removed without the surrounding layout shifting. */
    .rx-rows { display: flex; flex-direction: column; gap: var(--p-space-4); }

    .rx-row {
        border: 1px solid var(--p-border);
        border-radius: var(--p-radius);
        background: var(--p-surface-2);
        padding: var(--p-space-4);
    }

    .rx-row .form-control { background: var(--p-surface); }

    .rx-row__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--p-space-3);
        margin-bottom: var(--p-space-3);
    }

    .rx-row__num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.6rem;
        height: 1.6rem;
        border-radius: 999px;
        background: #0F4C81;
        color: #FFFFFF;
        font-size: 0.75rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .rx-remove[disabled] { opacity: 0.45; cursor: not-allowed; }

    .rx-patient { display: flex; align-items: center; gap: var(--p-space-3); }

    .rx-patient__avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 999px;
        background: rgba(15,76,129,.12);
        color: #0F4C81;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .rx-patient__name { font-weight: 600; color: var(--p-text); }
    .rx-patient__meta { font-size: 0.82rem; color: var(--p-text-muted); margin-top: 0.15rem; }

    .rx-lookup { max-width: 480px; }

    @media (max-width: 720px) {
        .rx-row .form-row,
        .rx-row .form-row-3 { grid-template-columns: 1fr; gap: var(--p-space-3); }
    }
</style>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('prescriber.create_title') }}</h1>
        <p class="page-subtitle">{{ __('prescriber.create_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.prescriber.index') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i>
        <span>{{ __('prescriber.back') }}</span>
    </a>
</div>

@if(session('error'))
<div class="alert alert-danger mb-4" role="alert">
    <i data-lucide="triangle-alert"></i>
    <div>{{ session('error') }}</div>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger mb-4" role="alert">
    <i data-lucide="triangle-alert"></i>
    <div>
        <ul class="alert-list">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

@if($patient === null)

    {{-- ── Step 1: find the patient ────────────────────────────────────────
         Exact Health ID only — patient names are deliberately not searchable. --}}
    <div class="panel form-panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="search"></i>
                {{ __('prescriber.find_patient') }}
            </h2>
        </div>
        <div class="panel-body">

            @if($notFound)
            <div class="alert alert-danger mb-4" role="alert">
                <i data-lucide="user-x"></i>
                <div>{{ __('prescriber.not_found') }}</div>
            </div>
            @endif

            <form method="GET" action="{{ route('portals.prescriber.create') }}" class="rx-lookup">
                <div class="form-group mb-4">
                    <label class="form-label form-label-required" for="rx-health-id">{{ __('prescriber.health_id') }}</label>
                    <input type="text"
                           id="rx-health-id"
                           name="health_id"
                           class="form-control"
                           value="{{ request('health_id') }}"
                           required
                           autocomplete="off"
                           autofocus>
                    <div class="form-hint">{{ __('prescriber.health_id_hint') }}</div>
                </div>

                <div class="row-actions-inline">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="search"></i>
                        <span>{{ __('prescriber.lookup') }}</span>
                    </button>
                    <a href="{{ route('portals.prescriber.index') }}" class="btn btn-secondary">{{ __('prescriber.cancel') }}</a>
                </div>
            </form>

        </div>
    </div>

@else

    @php
        // Rebuild the submitted lines on a validation bounce; otherwise start with
        // a single empty line. Always an array of arrays so the row markup can read
        // keys without null-offset noise.
        $oldItems = old('items');
        $itemRows = (is_array($oldItems) && count($oldItems) > 0)
            ? array_values(array_map(fn ($r) => (array) $r, $oldItems))
            : [[]];

        $patientName    = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
        $patientInitial = strtoupper(mb_substr($patientName !== '' ? $patientName : 'P', 0, 1));
        $patientDob     = $patient->date_of_birth;
    @endphp

    {{-- ── Selected patient ──────────────────────────────────────────────── --}}
    <div class="panel mb-4">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="user-round"></i>
                {{ __('prescriber.patient') }}
            </h2>
            <a href="{{ route('portals.prescriber.create') }}" class="btn btn-ghost btn-sm">
                <i data-lucide="repeat"></i>
                <span>{{ __('prescriber.change_patient') }}</span>
            </a>
        </div>
        <div class="panel-body">
            <div class="rx-patient">
                <span class="rx-patient__avatar" aria-hidden="true">{{ $patientInitial }}</span>
                <div class="min-w-0">
                    <div class="rx-patient__name">{{ $patientName }}</div>
                    <div class="rx-patient__meta">
                        {{ __('prescriber.health_id') }}: <span class="kv-strong">{{ $patient->health_id }}</span>
                        @if($patientDob)
                            &middot; {{ $patientDob->format('Y-m-d') }}
                        @endif
                        @if($patient->sex)
                            &middot; @enum($patient->sex)
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── The prescription ──────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('portals.prescriber.store') }}" id="rx-form">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">

        <div class="panel mb-4">
            <div class="panel-header">
                <h2 class="panel-title">
                    <i data-lucide="pill"></i>
                    {{ __('prescriber.medicines') }}
                </h2>
            </div>
            <div class="panel-body">

                <div class="rx-rows" id="rx-rows">
                    @foreach($itemRows as $i => $row)
                    <div class="rx-row" data-index="{{ $i }}">
                        <div class="rx-row__head">
                            <span class="rx-row__num">{{ $i + 1 }}</span>
                            <button type="button" class="btn btn-ghost btn-sm rx-remove">
                                <i data-lucide="trash-2"></i>
                                <span>{{ __('prescriber.remove_item') }}</span>
                            </button>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label form-label-required">{{ __('prescriber.drug_name') }}</label>
                            <input type="text"
                                   class="form-control rx-drug"
                                   name="items[{{ $i }}][drug_name]"
                                   value="{{ $row['drug_name'] ?? '' }}"
                                   list="rx-medicine-list"
                                   maxlength="200"
                                   required
                                   autocomplete="off"
                                   aria-label="{{ __('prescriber.drug_name') }}">
                            {{-- Filled by JS only when the typed text matches a catalogue entry exactly.
                                 Empty stays empty: ConvertEmptyStringsToNull turns it into null, which
                                 the nullable|uuid rule accepts. Off-catalogue drugs post name-only. --}}
                            <input type="hidden"
                                   class="rx-medicine-id"
                                   name="items[{{ $i }}][medicine_id]"
                                   value="{{ $row['medicine_id'] ?? '' }}">
                        </div>

                        <div class="form-row-3 mb-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('prescriber.dose') }}</label>
                                <input type="text"
                                       class="form-control"
                                       name="items[{{ $i }}][dose]"
                                       value="{{ $row['dose'] ?? '' }}"
                                       maxlength="100"
                                       placeholder="{{ __('prescriber.dose_ph') }}"
                                       aria-label="{{ __('prescriber.dose') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('prescriber.frequency') }}</label>
                                <input type="text"
                                       class="form-control"
                                       name="items[{{ $i }}][frequency]"
                                       value="{{ $row['frequency'] ?? '' }}"
                                       maxlength="100"
                                       placeholder="{{ __('prescriber.frequency_ph') }}"
                                       aria-label="{{ __('prescriber.frequency') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('prescriber.route') }}</label>
                                <select class="form-control"
                                        name="items[{{ $i }}][route]"
                                        aria-label="{{ __('prescriber.route') }}">
                                    <option value=""></option>
                                    @foreach($routes as $administrationRoute)
                                        <option value="{{ $administrationRoute }}"
                                            @selected(($row['route'] ?? '') === $administrationRoute)>{{ $administrationRoute }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">{{ __('prescriber.duration_days') }}</label>
                                <input type="number"
                                       class="form-control"
                                       name="items[{{ $i }}][duration_days]"
                                       value="{{ $row['duration_days'] ?? '' }}"
                                       min="1" max="365" step="1" inputmode="numeric"
                                       aria-label="{{ __('prescriber.duration_days') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('prescriber.quantity') }}</label>
                                <input type="number"
                                       class="form-control"
                                       name="items[{{ $i }}][quantity]"
                                       value="{{ $row['quantity'] ?? '' }}"
                                       min="1" max="1000" step="1" inputmode="numeric"
                                       aria-label="{{ __('prescriber.quantity') }}">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="row-actions-inline mt-3">
                    <button type="button" class="btn btn-secondary btn-sm" id="rx-add">
                        <i data-lucide="plus"></i>
                        <span>{{ __('prescriber.add_item') }}</span>
                    </button>
                </div>

            </div>
        </div>

        {{-- Catalogue autocomplete. Free text is still accepted, so an
             off-catalogue medicine can always be written. --}}
        <datalist id="rx-medicine-list">
            @foreach($medicines as $medicine)
                <option data-id="{{ $medicine->id }}"
                        value="{{ trim($medicine->name . ' ' . ($medicine->strength ?? '')) }}"></option>
            @endforeach
        </datalist>

        <div class="panel mb-4">
            <div class="panel-body">
                <div class="form-group mb-4">
                    <label class="form-label" for="rx-notes">{{ __('prescriber.notes') }}</label>
                    <textarea id="rx-notes"
                              name="notes"
                              rows="3"
                              class="form-control"
                              maxlength="1000"
                              placeholder="{{ __('prescriber.notes_ph') }}">{{ old('notes') }}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="rx-validity">{{ __('prescriber.validity_days') }}</label>
                        <input type="number"
                               id="rx-validity"
                               name="validity_days"
                               class="form-control"
                               value="{{ old('validity_days') }}"
                               min="1" max="365" step="1" inputmode="numeric">
                    </div>
                    <div class="form-group"></div>
                </div>
            </div>
        </div>

        {{-- Drug-interaction warnings (imported CDSS). Advisory only: the
             prescription saves whether or not the box is ticked, and both the
             warnings shown and the acknowledgement are audited on save. --}}
        <div class="panel mb-4" id="rx-interactions" data-check-url="{{ route('portals.prescriber.interaction_check') }}" hidden>
            <div class="panel-header">
                <h2 class="panel-title">
                    <i data-lucide="triangle-alert"></i>
                    {{ __('cdss.panel_title') }}
                </h2>
            </div>
            <div class="panel-body">
                <p class="form-hint mb-3">{{ __('cdss.panel_hint') }}</p>
                <ul class="alert-list mb-3" id="rx-interactions-list"></ul>
                <label class="form-check">
                    <input type="hidden" name="interactions_acknowledged" value="0">
                    <input type="checkbox" name="interactions_acknowledged" value="1" id="rx-interactions-ack">
                    <span>{{ __('cdss.acknowledge') }}</span>
                </label>
            </div>
        </div>
        <p class="form-hint mb-4">{{ __('cdss.coverage_note') }}</p>

        <div class="row-actions-inline">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="file-plus-2"></i>
                <span>{{ __('prescriber.issue') }}</span>
            </button>
            <a href="{{ route('portals.prescriber.index') }}" class="btn btn-secondary">{{ __('prescriber.cancel') }}</a>
        </div>
    </form>

@endif

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
