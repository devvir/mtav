# Session: Testing Philosophy & Clean Code Lessons

**Date**: October 30, 2025

**Context**: Deep learning session where user taught me about clean test design by showing the transformation from my original LoginPage tests to their refactored version.

---

## Session Overview

This was primarily an educational session. User completed significant solo refactoring work and returned to:
1. Update the Tests KB with recent API changes
2. Teach me what "clean code" looks like through example
3. Help me internalize their testing philosophy

The session was about learning from examples, not just following instructions.

---

## Key API Changes Documented

### Helper Location
- **Changed**: `tests/_helpers/` → `tests/Helpers/`
- **Reason**: Standard Pest convention, autoloaded by framework
- All new helpers now go in `tests/Helpers/`

### Method Renames
- `isSuperAdmin()` → `isSuperadmin()` (lowercase 'admin')
- `updateState()` → `setState()` (consistent naming)
- `getCurrentProject()` → `currentProject()` (removed 'get' prefix)
- `getCurrentProjectId()` → `currentProjectId()` (removed 'get' prefix)

### Superadmin Configuration
- Changed from ID-based to email-based
- Now uses `config('auth.superadmins')` array of email addresses
- `isSuperadmin()` checks: `in_array($this->email, config('auth.superadmins'))`

### State Management
- **Critical**: Always use `state()` and `setState()` helpers
- Don't access session directly: `session()->get('state.project')`
- Helpers provide abstraction layer in case implementation changes
- Example: `setState('project', $project)` not `session()->put('project', $project)`

### New Test Helpers
- `inertiaRoute(TestResponse $response): ?string` - Extracts loaded route from Inertia props
- `visitRoute(string $route, int|User $asUser, bool $redirects = true): TestResponse` - Visit route as user

### Fixture Loading
- Fixed to load **once per entire test run** (not per suite)
- Uses static flag in `TestCase::setUp()` to ensure single load
- All test suites share the same fixture data

### Test Suites
- Archive suites merged into single `Archive` suite in `phpunit.xml`
- Run with: `./mtav test --pest --testsuite=Archive`
- Run with: `./mtav test --pest --testsuite=Authentication`

---

## The Learning Exercise: LoginPage Transformation

### Original Version (After Joint Session)

```php
it('redirects authenticated member to home', function () {
    $response = $this->actingAs(User::find(7))
        ->followingRedirects()
        ->getRoute('login');

    expect($response)->toBeOk();
    expect($response->viewData('page')['component'])->toBe('Dashboard');
});
```

**Characteristics**:
- 6 lines per test
- Testing HTTP mechanics (status codes, view internals)
- Verbose setup with actingAs, followingRedirects
- Asks: "Did Laravel redirect correctly?"

### Refactored Version (User's Solo Work)

```php
it('redirects them to the Dashboard (if their Project is active)', function () {
    $response = $this->visitRoute('login', asUser: 7);

    expect(inertiaRoute($response))->toBe('home');
});
```

**Characteristics**:
- 2 lines per test
- Testing business outcomes (which route loaded)
- Clean abstraction with visitRoute helper
- Named arguments for clarity (`asUser:`)
- Asks: "Where does the user end up?"

### The Transformation Analysis

**What Changed**:
1. **Abstraction**: `visitRoute()` replaces `actingAs()->followingRedirects()->getRoute()`
2. **Semantic Testing**: `inertiaRoute()` replaces checking HTTP status and view internals
3. **Readability**: Named arguments make intent crystal clear
4. **Focus**: Test the outcome, not the HTTP mechanics

**Why It Matters**:
- Business rule: "Active members visiting login go to Dashboard"
- Original tests HTTP protocol compliance
- Refactored tests the business rule directly
- Tests read as specifications, not verification code

---

## Core Lessons Learned

### 1. Tests as Executable Business Rules

**Philosophy**: Tests are not just verification—they are **executable specifications**.

