<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Response;

class DocumentationController extends Controller
{
    /**
     * Display FAQ page based on user role and locale.
     */
    public function faq(Request $request): Response
    {
        $locale = app()->getLocale();
        $role = $this->role($request);
        $faqPath = resource_path("views/guides/{$locale}/FAQ.{$role}.json");

        // Fallback to English if current locale file doesn't exist
        if ($locale !== 'en' && ! File::exists($faqPath)) {
            $faqPath = resource_path("views/guides/en/FAQ.{$role}.json");
        }

        $faqData = json_decode(File::get($faqPath), true);

        return inertia('Documentation/Faq', [
            'questions'   => $faqData['questions'],
            'title'       => $faqData['title'],
            'description' => $faqData['description'],
            'guideText'   => $faqData['guideText'],
            'userRole'    => $role,
        ]);
    }

    /**
     * Display comprehensive guide based on user role and locale.
     */
    public function guide(Request $request): Response
    {
        $locale = app()->getLocale();
        $user = $request->user();
        $role = $user->isSuperadmin() ? 'superadmin' : $this->role($request);
        $guidePath = resource_path("views/guides/{$locale}/guide.{$role}.html");

        // Fallback to English if current locale file doesn't exist
        if ($locale !== 'en' && ! File::exists($guidePath)) {
            $guidePath = resource_path("views/guides/en/guide.{$role}.html");
        }

        $content = File::get($guidePath);
        return inertia('Documentation/Guide', [
            'userRole' => $role,
            'content'  => $content,
        ]);
    }

    /**
     * The member manual on its own, for anyone (signed in or not): the manual alone, in the
     * browser's language (or the one picked), as the device shows things.
     */
    public function manual(): Response
    {
        $path = resource_path('views/guides/' . app()->getLocale() . '/guide.member.html');

        return inertia('Manual', [
            'content' => File::get(File::exists($path) ? $path : resource_path('views/guides/en/guide.member.html')),
        ]);
    }

    /**
     * Admins of any project (or superadmins) read the admin guide.
     */
    private function role(Request $request): string
    {
        $user = $request->user();

        return $user->isSuperadmin() || $user->memberships->contains('is_admin', true) ? 'admin' : 'member';
    }
}
