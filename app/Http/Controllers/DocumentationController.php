<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Response;

class DocumentationController extends Controller
{
    /**
     * Display FAQ page based on user role.
     */
    public function faq(Request $request, ?string $role = null): Response
    {
        $user = $request->user();

        // Determine role: use provided role, or detect from authenticated user
        if (! $role && $user) {
            $role = $user->is_admin ? 'admin' : 'member';
        } elseif (! $role) {
            $role = 'member'; // Default to member for guests
        }

        $component = $role === 'admin' ? 'Documentation/AdminFaq' : 'Documentation/MemberFaq';

        return inertia($component, [
            'userRole' => $role,
        ]);
    }    /**
     * Display comprehensive guide based on user role.
     */
    public function guide(Request $request, ?string $role = null): Response
    {
        $user = $request->user();
        $locale = app()->getLocale();

        // Determine role
        if (! $role && $user) {
            $role = $user->is_admin ? 'admin' : 'member';
        } elseif (! $role) {
            $role = 'member';
        }

        // Load markdown content
        $filename = $role === 'admin' ? 'guia-admin.md' : 'guia-miembro.md';
        if ($locale === 'en') {
            $filename = $role === 'admin' ? 'admin-guide.md' : 'member-guide.md';
        }

        $path = base_path("documentation/guides/{$locale}/{$filename}");
        $content = File::exists($path) ? File::get($path) : '';

        $component = $role === 'admin' ? 'Documentation/AdminGuide' : 'Documentation/MemberGuide';

        return inertia($component, [
            'isAuthenticated' => (bool) $user,
            'userRole' => $role,
            'content' => $content,
        ]);
    }
}
