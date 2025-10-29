# Testing Knowledge Base

**Purpose**: Persistent guidelines and principles for test development. This document captures our evolving alignment on testing practices, code quality standards, and collaboration rules.

**Last Updated**: 2025-10-29

---

## Philosophy: Tests Are An Application

### The Foundation Principle

A test suite is its own application where:
- **Input**: The code under test
- **Rules**: The business rules that must be asserted
- **Output**: Verification that the application behaves correctly

**CRITICAL**: A solid foundation is essential. You cannot build a quality application—or test suite—without proper architecture, tools, and structure.

### The Toolbox Metaphor

**"If all you have is a hammer, everything looks like a nail."**

We don't write tests blindly. We don't force inadequate patterns onto problems. Instead, we build a comprehensive toolbox FIRST, then apply the right tool to each problem.

**Anti-patterns we avoid**:
- ❌ Repeating the same setup code across dozens of tests
- ❌ Tests with dozens of lines doing simple things
- ❌ Convoluted tests no human can understand
- ❌ Forcing one pattern to solve all problems

**Our approach**:
- ✅ Build tools that eliminate repetition
- ✅ Abstract complexity into reusable helpers
- ✅ Keep tests short, clear, and focused
- ✅ Expand the toolbox when we encounter new patterns

### Tools We've Built

Our current toolbox includes:

1. **Fixture (`universe.sql`)**: Consistent, predictable test data
   - Eliminates ARRANGE step in most tests
   - Saves thousands of lines of repetitive setup code
   - Documents common scenarios we need to test

2. **Database Transactions**: Efficient test isolation
   - Universe loaded once, not before every test
   - Changes automatically roll back
   - Saves minutes in every test run

3. **Route Helpers**: Configuration-independent testing
   - Tests don't break when URIs change
   - Uses route names, not hardcoded paths

4. **Environment Fidelity**: Test environment mirrors production
   - No arbitrary config overrides
   - Tests run in realistic conditions

5. **Project Context Helpers**: Domain-specific utilities
   - Clean API for core business concepts
   - Global automatic cleanup between tests

6. **Naming Guidelines**: Tests as documentation
   - Reads like English
   - Conveys exactly what's being tested

7. **Clear Structure**: Organized, supervised helper system
   - Predictable locations for new code
   - Everything reviewed and approved

### Hard Rules for Test Quality

**STOP and discuss if**:

1. **A test has >4 lines** (excluding comments)
   - Either it's doing too much (split into multiple tests)
   - Or we need new tools to abstract the complexity

2. **A test has >2 assertions** (ideally exactly 1)
   - Either it's testing multiple concerns (split it)
   - Or we need better helpers to simplify

3. **You're about to repeat yourself**
   - Don't write the same pattern twice
   - Build a tool instead

**Why these limits?**
- Forces us to build proper abstractions
- Keeps tests readable and maintainable
- Prevents technical debt in the test suite

### Review Process

**Everything is reviewed before commitment.**

This is a final university project for an Engineering degree. Code quality matters:
- Tests must work correctly
- Tests must be understandable by humans
- Tests must be maintainable and improvable

**Workflow**:
1. Small chunks of tests are proposed
2. We iterate until they look great
3. We add tools, helpers, or fixture data as needed
4. Only when it's right do we move on

**No blind acceptance.** No "good enough for now." We build it right or we don't build it.

---

## Test Status Flags

### Skipping and Marking Tests

When tests cannot run (due to missing features, bugs, or uncertainty), use consistent flags for easy searching and management.

**Format**: Use `->skip('PREFIX: description')`

### Flag Types

**1. Feature Not Implemented**
```php
it('allows member in inactive project', function () {
    // test code
})->skip('Feature not implemented: inactive project access');
```
- **When**: Application feature doesn't exist yet
- **Search**: `grep "Feature not implemented"`
- **Action**: Implement feature, then remove skip

**2. Known Bug**
```php
it('redirects to correct page', function () {
    // test code
})->skip('Bug: redirect loops when project is deleted');
```
- **When**: Test is correct, but application has a bug
- **Search**: `grep "Bug:"`
- **Action**: Fix bug, then remove skip

