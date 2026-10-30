<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Each request in the language its person picked: their account's, else the one this browser
 * keeps (a pick lasts there, and becomes the account's once they sign in); else their
 * browser's own, when the platform has it (es-AR finds Spanish, pt-PT Portuguese); else
 * English. Someone signed in without a choice keeps the one found, so their mail speaks it too.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = array_keys(config('app.locales'));
        $user = $request->user();
        $picked = $request->hasSession() ? $request->session()->get('locale') : null;
        $kept = $request->cookie('locale');

        if ($user && in_array($picked, $locales, true)) {
            $user->forceFill(['locale' => $picked])->saveQuietly();
            $request->session()->forget('locale');
        }

        $locale = in_array($user?->locale, $locales, true) ? $user->locale : null;
        $locale ??= ! $user && in_array($picked, $locales, true) ? $picked : null;
        $locale ??= in_array($kept, $locales, true) ? $kept : null;

        if (! in_array($user?->locale, $locales, true) && ($user || ! $locale)) {
            $locale ??= $this->fromBrowser($request, $locales) ?? 'en';
            $user?->forceFill(['locale' => $locale])->saveQuietly();
        }

        app()->setLocale($locale);

        return $next($request);
    }

    /** The first of the browser's languages the platform has, exactly or by its language */
    private function fromBrowser(Request $request, array $locales): ?string
    {
        foreach ($request->getLanguages() as $wanted) {
            if (in_array($wanted, $locales, true)) {
                return $wanted;
            }
            $language = explode('_', $wanted)[0];
            foreach ($locales as $locale) {
                if (explode('_', $locale)[0] === $language) {
                    return $locale;
                }
            }
        }

        return null;
    }
}
