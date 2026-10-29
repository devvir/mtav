<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * What superadmins set for the whole platform: how long a lottery takes, as people see it
 */
class SettingsController extends Controller
{
    /** The longest a lottery may be made to take, in seconds */
    public const MAX_LOTTERY_SECONDS = 300;

    public function edit(): Response
    {
        return inertia('Platform/Settings', [
            'settings' => [
                'lottery_seconds' => PlatformSetting::value(PlatformSetting::LOTTERY_SECONDS),
            ],
            'maxLotterySeconds' => self::MAX_LOTTERY_SECONDS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lottery_seconds' => ['required', 'integer', 'between:0,' . self::MAX_LOTTERY_SECONDS],
        ]);

        PlatformSetting::put(PlatformSetting::LOTTERY_SECONDS, $data['lottery_seconds']);

        return back()->with('success', __('flash.platform_settings_saved'));
    }
}