**3. Needs Investigation**
```php
it('calculates total correctly', function () {
    // test code
})->skip('Investigate: unclear if test or app logic is wrong');
```
- **When**: Uncertain whether test expectations or code behavior is incorrect
- **Search**: `grep "Investigate:"`
- **Action**: Research, determine root cause, then fix and unskip

### Using `.todo()`

Reserve `->todo()` for **tests themselves** that are incomplete:

```php
it('validates complex edge case', function () {
    // TODO: need to figure out how to set up this scenario
})->todo('Test implementation in progress');
```

**Not for**: Application features in progress (use `->skip('Feature not implemented: ...')` instead)

---

## Core Principles

### Production Code Integrity

**RULE**: Production code should NOT be modified to accommodate tests unless there is a very good reason.

- Tests are meant to cover what exists or is planned
- If a test reveals a bug, present it for review before fixing
- User decides if reasons to modify production code are "good enough"
- Tests should work with the application as it is, not as we wish it were

**Example**: When tests revealed an SQL bug (ambiguous 'active' column), we didn't immediately fix it—we identified it for user review.

### Test Environment Fidelity

**RULE**: Test environment should match production as closely as possible.

- Don't override environment variables in `phpunit.xml` without compelling reason
- Use same hashing rounds, same configuration, same behavior
- If tests need different config, question whether the test approach is wrong

**Example**: Removed `BCRYPT_ROUNDS=4` override from `phpunit.xml` because it created artificial differences from production.

### Supervision and Control

**RULE**: User maintains supervision over test helper code and structure.

- All test helpers go in supervised locations (e.g., `tests/_helpers/`)
- User reviews and approves patterns before widespread adoption
- No "black box" test utilities that hide complexity

### Test File Documentation

**GUIDELINE**: Group strongly related tests in single files and maintain clear documentation.

Each test file should have:

1. **File-level comment** (2-3 lines) describing the scope
2. **README entry** in the test directory listing all tests

**Example file header:**
```php
<?php

/**
 * Tests for the login page behavior - guest access, authenticated user redirects,
 * and edge cases like deleted/inactive projects and users.
 */

use App\Models\User;

describe('Login Page', function () {
    // tests...
});
```

**Example README entry:**
```markdown
### LoginPage.php
Tests for the login page behavior - guest access, authenticated user redirects,
and edge cases like deleted/inactive projects and users.

**Tests:**
- ✅ can be rendered by guests
- ✅ redirects admin with one project to dashboard
- ⏭️ returns 403 for admin with no projects
...

**Status:** 3/16 passing, 13 skipped (feature not implemented or bugs)
```

**Benefits:**
- Easy to find relevant tests when working on features
- README shows current coverage at a glance
- Minimal maintenance (test names document themselves)
- Clear scope prevents files from becoming grab-bags

---

## Test Structure Preferences

### Ideal Test Anatomy

```php
it('redirects admin with one project to dashboard', function () {
    // Admin #2 manages only Project #1
    $response = $this->actingAs(User::find(2))
        ->getRoute('login');

    $response->assertStatus(302);
    $response->assertRedirect(route('home'));
});
```

**Characteristics**:
- **Short**: Ideally 2 lines (one act, one assert)
- **Minimal Arrange**: Reuse fixtured universe when possible
- **Readable Title**: Reads like English, includes verb (it/test)
- **Meaningful Comments Only**: Add information that clarifies intent
- **Fluent Formatting**: One method call per line for readability

### Fluent API Formatting

**RULE**: Format fluent method chains with one call per line for readability.

```php
// ✅ GOOD - Reads like English: "acting as user #3, and following redirects, get the login page"
$response = $this->actingAs(User::find(3))
    ->followingRedirects()
    ->getRoute('login');

// ❌ BAD - Hard to parse, hides the sequence of steps
$admin = User::find(3);
$response = $this->actingAs($admin)->followingRedirects()->get('/login');
```

**Why**: The formatted version exposes the nature of the action parts and the sequence of steps. Even non-technical people can read it easily.

**Benefits**:
- Does not separate a single action into multiple statements with intermediate variables
- Still separates "sub-steps" into chunks (one per line)
- Makes the sequence of operations explicit and scannable

