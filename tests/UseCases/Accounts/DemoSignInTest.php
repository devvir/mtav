<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

/**
 * Entering as a demo account with no password: on a developer's machine, dev and staging, and
 * only as an account nobody real could own.
 */

function demoSignIn($test, User $user)
{
    return $test->post(route('login.demo'), ['user' => $user->id]);
}

/** The app environment and the deployment a host would say it is */
function runningAs(string $environment, ?string $deployment): void
{
    app()->detectEnvironment(fn () => $environment);
    config(['app.deployment' => $deployment]);
}

function demoListOnLanding($test): ?array
{
    return $test->get('/')->inertiaProps('demo');
}

dataset('allowed stages', [
    'local'   => ['local', null],
    'dev'     => ['production', 'dev'],
    'staging' => ['production', 'staging'],
]);

dataset('other stages', [
    'production'                    => ['production', 'production'],
    'production on a local machine' => ['local', 'production'],
    'unknown'                       => ['production', 'somewhere'],
    'not set'                       => ['production', null],
]);

beforeEach(function () {
    // Only the testing environment skips it by itself, and these run as others
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->demo = aUser(['email' => 'ana@example.com', 'firstname' => 'Ana', 'lastname' => 'Silva']);
});

it('enters with no password where it exists', function (string $environment, ?string $deployment) {
    runningAs($environment, $deployment);

    demoSignIn($this, $this->demo)->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticatedAs($this->demo);
})->with('allowed stages');

it('lists the demo accounts where it exists', function (string $environment, ?string $deployment) {
    runningAs($environment, $deployment);

    expect(demoListOnLanding($this))->toBe([['id' => $this->demo->id, 'name' => 'Ana Silva', 'email' => 'ana@example.com']]);
})->with('allowed stages');

it('does not exist anywhere else, and sends no list', function (string $environment, ?string $deployment) {
    runningAs($environment, $deployment);

    expect(demoListOnLanding($this))->toBeNull();
    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('demo', null));

    demoSignIn($this, $this->demo)->assertNotFound();

    $this->assertGuest();
})->with('other stages');

it('takes every example domain the seeder may use', function (string $email) {
    runningAs('local', null);
    $user = aUser(['email' => $email]);

    expect(array_column(demoListOnLanding($this), 'email'))->toContain($email);
    demoSignIn($this, $user);

    $this->assertAuthenticatedAs($user);
})->with(['maria@example.org', 'pedro@example.net']);

describe('Where it exists', function () {
    beforeEach(fn () => runningAs('production', 'staging'));

    it('refuses a real account, even on a crafted request', function (string $email) {
        $real = aUser(['email' => $email]);

        expect(demoSignIn($this, $real))->toBeDenied();

        $this->assertGuest();
        expect(array_column(demoListOnLanding($this), 'email'))->not->toContain($email);
    })->with([
        'a real address'           => ['ana@gmail.com'],
        'a lookalike ending'       => ['ana@example.com.evil.org'],
        'a lookalike beginning'    => ['ana@notexample.com'],
        'a domain below a demo'    => ['ana@mail.example.com'],
        'example only in the name' => ['example.com@gmail.com'],
    ]);

    it('refuses a blocked demo account, and leaves it off the list', function () {
        $this->demo->forceFill(['blocked_at' => now()])->save();

        expect(demoSignIn($this, $this->demo))->toBeDenied();

        $this->assertGuest();
        expect(demoListOnLanding($this))->toBe([]);
    });

    it('refuses an account that does not exist, or none', function () {
        expect($this->post(route('login.demo'), ['user' => 999999]))->toBeDenied();
        expect($this->post(route('login.demo')))->toBeDenied();

        $this->assertGuest();
    });

    it('is not for someone already signed in', function () {
        $someone = aUser();

        demoSignIn($this->as($someone), $this->demo)->assertRedirect();

        $this->assertAuthenticatedAs($someone);
    });
});
