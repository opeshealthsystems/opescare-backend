<?php

namespace App\Http\Controllers\MedicalId;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class AdminSupportController extends Controller
{
    private function actorId(): string
    {
        return Auth::id() ?? session('auth_email', 'system');
    }

    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $query = SupportTicket::query()
            ->with(['assignments']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', '%' . $search . '%')
                  ->orWhere('id', 'like', '%' . $search . '%');
            });
        }

        $total = SupportTicket::count();
        $open = SupportTicket::where('status', 'open')->count();
        $pending = SupportTicket::where('status', 'in_progress')->count();
        $resolved = SupportTicket::where('status', 'resolved')->count();

        $avgResolutionHours = SupportTicket::whereNotNull('resolved_at')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at))/3600) as avg_hours')
            ->value('avg_hours');

        $stats = [
            'total'                    => $total,
            'open'                     => $open,
            'pending'                  => $pending,
            'resolved'                 => $resolved,
            'average_resolution_hours' => round((float) $avgResolutionHours, 1),
        ];

        $tickets = $query->latest()->paginate(25)->withQueryString();

        return view('portals.admin.support.index', compact('tickets', 'stats'));
    }

    public function show(string $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $ticket = SupportTicket::with(['messages', 'assignments'])->findOrFail($id);

        $assignees = User::orderBy('name')->get();

        return view('portals.admin.support.show', compact('ticket', 'assignees'));
    }

    public function assign(Request $request, string $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'assignee_id' => 'required|exists:users,id',
        ]);

        $ticket = SupportTicket::findOrFail($id);

        $ticket->update([
            'assigned_to' => $validated['assignee_id'],
            'status'      => 'in_progress',
        ]);

        return redirect()->back()->with('success', __('flash.ticket_assigned'));
    }

    public function close(string $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $ticket = SupportTicket::findOrFail($id);

        $ticket->update([
            'status'      => 'resolved',
            'resolved_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', __('flash.ticket_closed'));
    }

    public function reopen(string $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $ticket = SupportTicket::findOrFail($id);

        $ticket->update([
            'status' => 'open',
        ]);

        return redirect()->back()->with('success', __('flash.ticket_reopened'));
    }

    public function destroy(string $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $ticket = SupportTicket::findOrFail($id);
        $ticket->delete();

        return redirect()->route('portals.admin.support.index')->with('success', __('flash.ticket_deleted'));
    }
}
