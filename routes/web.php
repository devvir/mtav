<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\Manage;
use App\Http\Controllers\Member;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Platform;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Settings\AvatarController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\ResolveProject;
use Illuminate\Support\Facades\Route;

/**
 * The routes (see documentation/technical/architecture.md):
 *  - a project's pages, /projects/{project}/…          the same for everyone
 *  - admin tools, /projects/{project}/manage/…         what admins do on them, and the
 *    few pages only admins have
 *  - the platform console, /admin/…                    superadmins only
 */

Route::get('ping', fn () => 'pong');

/** Installing the app on a device */
Route::get('manifest.webmanifest', ManifestController::class)->name('manifest');

/**
 * Accounts
 */
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,10');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,5')
        ->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');

    Route::get('auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('google.callback');
});

/** The landing page for guests; everyone else goes on to their projects */
Route::get('/', HomeController::class)->name('home');
/** For anyone: the member manual (to share, and to read before joining), and privacy */
Route::get('manual', [DocumentationController::class, 'manual'])->name('manual');
Route::get('privacy', [DocumentationController::class, 'privacy'])->name('privacy');

/** The language picked, before signing in too (it then becomes the account's) */
Route::put('language', LanguageController::class)->middleware('throttle:20,1')->name('language');

/** Invitation links work for guests and signed-in people alike */
Route::get('invitations/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('invitations/{token}', [InvitationController::class, 'accept'])
    ->middleware('throttle:10,1')
    ->name('invitation.accept');

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('verify-email', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,10')
        ->name('verification.send');
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::get('documentation/faq', [DocumentationController::class, 'faq'])->name('documentation.faq');
    Route::get('documentation/guide', [DocumentationController::class, 'guide'])->name('documentation.guide');
});

