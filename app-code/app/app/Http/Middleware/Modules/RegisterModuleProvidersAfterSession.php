<?php

declare(strict_types=1);

namespace App\Http\Middleware\Modules;

use App\Services\Modules\ModuleProviderRegistrarService;
use App\Services\Modules\ModuleProviderResolverService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registers module providers that require the middleware-after-session strategy.
 *
 * This middleware must run in the web stack after session middleware so request
 * context and session-dependent decisions are available.
 */
class RegisterModuleProvidersAfterSession
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningInConsole() === false) {
            app(ModuleProviderRegistrarService::class)->register(
                app(ModuleProviderResolverService::class)->resolveForStrategy('middleware_after_session', $request),
            );
        }

        return $next($request);
    }
}
