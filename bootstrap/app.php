<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\AttachRequestId;
use App\Http\Middleware\AuthenticateFirebaseMobile;
use App\Http\Middleware\EnsureUserIsActive;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'auth.firebase' => AuthenticateFirebaseMobile::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->statefulApi();
        $middleware->appendToGroup('web', AddSecurityHeaders::class);
        $middleware->appendToGroup('api', AttachRequestId::class);
        $middleware->appendToGroup('api', AddSecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Validation failed.', 422, $exception->errors());
        });
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            if ($exception instanceof ModelNotFoundException) {
                return ApiResponse::error('Resource not found.', 404);
            }
            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $message = match ($status) {
                    401 => 'Unauthenticated.', 403 => 'Forbidden.', 404 => 'Resource not found.', 429 => 'Too many requests.', default => $status >= 500 ? 'An unexpected error occurred.' : ($exception->getMessage() ?: 'Request failed.')
                };

                return ApiResponse::error($message, $status);
            }

            report($exception);

            return ApiResponse::error('An unexpected error occurred.', 500);
        });
    })->create();
