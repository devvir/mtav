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
        $role = $request->user()?->is_admin ? 'admin' : 'member';
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
        $role = $request->user()?->is_admin ? 'admin' : 'member';
        $guidePath = resource_path("views/guides/{$locale}/guide.{$role}.html");

        // Fallback to English if current locale file doesn't exist
        if ($locale !== 'en' && ! File::exists($guidePath)) {
            $guidePath = resource_path("views/guides/en/guide.{$role}.html");
        }

        $content = File::get($guidePath);
        $component = $role === 'admin' ? 'Documentation/AdminGuide' : 'Documentation/MemberGuide';

        return inertia($component, [
            'userRole' => $role,
            'content'  => $content,
        ]);
    }
}