### Route Names, Not URIs

**RULE**: Always use route names, never hardcoded URIs.

**Rationale**: Routes can change. Today it's `/login`, tomorrow it may be `/entrar` or `/authenticate` or `/`.

```php
// ✅ GOOD - Uses route name via chainable method
$response = $this->getRoute('login');

// ❌ BAD - Hardcoded URI will break if route changes
$response = $this->get('/login');
```

### Creating Custom Chainable Methods for Pest

**Pattern**: Add protected methods to `tests/TestCase.php` that return `TestResponse`.

This allows you to create domain-specific test helpers that chain perfectly with Laravel's fluent testing API.

**Example Implementation** (in `tests/TestCase.php`):
```php
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * Visit the given route and return the response.
     */
    protected function getRoute(string $name, array $parameters = []): TestResponse
    {
        return $this->get(route($name, $parameters));
    }

    /**
     * POST to the given route and return the response.
     */
    protected function postRoute(string $name, array $data = [], array $parameters = []): TestResponse
    {
        return $this->post(route($name, $parameters), $data);
    }

    /**
     * Alias for postRoute() that reads more naturally in English.
     * You're posting data TO a route, not "routing" the data.
     */
    protected function postToRoute(string $name, array $data = [], array $parameters = []): TestResponse
    {
        return $this->postRoute($name, $data, $parameters);
    }
}
```

**Usage in Tests** - Chains beautifully with existing methods:
```php
// Simple usage
$response = $this->getRoute('login');

// Chains with actingAs
$response = $this->actingAs($user)
    ->getRoute('login');

// Chains with actingAs + followingRedirects
$response = $this->actingAs($user)
    ->followingRedirects()
    ->getRoute('login');

// With route parameters
$response = $this->actingAs($admin)
    ->getRoute('projects.show', ['project' => 1]);

// POST with data - using postToRoute alias for better readability
$response = $this->actingAs($user)
    ->postToRoute('login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);
```

**Why This Works**:
- `TestCase` is extended by all Pest tests automatically
- Protected methods are available via `$this` in test closures
- Returning `TestResponse` allows further chaining with assertion methods
- Integrates seamlessly with Laravel's testing helpers (`actingAs`, `followingRedirects`, etc.)

**Benefits**:
- Route names instead of URIs (config-independent)
- Shorter, more readable test code
- Consistent API across all tests
- Easy to add more helpers as patterns emerge

**Available Route Helpers** (all return `TestResponse`):
- `getRoute(string $routeName, array $parameters = [])`
- `postRoute(string $routeName, array $data = [], array $parameters = [])` or `postToRoute()` (alias)
- `putRoute(string $routeName, array $data = [], array $parameters = [])` or `putToRoute()` (alias)
- `patchRoute(string $routeName, array $data = [], array $parameters = [])` or `patchToRoute()` (alias)
- `deleteRoute(string $routeName, array $parameters = [])`

**Note on Aliases**: The `*ToRoute()` aliases (postToRoute, putToRoute, patchToRoute) read more naturally in English since you're sending data TO a route, not "routing" the data. Use whichever reads better in context.

### Comment Guidelines

**✅ GOOD Comments** - Add context not obvious from code:
```php
// Admin #3 manages Projects #2 and #3
$admin = User::find(3);
```
This explains *why* we picked User #3—it makes the test's logic explicit.

**❌ BAD Comments** - Restate what code already says:
```php
// Should successfully load a page after redirect chain completes
$response->assertStatus(200);
```
This adds nothing; `assertStatus(200)` already says "page loaded successfully."

### Docblock Guidelines

**GUIDELINE**: Since PHP has type hints, skip redundant `@param` and `@return` tags.

Type hints are the source of truth. Docblocks should add context, not repeat what the signature already declares.

Keep the familiar multi-line format for visual recognition, but keep content minimal.

**✅ GOOD - Clean, minimal docblocks with familiar format**:
```php
/**
 * Visit the given route and return the response.
 */
protected function getRoute(string $name, array $parameters = []): TestResponse
{
    return $this->get(route($name, $parameters));
}

/**
 * Alias for postRoute() that reads more naturally in English.
 */
protected function postToRoute(string $name, array $data = [], array $parameters = []): TestResponse
{
    return $this->postRoute($name, $data, $parameters);
}
```