Route::middleware(['auth', 'verified'])->group(function () {
    /** Settings */
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('settings/avatar', [AvatarController::class, 'update'])->name('avatar.update');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    /** Notifications */
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('notifications/{notification}/read', [NotificationController::class, 'unread'])->name('notifications.unread');

    /** One's projects; starting one */
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::post('my-invitations/{invitation}', [InvitationController::class, 'respond'])->name('invitations.respond');

    /**
     * The member app
     */
    Route::prefix('projects/{project}')
        ->middleware(ResolveProject::class)
        ->scopeBindings()
        ->group(function () {
            Route::get('/', Member\DashboardController::class)->name('projects.show');

            Route::get('people', [Member\PeopleController::class, 'index'])->name('people.index');
            Route::get('people/{member}', [Member\PeopleController::class, 'show'])->name('people.show');
            Route::delete('membership', [Member\PeopleController::class, 'leave'])->name('membership.leave');

            Route::get('families', [Member\FamilyController::class, 'index'])->name('families.index');
            Route::get('families/{family}', [Member\FamilyController::class, 'show'])->name('families.show');

            /** Members invite people into their own family */
            Route::get('invitations', [Member\InvitationController::class, 'index'])->name('invitations.index');
            Route::post('invitations', [Member\InvitationController::class, 'store'])
                ->middleware('throttle:30,1')
                ->name('invitations.store');
            Route::delete('invitations/{invitation}', [Member\InvitationController::class, 'destroy'])
                ->name('invitations.destroy');

            Route::get('units', [Member\UnitController::class, 'index'])->name('units.index');
            Route::get('units/{unit}', [Member\UnitController::class, 'show'])->name('units.show');

            Route::get('events', [Member\EventController::class, 'index'])->name('events.index');
            Route::get('events/{event}', [Member\EventController::class, 'show'])->name('events.show');
            Route::put('events/{event}/rsvp', [Member\EventController::class, 'rsvp'])->name('events.rsvp');

            Route::resource('media', Member\MediaController::class)->except('create', 'edit')->parameters(['media' => 'media']);
            Route::get('media/{media}/file', [Member\MediaFileController::class, 'file'])->name('media.file');
            Route::get('media/{media}/thumbnail', [Member\MediaFileController::class, 'thumbnail'])->name('media.thumbnail');

            Route::get('lottery', [Member\LotteryController::class, 'show'])->name('lottery.show');
            Route::put('lottery/preferences', [Member\LotteryController::class, 'rank'])->name('lottery.preferences');

            /**
             * The project admin area
             */
            Route::prefix('manage')->name('manage.')->middleware('can:update,project')->group(function () {
                Route::get('/', Manage\OverviewController::class)->name('overview');

                Route::get('settings', [Manage\SettingsController::class, 'edit'])->name('settings.edit');
                Route::put('settings', [Manage\SettingsController::class, 'update'])->name('settings.update');
                Route::delete('settings', [Manage\SettingsController::class, 'destroy'])->name('settings.destroy');
                // Each request tells every superadmin: a few a day are plenty
                Route::post('approval', [Manage\SettingsController::class, 'requestApproval'])
                    ->middleware('throttle:3,60')
                    ->name('approval.request');

                Route::put('people/{member}', [Manage\PeopleController::class, 'update'])->name('people.update');
                Route::delete('people/{member}', [Manage\PeopleController::class, 'destroy'])->name('people.destroy');

                Route::get('invitations', [Manage\InvitationController::class, 'index'])->name('invitations.index');
                Route::post('invitations', [Manage\InvitationController::class, 'store'])
                    ->middleware('throttle:60,1')
                    ->name('invitations.store');
                Route::delete('invitations/{invitation}', [Manage\InvitationController::class, 'destroy'])
                    ->name('invitations.destroy');

                Route::resource('families', Manage\FamilyController::class)->only('store', 'update', 'destroy');
                Route::resource('unit_types', Manage\UnitTypeController::class)
                    ->only('store', 'update', 'destroy')
                    ->parameters(['unit_types' => 'unitType']);
                Route::resource('units', Manage\UnitController::class)->only('store', 'update', 'destroy');
                Route::put('plan', [Manage\PlanController::class, 'update'])->name('plan.update');
                Route::post('plan/editor', [Manage\PlanController::class, 'lock'])->name('plan.lock');
                Route::delete('plan/editor', [Manage\PlanController::class, 'unlock'])->name('plan.unlock');
                Route::resource('events', Manage\EventController::class)->only('store', 'update', 'destroy');

                Route::put('lottery', [Manage\LotteryController::class, 'schedule'])->name('lottery.schedule');
                Route::post('lottery/runs', [Manage\LotteryController::class, 'run'])->name('lottery.run');
                Route::post('lottery/runs/{lotteryRun}/invalidation', [Manage\LotteryController::class, 'invalidate'])
                    ->name('lottery.invalidate');
            });
        });

    /**
     * The platform console
     */
    Route::prefix('admin')->name('admin.')->middleware(EnsureSuperadmin::class)->group(function () {
        Route::get('/', [Platform\ProjectController::class, 'index'])->name('projects.index');
        Route::put('projects/{project}/approval', [Platform\ProjectController::class, 'approve'])->name('projects.approve');
        Route::put('projects/{project}/restoration', [Platform\ProjectController::class, 'restore'])
            ->name('projects.restore')->withTrashed();
        Route::delete('projects/{project}', [Platform\ProjectController::class, 'destroy'])
            ->name('projects.destroy')->withTrashed();
        Route::get('users', [Platform\UserController::class, 'index'])->name('users.index');
        Route::put('users/{user}/superadmin', [Platform\UserController::class, 'superadmin'])->name('users.superadmin');
        Route::get('superadmins', [Platform\UserController::class, 'superadmins'])->name('superadmins.index');
        Route::post('superadmins', [Platform\UserController::class, 'grant'])->name('superadmins.store');
        Route::inertia('lab', 'Platform/Lab')->name('lab');
        Route::get('settings', [Platform\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Platform\SettingsController::class, 'update'])->name('settings.update');

        Route::middleware('can:deploy')->prefix('deployments')->name('deployments.')->group(function () {
            Route::get('/', [Platform\DeploymentController::class, 'index'])->name('index');
            Route::middleware('throttle:6,1')->group(function () {
                Route::post('dev', [Platform\DeploymentController::class, 'dev'])->name('dev');
                Route::post('production', [Platform\DeploymentController::class, 'production'])->name('production');
                Route::post('rollback', [Platform\DeploymentController::class, 'rollback'])->name('rollback');
            });
        });
    });
});

/**
 * An address that leads nowhere: answered by the app, signed in or not, so the error page
 * has everything every page has (the language, the person, the routes)
 */
Route::fallback(fn () => abort(404));
