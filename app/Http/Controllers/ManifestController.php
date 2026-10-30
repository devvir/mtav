<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * What a device needs to install the app: its name (the deployment's own), its icons and
 * colours. Installed, it opens in its own window, like any other app.
 */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $name = config('app.name');

        return response()->json([
            'name'             => $name,
            'short_name'       => $name,
            'description'      => __('Housing cooperatives, together'),
            'lang'             => str_replace('_', '-', app()->getLocale()),
            'start_url'        => '/',
            'scope'            => '/',
            'display'          => 'standalone',
            'background_color' => '#faf7f4',
            'theme_color'      => '#faf7f4',
            'icons'            => [
                ['src' => '/images/app/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/images/app/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                [
                    'src'     => '/images/app/icon-maskable-512.png',
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], options: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Type', 'application/manifest+json');
    }
}
