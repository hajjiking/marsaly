<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['api', 'set.locale'])
                ->prefix('api/v1')
                ->group(base_path('routes/api/v1.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'       => \App\Http\Middleware\EnsureRole::class,
            'set.locale' => \App\Http\Middleware\SetLocale::class,
        ]);

        // Return the Marsaly JSON envelope for API errors instead of HTML.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Consistent JSON envelope for every API error.
        $exceptions->render(function (Throwable $e, Illuminate\Http\Request $request) {
            if (!$request->is('api/*')) {
                return null; // web/admin keeps default rendering
            }

            $status = method_exists($e, 'getStatusCode')
                ? $e->getStatusCode()
                : ($e instanceof Symfony\Component\HttpKernel\Exception\HttpException ? $e->getStatusCode() : 500);

            if ($e instanceof Illuminate\Validation\ValidationException) {
                return \App\Support\ApiResponse::error(
                    __('api.validation_failed'), 422, $e->errors(),
                );
            }
            if ($e instanceof Illuminate\Auth\AuthenticationException) {
                return \App\Support\ApiResponse::error(__('api.unauthorized'), 401);
            }
            if ($e instanceof Illuminate\Auth\Access\AuthorizationException) {
                return \App\Support\ApiResponse::error(__('api.forbidden'), 403);
            }
            if ($e instanceof Illuminate\Http\Exception) {
                // abort(409, 'message') etc. — pass through the message we control.
                $msg = $e->getMessage() ?: __('api.server_error');
                return \App\Support\ApiResponse::error($msg, $status);
            }
            if ($e instanceof \App\Exceptions\InvalidOrderTransitionException) {
                return \App\Support\ApiResponse::error(
                    __('api.invalid_transition', ['from' => $e->from, 'to' => $e->to]), 409,
                );
            }
            if ($e instanceof Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return \App\Support\ApiResponse::error(__('api.not_found'), 404);
            }
            if ($e instanceof Illuminate\Routing\Exceptions\MethodNotAllowedHttpException) {
                return \App\Support\ApiResponse::error(__('api.not_found'), 405);
            }
            if ($e instanceof Illuminate\Database\Eloquent\ModelNotFoundException) {
                return \App\Support\ApiResponse::error(__('api.not_found'), 404);
            }
            if ($e instanceof Illuminate\Routing\Exceptions\StreamedResponseException) {
                return null;
            }

            report($e);
            $msg = config('app.debug') && $e->getMessage() ? $e->getMessage() : __('api.server_error');
            return \App\Support\ApiResponse::error($msg, 500);
        });
    })->create();
