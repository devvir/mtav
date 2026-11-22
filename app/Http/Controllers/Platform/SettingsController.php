<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * What superadmins set for the whole platform: how long a lottery takes, as people see it,
 * and how long after a run every family's ranking becomes everyone's
 */
class SettingsController extends Controller
{
    /** The longest a lottery may be made to take, in seconds */
    public const MAX_LOTTERY_SECONDS = 300;

    /** The longest rankings may be kept from the project after a run, in hours (a month) */
    public const MAX_DISCLOSURE_HOURS = 720;

    public function edit(): Response
    {
        return inertia('Platform/Settings', [
            'settings' => [
                'lottery_seconds'  => PlatformSetting::value(PlatformSetting::LOTTERY_SECONDS),
                'disclosure_hours' => PlatformSetting::value(PlatformSetting::DISCLOSURE_HOURS),
            ],
            'maxLotterySeconds'  => self::MAX_LOTTERY_SECONDS,
            'maxDisclosureHours' => self::MAX_DISCLOSURE_HOURS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lottery_seconds'  => ['required', 'integer', 'between:0,' . self::MAX_LOTTERY_SECONDS],
            'disclosure_hours' => ['required', 'integer', 'between:0,' . self::MAX_DISCLOSURE_HOURS],
        ]);

        PlatformSetting::put(PlatformSetting::LOTTERY_SECONDS, $data['lottery_seconds']);
        PlatformSetting::put(PlatformSetting::DISCLOSURE_HOURS, $data['disclosure_hours']);

        return back()->with('success', __('flash.platform_settings_saved'));
    }
}
