<?php

use App\Core\Http\ApiResponse;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetHospitalContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->statefulApi();
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'active' => EnsureUserIsActive::class,
        ]);
        $middleware->appendToGroup('api', [
            SetHospitalContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return ApiResponse::error('Validation failed.', 422, $e->errors());
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error('You are not authorized to perform this action.', 403);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ApiResponse::error('Resource not found.', 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                return ApiResponse::error($e->getMessage() ?: 'Request failed.', $e->getStatusCode());
            }

            report($e);

            $message = app()->hasDebugModeEnabled()
                ? $e->getMessage()
                : 'An unexpected error occurred.';

            return ApiResponse::error($message, 500);
        });
    })->create();
