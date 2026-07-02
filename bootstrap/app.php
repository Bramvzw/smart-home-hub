<?php

use App\Dashboard\SidebarState;
use App\Http\Middleware\EnsurePrivateNetworkAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Honor X-Forwarded-* from the NAS reverse proxy for https scheme detection. Only LAN proxies are
        // trusted, so a public client can't spoof X-Forwarded-For to bypass EnsurePrivateNetworkAccess.
        $middleware->trustProxies(
            at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
        $middleware->append(EnsurePrivateNetworkAccess::class);

        // The sidebar cookie is read back on every server render, so it must stay unencrypted.
        $middleware->encryptCookies(except: [SidebarState::COOKIE]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