Well-written tests should be readable by non-programmers as statements of how the system behaves. Each test is a business rule that can be executed and verified automatically.

**Example**:
```php
describe('When visiting the login page', function () {
    describe('as Admin', function () {
        it('redirects to projects index if they manage more than one Project', function () {
            $response = $this->visitRoute('login', asUser: 3);

            expect(inertiaRoute($response))->toBe('projects.index');
        });
    });
});
```

This test IS the specification. It's executable, verifiable, and guaranteed to be up-to-date.

### 2. Ruthless Abstraction

**Rule**: If you do something twice, abstract it into a helper.

**Philosophy**: Don't tolerate repetition. Every repeated pattern is an opportunity to build a better tool.

**Example Evolution**:
```php
// Pattern appears twice
$response = $this->actingAs(User::find(7))
    ->followingRedirects()
    ->getRoute('login');

// Abstract into helper
protected function visitRoute(string $route, int|User $asUser, bool $redirects = true): TestResponse
{
    $user = is_int($asUser) ? User::find($asUser) : $asUser;
    $request = $this->actingAs($user);

    if ($redirects) {
        $request = $request->followingRedirects();
    }

    return $request->getRoute($route);
}

// Now every test is 1 line of setup
$response = $this->visitRoute('login', asUser: 7);
```

**Benefits**:
- Tests are shorter and more readable
- Changes happen in one place
- Helper name documents intent
- Named arguments make it clear

### 3. The 2-Line Ideal

**Target**: Most tests should be **2 lines** (action + assertion).

```php
it('redirects them to the Dashboard', function () {
    $response = $this->visitRoute('login', asUser: 7);

    expect(inertiaRoute($response))->toBe('home');
});
```

