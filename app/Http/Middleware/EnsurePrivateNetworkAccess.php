<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePrivateNetworkAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('network.private_access.enabled')) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $allowedCidrs = config('network.private_access.allowed_cidrs', []);

        if (is_string($allowedCidrs)) {
            $allowedCidrs = array_values(array_filter(array_map('trim', explode(',', $allowedCidrs))));
        }

        if ($clientIp !== null && IpUtils::checkIp($clientIp, $allowedCidrs)) {
            return $next($request);
        }

        abort(403, 'This site is only available on the private network.');
    }
}
