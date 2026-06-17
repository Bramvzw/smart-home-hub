<?php

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
        // Trust the reverse proxy (e.g. Synology) so X-Forwarded-Proto/-For are honored.
        // Required for correct https scheme detection behind the NAS reverse proxy:
        // fixes asset URLs, redirects and OAuth callbacks when served over HTTPS.
        $middleware->trustProxies(at: '*');
        $middleware->append(EnsurePrivateNetworkAccess::class);

        // Sidebar collapse state is written client-side (JS) and read back on
        // every server render to avoid a flash on navigation, so it must stay
        // unencrypted.
        $middleware->encryptCookies(except: ['sidebar_state']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