**Why 2 lines?**:
- Forces proper abstraction (can't hide complexity)
- Makes tests scannable at a glance
- Reveals missing tools in your DSL
- Keeps focus on what matters (the assertion)

**When you need 3+ lines**:
- Additional setup is genuinely necessary
- Testing a complex scenario
- Need intermediate assertions

**Red flag**: If most tests are 4+ lines, you're missing abstractions.

### 4. Compositional Test Naming in Pest

**Pest Feature**: Test names are built from **describe block chains** + `it()`.

```php
describe('When visiting the login page', function () {
    describe('as Guest', function () {
        it('renders the login page', function () {
            // test code
        });
    });
});
```

**Pest Output**:
```
✓ When visiting the login page › as Guest › renders the login page
```

**This reads as a complete sentence!**

**Pattern Guidelines**:
- First `describe`: Context or action being tested
- Nested `describe`: Actor or additional context
- `it()`: The expected behavior

**Benefits**:
- Tests read like Gherkin scenarios
- Non-programmers can understand test names
- Compositional structure encourages logical organization
- Test output is documentation

### 5. Nested Describe Blocks for IDE Navigation

**Practical Benefit**: Nested `describe` blocks create **collapsible sections** in your IDE.

```php
describe('When visiting the login page', function () {
    describe('as Guest', function () {
        // 2 tests here
    });

    describe('as Authenticated Member', function () {
        // 4 tests here
    });

    describe('as Admin', function () {
        // 7 tests here
    });
});
```

**In your IDE**:
- Each `describe` block can be collapsed
- Jump to "as Admin" section and collapse the rest
- Manage cognitive load—focus on one actor at a time
- Navigate large test files efficiently

**Why this matters**:
- Test files grow over time (LoginPage has 13 tests)
- Collapsing sections = easier navigation
- Logical grouping makes tests findable
- IDE shows structure in outline view

### 6. Testing Outcomes, Not Mechanics

**Principle**: Test **what users experience**, not **how the framework achieves it**.

```php
// ❌ Testing mechanics
expect($response->status())->toBe(302);
expect($response->headers->get('Location'))->toContain('home');

// ✅ Testing outcome
expect(inertiaRoute($response))->toBe('home');
```

**Why**:
- Business rule: "User ends up at home"
- Not: "Laravel sends HTTP 302 with Location header"
- Tests should survive implementation changes
- Tests document user experience, not HTTP protocol

**Building outcome helpers**:
```php
// Helper that extracts semantic meaning
function inertiaRoute(TestResponse $response): ?string
{
    return data_get($response->viewData('page'), 'props.route.name');
}
```

Now tests ask: **"Where did the user end up?"** not **"What HTTP status was returned?"**

### 7. Consistency = Readability

**Absolute Rule**: Pick a pattern and use it **everywhere**.

**Why**: Humans read by pattern recognition. Inconsistency breaks this.

**Example**: `visitRoute()` pattern used consistently across all LoginPage tests:
```php
$response = $this->visitRoute('login', asUser: 7);   // Member
$response = $this->visitRoute('login', asUser: 2);   // Admin with 1 project
$response = $this->visitRoute('login', asUser: 3);   // Admin with 2 projects
```

**Every single test** uses this pattern. You learn it once, then scan effortlessly.

**Rule of thumb**: If a new pattern emerges, **update all existing tests** to match. Don't leave mixed styles.

### 8. Building a Testing Vocabulary

**Goal**: Create a **domain-specific language (DSL)** for testing your application.

As you write tests, you build vocabulary:
- `visitRoute()` - user navigates to a route
- `inertiaRoute()` - extract which route loaded
- `currentProject()` - get the project in context
- `setState()` - configure session state
- `isSuperadmin()` - check superadmin status

**This vocabulary encodes domain knowledge**:
- Not just "make tests shorter"
- Each helper captures a concept from your domain
- Tests read like business requirements
- New developers learn the domain by reading tests

**Example**:
```php
it('redirects to Projects and resets current project if admin does not manage selected one', function () {
    setCurrentProject(1);

    $response = $this->visitRoute('login', asUser: 3);

    expect(currentProjectId())->toBeNull();
    expect(inertiaRoute($response))->toBe('projects.index');
});
```

**What you learn from this test**:
- System has concept of "current project"
- Admins can manage multiple projects
- System validates admin access to current project
- Invalid access clears selection and shows project list

This teaches you about **the business domain**, not Laravel internals.

---

## The Full LoginPage Example

Here's the complete refactored file showing all patterns in action:

```php
<?php

/**
 * Tests for the login page behavior - guest access, authenticated user redirects,
 * and edge cases like deleted/inactive projects and users.
 */

use App\Models\User;

uses()->group('Authentication');

describe('When visiting the login page', function () {
    describe('as Guest', function () {
        it('renders the login page', function () {
            $response = $this->getRoute('login');

            expect($response)->toBeOk();
        });
    });

    describe('as Authenticated Member', function () {
        it('redirects them to the Dashboard (if their Project is active)', function () {
            $response = $this->visitRoute('login', asUser: 7);

            expect(inertiaRoute($response))->toBe('home');
        });

        it('logs them out if they are not active in any Project', function () {
            $response = $this->visitRoute('login', asUser: 5);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('logs them out if their Project was deleted', function () {
            $response = $this->visitRoute('login', asUser: 52);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('redirects to Dashboard if their Project is inactive', function () {
            $response = $this->visitRoute('login', asUser: 51);

            expect(inertiaRoute($response))->toBe('home');
        });
    });

    describe('as Admin', function () {
        it('redirects to the Dashboard if they manage only one Project', function () {
            $response = $this->visitRoute('login', asUser: 2);

            expect(inertiaRoute($response))->toBe('home');
        });

        it('redirects to projects index if they manage more than one Project', function () {
            $response = $this->visitRoute('login', asUser: 3);

            expect(inertiaRoute($response))->toBe('projects.index');
        });

        it('logs out and redirect back to login if they manage no projects', function () {
            $response = $this->visitRoute('login', asUser: 1);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('redirects to the Dashboard if current project is set and managed by them', function () {
            setCurrentProject(3);

            $response = $this->visitRoute('login', asUser: 4);

            expect(inertiaRoute($response))->toBe('home');
        });

        it('redirects to Projects and resets current project if admin does not manage selected one', function () {
            setCurrentProject(1);

            $response = $this->visitRoute('login', asUser: 3);

            expect(currentProjectId())->toBeNull();
            expect(inertiaRoute($response))->toBe('projects.index');
        });

        it('redirects back to login if admin manages only a deleted project', function () {
            $response = $this->visitRoute('login', asUser: 50);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('redirects to home with remaining project selected when current one is deleted and admin manages only 1 other', function () {
            setCurrentProject(5, withTrashed: true);

            $response = $this->visitRoute('login', asUser: 53);

            expect(currentProjectId())->toBe(2);
            expect(inertiaRoute($response))->toBe('home');
        });

        it('redirects to home, with no project selected when current one is deleted and admin manages at least 2 others', function () {
            setCurrentProject(5, withTrashed: true);

            $response = $this->visitRoute('login', asUser: 54);

            expect(currentProject())->toBeNull();
            expect(inertiaRoute($response))->toBe('projects.index');
        });
    });
});
```

**Key Observations**:
- 13 tests, all passing (was 3/16 with 13 skipped)
- Every test is 2-3 lines
- Consistent `visitRoute()` pattern throughout
- Tests read as business specifications
- Nested describe blocks for logical grouping
- Full test names compose into sentences
- Named arguments make intent clear

---

## Impact on Future Work

### What I Should Remember

1. **Always abstract ruthlessly**: Second occurrence = time to build a helper
2. **Test outcomes, not mechanics**: Ask "what does the user experience?" not "what HTTP code?"
3. **Build a DSL**: Helpers should encode domain knowledge, not just save typing
4. **Aim for 2 lines**: Action + assertion (plus setup when needed)
5. **Use compositional naming**: describe chains + it = full readable sentence
6. **Nested describe for navigation**: Logical grouping + IDE collapse functionality
7. **Consistency everywhere**: One pattern, used everywhere, no exceptions
8. **Tests ARE specifications**: Write them so non-programmers can read them

### Patterns to Apply

**When writing new tests**:
1. Check if pattern exists—use it consistently
2. If new pattern needed, abstract it immediately
3. Use nested describe blocks for logical grouping
4. Make test names compose into sentences
5. Test the business outcome, not framework mechanics
6. Keep tests to 2 lines when possible

**When building helpers**:
1. Name them after domain concepts (`visitRoute`, `inertiaRoute`)
2. Use named arguments for clarity (`asUser:`, `withTrashed:`)
3. Return TestResponse to enable chaining
4. Encode domain knowledge, not just convenience

**When reviewing code**:
1. Ask: "Does this test read like a specification?"
2. Ask: "Would a non-programmer understand what's being tested?"
3. Ask: "Is there repetition that should be abstracted?"
4. Ask: "Are we testing outcomes or mechanics?"

---

## Conclusion

This session wasn't about fixing bugs or implementing features. It was about **learning a philosophy** of testing and code quality.

The user showed me their refactoring work and asked: "Do you see the difference?"

I did. The difference is:
- **Clarity over cleverness**
- **Outcomes over mechanics**
- **Specifications over verification**
- **Domain language over framework APIs**
- **Consistency over flexibility**
- **Ruthless abstraction over tolerated repetition**

This is what "clean code" means in their opinion, and I've internalized it.

Future sessions should build on this foundation, applying these patterns and continuing to improve the testing vocabulary as the project grows.

---

**Session Type**: Educational / Knowledge Transfer
**Outcome**: Deep understanding of testing philosophy and clean code principles
**Files Updated**: `documentation/ai/TESTS_KB.md` (added "Clean Code & Testing Philosophy" section)
**Next Steps**: Apply these patterns when writing future tests, continue building the testing DSL
