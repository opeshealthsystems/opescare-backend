<?php

namespace App\Support\Diagrams;

/**
 * The one source of truth for every flow and architecture diagram.
 *
 * The web SVG, the numbered step list, the PDF figure and /docs/flows.json are
 * all produced from the arrays below; nothing is drawn by hand anywhere else.
 * tests/Feature/Diagrams/DiagramSpecTest.php proves each graph (one start,
 * everything reachable, every path ends in an outcome, no undeclared cycles,
 * every step a real path) and resolves every node's evidence in the running
 * app.
 *
 * Node:   id => [kind, icon, [col, row], label key, sub key, evidence[]]
 * Edge:   [from, to, kind, label key|null, options]
 *         kind: request | response | async | refusal
 *         options: out/in port (t b l r, with - or + for an offset port),
 *                  loop => true for a declared retry loop
 * Step:   [path of node ids, title key, text key]
 * Group:  [label key, col0, row0, col1, row1]
 *
 * Evidence: route:NAME   a named route (Route::has)
 *           uri:METHOD path   a registered route by method + URI
 *           class:FQCN   method:FQCN::name   property:FQCN::name
 *           config:dotted.key   command:artisan-name
 */
final class Specs
{
    private const G = 'App\\Support\\ClinicalRelationshipGate';
    private const FHIR = 'App\\Services\\Fhir\\FhirIngestGuard';
    private const MPI = 'App\\Modules\\MasterPatientIndex\\Services\\MasterPatientIndexService';
    private const MERGE = 'App\\Modules\\MasterPatientIndex\\Services\\PatientMergeService';
    private const GOV = 'App\\Http\\Controllers\\Api\\Mobile\\MobileGovernanceController';
    private const FAM = 'App\\Http\\Controllers\\Api\\Mobile\\MobileFamilyController';
    private const SYNC = 'App\\Modules\\Offline\\Services\\SyncService';
    private const JOB = 'App\\Jobs\\SendWebhookJob';

    /** Docs flow pages, in nav order. */
    public const DOCS_FLOWS = ['consent', 'fhir', 'mpi', 'webhooks', 'qr'];

    /** Every diagram id. */
    public static function ids(): array
    {
        return array_keys(self::all());
    }

    public static function exists(string $id): bool
    {
        return array_key_exists($id, self::all());
    }

    public static function get(string $id): array
    {
        $all = self::all();
        if (! isset($all[$id])) {
            throw new \InvalidArgumentException("Unknown diagram {$id}");
        }

        return ['id' => $id] + $all[$id];
    }

    /** @return array<string, array> */
    public static function all(): array
    {
        static $all = null;

        return $all ??= [
            'overview'              => self::overview(),
            'consent'               => self::consent(),
            'fhir'                  => self::fhir(),
            'mpi'                   => self::mpi(),
            'webhooks'              => self::webhooks(),
            'qr'                    => self::qr(),
            'record-is-the-product' => self::recordIsTheProduct(),
            'one-app-five-roles'    => self::oneAppFiveRoles(),
            'family-act-as'         => self::familyActAs(),
            'offline-outbox'        => self::offlineOutbox(),
            'pharmacy-dispense'     => self::pharmacyDispense(),
            'lab-release'           => self::labRelease(),
            'blood-request-queue'   => self::bloodRequestQueue(),
            'facility-go-live'      => self::facilityGoLive(),
        ];
    }

    // ── Developer docs ─────────────────────────────────────────────────────

    private static function overview(): array
    {
        $p = 'docs_ui.dg.overview.box.';
        $f = 'flows.overview.';

        return [
            'title' => 'docs_ui.dg.overview.title', 'summary' => 'docs_ui.dg.overview.summary',
            'cols' => 4, 'rows' => 6,
            'groups' => [
                ['docs_ui.dg.overview.group.host', 0, 1, 2, 2],
            ],
            'nodes' => [
                'clients'   => ['actor', 'users', [1, 0], $f . 'clients', $f . 'clients_sub', ['route:docs.index', 'route:docs.widget', 'route:docs.bridge']],
                'r_web'     => ['service', 'monitor', [0, 1], $p . 'r_web', $p . 'r_web_sub', ['route:login', 'route:public.network.medicine-finder']],
                'r_api'     => ['service', 'braces', [1, 1], $p . 'r_api', $p . 'r_api_sub', ['uri:POST api/v1/connect/auth/token', 'uri:GET api/mobile/consents']],
                'r_fhir'    => ['service', 'server', [2, 1], $p . 'r_fhir', $p . 'r_fhir_sub', ['uri:POST api/fhir/R4', 'uri:GET api/fhir/R4/metadata']],
                'g_auth'    => ['gate', 'key-round', [0, 2], $p . 'g_auth', $p . 'g_auth_sub', ['route:login.submit', 'method:App\\Http\\Controllers\\Api\\V1\\Connect\\AuthController::introspect']],
                'g_consent' => ['gate', 'shield-check', [1, 2], $p . 'g_consent', $p . 'g_consent_sub', ['method:' . self::G . '::evaluate', 'config:staff_capabilities.capabilities.clinical_chart']],
                'g_fhir'    => ['gate', 'shield', [2, 2], $p . 'g_fhir', $p . 'g_fhir_sub', ['method:' . self::FHIR . '::requireConsent']],
                'refused'   => ['outcome-refused', 'circle-x', [3, 2], $f . 'refused', $f . 'refused_sub', ['route:docs.errors']],
                'services'  => ['service', 'heart-pulse', [1, 3], $f . 'services', $f . 'services_sub', ['class:App\\Modules\\PatientIdentity\\Services\\PatientIdentityService', 'class:App\\Services\\WebhookService', 'route:public.care-map']],
                'pg'        => ['store', 'database', [1, 4], $p . 'pg', $p . 'pg_sub', ['config:database.connections.pgsql']],
                'audit'     => ['store', 'scroll-text', [2, 4], $p . 'audit', $p . 'audit_sub', ['class:App\\Models\\AccessLog', 'uri:GET api/mobile/access-logs']],
                'ok'        => ['outcome-ok', 'circle-check', [2, 5], $f . 'ok', $f . 'ok_sub', ['route:docs.api']],
            ],
            'edges' => [
                ['clients', 'r_web', 'request'],
                ['clients', 'r_api', 'request'],
                ['clients', 'r_fhir', 'request'],
                ['r_web', 'g_auth', 'request'],
                ['r_api', 'g_auth', 'request'],
                ['r_fhir', 'g_fhir', 'request'],
                ['g_auth', 'g_consent', 'request', 'flows.edge.signed_in'],
                ['g_auth', 'refused', 'refusal', null, ['out' => 'b', 'in' => 'b-']],
                ['g_consent', 'refused', 'refusal', null, ['out' => 'b+', 'in' => 'b-']],
                ['g_fhir', 'refused', 'refusal'],
                ['g_consent', 'services', 'request', 'flows.edge.basis_held', ['out' => 'b-', 'in' => 't-']],
                ['g_fhir', 'services', 'request', null, ['out' => 'b', 'in' => 'r']],
                ['services', 'pg', 'request'],
                ['pg', 'audit', 'request'],
                ['audit', 'ok', 'response'],
            ],
            'steps' => [
                [['clients', 'r_api'], $f . 's1', $f . 'd1'],
                [['r_api', 'g_auth'], $f . 's2', $f . 'd2'],
                [['g_auth', 'g_consent', 'services'], $f . 's3', $f . 'd3'],
                [['r_fhir', 'g_fhir', 'services'], $f . 's4', $f . 'd4'],
                [['services', 'pg', 'audit', 'ok'], $f . 's5', $f . 'd5'],
                [['g_auth', 'refused'], $f . 's6', $f . 'd6'],
            ],
        ];
    }

