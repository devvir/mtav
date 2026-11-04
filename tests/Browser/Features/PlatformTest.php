<?php

use App\Models\Project;
use App\Models\SupportInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

/**
 * The platform console, for superadmins: every account, and who else manages the platform.
 */

beforeEach(function () {
    $this->superadmin = aSuperadmin();
    $this->as($this->superadmin);
});

/** The console's gravest actions ask for the password once: given, they return to do it again */
function confirmPassword(object $page): object
{
    return $page->assertSee('Confirm your password')
        ->fill('password', 'password')
        ->click('[type="submit"]')
        ->assertSee('Confirmed. Now do it again.');
}

it('invites a support account by an email not in use, and takes the role back', function () {
    Notification::fake();
    aUser(['firstname' => 'Ana', 'lastname' => 'García', 'email' => 'ana@example.com']);

    // Never offered on the accounts themselves
    visit(route('admin.users.index'))
        ->assertSee('Ana García')
        ->assertDontSee('Make superadmin');

    $invite = fn ($page, $email) => $page->fill('email', $email)
        ->click('form >> text=Send the invitation')
        ->fill('[role="dialog"] input', $email)
        ->click(inDialog('Send the invitation'));

    $page = confirmPassword($invite(visit(route('admin.superadmins.index')), 'support@example.com'));
    expect(SupportInvitation::count())->toBe(0);

    $invite($page, 'support@example.com')->assertSee('Invitation sent to support@example.com.');
    expect(SupportInvitation::sole()->email)->toBe('support@example.com');

    // Someone already here keeps their own account: a support account takes another address
    $invite($page, 'ana@example.com')->assertSee(__('validation.support_email_taken'));
    expect(SupportInvitation::count())->toBe(1);

    // Asked once for a while: taking the role back goes straight on
    User::factory()->superadmin()->create(['firstname' => 'Bruno', 'lastname' => 'Pérez', 'email' => 'bruno@example.com']);
    $page = visit(route('admin.superadmins.index'))
        ->click('.row:has-text("Bruno Pérez") >> text=Remove the role')
        ->click(inDialog('Remove the role'))
        ->assertDontSee('bruno@example.com');

    expect(User::where('email', 'bruno@example.com')->sole()->isSuperadmin())->toBeFalse();
});

it('finds accounts by name or email', function () {
    aUser(['firstname' => 'Ana', 'lastname' => 'García', 'email' => 'ana@example.com']);
    aUser(['firstname' => 'Bruno', 'lastname' => 'Pérez', 'email' => 'bruno@example.com']);

    visit(route('admin.users.index'))
        ->type('[aria-label="Search by name or email"]', 'bruno@')
        ->assertSee('Bruno Pérez')
        ->assertDontSee('Ana García')
        ->assertQueryStringHas('q', 'bruno@');
});

it('pages through long lists', function () {
    User::factory()->count(45)->create();

    visit(route('admin.users.index'))
        ->click('[aria-label="Next page"]')
        ->assertQueryStringHas('page', '2')
        ->click('[aria-label="Previous page"]')
        ->assertQueryStringHas('page', '1');
});

it('previews the components in either scheme', function () {
    visit(route('admin.lab'))
        ->click('.lab-head >> text=Dark')
        ->assertScript("document.documentElement.getAttribute('data-mantine-color-scheme')", 'dark')
        ->click('.lab-head >> text=Light')
        ->assertScript("document.documentElement.getAttribute('data-mantine-color-scheme')", 'light');
});

it('leaves nothing of the states it holds still once one leaves the lab', function () {
    $held = '() => {
        const has = (list) => [...list.cssRules].some((rule) => rule.selectorText?.includes(".ps-hover") || (rule.cssRules && has(rule)));
        return [...document.styleSheets].some((sheet) => { try { return has(sheet); } catch { return false; } });
    }';

    $page = visit(route('admin.lab'))->assertPresent('.lab-head');
    expect($page->script($held))->toBeTrue();

    $page->click('.shell-nav >> a:has-text("Accounts")')->assertMissing('.lab-head');
    expect($page->script($held))->toBeFalse();

    $page->click('.shell-nav >> a:has-text("UI Lab")')->assertPresent('.lab-head');
    expect($page->script($held))->toBeTrue();
});

it('restores an archived project, or deletes it for good after its summary and its name', function () {
    $kept = aProject(['name' => 'Cooperativa Vuelta']);
    $gone = aProject(['name' => 'Cooperativa Final']);
    aMemberOf(aFamily($kept));
    aMemberOf(aFamily($gone));
    $kept->delete();
    $gone->delete();

    $page = visit(route('admin.projects.index', ['show' => 'archived']))
        ->assertSee('Cooperativa Vuelta')
        ->assertSee('Cooperativa Final');

    $page->click('.row:has-text("Cooperativa Vuelta") >> ' . button('Restore'))
        ->click(inDialog('Restore'))
        ->assertSee('Cooperativa Vuelta is back, as it was.');

    $page->click('.row:has-text("Cooperativa Final") >> ' . button('Delete for good'))
        ->assertSee('Delete Cooperativa Final for good?')
        ->assertSee('Lottery runs')
        ->assertSee('Everything in it is deleted, its files too.');

    // Not before the name is typed
    expect($page->script('[...document.querySelectorAll(\'[role="dialog"] button\')].find((b) => b.textContent.includes("Delete for good")).disabled'))->toBeTrue();

    $page->fill('[role="dialog"] input', 'Cooperativa Final')
        ->click(inDialog('Delete for good'));
    confirmPassword($page);
    expect(Project::withTrashed()->find($gone->id))->not->toBeNull();

    $page->click('.row:has-text("Cooperativa Final") >> ' . button('Delete for good'))
        ->fill('[role="dialog"] input', 'Cooperativa Final')
        ->click(inDialog('Delete for good'))
        ->assertSee('Cooperativa Final was deleted for good.')
        ->assertNoSmoke();

    expect(Project::withTrashed()->find($gone->id))->toBeNull();
    expect(Project::find($kept->id))->not->toBeNull();
});

