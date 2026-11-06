<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureLoginDeviceIsApproved;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->replace(
            \Illuminate\Http\Middleware\TrustProxies::class,
            \App\Http\Middleware\TrustCloudflareClientIp::class,
        );

        $middleware->appendToGroup('web', [
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            EnsureLoginDeviceIsApproved::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $exception, \Illuminate\Http\Request $request) {
            if ($exception->getStatusCode() !== 419 || !$request->isMethod('POST') || !$request->is('login')) {
                return null;
            }

            // Record configuration clues only; never log credentials, cookies or tokens.
            \Illuminate\Support\Facades\Log::warning('auth.login_csrf_mismatch', [
                'host' => $request->getHost(),
                'scheme' => $request->getScheme(),
                'session_cookie_present' => $request->cookies->has(config('session.cookie')),
                'session_driver' => config('session.driver'),
                'session_lifetime' => config('session.lifetime'),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Phiên đăng nhập đã hết hạn hoặc thay đổi. Vui lòng tải lại trang đăng nhập.'], 419);
            }

            $username = $request->input('username');

            return redirect()->route('login', [], 303)
                ->withErrors(['username' => 'Phiên đăng nhập đã hết hạn hoặc thay đổi. Vui lòng nhập lại mật khẩu và đăng nhập.'])
                ->withInput(['username' => is_string($username) ? mb_substr($username, 0, 255) : '']);
        });
    })->create();
