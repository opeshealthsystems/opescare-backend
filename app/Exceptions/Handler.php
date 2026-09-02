<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Global exception handler — ensures all API routes return JSON, never HTML stack traces.
 *
 * Web routes still return HTML. Only requests where the path starts with /api/
 * or where Accept: application/json is present get JSON error responses.
 */
class Handler extends ExceptionHandler
{
    protected $dontReport = [
        AuthenticationException::class,
        ValidationException::class,
        ModelNotFoundException::class,
    ];

    public function render($request, Throwable $e): \Illuminate\Http\Response|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($this->isApiRequest($request)) {
            return $this->renderApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*')
            || $request->expectsJson()
            || $request->is('fhir/*');
    }

    private function renderApiException(Request $request, Throwable $e): JsonResponse
    {
        // Validation errors — 422 with per-field details
        if ($e instanceof ValidationException) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'VALIDATION_FAILED',
                'message'    => 'The request data failed validation.',
                'errors'     => $e->errors(),
            ], 422);
        }

        // Model not found — 404
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'NOT_FOUND',
                'message'    => 'The requested resource was not found.',
            ], 404);
        }

        // Route not found — 404
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'ENDPOINT_NOT_FOUND',
                'message'    => 'The API endpoint does not exist. Check the URL and HTTP method.',
            ], 404);
        }

        // Authentication — 401
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'UNAUTHENTICATED',
                'message'    => 'Authentication is required.',
            ], 401);
        }

        // Other HTTP exceptions (403, 405, 429, etc.)
        if ($e instanceof HttpException) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'HTTP_ERROR',
                'message'    => $e->getMessage() ?: 'An HTTP error occurred.',
            ], $e->getStatusCode());
        }

        // Unexpected server errors — log but never leak stack trace
        $this->report($e);

        $message = config('app.debug')
            ? $e->getMessage()
            : 'An unexpected server error occurred. Please try again or contact support.';

        return response()->json([
            'status'     => 'error',
            'error_code' => 'INTERNAL_SERVER_ERROR',
            'message'    => $message,
            'request_id' => $request->header('X-Request-Id', bin2hex(random_bytes(8))),
        ], 500);
    }
}
