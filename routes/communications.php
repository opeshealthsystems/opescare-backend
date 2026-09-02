<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CommunicationController;

Route::prefix('v1')->middleware(['verify.integration.client'])->group(function () {

    // Notifications
    Route::get('/notifications', [CommunicationController::class, 'getNotifications']);
    Route::get('/notifications/unread-count', [CommunicationController::class, 'getUnreadCount']);
    Route::post('/notifications/{id}/mark-read', [CommunicationController::class, 'markRead']);
    Route::post('/notifications/{id}/acknowledge', [CommunicationController::class, 'acknowledgeNotification']);
    Route::post('/notifications/mark-all-read', [CommunicationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/archive', [CommunicationController::class, 'archiveNotification']);

    Route::get('/notification-preferences', [CommunicationController::class, 'getPreferences']);
    Route::put('/notification-preferences', [CommunicationController::class, 'updatePreferences']);

    // Tasks
    Route::get('/tasks', [CommunicationController::class, 'getTasks']);
    Route::get('/tasks/{id}', [CommunicationController::class, 'getTask']);
    Route::post('/tasks/{id}/acknowledge', [CommunicationController::class, 'acknowledgeTask']);
    Route::post('/tasks/{id}/complete', [CommunicationController::class, 'completeTask']);
    Route::post('/tasks/{id}/assign', [CommunicationController::class, 'assignTask']);
    Route::post('/tasks/{id}/escalate', [CommunicationController::class, 'escalateTask']);

    // Admin Templates, Deliveries & Escalations
    // [RBAC FIX] These admin endpoints previously had NO middleware — any unauthenticated
    // caller could create/update/publish notification templates. Now protected by
    // VerifyIntegrationClient (B2B Argon2id credential check).
    Route::prefix('admin')->middleware(['api.admin'])->group(function () {
        Route::get('/notification-templates', [CommunicationController::class, 'getAdminTemplates']);
        Route::post('/notification-templates', [CommunicationController::class, 'createAdminTemplate']);
        Route::put('/notification-templates/{id}', [CommunicationController::class, 'updateAdminTemplate']);
        Route::post('/notification-templates/{id}/submit-review', [CommunicationController::class, 'submitTemplateReview']);
        Route::post('/notification-templates/{id}/approve', [CommunicationController::class, 'approveTemplate']);
        Route::post('/notification-templates/{id}/publish', [CommunicationController::class, 'publishTemplate']);
        Route::post('/notification-templates/{id}/rollback', [CommunicationController::class, 'rollbackTemplate']);

        Route::get('/notification-deliveries', [CommunicationController::class, 'getAdminDeliveries']);
        Route::post('/notification-deliveries/{id}/retry', [CommunicationController::class, 'retryDelivery']);

        Route::get('/escalation-chains', [CommunicationController::class, 'getEscalationChains']);
        Route::post('/escalation-chains', [CommunicationController::class, 'createEscalationChain']);
        Route::put('/escalation-chains/{id}', [CommunicationController::class, 'updateEscalationChain']);
        Route::post('/escalation-chains/{id}/activate', [CommunicationController::class, 'activateEscalationChain']);
        Route::post('/escalation-chains/{id}/deactivate', [CommunicationController::class, 'deactivateEscalationChain']);

        Route::post('/broadcasts', [CommunicationController::class, 'createBroadcast']);
        Route::put('/broadcasts/{id}', [CommunicationController::class, 'updateAdminTemp']);
        Route::post('/broadcasts/{id}/publish', [CommunicationController::class, 'publishBroadcast']);
        Route::post('/broadcasts/{id}/cancel', [CommunicationController::class, 'cancelBroadcast']);
        Route::get('/broadcasts/{id}/acknowledgements', [CommunicationController::class, 'broadcastAcknowledgements']);
    });

    // Messaging
    Route::get('/messages/threads', [CommunicationController::class, 'getThreads']);
    Route::post('/messages/threads', [CommunicationController::class, 'createThread']);
    Route::get('/messages/threads/{id}', [CommunicationController::class, 'getThread']);
    Route::post('/messages/threads/{id}/messages', [CommunicationController::class, 'sendMessage']);
    Route::post('/messages/threads/{id}/participants', [CommunicationController::class, 'getThread']); // stub
    Route::post('/messages/threads/{id}/assign', [CommunicationController::class, 'getThread']); // stub
    Route::post('/messages/threads/{id}/close', [CommunicationController::class, 'closeThread']);
    Route::post('/messages/threads/{id}/reopen', [CommunicationController::class, 'reopenThread']);
    Route::post('/messages/{id}/edit', [CommunicationController::class, 'editMessage']);
    Route::post('/messages/{id}/delete-for-me', [CommunicationController::class, 'deleteMessageForMe']);
    Route::post('/messages/{id}/report', [CommunicationController::class, 'reportMessage']);
    Route::post('/messages/{id}/attachments', [CommunicationController::class, 'uploadAttachment']);

    // Broadcasts
    Route::get('/broadcasts', [CommunicationController::class, 'getBroadcasts']);
    Route::post('/broadcasts/{id}/acknowledge', [CommunicationController::class, 'acknowledgeBroadcast']);
});
