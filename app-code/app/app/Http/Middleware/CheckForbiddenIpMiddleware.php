<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckForbiddenIpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $forbidden_ips = trim_strs_in_arr(config('ip.forbidden_list', []));

        if (in_array($ip, $forbidden_ips, true)) {
            abort(403, 'Access denied for this site!');
        }

        return $next($request);
    }
}
