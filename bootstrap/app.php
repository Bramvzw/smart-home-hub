<?php

use App\Dashboard\SidebarState;
use App\Http\Middleware\EnsurePrivateNetworkAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Honor X-Forwarded-* from the NAS reverse proxy so https scheme detection
        // works (asset URLs, redirects, OAuth callbacks served over HTTPS).
        $middleware->trustProxies(at: '*');
        $middleware->append(EnsurePrivateNetworkAccess::class);

        // The sidebar cookie is read back on every server render, so it must stay unencrypted.
        $middleware->encryptCookies(except: [SidebarState::COOKIE]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
