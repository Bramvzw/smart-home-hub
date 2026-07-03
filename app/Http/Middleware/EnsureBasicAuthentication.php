<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureBasicAuthentication
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = (string) config('network.basic_auth.username', '');
        $password = (string) config('network.basic_auth.password', '');

        if ($username === '' || $password === '') {
            return $next($request);
        }

        // The /up health endpoint stays open so container healthchecks keep working.
        if ($request->is('up')) {
            return $next($request);
        }

        if (hash_equals($username, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword())) {
            return $next($request);
        }

        return response('Unauthorized.', 401, ['WWW-Authenticate' => 'Basic realm="Smart Home Hub"']);
    }
}
