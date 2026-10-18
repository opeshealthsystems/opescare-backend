<?php

// Registered from AppServiceProvider. Routes for code imported from the platform
// (docs/PLATFORM_IMPORTS.md). The middleware stack is declared here next to the routes it gates.
//
// Group B — staff task inbox, support tickets (Help), broadcasts.
//
//   /facility/tasks, /facility/support   operational staff + facility admins
//       portal.access admits them via the 'facility/tasks' / 'facility/support'
//       keys in EnsurePortalAccess; no capability gates these (every staff role
//       has tasks and may ask for help). The facility is resolved inside the
//       controllers by DeskFacilityContext — never from the request — and a
//       task or ticket at another facility is a 404.
//
//   /admin/tasks, /admin/broadcasts, /admin/support   platform tier only:
//       everything under /admin/* is RequirePlatformAdmin's (platform.admin).
//
// Every transition is a POST. There is no DELETE: tasks and broadcasts are
// cancelled, tickets are resolved.

use App\Http\Controllers\MedicalId\AdminBroadcastController;
use App\Http\Controllers\MedicalId\AdminSupportController;
use App\Http\Controllers\MedicalId\AdminTaskController;
use App\Http\Controllers\MedicalId\StaffSupportController;
use App\Http\Controllers\MedicalId\TaskInboxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'throttle:portal'])->group(function () {
    Route::get('/facility/tasks', [TaskInboxController::class, 'index'])->name('portals.staff.tasks');
    Route::post('/facility/tasks/{uuid}/acknowledge', [TaskInboxController::class, 'acknowledge'])->name('portals.staff.tasks.acknowledge');
    Route::post('/facility/tasks/{uuid}/complete', [TaskInboxController::class, 'complete'])->name('portals.staff.tasks.complete');
    Route::post('/facility/tasks/{uuid}/escalate', [TaskInboxController::class, 'escalate'])->name('portals.staff.tasks.escalate');
    Route::post('/facility/tasks/broadcasts/{uuid}/acknowledge', [TaskInboxController::class, 'acknowledgeBroadcast'])->name('portals.staff.broadcasts.acknowledge');

    Route::get('/facility/support', [StaffSupportController::class, 'index'])->name('portals.staff.support');
    Route::post('/facility/support', [StaffSupportController::class, 'store'])->name('portals.staff.support.store');
    Route::get('/facility/support/articles/{slug}', [StaffSupportController::class, 'article'])->name('portals.staff.support.article');
    Route::get('/facility/support/{id}', [StaffSupportController::class, 'show'])->name('portals.staff.support.show');
    Route::post('/facility/support/{id}/reply', [StaffSupportController::class, 'reply'])->name('portals.staff.support.reply');
    Route::post('/facility/support/{id}/close', [StaffSupportController::class, 'close'])->name('portals.staff.support.close');
});

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'platform.admin', 'throttle:portal'])->group(function () {
    Route::get('/admin/tasks', [AdminTaskController::class, 'index'])->name('portals.admin.tasks');
    Route::get('/admin/tasks/{uuid}', [AdminTaskController::class, 'show'])->name('portals.admin.tasks.show');
    Route::post('/admin/tasks', [AdminTaskController::class, 'store'])->name('portals.admin.tasks.store');
    Route::post('/admin/tasks/{uuid}/reassign', [AdminTaskController::class, 'reassign'])->name('portals.admin.tasks.reassign');
    Route::post('/admin/tasks/{uuid}/complete', [AdminTaskController::class, 'complete'])->name('portals.admin.tasks.complete');
    Route::post('/admin/tasks/{uuid}/escalate', [AdminTaskController::class, 'escalate'])->name('portals.admin.tasks.escalate');
    Route::post('/admin/tasks/{uuid}/cancel', [AdminTaskController::class, 'cancel'])->name('portals.admin.tasks.cancel');

    Route::get('/admin/broadcasts', [AdminBroadcastController::class, 'index'])->name('portals.admin.broadcasts');
    Route::post('/admin/broadcasts', [AdminBroadcastController::class, 'store'])->name('portals.admin.broadcasts.store');
    Route::get('/admin/broadcasts/{uuid}', [AdminBroadcastController::class, 'show'])->name('portals.admin.broadcasts.show');
    Route::post('/admin/broadcasts/{uuid}/publish', [AdminBroadcastController::class, 'publish'])->name('portals.admin.broadcasts.publish');
    Route::post('/admin/broadcasts/{uuid}/cancel', [AdminBroadcastController::class, 'cancel'])->name('portals.admin.broadcasts.cancel');

    Route::get('/admin/support', [AdminSupportController::class, 'index'])->name('portals.admin.support.index');
    Route::get('/admin/support/{id}', [AdminSupportController::class, 'show'])->name('portals.admin.support.show');
    Route::post('/admin/support/{id}/assign', [AdminSupportController::class, 'assign'])->name('portals.admin.support.assign');
    Route::post('/admin/support/{id}/reply', [AdminSupportController::class, 'reply'])->name('portals.admin.support.reply');
    Route::post('/admin/support/{id}/close', [AdminSupportController::class, 'close'])->name('portals.admin.support.close');
    Route::post('/admin/support/{id}/reopen', [AdminSupportController::class, 'reopen'])->name('portals.admin.support.reopen');
});
