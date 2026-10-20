<?php

/*
|--------------------------------------------------------------------------
| OPES health systems catalogue — /solutions
|--------------------------------------------------------------------------
| Every system listed on https://opesware.com/health (the owner's company
| site), imported 2026-10-20. Names, features, workflow steps and audiences
| come from each system's detail page there; nothing here is invented. All
| words live in lang/{en,fr}/solutions.php under systems.{slug}.*.
|
|   slug      URL segment (/solutions/{slug}) and lang key
|   category  core | diagnostics | specialist | administration
|   icon      Lucide key (App\Support\Icons\Lucide)
|   source    detail page on opesware.com
|   features  feature key => Lucide icon (text in systems.{slug}.features.{key})
|   workflow  number of workflow steps (systems.{slug}.workflow.s1..sN); 0 = none on the source
*/

return [
    'source_index' => 'https://opesware.com/health',

    'categories' => ['core', 'diagnostics', 'specialist', 'administration'],

    'systems' => [
        // ── Core platform ────────────────────────────────────────────────
        'opescare-health-id' => [
            'category' => 'core', 'icon' => 'network',
            'source'   => 'https://opesware.com/health/opescare-health-id.html',
            'features' => ['health_id' => 'id-card', 'mpi' => 'git-merge', 'exchange' => 'share-2', 'referrals' => 'send', 'portal' => 'smartphone', 'interop' => 'plug'],
            'workflow' => 0,
        ],
        'opes-emr' => [
            'category' => 'core', 'icon' => 'file-check',
            'source'   => 'https://opesware.com/health/opes-emr.html',
            'features' => ['registration' => 'user-plus', 'notes' => 'scroll-text', 'orders' => 'send', 'history' => 'clock', 'bilingual' => 'languages', 'billing' => 'credit-card'],
            'workflow' => 5,
        ],
        'opes-hospital-his' => [
            'category' => 'core', 'icon' => 'building-2',
            'source'   => 'https://opesware.com/health/opes-hospital-his.html',
            'features' => ['adt' => 'log-in', 'wards' => 'hard-drive', 'theatre' => 'calendar-clock', 'pharmacy' => 'pill', 'billing' => 'receipt', 'reporting' => 'chart-column'],
            'workflow' => 4,
        ],
        'uhc-is' => [
            'category' => 'core', 'icon' => 'shield-check',
            'source'   => 'https://opesware.com/health/uhc-is.html',
            'features' => ['enrolment' => 'users', 'eligibility' => 'check-circle', 'claims' => 'file-check', 'adjudication' => 'sliders-horizontal', 'fraud' => 'shield-alert', 'reporting' => 'chart-column'],
            'workflow' => 0,
        ],

        // ── Diagnostics ──────────────────────────────────────────────────
        'opes-lab' => [
            'category' => 'diagnostics', 'icon' => 'flask-conical',
            'source'   => 'https://opesware.com/health/opes-lab.html',
            'features' => ['ordering' => 'qr-code', 'worklists' => 'list-ordered', 'validation' => 'circle-check', 'delivery' => 'send', 'stock' => 'database', 'quality' => 'chart-column'],
            'workflow' => 4,
        ],
        'radis' => [
            'category' => 'diagnostics', 'icon' => 'scan-line',
            'source'   => 'https://opesware.com/health/radis.html',
            'features' => ['scheduling' => 'calendar', 'worklists' => 'list-ordered', 'reporting' => 'scroll-text', 'images' => 'link', 'results' => 'send', 'utilisation' => 'chart-column'],
            'workflow' => 5,
        ],
        'pharmis' => [
            'category' => 'diagnostics', 'icon' => 'pill',
            'source'   => 'https://opesware.com/health/pharmis.html',
            'features' => ['dispensing' => 'file-check', 'batches' => 'calendar-clock', 'checks' => 'alert-triangle', 'controlled' => 'lock', 'momo' => 'smartphone', 'reorder' => 'refresh-cw', 'offline' => 'cloud-off', 'branches' => 'git-branch'],
            'workflow' => 0,
        ],
        'opes-triage' => [
            'category' => 'diagnostics', 'icon' => 'list-ordered',
            'source'   => 'https://opesware.com/health/opes-triage.html',
            'features' => ['urgency' => 'alert-circle', 'live_view' => 'monitor', 'escalation' => 'bell-ring', 'wait_times' => 'timer', 'any_software' => 'plug', 'reporting' => 'chart-column'],
            'workflow' => 0,
        ],

        // ── Specialist systems ───────────────────────────────────────────
        'cardis' => [
            'category' => 'specialist', 'icon' => 'heart-pulse',
            'source'   => 'https://opesware.com/health/cardis.html',
            'features' => ['investigations' => 'activity', 'risk' => 'chart-column', 'procedures' => 'scroll-text', 'medication' => 'pill', 'follow_up' => 'calendar-clock', 'record' => 'network'],
            'workflow' => 0,
        ],
        'dentis' => [
            'category' => 'specialist', 'icon' => 'smile',
            'source'   => 'https://opesware.com/health/dentis.html',
            'features' => ['charting' => 'smile', 'planning' => 'list-ordered', 'recalls' => 'calendar-clock', 'imaging' => 'scan-line', 'payments' => 'credit-card', 'practitioners' => 'users', 'lab' => 'send'],
            'workflow' => 0,
        ],
        'dermis' => [
            'category' => 'specialist', 'icon' => 'fingerprint',
            'source'   => 'https://opesware.com/health/dermis.html',
            'features' => ['photos' => 'eye', 'body_map' => 'map-pin', 'diagnosis' => 'stethoscope', 'treatment' => 'scroll-text', 'biopsy' => 'flask-conical', 'follow_up' => 'calendar-clock'],
            'workflow' => 0,
        ],
        'endois' => [
            'category' => 'specialist', 'icon' => 'droplet',
            'source'   => 'https://opesware.com/health/endois.html',
            'features' => ['glycaemic' => 'chart-column', 'complications' => 'alert-triangle', 'insulin' => 'pill', 'metabolic' => 'activity', 'endocrine' => 'stethoscope', 'education' => 'book-open'],
            'workflow' => 0,
        ],
        'gynobsis' => [
            'category' => 'specialist', 'icon' => 'heart',
            'source'   => 'https://opesware.com/health/gynobsis.html',
            'features' => ['antenatal' => 'calendar', 'risk' => 'alert-triangle', 'labour' => 'activity', 'postnatal' => 'baby', 'gynaecology' => 'stethoscope', 'reporting' => 'chart-column'],
            'workflow' => 6,
        ],
        'mhis' => [
            'category' => 'specialist', 'icon' => 'message-circle',
            'source'   => 'https://opesware.com/health/mhis.html',
            'features' => ['confidentiality' => 'lock-keyhole', 'assessments' => 'chart-column', 'sessions' => 'scroll-text', 'medication' => 'pill', 'care_plans' => 'list-ordered', 'risk' => 'shield-alert'],
            'workflow' => 0,
        ],
        'ndis' => [
            'category' => 'specialist', 'icon' => 'apple',
            'source'   => 'https://opesware.com/health/ndis.html',
            'features' => ['growth' => 'chart-column', 'screening' => 'search', 'feeding' => 'apple', 'dietary' => 'scroll-text', 'commodities' => 'database', 'reporting' => 'file-check'],
            'workflow' => 0,
        ],
        'ophis' => [
            'category' => 'specialist', 'icon' => 'eye',
            'source'   => 'https://opesware.com/health/ophis.html',
            'features' => ['acuity' => 'eye', 'refraction' => 'scroll-text', 'surgery' => 'calendar-clock', 'glaucoma' => 'chart-column', 'optical' => 'tag', 'outreach' => 'map-pin', 'identity' => 'id-card', 'roles' => 'user-check'],
            'workflow' => 0,
        ],
        'orthois' => [
            'category' => 'specialist', 'icon' => 'accessibility',
            'source'   => 'https://opesware.com/health/orthois.html',
            'features' => ['assessment' => 'stethoscope', 'specification' => 'sliders-horizontal', 'fabrication' => 'server', 'fitting' => 'user-check', 'review' => 'refresh-cw', 'stock' => 'database'],
            'workflow' => 5,
        ],
        'paedis' => [
            'category' => 'specialist', 'icon' => 'baby',
            'source'   => 'https://opesware.com/health/paedis.html',
            'features' => ['growth' => 'chart-column', 'immunisation' => 'shield-check', 'dosing' => 'pill', 'milestones' => 'star', 'inpatient' => 'heart-pulse', 'mother' => 'link'],
            'workflow' => 0,
        ],
        'rehabis' => [
            'category' => 'specialist', 'icon' => 'activity',
            'source'   => 'https://opesware.com/health/rehabis.html',
            'features' => ['assessment' => 'sliders-horizontal', 'programmes' => 'list-ordered', 'sessions' => 'calendar-clock', 'outcomes' => 'chart-column', 'mdt' => 'users', 'home' => 'book-open'],
            'workflow' => 0,
        ],
        'sltis' => [
            'category' => 'specialist', 'icon' => 'languages',
            'source'   => 'https://opesware.com/health/sltis.html',
            'features' => ['assessment' => 'scroll-text', 'bilingual' => 'languages', 'planning' => 'list-ordered', 'sessions' => 'calendar-clock', 'progress' => 'chart-column', 'liaison' => 'users'],
            'workflow' => 0,
        ],

        // ── Administration & finance ─────────────────────────────────────
        'opes-cdms' => [
            'category' => 'administration', 'icon' => 'scroll-text',
            'source'   => 'https://opesware.com/health/opes-cdms.html',
            'features' => ['scanning' => 'scan-line', 'classification' => 'tag', 'search' => 'search', 'consent' => 'file-check', 'retention' => 'clock', 'audit' => 'shield'],
            'workflow' => 0,
        ],
        'rcmis' => [
            'category' => 'administration', 'icon' => 'receipt',
            'source'   => 'https://opesware.com/health/rcmis.html',
            'features' => ['charges' => 'receipt', 'claims' => 'file-check', 'momo' => 'smartphone', 'debt' => 'timer', 'payers' => 'users', 'reporting' => 'chart-column'],
            'workflow' => 0,
        ],
    ],

    // Facility types offered on the request form (labels: solutions.request.types.*).
    'facility_types' => ['community_health_centre', 'hospital', 'ngo_hospital', 'clinic', 'pharmacy', 'laboratory', 'imaging_centre', 'other'],
];
