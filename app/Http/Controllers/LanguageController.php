<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

/**
 * The language someone picks: their account's when signed in, else kept for the visit
 * (and given to the account they then sign in to); and always in this browser, which keeps
 * it whatever happens to sessions and accounts.
 */
class LanguageController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('app.locales')))],
        ])['locale'];

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        } else {
            $request->session()->put('locale', $locale);
        }

        Cookie::queue('locale', $locale, 60 * 24 * 365);

        return back();
    }
}
