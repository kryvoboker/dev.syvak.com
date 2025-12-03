<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultLocalePrefix
{
    /**
     * Handle an incoming request.
     *
     * @param Request                      $request
     * @param Closure(Request): (Response) $next
     *
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $locale = $request->route('locale');

        // If locale is in route, save it to session
        if (!$locale || !in_array($locale, config('app.locales', ['en']))) {
            $locale = session('locale', config('app.locale', 'en'));

            return redirect()->route(
                $request->route()->getName(),
                array_merge($request->route()->parameters(), ['locale' => $locale])
            );
        }

        session()->put('locale', $locale);
        app()->setLocale($locale);
        url()->defaults(['locale' => $locale]);

        return $next($request);
    }
}
