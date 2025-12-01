# Pest Browser Testing with Docker

**See Also**: `PHILOSOPHY.md` for general testing patterns with universe fixture.

## Quick Reference

### Architecture
- **PHP Test Runner (Pest)** - Runs tests
- **Playwright Node.js Server** - Controls browsers via WebSocket
- **Built-in HTTP Server (AMP)** - Pure PHP HTTP server (no nginx/Apache needed)

### Setup Decision: Separate Playwright Container

We use separate containers (php-fpm + playwright) because:
- PHP container stays lean
- Can use Alpine for PHP
- Playwright reusable across projects
- Already have multi-container setup (php-fpm + nginx + mysql)

### Docker Configuration

**docker-compose.yml** (playwright service):
```yaml
playwright:
  image: mcr.microsoft.com/playwright:v1.48.2-noble
  command: >
    sh -c "npm install -g playwright@1.54.1 &&
           npx playwright run-server --host 0.0.0.0 --port 3000"
  ports:
    - "3000:3000"
  networks:
    - app_network
```

**tests/Pest.php** (configure connection):
```php
if ($playwrightHost = getenv('PLAYWRIGHT_HOST')) {
    \Pest\Browser\Playwright\Servers\AlreadyStartedPlaywrightServer::persist(
        $playwrightHost,
        (int) (getenv('PLAYWRIGHT_PORT') ?: 3000)
    );
}
```

**.env.testing**:
```env
PLAYWRIGHT_HOST=playwright
PLAYWRIGHT_PORT=3000
```

### Running Tests

```bash
# All browser tests
mtav pest tests/Browser/

# With visible browser (debug)
mtav pest tests/Browser/ --debug

# Parallel execution
mtav pest tests/Browser/ --parallel
```

## Request Flow

```
Test Code (PHP)
  ↓ visit('/login')
Pest Browser Plugin
  ↓ WebSocket (ws://playwright:3000)
Playwright Server (Node.js)
  ↓ Chrome DevTools Protocol
Browser (Chromium)
  ↓ HTTP (http://127.0.0.1:random-port/)
AMP HTTP Server (PHP - built-in)
  ↓ Kernel::handle($request)
Laravel Application
```

**Key Insight**: The HTTP server is embedded in Pest, runs in same PHP process. No external web server needed.

## Network Requirements

- Both containers on same Docker network
- Playwright container exposes port 3000
- PHP container can resolve `playwright` hostname
- Browser can reach PHP container's dynamic HTTP port

## Common Patterns

### Basic Test
```php
test('can login', function () {
    $user = User::factory()->create([
        'email' => 'test@test.com',
        'password' => bcrypt('password'),
    ]);

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('Log In')
        ->assertPathIs('/dashboard');

    // Laravel assertions still work!
    $this->assertAuthenticatedAs($user);
});
```

### With Laravel Fakes
```php
test('password reset sends email', function () {
    Mail::fake(); // Native Laravel fake

    visit('/forgot-password')
        ->type('email', 'user@test.com')
        ->press('Send Reset Link');

    Mail::assertSent(ResetPasswordNotification::class);
    $this->assertDatabaseHas('password_reset_tokens', [...]);
});
```

### Accessibility Testing
```php
test('login page has no accessibility issues', function () {
    visit('/login')->assertNoAccessibilityIssues();
});
```

### Screenshots
```php
test('homepage', function () {
    visit('/')->screenshot('homepage');
});
```

## Why Pest Over Cypress

**For Laravel apps, Pest Browser Testing is superior because**:

1. **Native Laravel Integration**: Direct access to `Mail::fake()`, `Event::fake()`, `User::factory()`, `assertAuthenticated()`, database queries
2. **Same Language**: Tests in PHP, same as app - no context switching
3. **Unified Runner**: `mtav pest` runs ALL tests (unit, feature, browser)
4. **Less Friction**: No network interception, no cookie inspection, no complex mocking

**Cypress requires**:
- Testing through HTTP layer (can't use Laravel fakes)
- Network interception for email/events (brittle)
- Cookie inspection for auth (indirect)
- Separate test runner and mental model

**User Experience**: "I asked copilot to write Cypress tests and could not make them pass even though features work" - tests were wrong, not the app. This doesn't happen with Pest.

## Configuration

**Browser options** (tests/Pest.php):
```php
pest()->browser()
    ->inChrome()           // or inFirefox(), inSafari()
    ->timeout(10000)       // 10 seconds
    ->headed();            // Show browser (omit for headless)
```

**Per-test device emulation**:
```php
test('mobile', function () {
    visit('/')->on()->iPhone14Pro();
});
```

## Troubleshooting

**"Could not connect to Playwright server"**:
- Check playwright container running: `docker compose ps playwright`
- Verify same network in docker-compose.yml
- Ensure `AlreadyStartedPlaywrightServer::persist()` called

**"Browser cannot reach application"**:
- Both containers must be on same Docker network
- Use service names for DNS (php-fpm, playwright)

**Screenshots not saved**:
```bash
mkdir -p tests/Browser/Screenshots
echo "/tests/Browser/Screenshots" >> .gitignore
```

## CI/CD Notes

- Run in headless mode (default)
- Use parallel execution: `mtav pest --parallel`
- Database transactions ensure clean state between tests
- Capture screenshots on failure: `->screenshot('failure-state')`

---

**See Full Architecture Details**: Original detailed documentation archived but available if needed. This quick reference covers 95% of use cases.

*Last updated: 2 December 2025*