it('names each error for those who cannot see its card, and tells them the trace opened', function () {
    [$runtime] = collect([RuntimeException::class, LogicException::class])->map(fn ($class) => App\Models\ErrorEntry::create([
        'fingerprint'   => sha1($class), 'class' => $class, 'message' => 'Could not', 'trace' => '#0 here',
        'first_seen_at' => now(), 'last_seen_at' => now(),
    ]));
    $toggle = "button[aria-controls=\"trace-{$runtime->id}\"]";

    $page = visit(route('admin.errors.index'))
        ->assertPresent('h2:has-text("RuntimeException")')
        ->assertPresent('button[aria-label="Mark RuntimeException solved"]')
        ->assertPresent('button[aria-label="Mark LogicException solved"]')
        ->assertPresent("{$toggle}[aria-expanded=\"false\"]");

    $page->click($toggle)
        ->assertPresent("{$toggle}[aria-expanded=\"true\"]")
        ->assertNoSmoke();

    $page->click('button[aria-label="Mark LogicException solved"]')
        ->assertSee('Marked solved.');
    expect(App\Models\ErrorEntry::where('class', LogicException::class)->exists())->toBeFalse();
});

it('pages a long activity log without saying how long it is', function () {
    App\Models\Activity::insert(array_fill(0, 51, ['action' => 'signed_in', 'actor' => 'Ana', 'created_at' => now()]));

    visit(route('admin.activity.index'))
        ->assertSee('1–50')
        ->assertDontSee('1–50 of')
        ->click('nav[aria-label="Pages"] a[aria-label="Next page"]')
        ->assertSee('51–')
        ->assertNoSmoke();
});

it('shows superadmins what changed, and narrows it to a project', function () {
    $project = aProject(['name' => 'Los Ceibos']);
    aUnit(aUnitType($project), ['identifier' => 'A-101']);
    aUnit(aUnitType(aProject(['name' => 'Otra'])), ['identifier' => 'Z-9']);
    $this->as(aSuperadmin());

    visit(route('admin.activity.index', ['project' => $project->id]))
        ->assertSee('Activity')
        ->assertSee('«A-101»')
        ->assertDontSee('«Z-9»')
        ->assertNoSmoke();
});

it('asks before a deploy starts dev over', function () {
    config(['delivery.deployers' => ['root@example.com'], 'delivery.github.token' => 'token']);
    $repo = 'https://api.github.com/repos/devvir/mtav/';
    Http::fake([
        $repo . 'commits/*'           => Http::response(['sha' => 'c3', 'commit' => ['message' => 'A commit', 'committer' => ['date' => '2026-10-29T10:00:00Z']]]),
        $repo . 'actions/runs*'       => Http::response(['workflow_runs' => []]),
        $repo . 'branches*'           => Http::response([['name' => 'main']]),
        $repo . 'compare/*'           => Http::response(['ahead_by' => 0]),
        $repo . 'actions/workflows/*' => Http::response(null, 204),
        $repo . 'deployments*'        => Http::response([]),
    ]);
    $dispatched = fn () => Http::recorded(fn ($request) => str_contains($request->url(), 'actions/workflows/'))->count();
    $this->as(aUser(['email' => 'root@example.com', 'is_superadmin' => true]));

    $page = visit(route('admin.deployments.index'))
        ->click(button('Deploy dev'))
        ->assertSee('Deploy main to dev?')
        ->assertSee('is replaced by the demo data')
        ->click(inDialog('Cancel'));
    expect($dispatched())->toBe(0);

    $page->click(button('Deploy dev'))
        ->click(inDialog('Deploy dev'));
    confirmPassword($page);
    expect($dispatched())->toBe(0);

    $page->click(button('Deploy dev'))
        ->click(inDialog('Deploy dev'))
        ->assertNoSmoke();
    $page->wait(0.5);
    $page->assertPathIs(route('admin.deployments.index', absolute: false));
    expect($dispatched())->toBe(1);
});

it('offers the link to choose a password where it asks for one, for an account opened with Google', function () {
    Notification::fake();
    $this->as(tap(aUser(['email' => 'g@example.com', 'is_superadmin' => true]), fn ($user) => $user->forceFill(['google_id' => 'g-1'])->save()));

    visit(route('admin.superadmins.index'))
        ->fill('email', 'someone@example.com')
        ->click('form >> text=Send the invitation')
        ->fill('[role="dialog"] input', 'someone@example.com')
        ->click(inDialog('Send the invitation'))
        ->assertSee('Confirm your password')
        ->click('Email me a link to choose a password')
        ->assertSee(__('passwords.sent'))
        ->assertNoSmoke();
});