    private static function consent(): array
    {
        $p = 'docs_ui.dg.consent.';
        $f = 'flows.consent.';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 4,
            'nodes' => [
                'clinician' => ['actor', 'stethoscope', [0, 0], $p . 'lane.clinician', $p . 'lane.clinician_sub', ['config:staff_capabilities.capabilities.identify_patient', 'route:portals.chart.consent_request']],
                'lookup'    => ['service', 'id-card', [1, 0], $p . 'm1', $p . 'lane.api', ['uri:POST api/mobile/doctor/patients/scan', 'method:App\\Http\\Controllers\\Api\\Mobile\\MobileProviderScanController::scan']],
                'basis'     => ['decision', 'git-branch', [2, 0], $f . 'basis', $f . 'basis_sub', ['method:' . self::G . '::evaluate', 'method:' . self::G . '::holds']],
                'request'   => ['service', 'send', [3, 0], $p . 'm3', $p . 'lane.api', ['uri:POST api/mobile/doctor/consent-requests', 'method:App\\Http\\Controllers\\Api\\Mobile\\MobileConsentRequestController::store', 'config:staff_capabilities.capabilities.request_access']],
                'ok'        => ['outcome-ok', 'circle-check', [0, 1], $p . 'm10', $p . 'lane.clinician', ['uri:GET api/mobile/doctor/patients/{patient}', 'method:App\\Http\\Controllers\\Api\\Mobile\\MobileDoctorChartController::show']],
                'log'       => ['store', 'scroll-text', [1, 1], $p . 'm9', $p . 'lane.record', ['class:App\\Models\\AccessLog', 'uri:GET api/mobile/access-logs']],
                'gate'      => ['gate', 'shield-check', [2, 1], $p . 'm8', $p . 'lane.api', ['method:' . self::G . '::hasActiveConsentGrant', 'method:' . self::G . '::coversFullRecord']],
                'notify'    => ['service', 'bell', [3, 1], $p . 'm4', $p . 'lane.patient', ['uri:GET api/mobile/consent-requests', 'uri:GET api/mobile/consent-requests/pending-count']],
                'revoked'   => ['outcome-refused', 'ban', [1, 2], $p . 'm12', $f . 'revoked_sub', ['uri:POST api/mobile/consents/{id}/revoke', 'method:' . self::GOV . '::revokeConsent']],
                'grant'     => ['store', 'database', [2, 2], $p . 'm6', $p . 'lane.record', ['class:App\\Models\\ConsentGrant', 'method:' . self::GOV . '::approveConsent']],
                'patient'   => ['decision', 'users', [3, 2], $p . 'm5', $p . 'lane.patient', ['uri:POST api/mobile/consent-requests/{id}/approve', 'uri:POST api/mobile/consent-requests/{id}/deny']],
                'denied'    => ['outcome-refused', 'circle-x', [3, 3], $f . 'denied', $f . 'denied_sub', ['method:' . self::GOV . '::denyConsent']],
            ],
            'edges' => [
                ['clinician', 'lookup', 'request'],
                ['lookup', 'basis', 'request'],
                ['basis', 'request', 'request', 'flows.edge.no_basis'],
                ['basis', 'gate', 'request', 'flows.edge.basis_held'],
                ['request', 'notify', 'async'],
                ['notify', 'patient', 'async'],
                ['patient', 'grant', 'response', 'flows.edge.approve'],
                ['patient', 'denied', 'refusal', 'flows.edge.deny'],
                ['grant', 'gate', 'request', 'flows.edge.open_chart'],
                ['gate', 'log', 'request', 'flows.edge.admitted'],
                ['log', 'ok', 'response'],
                ['gate', 'revoked', 'refusal', null, ['out' => 'b-', 'in' => 't']],
            ],
            'steps' => [
                [['clinician', 'lookup'], $p . 'm1', $p . 'd1'],
                [['lookup', 'basis'], $p . 'm2', $p . 'd2'],
                [['basis', 'request'], $p . 'm3', $p . 'd3'],
                [['request', 'notify'], $p . 'm4', $p . 'd4'],
                [['notify', 'patient'], $p . 'm5', $p . 'd5'],
                [['patient', 'grant'], $p . 'm6', $p . 'd6'],
                [['grant', 'gate'], $p . 'm7', $p . 'd7'],
                [['basis', 'gate'], $p . 'm8', $p . 'd8'],
                [['gate', 'log'], $p . 'm9', $p . 'd9'],
                [['log', 'ok'], $p . 'm10', $p . 'd10'],
                [['gate', 'revoked'], $p . 'm11', $p . 'd11'],
                [['patient', 'denied'], $f . 'denied', $f . 'denied_d'],
            ],
        ];
    }

    private static function fhir(): array
    {
        $p = 'docs_ui.dg.fhir.';
        $f = 'flows.fhir.';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 2,
            'nodes' => [
                'partner' => ['external', 'building-2', [0, 0], $p . 'lane.partner', $p . 'lane.partner_sub', ['uri:POST api/fhir/R4', 'uri:GET api/fhir/R4/metadata']],
                'jwt'     => ['gate', 'key-round', [1, 0], $p . 'm2', $p . 'lane.bearer', ['method:' . self::FHIR . '::scopes', 'method:' . self::FHIR . '::requireScope']],
                'g1'      => ['gate', 'shield-check', [2, 0], $p . 'm3', $p . 'lane.guard', ['method:' . self::FHIR . '::facilityId']],
                'g2'      => ['gate', 'user-check', [3, 0], $p . 'm4', $p . 'lane.guard', ['method:' . self::FHIR . '::resolveSubject']],
                'refused' => ['outcome-refused', 'circle-x', [0, 1], $p . 'm8', $f . 'refused_sub', ['method:' . self::FHIR . '::recordRefusal']],
                'ok'      => ['outcome-ok', 'file-check', [1, 1], $p . 'm7', $f . 'ok_sub', ['method:' . self::FHIR . '::recordAccepted', 'uri:POST api/fhir/R4/Observation']],
                'store'   => ['store', 'database', [2, 1], $p . 'm6', $p . 'lane.db', ['method:App\\Http\\Controllers\\Api\\Fhir\\FhirIngestController::transaction', 'class:App\\Services\\Fhir\\FhirIngestService']],
                'g3'      => ['gate', 'lock', [3, 1], $p . 'm5', $p . 'lane.guard', ['method:' . self::FHIR . '::requireConsent', 'class:App\\Models\\ConsentGrant']],
            ],
            'edges' => [
                ['partner', 'jwt', 'request', 'flows.edge.bundle'],
                ['jwt', 'g1', 'request'],
                ['g1', 'g2', 'request'],
                ['g2', 'g3', 'request'],
                ['g3', 'store', 'request', 'flows.edge.all_pass'],
                ['store', 'ok', 'response'],
                ['jwt', 'refused', 'refusal', null, ['out' => 'b-', 'in' => 't']],
                ['g1', 'refused', 'refusal', null, ['out' => 'b-', 'in' => 't']],
                ['g2', 'refused', 'refusal', null, ['out' => 'b-', 'in' => 't']],
                ['g3', 'refused', 'refusal', 'flows.edge.any_fails', ['out' => 'b', 'in' => 'b']],
            ],
            'steps' => [
                [['partner', 'jwt'], $p . 'm1', $p . 'd1'],
                [['jwt', 'g1'], $p . 'm2', $p . 'd2'],
                [['g1', 'g2'], $p . 'm3', $p . 'd3'],
                [['g2', 'g3'], $p . 'm4', $p . 'd4'],
                [['g3', 'store'], $p . 'm5', $p . 'd5'],
                [['g3', 'store', 'ok'], $p . 'm6', $p . 'd6'],
                [['store', 'ok'], $p . 'm7', $p . 'd7'],
                [['g3', 'refused'], $p . 'm8', $p . 'd8'],
            ],
        ];
    }

    private static function mpi(): array
    {
        $p = 'docs_ui.dg.mpi.';
        $f = 'flows.mpi.';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 3,
            'nodes' => [
                'scheduler' => ['actor', 'calendar-clock', [0, 0], $p . 'lane.scheduler', $p . 'lane.scheduler_sub', ['command:mpi:detect', 'class:App\\Console\\Commands\\DetectDuplicatePatients']],
                'scan'      => ['service', 'search', [1, 0], $p . 'm2', $p . 'lane.detector', ['method:' . self::MPI . '::detectDuplicates', 'method:' . self::MPI . '::scoreMatch']],
                'queue'     => ['store', 'list-ordered', [2, 0], $p . 'm3', $p . 'lane.desk', ['class:App\\Models\\MpiCandidate', 'route:portals.admin.mpi.index', 'route:portals.admin.mpi.candidates.raise']],
                'review'    => ['decision', 'users', [3, 0], $p . 'm4', $p . 'lane.desk', ['route:portals.admin.mpi.candidates.show']],
                'done'      => ['outcome-ok', 'circle-check', [0, 1], $f . 'done', $f . 'done_sub', ['class:App\\Models\\PatientMergeAlias']],
                'alias'     => ['store', 'database', [1, 1], $p . 'm6', $p . 'lane.register', ['method:App\\Models\\Patient::findExistingIdentity', 'class:App\\Models\\PatientMerge']],
                'merge'     => ['service', 'git-merge', [2, 1], $p . 'm5', $p . 'lane.desk', ['route:portals.admin.mpi.candidates.merge', 'method:' . self::MERGE . '::merge']],
                'reject'    => ['outcome-refused', 'ban', [3, 1], $p . 'm7', $p . 'lane.desk', ['route:portals.admin.mpi.candidates.reject', 'method:' . self::MPI . '::rejectMatch']],
                'restored'  => ['outcome-ok', 'undo-2', [0, 2], $f . 'restored', $f . 'restored_sub', ['method:' . self::MERGE . '::unmerge']],
                'unmerge'   => ['service', 'undo-2', [1, 2], $p . 'm8', $p . 'lane.desk', ['route:portals.admin.mpi.merges.unmerge']],
            ],
            'edges' => [
                ['scheduler', 'scan', 'async', 'flows.edge.nightly'],
                ['scan', 'queue', 'request'],
                ['queue', 'review', 'request'],
                ['review', 'merge', 'request', 'flows.edge.same_person', ['out' => 'b-', 'in' => 't']],
                ['review', 'reject', 'refusal', null, ['out' => 'b', 'in' => 't']],
                ['merge', 'alias', 'request'],
                ['alias', 'done', 'response'],
                ['alias', 'unmerge', 'request', 'flows.edge.undo'],
                ['unmerge', 'restored', 'response'],
            ],
            'steps' => [
                [['scheduler', 'scan'], $p . 'm1', $p . 'd1'],
                [['scan'], $p . 'm2', $p . 'd2'],
                [['scan', 'queue'], $p . 'm3', $p . 'd3'],
                [['queue', 'review'], $p . 'm4', $p . 'd4'],
                [['review', 'merge'], $p . 'm5', $p . 'd5'],
                [['merge', 'alias', 'done'], $p . 'm6', $p . 'd6'],
                [['review', 'reject'], $p . 'm7', $p . 'd7'],
                [['alias', 'unmerge', 'restored'], $p . 'm8', $p . 'd8'],
            ],
        ];
    }

    private static function webhooks(): array
    {
        $p = 'docs_ui.dg.webhooks.';
        $f = 'flows.webhooks.';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 3,
            'nodes' => [
                'event'     => ['actor', 'zap', [0, 0], $p . 'm1', $p . 'lane.event', ['method:App\\Services\\WebhookService::dispatch', 'class:App\\Models\\WebhookSubscription']],
                'sign'      => ['service', 'key-round', [1, 0], $p . 'm2', $p . 'lane.worker', ['method:' . self::JOB . '::handle', 'class:App\\Models\\WebhookSecret']],
                'public'    => ['gate', 'shield-check', [2, 0], $p . 'm3', $p . 'lane.worker', ['method:App\\Support\\PublicUrlGuard::isAllowed', 'method:App\\Support\\PublicUrlGuard::isPublicIp']],
                'post'      => ['service', 'send', [3, 0], $p . 'm4', $p . 'lane.worker', ['class:App\\Models\\WebhookDeliveryLog', 'route:docs.webhooks']],
                'blocked'   => ['outcome-refused', 'ban', [1, 1], $f . 'blocked', $f . 'blocked_sub', ['method:App\\Support\\PublicUrlGuard::resolve']],
                'delivered' => ['outcome-ok', 'circle-check', [2, 1], $p . 'm6', $p . 'lane.endpoint', ['class:App\\Models\\WebhookDeliveryLog']],
                'endpoint'  => ['external', 'webhook', [3, 1], $p . 'm5', $p . 'lane.endpoint', ['route:docs.webhooks', 'route:portals.developer.webhooks']],
                'failed'    => ['outcome-refused', 'circle-x', [2, 2], $p . 'm8', $f . 'failed_sub', ['method:' . self::JOB . '::failed', 'route:portals.developer.webhooks.replay', 'method:App\\Services\\WebhookService::replay']],
                'retry'     => ['service', 'refresh-cw', [3, 2], $p . 'm7', $p . 'lane.worker', ['property:' . self::JOB . '::tries', 'property:' . self::JOB . '::backoff']],
            ],
            'edges' => [
                ['event', 'sign', 'async', 'flows.edge.queued'],
                ['sign', 'public', 'request'],
                ['public', 'post', 'request', 'flows.edge.public'],
                ['public', 'blocked', 'refusal', 'flows.edge.private', ['out' => 'b', 'in' => 't']],
                ['post', 'endpoint', 'request'],
                ['endpoint', 'delivered', 'response', 'flows.edge.http_2xx'],
                ['endpoint', 'retry', 'refusal', 'flows.edge.no_2xx'],
                ['retry', 'post', 'request', 'flows.edge.retry', ['out' => 'r', 'in' => 'r', 'loop' => true]],
                ['retry', 'failed', 'refusal', 'flows.edge.exhausted'],
            ],
            'steps' => [
                [['event', 'sign'], $p . 'm1', $p . 'd1'],
                [['sign', 'public'], $p . 'm2', $p . 'd2'],
                [['public', 'post'], $p . 'm3', $p . 'd3'],
                [['post', 'endpoint'], $p . 'm4', $p . 'd4'],
                [['endpoint'], $p . 'm5', $p . 'd5'],
                [['endpoint', 'delivered'], $p . 'm6', $p . 'd6'],
                [['endpoint', 'retry', 'post'], $p . 'm7', $p . 'd7'],
                [['retry', 'failed'], $p . 'm8', $p . 'd8'],
                [['public', 'blocked'], $f . 'blocked', $f . 'blocked_d'],
            ],
        ];
    }

    private static function qr(): array
    {
        $p = 'docs_ui.dg.qr.';
        $f = 'flows.qr.';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 2,
            'nodes' => [
                'patient'  => ['actor', 'qr-code', [0, 0], $p . 'm1', $p . 'lane.patient', ['route:portals.patient.health-id-card', 'method:App\\Services\\Identity\\QrTokenService::generateToken']],
                'verifier' => ['actor', 'scan-line', [1, 0], $p . 'm2', $p . 'lane.verifier', ['route:verify.qr']],
                'check'    => ['decision', 'git-branch', [2, 0], $p . 'm3', $p . 'lane.verify', ['method:App\\Http\\Controllers\\MedicalId\\VerifyController::qr', 'method:App\\Services\\Identity\\QrTokenService::verifyToken', 'method:App\\Services\\Identity\\QrTokenService::failureState']],
                'log'      => ['store', 'scroll-text', [3, 0], $p . 'm5', $p . 'lane.log', ['class:App\\Models\\HealthIdVerificationEvent']],
                'invalid'  => ['outcome-refused', 'circle-x', [0, 1], $f . 'invalid', $f . 'invalid_sub', ['method:App\\Services\\Identity\\QrTokenService::failureState']],
                'revoked'  => ['outcome-refused', 'ban', [1, 1], $f . 'revoked', $f . 'revoked_sub', ['method:App\\Services\\Identity\\QrTokenService::revokeToken']],
                'expired'  => ['outcome-refused', 'circle-x', [2, 1], $f . 'expired', $f . 'expired_sub', ['class:App\\Models\\HealthIdQrToken']],
                'verified' => ['outcome-ok', 'circle-check', [3, 1], $f . 'verified', $f . 'verified_sub', ['route:verify.qr']],
            ],
            'edges' => [
                ['patient', 'verifier', 'request', 'flows.edge.shows'],
                ['verifier', 'check', 'request'],
                ['check', 'log', 'request', 'flows.edge.valid'],
                ['log', 'verified', 'response'],
                ['check', 'expired', 'refusal'],
                ['check', 'revoked', 'refusal', null, ['out' => 'b-', 'in' => 't']],
                ['check', 'invalid', 'refusal', null, ['out' => 'b-', 'in' => 't']],
            ],
            'steps' => [
                [['patient'], $p . 'm1', $p . 'd1'],
                [['patient', 'verifier'], $p . 'm2', $p . 'd2'],
                [['verifier', 'check'], $p . 'm3', $p . 'd3'],
                [['check', 'expired'], $p . 'm4', $p . 'd4'],
                [['check', 'log', 'verified'], $p . 'm5', $p . 'd5'],
            ],
        ];
    }

    // ── Blog ───────────────────────────────────────────────────────────────

    private static function recordIsTheProduct(): array
    {
        $p = 'blog_diagrams.record-is-the-product.';
        $b = $p . 'box.';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 5,
            'groups' => [
                [$p . 'group.market', 1, 0, 3, 0],
                [$p . 'group.clinic', 1, 1, 3, 1],
                [$p . 'group.family', 0, 1, 0, 3],
                [$p . 'group.governance', 1, 3, 3, 3],
            ],
            'nodes' => [
                'patient'    => ['actor', 'user-round', [0, 0], $b . 'patient', $b . 'patient_sub', ['route:portals.patient.access-logs', 'class:App\\Modules\\PatientIdentity\\Services\\PatientIdentityService']],
                'medicine'   => ['service', 'pill', [1, 0], $b . 'medicine', $b . 'medicine_sub', ['route:public.network.medicine-finder']],
                'blood'      => ['service', 'droplet', [2, 0], $b . 'blood', $b . 'blood_sub', ['route:public.network.blood-finder']],
                'care'       => ['service', 'map-pin', [3, 0], $b . 'care', $b . 'care_sub', ['route:public.care-map']],
                'invites'    => ['service', 'mail', [0, 1], $b . 'invites', $b . 'invites_sub', ['route:portals.patient.family.invite.send']],
                'pharmlab'   => ['actor', 'flask-conical', [1, 1], $b . 'pharmlab', $b . 'pharmlab_sub', ['config:staff_capabilities.capabilities.dispense', 'config:staff_capabilities.capabilities.lab_results']],
                'desk'       => ['actor', 'monitor', [2, 1], $b . 'desk', $b . 'desk_sub', ['config:staff_capabilities.capabilities.front_desk']],
                'doctor'     => ['actor', 'stethoscope', [3, 1], $b . 'doctor', $b . 'doctor_sub', ['config:staff_capabilities.capabilities.clinical_chart']],
                'guardian'   => ['actor', 'users', [0, 2], $b . 'guardian', $b . 'guardian_sub', ['route:portals.patient.family']],
                'breakglass' => ['gate', 'shield-alert', [3, 2], $b . 'breakglass', $b . 'breakglass_sub', ['method:App\\Modules\\Governance\\Services\\EmergencyAccessService::openBreakGlass', 'config:staff_capabilities.capabilities.emergency_access']],
                'actas'      => ['service', 'link', [0, 3], $b . 'actas', $b . 'actas_sub', ['method:App\\Http\\Middleware\\ActAsDependent::handle']],
                'ledger'     => ['gate', 'shield-check', [1, 3], $b . 'ledger', $b . 'ledger_sub', ['class:App\\Models\\ConsentGrant', 'method:' . self::G . '::evaluate']],
                'record'     => ['store', 'heart-pulse', [2, 3], $b . 'record', $b . 'record_sub', ['class:App\\Models\\Patient', 'class:App\\Modules\\PatientIdentity\\Services\\PatientIdentityService']],
                'accesslog'  => ['store', 'scroll-text', [3, 3], $b . 'accesslog', $b . 'accesslog_sub', ['class:App\\Models\\AccessLog', 'route:portals.patient.access-logs']],
                'refused'    => ['outcome-refused', 'circle-x', [1, 4], $b . 'refused', $b . 'refused_sub', ['method:' . self::G . '::none']],
                'ok'         => ['outcome-ok', 'circle-check', [3, 4], $b . 'ok', $b . 'ok_sub', ['uri:GET api/mobile/access-logs']],
            ],
            'edges' => [
                ['patient', 'medicine', 'request', null, ['out' => 't', 'in' => 't']],
                ['patient', 'blood', 'request', null, ['out' => 't', 'in' => 't']],
                ['patient', 'care', 'request', null, ['out' => 't', 'in' => 't']],
                ['patient', 'invites', 'request'],
                ['medicine', 'pharmlab', 'request'],
                ['blood', 'desk', 'request'],
                ['care', 'doctor', 'request', 'flows.edge.book'],
                ['desk', 'doctor', 'request'],
                ['invites', 'guardian', 'request', 'flows.edge.accept'],
                ['guardian', 'actas', 'request'],
                ['actas', 'ledger', 'request'],
                ['pharmlab', 'ledger', 'request'],
                ['doctor', 'breakglass', 'request', 'flows.edge.emergency'],
                ['doctor', 'ledger', 'request', null, ['out' => 'b-', 'in' => 't+']],
                ['breakglass', 'record', 'request', null, ['out' => 'b', 'in' => 't']],
                ['ledger', 'record', 'request', 'flows.edge.basis_held'],
                ['ledger', 'refused', 'refusal', 'flows.edge.no_basis'],
                ['record', 'accesslog', 'request'],
                ['accesslog', 'ok', 'response'],
            ],
            'steps' => [
                [['ledger', 'record', 'accesslog', 'ok'], $p . 's1', $p . 'd1'],
                [['patient', 'care', 'doctor'], $p . 's2', $p . 'd2'],
                [['doctor', 'breakglass', 'record', 'accesslog'], $p . 's3', $p . 'd3'],
                [['patient', 'invites', 'guardian', 'actas', 'ledger'], $p . 's4', $p . 'd4'],
                [['desk', 'doctor', 'ledger'], $p . 's5', $p . 'd5'],
            ],
        ];
    }

    private static function oneAppFiveRoles(): array
    {
        $p = 'blog_diagrams.one-app-five-roles.';
        $b = $p . 'box.';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 5, 'rows' => 5, 'cw' => 176, 'cg' => 40,
            'nodes' => [
                'app'        => ['actor', 'smartphone', [2, 0], $b . 'app', $b . 'app_sub', ['route:login.submit', 'config:staff_capabilities.capabilities']],
                'patient'    => ['actor', 'user-round', [0, 1], $b . 'patient', $b . 'patient_sub', ['route:portals.patient.access-logs']],
                'doctor'     => ['actor', 'stethoscope', [1, 1], $b . 'doctor', $b . 'doctor_sub', ['config:staff_capabilities.capabilities.clinical_chart', 'config:staff_capabilities.capabilities.prescribe']],
                'desk'       => ['actor', 'monitor', [2, 1], $b . 'desk', $b . 'desk_sub', ['config:staff_capabilities.capabilities.front_desk']],
                'pharmacist' => ['actor', 'pill', [3, 1], $b . 'pharmacist', $b . 'pharmacist_sub', ['config:staff_capabilities.capabilities.dispense']],
                'lab'        => ['actor', 'flask-conical', [4, 1], $b . 'lab', $b . 'lab_sub', ['config:staff_capabilities.capabilities.lab_results', 'route:portals.lab.release']],
                'gate'       => ['gate', 'shield-check', [2, 2], $b . 'gate', $b . 'gate_sub', ['config:staff_capabilities.capabilities', 'method:' . self::G . '::evaluate']],
                'refused'    => ['outcome-refused', 'circle-x', [4, 2], $b . 'refused', $b . 'refused_sub', ['method:' . self::G . '::none']],
                'record'     => ['store', 'heart-pulse', [2, 3], $b . 'record', $b . 'record_sub', ['class:App\\Models\\Patient', 'class:App\\Models\\AccessLog']],
                'ok'         => ['outcome-ok', 'circle-check', [2, 4], $b . 'ok', $b . 'ok_sub', ['uri:GET api/mobile/access-logs']],
            ],
            'edges' => [
                ['app', 'patient', 'request'],
                ['app', 'doctor', 'request'],
                ['app', 'desk', 'request'],
                ['app', 'pharmacist', 'request'],
                ['app', 'lab', 'request'],
                ['patient', 'gate', 'request'],
                ['doctor', 'gate', 'request'],
                ['desk', 'gate', 'request'],
                ['pharmacist', 'gate', 'request'],
                ['lab', 'gate', 'request'],
                ['gate', 'refused', 'refusal', 'flows.edge.no_basis'],
                ['gate', 'record', 'request', 'flows.edge.allowed'],
                ['record', 'ok', 'response'],
            ],
            'steps' => [
                [['app', 'patient', 'gate'], $p . 's1', $p . 'd1'],
                [['app', 'doctor', 'gate'], $p . 's2', $p . 'd2'],
                [['app', 'desk', 'gate'], $p . 's3', $p . 'd3'],
                [['app', 'pharmacist', 'gate'], $p . 's4', $p . 'd4'],
                [['app', 'lab', 'gate'], $p . 's5', $p . 'd5'],
                [['gate', 'refused'], $p . 's6', $p . 'd6'],
                [['gate', 'record', 'ok'], $p . 's7', $p . 'd7'],
            ],
        ];
    }

    private static function familyActAs(): array
    {
        $p = 'blog_diagrams.family-act-as.';
        $b = $p . 'box.';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 4,
            'groups' => [
                [$p . 'group.actas', 0, 0, 3, 1],
                [$p . 'group.invite', 0, 2, 3, 3],
            ],
            'nodes' => [
                'guardian' => ['actor', 'user-round', [0, 0], $b . 'guardian', $b . 'guardian_sub', ['route:portals.patient.family', 'uri:GET api/mobile/family']],
                'header'   => ['service', 'link', [1, 0], $b . 'header', $b . 'header_sub', ['route:portals.patient.family.switch', 'class:App\\Http\\Middleware\\ActAsDependent']],
                'check'    => ['gate', 'shield-check', [2, 0], $b . 'check', $b . 'check_sub', ['method:App\\Http\\Middleware\\ActAsDependent::handle', 'class:App\\Models\\FamilyLink']],
                'record'   => ['store', 'heart-pulse', [3, 0], $b . 'record', $b . 'record_sub', ['uri:GET api/mobile/family/members/{linkId}']],
                'refused'  => ['outcome-refused', 'circle-x', [2, 1], $b . 'refused', $b . 'refused_sub', ['method:App\\Http\\Middleware\\ActAsDependent::handle']],
                'banner'   => ['outcome-ok', 'circle-check', [3, 1], $b . 'banner', $b . 'banner_sub', ['route:portals.patient.family.switch.back']],
                'invite'   => ['service', 'mail', [0, 2], $b . 'invite', $b . 'invite_sub', ['uri:POST api/mobile/family/invitations', 'route:portals.patient.family.invite.send']],
                'pending'  => ['decision', 'git-branch', [1, 2], $b . 'pending', $b . 'pending_sub', ['uri:GET api/mobile/family/invitations/incoming', 'route:portals.patient.family.invitations']],
                'accepted' => ['gate', 'user-check', [2, 2], $b . 'accepted', $b . 'accepted_sub', ['uri:POST api/mobile/family/invitations/{id}/accept', 'method:' . self::FAM . '::acceptInvitation']],
                'active'   => ['outcome-ok', 'user-check', [3, 2], $b . 'active', $b . 'active_sub', ['class:App\\Models\\FamilyLink', 'route:portals.patient.family.revoke']],
                'declined' => ['outcome-refused', 'ban', [1, 3], $b . 'declined', $b . 'declined_sub', ['uri:POST api/mobile/family/invitations/{id}/decline', 'method:' . self::FAM . '::declineInvitation']],
            ],
            'edges' => [
                ['guardian', 'header', 'request', 'flows.edge.act_as'],
                ['header', 'check', 'request'],
                ['check', 'record', 'request', 'flows.edge.linked'],
                ['check', 'refused', 'refusal', 'flows.edge.no_link'],
                ['record', 'banner', 'response'],
                ['guardian', 'invite', 'request', 'flows.edge.invite'],
                ['invite', 'pending', 'async'],
                ['pending', 'accepted', 'response', 'flows.edge.accept'],
                ['accepted', 'active', 'response'],
                ['pending', 'declined', 'refusal', 'flows.edge.decline'],
            ],
            'steps' => [
                [['guardian'], $p . 's1', $p . 'd1'],
                [['guardian', 'header'], $p . 's2', $p . 'd2'],
                [['header', 'check'], $p . 's3', $p . 'd3'],
                [['check', 'record', 'banner'], $p . 's4', $p . 'd4'],
                [['guardian', 'invite'], $p . 's5', $p . 'd5'],
                [['invite', 'pending'], $p . 's6', $p . 'd6'],
                [['pending', 'accepted', 'active'], $p . 's7', $p . 'd7'],
                [['pending', 'declined'], $p . 's8', $p . 'd8'],
            ],
        ];
    }

    private static function offlineOutbox(): array
    {
        $p = 'blog_diagrams.offline-outbox.';
        $b = $p . 'box.';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 4,
            'groups' => [
                [$p . 'group.device', 0, 0, 1, 3],
                [$p . 'group.server', 2, 0, 3, 3],
            ],
            'nodes' => [
                'optin'  => ['actor', 'smartphone', [1, 0], $b . 'optin', $b . 'optin_sub', ['uri:POST api/mobile/offline/policies']],
                'policy' => ['gate', 'key-round', [2, 0], $b . 'policy', $b . 'policy_sub', ['method:' . self::SYNC . '::createLocalCachePolicy', 'class:App\\Models\\LocalCachePolicy']],
                'wipe'   => ['outcome-refused', 'trash-2', [3, 0], $b . 'wipe', $b . 'wipe_sub', ['uri:POST api/mobile/offline/policies/{policy}/revoke', 'method:' . self::SYNC . '::revokeLocalCachePolicy']],
                'write'  => ['actor', 'cloud-off', [0, 1], $b . 'write', $b . 'write_sub', ['class:App\\Models\\OfflineQueue']],
                'cache'  => ['store', 'hard-drive', [1, 1], $b . 'cache', $b . 'cache_sub', ['class:App\\Models\\LocalCachePolicy']],
                'outbox' => ['store', 'inbox', [0, 2], $b . 'outbox', $b . 'outbox_sub', ['class:App\\Models\\OfflineQueue']],
                'settle' => ['service', 'refresh-cw', [1, 2], $b . 'settle', $b . 'settle_sub', ['uri:POST api/mobile/offline/policies/{policy}/queue']],
                'queue'  => ['store', 'database', [2, 2], $b . 'queue', $b . 'queue_sub', ['method:' . self::SYNC . '::queueEncryptedPayload', 'class:App\\Models\\OfflineQueue']],
                'job'    => ['service', 'git-merge', [3, 2], $b . 'job', $b . 'job_sub', ['class:App\\Models\\ReconciliationCase', 'method:' . self::SYNC . '::detectConflict']],
                'synced' => ['outcome-ok', 'circle-check', [2, 3], $b . 'synced', $b . 'synced_sub', ['method:' . self::SYNC . '::resolveConflict']],
                'audit'  => ['store', 'scroll-text', [3, 3], $b . 'audit', $b . 'audit_sub', ['class:App\\Models\\AuditEvent']],
            ],
            'edges' => [
                ['optin', 'policy', 'request'],
                ['policy', 'cache', 'response', 'flows.edge.scopes', ['out' => 'b-', 'in' => 't']],
                ['policy', 'wipe', 'refusal', 'flows.edge.revoke'],
                ['cache', 'write', 'request'],
                ['write', 'outbox', 'request'],
                ['outbox', 'settle', 'async', 'flows.edge.online'],
                ['settle', 'outbox', 'async', 'flows.edge.still_offline', ['out' => 'b', 'in' => 'b', 'loop' => true]],
                ['settle', 'queue', 'request'],
                ['queue', 'job', 'request'],
                ['job', 'audit', 'request'],
                ['audit', 'synced', 'response'],
            ],
            'steps' => [
                [['optin', 'policy'], $p . 's1', $p . 'd1'],
                [['policy', 'cache'], $p . 's2', $p . 'd2'],
                [['cache', 'write'], $p . 's3', $p . 'd3'],
                [['write', 'outbox'], $p . 's4', $p . 'd4'],
                [['outbox', 'settle'], $p . 's5', $p . 'd5'],
                [['settle', 'queue'], $p . 's6', $p . 'd6'],
                [['queue', 'job'], $p . 's7', $p . 'd7'],
                [['job', 'audit', 'synced'], $p . 's8', $p . 'd8'],
                [['policy', 'wipe'], $p . 's9', $p . 'd9'],
            ],
        ];
    }

    private static function pharmacyDispense(): array
    {
        $p = 'blog_diagrams.pharmacy-dispense.';
        $b = $p . 'box.';
        $desk = 'App\\Http\\Controllers\\Api\\Mobile\\Pharmacy\\PharmacyDeskController';
        $res = 'App\\Http\\Controllers\\Api\\Mobile\\Pharmacy\\PharmacyReservationController';
        $mp = 'App\\Http\\Controllers\\Api\\Mobile\\MobilePharmacyController';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 4,
            'nodes' => [
                'clinician'  => ['actor', 'stethoscope', [0, 0], $b . 'clinician', $b . 'clinician_sub', ['config:staff_capabilities.capabilities.prescribe']],
                'issued'     => ['service', 'file-check', [1, 0], $b . 'issued', $b . 'issued_sub', ['route:portals.prescriber.store', 'route:portals.prescriber.interaction_check', 'method:App\\Http\\Controllers\\MedicalId\\PrescriberController::store']],
                'patient'    => ['actor', 'user-round', [2, 0], $b . 'patient', $b . 'patient_sub', ['uri:GET api/mobile/prescriptions']],
                'reserved'   => ['service', 'pill', [3, 0], $b . 'reserved', $b . 'reserved_sub', ['uri:POST api/mobile/pharmacy/reservations', 'method:' . $mp . '::reserve']],
                'list'       => ['service', 'search', [0, 1], $b . 'list', $b . 'list_sub', ['route:portals.pharmacy.prescriptions', 'route:portals.pharmacy.prescriptions.lookup', 'uri:GET api/mobile/pharmacy-desk/prescriptions/lookup']],
                'share'      => ['service', 'send', [1, 1], $b . 'share', $b . 'share_sub', ['uri:POST api/mobile/prescriptions/{id}/share', 'method:App\\Http\\Controllers\\Api\\Mobile\\MobilePrescriptionController::share']],
                'cancelled'  => ['outcome-refused', 'ban', [2, 1], $b . 'cancelled', $b . 'cancelled_sub', ['route:portals.pharmacy.orders.cancel', 'method:' . $mp . '::cancelReservation']],
                'oaccept'    => ['service', 'inbox', [3, 1], $b . 'oaccept', $b . 'oaccept_sub', ['route:portals.pharmacy.orders.accept', 'method:' . $res . '::accept']],
                'accepted'   => ['service', 'inbox', [0, 2], $b . 'accepted', $b . 'accepted_sub', ['method:' . $desk . '::accept']],
                'ready'      => ['service', 'bell', [1, 2], $b . 'ready', $b . 'ready_sub', ['method:' . $desk . '::ready']],
                'dispatched' => ['service', 'send', [2, 2], $b . 'dispatched', $b . 'dispatched_sub', ['route:portals.pharmacy.orders.dispatch']],
                'oready'     => ['service', 'bell', [3, 2], $b . 'oready', $b . 'oready_sub', ['route:portals.pharmacy.orders.ready']],
                'dispensed'  => ['outcome-ok', 'circle-check', [1, 3], $b . 'dispensed', $b . 'dispensed_sub', ['route:portals.pharmacy.dispense', 'method:' . $desk . '::dispense', 'class:App\\Services\\Clinical\\PrescriptionService', 'route:documents.new.labels']],
                'delivered'  => ['outcome-ok', 'circle-check', [2, 3], $b . 'delivered', $b . 'delivered_sub', ['route:portals.pharmacy.orders.delivered']],
                'collected'  => ['outcome-ok', 'circle-check', [3, 3], $b . 'collected', $b . 'collected_sub', ['route:portals.pharmacy.orders.collected']],
            ],
            'edges' => [
                ['clinician', 'issued', 'request'],
                ['issued', 'patient', 'response'],
                ['patient', 'share', 'request', null, ['out' => 'b-', 'in' => 't']],
                ['patient', 'list', 'request', 'flows.edge.lookup', ['out' => 'b-', 'in' => 't']],
                ['share', 'list', 'async'],
                ['list', 'accepted', 'request'],
                ['accepted', 'ready', 'request'],
                ['ready', 'dispensed', 'response'],
                ['patient', 'reserved', 'request', 'flows.edge.reserve'],
                ['reserved', 'oaccept', 'request'],
                ['oaccept', 'oready', 'request'],
                ['oready', 'collected', 'response', 'flows.edge.pickup'],
                ['oready', 'dispatched', 'request', 'flows.edge.deliver'],
                ['dispatched', 'delivered', 'response'],
                ['reserved', 'cancelled', 'refusal', null, ['out' => 'b-', 'in' => 't']],
                ['oaccept', 'cancelled', 'refusal'],
                ['oready', 'cancelled', 'refusal', null, ['out' => 't-', 'in' => 'b']],
            ],
            'steps' => [
                [['clinician', 'issued'], $p . 's1', $p . 'd1'],
                [['issued', 'patient', 'share', 'list'], $p . 's2', $p . 'd2'],
                [['patient', 'list'], $p . 's3', $p . 'd3'],
                [['list', 'accepted', 'ready'], $p . 's4', $p . 'd4'],
                [['ready', 'dispensed'], $p . 's5', $p . 'd5'],
                [['patient', 'reserved', 'oaccept', 'oready'], $p . 's6', $p . 'd6'],
                [['oready', 'collected'], $p . 's7', $p . 'd7'],
                [['oready', 'dispatched', 'delivered'], $p . 's8', $p . 'd8'],
                [['oaccept', 'cancelled'], $p . 's9', $p . 'd9'],
            ],
        ];
    }

    private static function labRelease(): array
    {
        $p = 'blog_diagrams.lab-release.';
        $b = $p . 'box.';
        $svc = 'App\\Services\\Lab\\LabDeskService';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 4,
            'nodes' => [
                'patient'   => ['actor', 'user-round', [0, 0], $b . 'patient', $b . 'patient_sub', ['uri:POST api/mobile/labs/requests']],
                'clinician' => ['actor', 'stethoscope', [1, 0], $b . 'clinician', $b . 'clinician_sub', ['config:staff_capabilities.capabilities.clinical_chart']],
                'order'     => ['service', 'flask-conical', [2, 0], $b . 'order', $b . 'order_sub', ['uri:POST api/mobile/doctor/patients/{patient}/lab-orders', 'method:App\\Http\\Controllers\\Api\\Mobile\\MobileDoctorLabOrderController::store']],
                'collect'   => ['service', 'flask-conical', [3, 0], $b . 'collect', $b . 'collect_sub', ['route:portals.lab.collect', 'method:App\\Http\\Controllers\\MedicalId\\LabDeskWebController::collect']],
                'request'   => ['service', 'send', [0, 1], $b . 'request', $b . 'request_sub', ['uri:POST api/mobile/labs/requests']],
                'decide'    => ['decision', 'users', [1, 1], $b . 'decide', $b . 'decide_sub', ['route:portals.lab_manager.requests.approve', 'route:portals.lab_manager.requests.decline']],
                'refused'   => ['outcome-refused', 'circle-x', [2, 1], $b . 'refused', $b . 'refused_sub', ['method:' . $svc . '::release']],
                'results'   => ['store', 'database', [3, 1], $b . 'results', $b . 'results_sub', ['route:portals.lab.results.store']],
                'declined'  => ['outcome-refused', 'ban', [0, 2], $b . 'declined', $b . 'declined_sub', ['route:portals.lab_manager.requests.decline']],
                'correct'   => ['outcome-ok', 'file-check', [1, 2], $b . 'correct', $b . 'correct_sub', ['route:portals.lab.results.correct']],
                'release'   => ['gate', 'shield-check', [2, 2], $b . 'release', $b . 'release_sub', ['route:portals.lab.release', 'method:' . $svc . '::release']],
                'validate'  => ['gate', 'user-check', [3, 2], $b . 'validate', $b . 'validate_sub', ['route:portals.lab.validate']],
                'guardians' => ['outcome-ok', 'circle-check', [0, 3], $b . 'guardians', $b . 'guardians_sub', ['method:App\\Listeners\\NotifyGuardiansOfPatientEvent::handleLabResult']],
                'notify'    => ['service', 'bell', [1, 3], $b . 'notify', $b . 'notify_sub', ['method:' . $svc . '::notifyReleased', 'method:' . $svc . '::bellPatient', 'method:' . $svc . '::pushPatient']],
                'clinnote'  => ['outcome-ok', 'circle-check', [2, 3], $b . 'clinnote', $b . 'clinnote_sub', ['method:' . $svc . '::notifyReleased']],
                'callback'  => ['outcome-ok', 'circle-check', [3, 3], $b . 'callback', $b . 'callback_sub', ['route:portals.lab_manager.critical_callback', 'method:App\\Http\\Controllers\\MedicalId\\LabManagerDeskController::criticalCallback']],
            ],
            'edges' => [
                ['patient', 'clinician', 'request', 'flows.edge.consults'],
                ['patient', 'request', 'request'],
                ['request', 'decide', 'request'],
                ['decide', 'order', 'response', 'flows.edge.approve', ['out' => 't+', 'in' => 'b']],
                ['decide', 'declined', 'refusal', 'flows.edge.decline'],
                ['clinician', 'order', 'request'],
                ['order', 'collect', 'request'],
                ['collect', 'results', 'request'],
                ['results', 'validate', 'request'],
                ['validate', 'release', 'request'],
                ['release', 'refused', 'refusal', 'flows.edge.no_results'],
                ['release', 'correct', 'request', 'flows.edge.later'],
                ['release', 'notify', 'async'],
                ['release', 'clinnote', 'async'],
                ['release', 'callback', 'async', 'flows.edge.critical'],
                ['notify', 'guardians', 'async'],
            ],
            'steps' => [
                [['patient', 'clinician', 'order'], $p . 's1', $p . 'd1'],
                [['patient', 'request', 'decide', 'order'], $p . 's2', $p . 'd2'],
                [['order', 'collect'], $p . 's3', $p . 'd3'],
                [['collect', 'results'], $p . 's4', $p . 'd4'],
                [['results', 'validate'], $p . 's5', $p . 'd5'],
                [['validate', 'release'], $p . 's6', $p . 'd6'],
                [['release', 'notify'], $p . 's7', $p . 'd7'],
                [['release', 'clinnote'], $p . 's8', $p . 'd8'],
                [['notify', 'guardians'], $p . 's9', $p . 'd9'],
                [['release', 'callback'], $p . 's10', $p . 'd10'],
                [['release', 'correct'], $p . 's11', $p . 'd11'],
                [['release', 'refused'], $p . 's12', $p . 'd12'],
            ],
        ];
    }

    private static function bloodRequestQueue(): array
    {
        $p = 'blog_diagrams.blood-request-queue.';
        $b = $p . 'box.';
        $md = 'App\\Http\\Controllers\\Api\\Mobile\\MobileBloodDeskController';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 3,
            'nodes' => [
                'clinician' => ['actor', 'stethoscope', [0, 0], $b . 'clinician', $b . 'clinician_sub', ['config:staff_capabilities.capabilities.blood_inventory']],
                'desk'      => ['service', 'monitor', [1, 0], $b . 'desk', $b . 'desk_sub', ['route:portals.staff.blood_requests.store', 'method:App\\Http\\Controllers\\MedicalId\\BloodRequestDeskController::store']],
                'decide'    => ['decision', 'git-branch', [2, 0], $b . 'decide', $b . 'decide_sub', ['route:portals.staff.blood_requests.decide', 'uri:POST api/mobile/blood-desk/requests/{id}/decide', 'method:App\\Http\\Controllers\\Api\\V1\\BloodRequestQueueController::decide']],
                'inventory' => ['store', 'database', [3, 0], $b . 'inventory', $b . 'inventory_sub', ['route:portals.staff.inventory.blood', 'route:portals.staff.inventory.blood.adjust', 'uri:POST api/v1/connect/inventory/blood-stock/sync']],
                'patient'   => ['actor', 'user-round', [0, 1], $b . 'patient', $b . 'patient_sub', ['uri:GET api/mobile/blood/requests']],
                'app'       => ['service', 'droplet', [1, 1], $b . 'app', $b . 'app_sub', ['uri:POST api/mobile/blood/requests', 'method:App\\Http\\Controllers\\Api\\Mobile\\MobileBloodController::store']],
                'ready'     => ['service', 'bell', [2, 1], $b . 'ready', $b . 'ready_sub', ['route:portals.staff.blood_requests.ready', 'method:' . $md . '::ready']],
                'finder'    => ['outcome-ok', 'circle-check', [3, 1], $b . 'finder', $b . 'finder_sub', ['route:public.network.blood-finder', 'uri:GET api/mobile/blood/search']],
                'cancelled' => ['outcome-refused', 'ban', [1, 2], $b . 'cancelled', $b . 'cancelled_sub', ['uri:POST api/mobile/blood/requests/{id}/cancel']],
                'fulfilled' => ['outcome-ok', 'circle-check', [2, 2], $b . 'fulfilled', $b . 'fulfilled_sub', ['route:portals.staff.blood_requests.fulfil', 'method:' . $md . '::fulfil', 'route:documents.new.blood_request']],
                'declined'  => ['outcome-refused', 'circle-x', [3, 2], $b . 'declined', $b . 'declined_sub', ['route:portals.staff.blood_requests.decide']],
            ],
            'edges' => [
                ['patient', 'clinician', 'request', 'flows.edge.at_facility'],
                ['patient', 'app', 'request'],
                ['clinician', 'desk', 'request'],
                ['desk', 'decide', 'request'],
                ['app', 'decide', 'request', null, ['out' => 'r', 'in' => 'l+']],
                ['app', 'cancelled', 'refusal', 'flows.edge.cancel'],
                ['decide', 'inventory', 'async', 'flows.edge.checked'],
                ['inventory', 'finder', 'response'],
                ['decide', 'ready', 'request', 'flows.edge.accept'],
                ['ready', 'fulfilled', 'response'],
                ['decide', 'declined', 'refusal', 'flows.edge.decline', ['out' => 'r+', 'in' => 'l']],
            ],
            'steps' => [
                [['patient', 'app', 'decide'], $p . 's1', $p . 'd1'],
                [['patient', 'clinician', 'desk', 'decide'], $p . 's2', $p . 'd2'],
                [['decide', 'inventory'], $p . 's3', $p . 'd3'],
                [['decide', 'ready', 'fulfilled'], $p . 's4', $p . 'd4'],
                [['decide', 'declined'], $p . 's5', $p . 'd5'],
                [['app', 'cancelled'], $p . 's6', $p . 'd6'],
                [['inventory', 'finder'], $p . 's7', $p . 'd7'],
            ],
        ];
    }

    /**
     * Checked against the code (2026-10-19): approving a directory claim
     * (AdminDirectoryReviewController::approveClaim) does not open or feed
     * onboarding; the claim lives on the directory register (care_facilities)
     * and onboarding on the tenant register (facilities). They are drawn as
     * two independent paths from the owner, joined only by the listing link.
     */
    private static function facilityGoLive(): array
    {
        $p = 'blog_diagrams.facility-go-live.';
        $b = $p . 'box.';
        $ob = 'App\\Http\\Controllers\\MedicalId\\OnboardingPortalController';

        return [
            'title' => $p . 'title', 'summary' => $p . 'summary', 'cols' => 4, 'rows' => 3,
            'nodes' => [
                'owner'     => ['actor', 'user-round', [0, 0], $b . 'owner', $b . 'owner_sub', ['route:public.care-map']],
                'claim'     => ['service', 'map-pin', [1, 0], $b . 'claim', $b . 'claim_sub', ['route:public.care-map.claim', 'route:public.care-map.claim.store', 'class:App\\Http\\Controllers\\CareMap\\FacilityClaimController']],
                'review'    => ['decision', 'users', [2, 0], $b . 'review', $b . 'review_sub', ['route:admin.care-map.review', 'route:admin.care-map.review.claims.approve']],
                'listed'    => ['outcome-ok', 'circle-check', [3, 0], $b . 'listed', $b . 'listed_sub', ['route:admin.care-map.review.listings.link']],
                'checklist' => ['service', 'list-ordered', [0, 1], $b . 'checklist', $b . 'checklist_sub', ['route:portals.admin.onboarding.show', 'route:portals.admin.onboarding.mark', 'method:' . $ob . '::markItem']],
                'readiness' => ['gate', 'shield-check', [1, 1], $b . 'readiness', $b . 'readiness_sub', ['route:portals.admin.facilities.readiness', 'route:portals.admin.facilities.readiness.score', 'class:App\\Http\\Controllers\\MedicalId\\FacilityGoLiveReadinessController']],
                'rejected'  => ['outcome-refused', 'circle-x', [2, 1], $b . 'rejected', $b . 'rejected_sub', ['route:admin.care-map.review.claims.reject']],
                'approve'   => ['decision', 'git-branch', [1, 2], $b . 'approve', $b . 'approve_sub', ['route:portals.admin.onboarding.approve', 'method:' . $ob . '::approve']],
                'team'      => ['service', 'user-plus', [2, 2], $b . 'team', $b . 'team_sub', ['route:portals.facility.team.join.approve', 'route:professional.join.store']],
                'desks'     => ['outcome-ok', 'circle-check', [3, 2], $b . 'desks', $b . 'desks_sub', ['route:portals.pharmacy.stock', 'route:portals.lab.index', 'route:portals.queue.index']],
            ],
            'edges' => [
                ['owner', 'claim', 'request', 'flows.edge.claim'],
                ['claim', 'review', 'request'],
                ['review', 'listed', 'response', 'flows.edge.approve'],
                ['review', 'rejected', 'refusal', 'flows.edge.reject'],
                ['owner', 'checklist', 'request', 'flows.edge.onboard'],
                ['checklist', 'readiness', 'request'],
                ['readiness', 'approve', 'request'],
                ['approve', 'team', 'request', 'flows.edge.go_live'],
                ['team', 'desks', 'response'],
                ['approve', 'checklist', 'refusal', 'flows.edge.not_ready', ['out' => 'l', 'in' => 'b', 'loop' => true]],
            ],
            'steps' => [
                [['owner', 'claim', 'review'], $p . 's1', $p . 'd1'],
                [['review', 'listed'], $p . 's2', $p . 'd2'],
                [['owner', 'checklist'], $p . 's3', $p . 'd3'],
                [['checklist', 'readiness'], $p . 's4', $p . 'd4'],
                [['readiness', 'approve'], $p . 's5', $p . 'd5'],
                [['approve', 'team', 'desks'], $p . 's6', $p . 'd6'],
                [['approve', 'checklist'], $p . 's7', $p . 'd7'],
            ],
        ];
    }
}