**❌ BAD - Verbose, redundant docblocks**:
```php
/**
 * Visit the given route and return the response.
 *
 * @param string $name Route name
 * @param array $parameters Route parameters
 * @return TestResponse The test response
 */
protected function getRoute(string $name, array $parameters = []): TestResponse
{
    return $this->get(route($name, $parameters));
}
```

**Exception**: When types are ambiguous (mixed, array of specific type, union types), add clarifying documentation:
```php
/**
 * Load fixture data.
 *
 * @param array<string, mixed> $data Configuration data with 'users', 'projects', etc.
 */
protected function loadFixture(array $data): void
```

### Consistency is CRUCIAL

**RULE**: Pick one pattern and use it consistently throughout tests.

**Why**: Tests must be "easily readable and understood by humans."

**Example**: Use `actingAs()` consistently when performing single actions as a user:
```php
// ✅ GOOD - Consistent pattern
$admin = User::find(2);
$this->actingAs($admin)->get('/login');

// ❌ BAD - Mixed patterns confuse readers
Auth::loginUsingId(2);
$this->get('/login');
```

---

## Fixture-Based Testing

### Universe Fixture Strategy

We use `universe.sql` as a known, stable fixture with predictable data:

- **5 projects** with specific relationships
- **49 users** (mix of admins with different project assignments)
- **21 families**, **21 units**, **12 unit types**
- All users share password: `$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e`

### Fixture Loading Strategy

**RULE**: Load fixtures ONCE per test suite, not before each test.

**Why**: Most tests can safely share fixture data when using database transactions.

**Implementation**:
```php
// In Pest.php

// Authentication tests use DatabaseTransactions
pest()->use(Illuminate\Foundation\Testing\DatabaseTransactions::class)
    ->in('Authentication');

// Load universe once for all Authentication tests
beforeAll(function () {
    loadUniverse();
})->in('Authentication');
```

**How DatabaseTransactions Works**:
1. Starts a transaction before each test
2. Test runs and can read/write the database freely
3. Transaction rolls back after test completes
4. Next test sees the original universe data

**Benefits**:
- ✅ Universe loaded once, not before every test
- ✅ Tests are isolated (changes roll back automatically)
- ✅ Fast (no database wipes or re-inserts)
- ✅ Tests can modify data without polluting other tests

**RefreshDatabase vs DatabaseTransactions**:
- **RefreshDatabase**: Wipes entire DB before each test (use for old test suite in `_Feature`, `_Unit`)
- **DatabaseTransactions**: Wraps each test in a transaction that rolls back (use for new Authentication tests)

**When to reload manually**:
```php
// Rarely needed - only if DatabaseTransactions doesn't work for your use case
afterEach(function () {
    loadUniverse(); // Restore clean fixture
});
```

### Known Test Users

Reference these in tests for predictable scenarios:

- **User #1**: Admin with NO project assignments (403 cases)
- **User #2**: Admin managing ONLY Project #1 (single-project flows)
- **User #3**: Admin managing Projects #2 and #3 (multi-project flows)
- **User #4**: Admin managing Projects #2, #3, and #4 (multi-project flows)

### Fixture Philosophy

**Prefer fixture data over factories in tests** when possible:
- Faster (no object creation overhead)
- More predictable (same data every time)
- Better assertions (can reference known project names, IDs)

**Example**:
```php
// ✅ GOOD - Uses known fixture data
$admin = User::find(3); // We know #3 manages Projects #2 and #3

// ❌ LESS IDEAL - Creates new data
$admin = User::factory()->create();
// Now we have to query to know what projects they manage
```

---

## Test Helpers Architecture

### Location: `tests/_helpers/`

**RULE**: All new, supervised test helpers live in `tests/_helpers/`.

**Old Location**: `tests/Helpers/` contains helpers from the old test suite. Do NOT add new helpers there.

**Organization**: Helpers in `_helpers/` are organized by concern, well-structured, and reviewed. The old test suite will gradually be replaced, but new work goes only into the new supervised structure.

