<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Mail\OpesCareNotificationMail;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminOrganizationsController extends Controller
{
    private function actorId(): string
    {
        return Auth::id() ?? session('auth_email', 'system');
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $facilities = Facility::query()
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        $total = Facility::count();

        $byType = Facility::selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'type');

        $pending = Facility::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        $pendingCount = $pending->count();

        return view('portals.admin.organizations.index', compact(
            'facilities',
            'total',
            'byType',
            'pending',
            'pendingCount'
        ));
    }

    public function pending(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $organizations = Facility::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('portals.admin.organizations.pending', compact('organizations'));
    }

    public function approve(string $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $facility = Facility::findOrFail($id);

        $updates = ['status' => 'active'];

        if (Schema::hasColumn($facility->getTable(), 'approved_at')) {
            $updates['approved_at'] = now();
        }

        if (Schema::hasColumn($facility->getTable(), 'approved_by')) {
            $updates['approved_by'] = $this->actorId();
        }

        $facility->update($updates);

        $contactEmail = null;
        if (Schema::hasColumn($facility->getTable(), 'contact_email')) {
            $contactEmail = $facility->contact_email;
        } elseif (Schema::hasColumn($facility->getTable(), 'email')) {
            $contactEmail = $facility->email;
        }

        if ($contactEmail) {
            try {
                Mail::to($contactEmail)->send(new OpesCareNotificationMail(
                    'Your facility application has been approved',
                    "Dear {$facility->name},\n\nYour facility registration on OpesCare has been approved. You can now log in and begin using the platform.\n\nThank you."
                ));
            } catch (\Throwable $e) {
                // Non-fatal — approval still proceeds even if mail fails
            }
        }

        return redirect()->back()->with('success', __('flash.facility_approved'));
    }

    public function reject(string $id, Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $facility = Facility::findOrFail($id);

        $updates = ['status' => 'rejected'];

        if (Schema::hasColumn($facility->getTable(), 'rejection_reason')) {
            $updates['rejection_reason'] = $request->input('reason');
        }

        if (Schema::hasColumn($facility->getTable(), 'rejected_by')) {
            $updates['rejected_by'] = $this->actorId();
        }

        if (Schema::hasColumn($facility->getTable(), 'rejected_at')) {
            $updates['rejected_at'] = now();
        }

        $facility->update($updates);

        $contactEmail = null;
        if (Schema::hasColumn($facility->getTable(), 'contact_email')) {
            $contactEmail = $facility->contact_email;
        } elseif (Schema::hasColumn($facility->getTable(), 'email')) {
            $contactEmail = $facility->email;
        }

        if ($contactEmail) {
            try {
                $reason = $request->input('reason', 'No reason provided.');
                Mail::to($contactEmail)->send(new OpesCareNotificationMail(
                    'Your facility application was not approved',
                    "Dear {$facility->name},\n\nUnfortunately your facility registration could not be approved at this time.\n\nReason: {$reason}\n\nPlease contact support if you have questions."
                ));
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }

        return redirect()->back()->with('success', __('flash.facility_rejected'));
    }

    public function destroy(string $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $facility = Facility::findOrFail($id);

        if ($facility->status === 'active') {
            return redirect()->back()->with('error', __('flash.facility_delete_active'));
        }

        $facility->delete();

        return redirect()->route('portals.admin.organizations.index')->with('success', __('flash.facility_deleted'));
    }
}
