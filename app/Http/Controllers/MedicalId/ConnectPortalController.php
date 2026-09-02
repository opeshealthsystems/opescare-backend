<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\IntegrationClient;
use App\Models\SdkToken;
use App\Models\WebhookDeliveryLog;
use App\Models\WebhookSubscription;
use App\Modules\Connect\Services\ConnectAdminService;
use Illuminate\Http\Request;
use Throwable;

class ConnectPortalController extends Controller
{
    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-admin';
    }

    // ── Dashboard ─────────────────────────────────────────────────

    public function index(ConnectAdminService $svc)
    {
        $stats   = $svc->dashboardStats();
        $recent  = IntegrationClient::orderByDesc('created_at')->limit(5)->get();
        $pending = IntegrationClient::where('status', 'pending')->orderByDesc('created_at')->get();

        return view('portals.admin.connect.index', compact('stats', 'recent', 'pending'));
    }

    // ── API Clients ───────────────────────────────────────────────

    public function clients(Request $request)
    {
        $q = IntegrationClient::orderByDesc('created_at');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('environment')) {
            $q->where('environment', $request->environment);
        }

        $clients = $q->paginate(20)->withQueryString();

        return view('portals.admin.connect.clients', [
            'clients' => $clients,
            'scopes'  => IntegrationClient::availableScopes(),
        ]);
    }

    public function clientStore(Request $request, ConnectAdminService $svc)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:300',
            'contact_email' => 'nullable|email|max:100',
            'environment'   => 'required|in:sandbox,production',
            'scopes'        => 'required|array|min:1',
            'scopes.*'      => 'string',
        ]);

        try {
            $client = $svc->createClient($request->validated(), $this->demoActorId());
            return redirect()->route('portals.admin.connect.clients')
                ->with('success', __('flash.connect_client_created', ['name' => $client->name, 'id' => $client->client_id]));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function clientAction(Request $request, string $id, ConnectAdminService $svc)
    {
        $request->validate(['action' => 'required|in:approve,suspend,revoke']);
        $client = IntegrationClient::findOrFail($id);

        try {
            match($request->action) {
                'approve' => $svc->approveClient($client, $this->demoActorId()),
                'suspend' => $svc->suspendClient($client, $this->demoActorId()),
                'revoke'  => $svc->revokeClient($client, $this->demoActorId()),
            };

            return redirect()->route('portals.admin.connect.clients')
                ->with('success', __('flash.connect_client_action_done', ['action' => $request->action . 'd']));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── SDK Tokens ────────────────────────────────────────────────

    public function tokens(Request $request)
    {
        $tokens = SdkToken::orderByDesc('created_at')->paginate(20)->withQueryString();
        $clients = IntegrationClient::where('status', 'active')->orderBy('name')->get();

        return view('portals.admin.connect.tokens', compact('tokens', 'clients'));
    }

    public function tokenStore(Request $request, ConnectAdminService $svc)
    {
        $request->validate([
            'client_id'   => 'required|string',
            'label'       => 'nullable|string|max:80',
            'environment' => 'required|in:sandbox,production',
            'expires_days'=> 'nullable|integer|min:1|max:365',
        ]);

        try {
            $client = IntegrationClient::where('client_id', $request->client_id)->firstOrFail();
            $result = $svc->issueToken($client, $request->validated(), $this->demoActorId());

            return redirect()->route('portals.admin.connect.tokens')
                ->with('success', __('flash.token_issued'))
                ->with('new_token', $result['raw_token']);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function tokenRevoke(string $id, ConnectAdminService $svc)
    {
        $token = SdkToken::findOrFail($id);
        try {
            $svc->revokeToken($token, $this->demoActorId());
            return redirect()->route('portals.admin.connect.tokens')
                ->with('success', __('flash.token_revoked'));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Webhooks ──────────────────────────────────────────────────

    public function webhooks(Request $request)
    {
        $subscriptions = WebhookSubscription::orderByDesc('created_at')->paginate(20)->withQueryString();
        $deliveryLogs  = WebhookDeliveryLog::orderByDesc('created_at')->limit(30)->get();
        $stats         = [
            'delivered' => WebhookDeliveryLog::where('status','delivered')->count(),
            'failed'    => WebhookDeliveryLog::where('status','failed')->count(),
            'pending'   => WebhookDeliveryLog::where('status','pending')->count(),
        ];

        return view('portals.admin.connect.webhooks', compact('subscriptions', 'deliveryLogs', 'stats'));
    }

    public function webhookToggle(string $id)
    {
        $sub = WebhookSubscription::findOrFail($id);
        $sub->update(['status' => $sub->status === 'active' ? 'paused' : 'active']);
        return redirect()->route('portals.admin.connect.webhooks')
            ->with('success', __('flash.webhook_subscription_updated'));
    }

    // ── Widget Embed ──────────────────────────────────────────────

    public function widget(Request $request)
    {
        $clients = IntegrationClient::where('status', 'active')->orderBy('name')->get();
        $baseUrl = rtrim(config('app.url'), '/');

        return view('portals.admin.connect.widget', compact('clients', 'baseUrl'));
    }
}
