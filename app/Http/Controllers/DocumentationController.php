<?php

namespace App\Http\Controllers;

use App\Support\Variant;
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
        $faqData = $this->faqOf($this->role($request));

        return inertia('Documentation/Faq', [
            'questions'   => $faqData['questions'],
            'title'       => $faqData['title'],
            'description' => $faqData['description'],
            'guideText'   => $faqData['guideText'],
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

        return inertia('Documentation/Guide', [
            'content' => strtr(File::get($guidePath), ['{app}' => e(config('app.name'))]),
        ]);
    }

    /**
     * A guide on its own, for anyone (signed in or not): the member's unless the address names
     * the admins' or the superadmins', in the browser's language (or the one picked), as the
     * device shows things. Its title is the guide's own heading.
     */
    public function manual(?string $role = null): Response
    {
        $guide = ['admins' => 'admin', 'superadmins' => 'superadmin'][$role] ?? 'member';
        $content = strtr(File::get($this->localized("guides/{locale}/guide.{$guide}.html")), ['{app}' => e(config('app.name'))]);
        preg_match('#<h1>(.*?)</h1>#s', $content, $heading);

        return inertia('PublicDocument', ['title' => trim(strip_tags($heading[1] ?? '')), 'content' => $content]);
    }

    /** The questions on their own, for anyone: the member's, or the admins' (`role`), each ending where its guide is */
    public function questions(?string $role = null): Response
    {
        $faq = $this->faqOf($role === 'admins' ? 'admin' : 'member');

        $content = '<h1>' . e($faq['title']) . '</h1><p class="lead">' . e($faq['description']) . '</p>';
        foreach ($faq['questions'] as $question) {
            $content .= '<h2>' . e($question['question']) . '</h2><p>' . e($question['answer']) . '</p>';
        }
        $content .= '<p><a href="' . e(route('manual', $role)) . '">' . e($faq['guideText']) . '</a></p>';

        return inertia('PublicDocument', ['title' => $faq['title'], 'content' => $content]);
    }

    /** What is kept about people, who sees it, and their rights */
    public function privacy(): Response
    {
        return $this->public(__('Privacy'), 'legal/{locale}/' . Variant::privacy(), [
            '{app}'     => e(config('app.name')),
            '{contact}' => e(config('app.contact') ?: config('app.owner.email') ?: config('mail.from.address')),
        ]);
    }

    /** A document anyone can read, in their language (else in English) */
    private function public(string $title, string $path, array $fill = []): Response
    {
        return inertia('PublicDocument', [
            'title'   => $title,
            'content' => strtr(File::get($this->localized($path)), $fill),
        ]);
    }

    /** The file under resources/views for the current language, else the English one */
    private function localized(string $path): string
    {
        $file = resource_path('views/' . str_replace('{locale}', app()->getLocale(), $path));

        return File::exists($file) ? $file : resource_path('views/' . str_replace('{locale}', 'en', $path));
    }

    /** A role's questions, in the current language (else in English) */
    private function faqOf(string $role): array
    {
        return json_decode(File::get($this->localized("guides/{locale}/FAQ.{$role}.json")), true);
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