### Current Helpers

#### `projectContext.php`
Manages session-based project context for multi-project testing:

```php
setCurrentProject(int|Project $project)  // Set session project
resetCurrentProject()                     // Clear session project
getCurrentProject(): ?Project             // Get current project from session
```

**Global Cleanup**: `afterEach()` hook in `Pest.php` calls `resetCurrentProject()` to prevent test pollution.

#### `routing.php`
Provides clean methods for testing named routes:

```php
$this->getRoute('login');                          // GET route
$this->postRoute('login', ['email' => '...']);    // POST route with data
$this->getRoute('projects.show', ['project' => 1]); // Route with parameters
```

#### `fixtures.php`
Fixture loading utilities:

```php
loadUniverse()           // Load universe.sql fixture (called once per suite)
```

See [Route Names, Not URIs](#route-names-not-uris) section for full API and examples.

### Helper Design Principles

1. **Single Responsibility**: Each helper file covers one concern
2. **Type Safety**: Use proper type hints and return types
3. **Discoverability**: Function names should be self-documenting
4. **Global Cleanup**: Register cleanup hooks where needed

---

## Authentication Testing Patterns

### Using `actingAs()` for Single Actions

```php
$user = User::find(2);
$this->actingAs($user)->get('/login');
```

**When**: Performing a single request as a user.

### Following Redirects

```php
$response = $this->actingAs($admin)
    ->followingRedirects()
    ->get('/login');

$response->assertStatus(200); // Final destination loaded
```

**When**: Testing multi-step redirect chains (e.g., middleware cascades).

### Password Authentication

All fixture users share the same valid Laravel-generated hash:
```
$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e
```

This hash was generated by Laravel with 12 rounds (production config). No need to generate hashes dynamically in tests.

---

## Debugging and Problem-Solving

### Configuration Issues

**Symptom**: "Could not verify the hashed value's configuration"

**Likely Cause**: Mismatch between hash algorithm/rounds in tests vs production.

**Solution**: Check `phpunit.xml` for unnecessary environment overrides.

### SQL Errors in Tests

**Principle**: If tests reveal application bugs (not test bugs), present for review.

**Example**: Tests revealed ambiguous SQL column error in `ProjectController`. This is a production bug discovered by tests—not something to hide or work around.

### Test Failures

**Debugging Order**:
1. Is the test correctly written?
2. Is the fixture data correct?
3. Is the application code buggy?
4. Is the test environment configured correctly?

**Don't assume**: Tests can be wrong, fixtures can be stale, config can drift.

---

## Code Quality Standards

### Script Setup + TypeScript (Vue)

Always use `<script setup lang="ts">` in Vue files.

### Semicolons

Always end lines with semicolons in any JavaScript file (including Vue).

### Test Readability

Tests are documentation. They should read like specifications:

```php
it('redirects admin with one project to dashboard', function () { ... });
it('returns 403 for admin with no projects', function () { ... });
```

Not:
```php
test_admin_redirect() { ... }
```

---

## Iterative Alignment

This document is **living documentation**. As we discover new patterns, encounter edge cases, and refine our practices, we update this file.

**Goal**: Build shared understanding over time, not restart from scratch every session.

**Process**:
1. Establish principle through discussion
2. Document it here with rationale and examples
3. Reference this document in future work
4. Refine as we learn

---

## Quick Reference

### Before Writing a Test

- [ ] Can I use fixture data instead of factories?
- [ ] Is my test title readable English with a verb?
- [ ] Am I using patterns consistently with other tests?
- [ ] Do my comments add meaningful context?
- [ ] Is my test as short as possible (ideally 2 lines)?

### Before Modifying Production Code

- [ ] Is this change necessary to fix a real bug?
- [ ] Or am I just making tests pass artificially?
- [ ] Have I presented the issue for review?

### Before Adding Test Helpers

- [ ] Does this belong in `tests/_helpers/`?
- [ ] Is the API clean and self-documenting?
- [ ] Is there a cleanup strategy if needed?
- [ ] Am I following existing helper patterns?

---

**Remember**: We're building a test suite that will serve developers for years. Quality, clarity, and consistency matter more than speed.
