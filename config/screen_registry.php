<?php

/**
 * OpesCare Screen Registry
 *
 * Maps every existing portal route name to a canonical screen key.
 * Screen keys are used in Phase D (widget/sidebar assignment) and Phase E
 * (data-scope & action-policy enforcement).
 *
 * Format:
 *   'screen_key' => [
 *       'route'        => 'route.name',
 *       'portal'       => 'patient|staff|admin|insurance|developer|lite',
 *       'label'        => 'Human-readable name',
 *       'category'     => 'clinical|hr|billing|inventory|admin|...',
 *       'audit'        => true|false,   // should patient-data views be audit-logged?
 *       'high_risk'    => true|false,   // requires high-risk confirmation modal?
 *       'read_only'    => true|false,   // GET-only screen?
 *   ],
 */

return [

    // ─── Patient Portal ───────────────────────────────────────────────────────

    'patient.dashboard' => [
        'route'     => 'portals.patient',
        'portal'    => 'patient',
        'label'     => 'My Health ID',
        'category'  => 'identity',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => true,
    ],
    'patient.appointments' => [
        'route'     => 'portals.patient.appointments',
        'portal'    => 'patient',
        'label'     => 'My Appointments',
        'category'  => 'appointments',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'patient.access_logs' => [
        'route'     => 'portals.patient.logs',
        'portal'    => 'patient',
        'label'     => 'Who Accessed My Record',
        'category'  => 'privacy',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],

    // ─── Staff Portal — Overview ──────────────────────────────────────────────

    'staff.dashboard' => [
        'route'     => 'portals.staff',
        'portal'    => 'staff',
        'label'     => 'Staff Dashboard',
        'category'  => 'overview',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'staff.analytics' => [
        'route'     => 'portals.staff.analytics',
        'portal'    => 'staff',
        'label'     => 'Analytics',
        'category'  => 'analytics',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'staff.analytics.queue' => [
        'route'     => 'portals.staff.analytics.queue',
        'portal'    => 'staff',
        'label'     => 'Queue Analytics',
        'category'  => 'analytics',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'staff.analytics.ward' => [
        'route'     => 'portals.staff.analytics.ward',
        'portal'    => 'staff',
        'label'     => 'Ward Analytics',
        'category'  => 'analytics',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'staff.analytics.financial' => [
        'route'     => 'portals.staff.analytics.financial',
        'portal'    => 'staff',
        'label'     => 'Financial Analytics',
        'category'  => 'analytics',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'staff.analytics.data_quality' => [
        'route'     => 'portals.staff.analytics.data_quality',
        'portal'    => 'staff',
        'label'     => 'Data Quality',
        'category'  => 'analytics',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],

    // ─── Staff Portal — Clinical ──────────────────────────────────────────────

    'staff.appointments' => [
        'route'     => 'portals.staff.appointments',
        'portal'    => 'staff',
        'label'     => 'Appointments',
        'category'  => 'clinical',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.queue' => [
        'route'     => 'portals.staff.queue',
        'portal'    => 'staff',
        'label'     => 'Patient Queue',
        'category'  => 'clinical',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.queue_display' => [
        'route'     => 'portals.staff.queue-display',
        'portal'    => 'staff',
        'label'     => 'Queue Display',
        'category'  => 'clinical',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'staff.visits' => [
        'route'     => 'portals.staff.visits',
        'portal'    => 'staff',
        'label'     => 'Visits / Consultations',
        'category'  => 'clinical',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.cdss' => [
        'route'     => 'portals.staff.cdss',
        'portal'    => 'staff',
        'label'     => 'Clinical Alerts (CDSS)',
        'category'  => 'clinical',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.immunizations' => [
        'route'     => 'portals.staff.immunizations',
        'portal'    => 'staff',
        'label'     => 'Immunizations',
        'category'  => 'clinical',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.referrals' => [
        'route'     => 'portals.staff.referrals',
        'portal'    => 'staff',
        'label'     => 'Referrals',
        'category'  => 'clinical',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.telemedicine' => [
        'route'     => 'portals.staff.telemedicine.index',
        'portal'    => 'staff',
        'label'     => 'Telemedicine',
        'category'  => 'clinical',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.search' => [
        'route'     => 'portals.staff.search',
        'portal'    => 'staff',
        'label'     => 'Patient Search',
        'category'  => 'clinical',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => true,
    ],

    // ─── Staff Portal — Ward ──────────────────────────────────────────────────

    'staff.wards' => [
        'route'     => 'portals.staff.wards',
        'portal'    => 'staff',
        'label'     => 'Wards',
        'category'  => 'ward',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.wards.admissions' => [
        'route'     => 'portals.staff.wards.admissions',
        'portal'    => 'staff',
        'label'     => 'Ward Admissions',
        'category'  => 'ward',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Staff Portal — Inventory ─────────────────────────────────────────────

    'staff.inventory.pharmacy' => [
        'route'     => 'portals.staff.inventory.pharmacy',
        'portal'    => 'staff',
        'label'     => 'Pharmacy Inventory',
        'category'  => 'inventory',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.inventory.blood' => [
        'route'     => 'portals.staff.inventory.blood',
        'portal'    => 'staff',
        'label'     => 'Blood Bank',
        'category'  => 'inventory',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Staff Portal — HR ────────────────────────────────────────────────────

    'staff.hr.directory' => [
        'route'     => 'portals.staff.hr.directory',
        'portal'    => 'staff',
        'label'     => 'Staff Directory',
        'category'  => 'hr',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.hr.shifts' => [
        'route'     => 'portals.staff.hr.shifts',
        'portal'    => 'staff',
        'label'     => 'Shift Management',
        'category'  => 'hr',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.hr.roster' => [
        'route'     => 'portals.staff.hr.roster',
        'portal'    => 'staff',
        'label'     => 'Duty Roster',
        'category'  => 'hr',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],
    'staff.hr.leave' => [
        'route'     => 'portals.staff.hr.leave',
        'portal'    => 'staff',
        'label'     => 'Leave Requests',
        'category'  => 'hr',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Staff Portal — Billing ───────────────────────────────────────────────

    'staff.billing' => [
        'route'     => 'portals.staff.billing',
        'portal'    => 'staff',
        'label'     => 'Billing',
        'category'  => 'billing',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Staff Portal — Supply Chain ──────────────────────────────────────────

    'staff.supply' => [
        'route'     => 'portals.staff.supply',
        'portal'    => 'staff',
        'label'     => 'Supply Chain',
        'category'  => 'supply',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Staff Portal — Files ─────────────────────────────────────────────────

    'staff.files' => [
        'route'     => 'portals.staff.files.index',
        'portal'    => 'staff',
        'label'     => 'Medical Files',
        'category'  => 'files',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Staff Portal — Data Import ───────────────────────────────────────────

    'staff.data_import' => [
        'route'     => 'portals.staff.data_import.index',
        'portal'    => 'staff',
        'label'     => 'Data Import',
        'category'  => 'data_import',
        'audit'     => true,
        'high_risk' => true,
        'read_only' => false,
    ],

    // ─── Insurance Portal ─────────────────────────────────────────────────────

    'insurance.claims' => [
        'route'     => 'portals.insurance.claims',
        'portal'    => 'insurance',
        'label'     => 'Claims',
        'category'  => 'insurance',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'insurance.preauths' => [
        'route'     => 'portals.insurance.preauths',
        'portal'    => 'insurance',
        'label'     => 'Pre-Authorizations',
        'category'  => 'insurance',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'insurance.policies' => [
        'route'     => 'portals.insurance.policies',
        'portal'    => 'insurance',
        'label'     => 'Policies',
        'category'  => 'insurance',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],
    'insurance.providers' => [
        'route'     => 'portals.insurance.providers',
        'portal'    => 'insurance',
        'label'     => 'Providers',
        'category'  => 'insurance',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Admin Portal — Overview ──────────────────────────────────────────────

    'admin.dashboard' => [
        'route'     => 'portals.admin',
        'portal'    => 'admin',
        'label'     => 'Admin Dashboard',
        'category'  => 'admin',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],

    // ─── Admin Portal — Control Center (platform admin only) ─────────────────

    'admin.cc' => [
        'route'     => 'portals.admin.cc',
        'portal'    => 'admin',
        'label'     => 'Control Center',
        'category'  => 'platform',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'admin.cc.feature_flags' => [
        'route'     => 'portals.admin.cc.feature_flags',
        'portal'    => 'admin',
        'label'     => 'Feature Flags',
        'category'  => 'platform',
        'audit'     => true,
        'high_risk' => true,
        'read_only' => false,
    ],
    'admin.cc.modules' => [
        'route'     => 'portals.admin.cc.modules',
        'portal'    => 'admin',
        'label'     => 'Module Toggles',
        'category'  => 'platform',
        'audit'     => true,
        'high_risk' => true,
        'read_only' => false,
    ],
    'admin.cc.health' => [
        'route'     => 'portals.admin.cc.health',
        'portal'    => 'admin',
        'label'     => 'System Health',
        'category'  => 'platform',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'admin.cc.audit' => [
        'route'     => 'portals.admin.cc.audit',
        'portal'    => 'admin',
        'label'     => 'Audit Log',
        'category'  => 'platform',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'admin.cc.maintenance' => [
        'route'     => 'portals.admin.cc.maintenance',
        'portal'    => 'admin',
        'label'     => 'Maintenance Windows',
        'category'  => 'platform',
        'audit'     => true,
        'high_risk' => true,
        'read_only' => false,
    ],

    // ─── Admin Portal — Security Ops ──────────────────────────────────────────

    'admin.security' => [
        'route'     => 'portals.admin.security',
        'portal'    => 'admin',
        'label'     => 'Security Operations',
        'category'  => 'security',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'admin.security.incidents' => [
        'route'     => 'portals.admin.security.incidents',
        'portal'    => 'admin',
        'label'     => 'Security Incidents',
        'category'  => 'security',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'admin.security.emergency_access' => [
        'route'     => 'portals.admin.security.emergency_access',
        'portal'    => 'admin',
        'label'     => 'Emergency Access Review',
        'category'  => 'security',
        'audit'     => true,
        'high_risk' => true,
        'read_only' => false,
    ],
    'admin.security.audit_explorer' => [
        'route'     => 'portals.admin.security.audit_explorer',
        'portal'    => 'admin',
        'label'     => 'Audit Explorer',
        'category'  => 'security',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],

    // ─── Admin Portal — Connect Suite ─────────────────────────────────────────

    'admin.connect' => [
        'route'     => 'portals.admin.connect',
        'portal'    => 'admin',
        'label'     => 'Connect Suite',
        'category'  => 'connect',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Admin Portal — Bridge Agent ─────────────────────────────────────────

    'admin.bridge' => [
        'route'     => 'portals.admin.bridge',
        'portal'    => 'admin',
        'label'     => 'Bridge Agent',
        'category'  => 'bridge',
        'audit'     => true,
        'high_risk' => true,
        'read_only' => false,
    ],

    // ─── Admin Portal — Subscription ─────────────────────────────────────────

    'admin.subscription' => [
        'route'     => 'portals.admin.subscription',
        'portal'    => 'admin',
        'label'     => 'Subscriptions',
        'category'  => 'saas',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Admin Portal — Legal ─────────────────────────────────────────────────

    'admin.legal' => [
        'route'     => 'portals.admin.legal',
        'portal'    => 'admin',
        'label'     => 'Legal Documents',
        'category'  => 'legal',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Admin Portal — Onboarding / KPI / Go-Live ───────────────────────────

    'admin.onboarding' => [
        'route'     => 'portals.admin.onboarding',
        'portal'    => 'admin',
        'label'     => 'Facility Onboarding',
        'category'  => 'onboarding',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],
    'admin.kpi' => [
        'route'     => 'portals.admin.kpi.index',
        'portal'    => 'admin',
        'label'     => 'KPI Dashboard',
        'category'  => 'analytics',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'admin.go_live' => [
        'route'     => 'portals.admin.go-live',
        'portal'    => 'admin',
        'label'     => 'Go-Live Readiness',
        'category'  => 'onboarding',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'admin.certifications' => [
        'route'     => 'portals.admin.certifications.index',
        'portal'    => 'admin',
        'label'     => 'Integration Certifications',
        'category'  => 'connect',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'admin.code_mappings' => [
        'route'     => 'portals.admin.code_mappings.index',
        'portal'    => 'admin',
        'label'     => 'Code System Mappings',
        'category'  => 'clinical',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'admin.developer.accounts' => [
        'route'     => 'portals.admin.developer.accounts',
        'portal'    => 'admin',
        'label'     => 'Developer Accounts',
        'category'  => 'developer',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],
    'admin.developer.production_requests' => [
        'route'     => 'portals.admin.developer.production_requests',
        'portal'    => 'admin',
        'label'     => 'Production Requests',
        'category'  => 'developer',
        'audit'     => true,
        'high_risk' => true,
        'read_only' => false,
    ],

    // ─── Developer Portal ─────────────────────────────────────────────────────

    'developer.dashboard' => [
        'route'     => 'portals.developer.dashboard',
        'portal'    => 'developer',
        'label'     => 'Developer Dashboard',
        'category'  => 'developer',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => true,
    ],
    'developer.apps' => [
        'route'     => 'portals.developer.apps',
        'portal'    => 'developer',
        'label'     => 'My Apps',
        'category'  => 'developer',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],
    'developer.production_requests' => [
        'route'     => 'portals.developer.production_requests',
        'portal'    => 'developer',
        'label'     => 'Production Requests',
        'category'  => 'developer',
        'audit'     => true,
        'high_risk' => false,
        'read_only' => false,
    ],

    // ─── Lite Portal ──────────────────────────────────────────────────────────

    'lite.dashboard' => [
        'route'     => 'portals.lite.dashboard',
        'portal'    => 'lite',
        'label'     => 'Lite Dashboard',
        'category'  => 'lite',
        'audit'     => false,
        'high_risk' => false,
        'read_only' => false,
    ],

];
