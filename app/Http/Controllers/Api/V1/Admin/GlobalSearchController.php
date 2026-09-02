<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Search\Services\GlobalSearchService;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $service)
    {
        $validated = $request->validate([
            'q' => ['required', 'string'],
            'actor_id' => ['nullable', 'string'],
            'include_sensitive' => ['nullable'],
            'purpose' => ['nullable', 'string'],
            'authorized_message_user_id' => ['nullable', 'string'],
        ]);

        $includeSensitive = filter_var($validated['include_sensitive'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($includeSensitive) {
            $hasPermission = $request->attributes->get('admin_role') ||
                             $request->attributes->get('permission_global_resolve');
            if (!$hasPermission) {
                $includeSensitive = false;
            }
        }

        return response()->json($service->search($validated['q'], [
            'actor_id' => $validated['actor_id'] ?? null,
            'include_sensitive' => $includeSensitive,
            'purpose' => $validated['purpose'] ?? null,
            'authorized_message_user_id' => $validated['authorized_message_user_id'] ?? null,
        ]));
    }
}
