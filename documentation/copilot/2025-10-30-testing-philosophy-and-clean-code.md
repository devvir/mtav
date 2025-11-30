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

**See**: `tests/Feature/Authentication/LoginPageTest.php`

### Before vs After

**Original** (6 lines, testing HTTP mechanics):
- Used raw `actingAs()->followingRedirects()->getRoute()`
- Checked HTTP status codes and view internals
- Asked: "Did Laravel redirect correctly?"

**Refactored** (2 lines, testing business outcomes):
- Uses `visitRoute()` helper with named arguments
- Uses `inertiaRoute()` to check destination
- Asks: "Where does the user end up?"

### Key Transformations

1. **Abstraction**: Custom helpers replace verbose Laravel APIs
2. **Semantic Testing**: Extract meaning (which route) not mechanics (HTTP 302)
3. **Readability**: Named arguments make intent explicit
4. **Focus**: Test business outcomes, not framework implementation

**Impact**: Tests now read as executable business specifications, not HTTP protocol verification

---

## Core Lessons Learned

### 1. Tests as Executable Business Rules

**Philosophy**: Tests are **executable specifications**, not just verification code.

Well-written tests should be readable by non-programmers as statements of how the system behaves. Each test is a business rule that can be executed and verified automatically.

**Example**: See any test in `tests/Feature/Authentication/LoginPageTest.php`

The test name + assertion reads like plain English: *"When visiting the login page as Admin, it redirects to projects index if they manage more than one Project"*

This IS the specification—executable, verifiable, and guaranteed current.

### 2. Ruthless Abstraction

**Rule**: If you do something twice, abstract it into a helper.

**Philosophy**: Don't tolerate repetition. Every repeated pattern is an opportunity to build a better tool.

**Example**: `visitRoute()` in `tests/Pest.php`
- **Before**: `$this->actingAs(User::find(7))->followingRedirects()->getRoute('login')`
- **After**: `$this->visitRoute('login', asUser: 7)`

**Benefits**:
- Tests become shorter and more readable
- Changes happen in one place
- Helper name documents intent
- Named arguments clarify purpose

### 3. The 2-Line Ideal

**Target**: Most tests should be **2 lines** (action + assertion).

**Why**:
- Forces proper abstraction (can't hide complexity)
- Makes tests scannable at a glance
- Reveals missing tools in your DSL
- Keeps focus on the assertion

**When 3+ lines are OK**:
- Additional setup is genuinely necessary
- Testing a complex scenario
- Need intermediate assertions

**Red flag**: If most tests are 4+ lines, you're missing abstractions.

**Example**: See `tests/Feature/Authentication/LoginPageTest.php` - most tests are 2 lines

### 4. Compositional Test Naming in Pest

**Pest Feature**: Test names are built from **describe block chains** + `it()`.

**Pattern**:
```
describe('When visiting the login page', function () {
    describe('as Guest', function () {
        it('renders the login page', function () { ... });
    });
});
```

**Output**: `✓ When visiting the login page › as Guest › renders the login page`

**Guidelines**:
- First `describe`: Context or action being tested
- Nested `describe`: Actor or additional context
- `it()`: The expected behavior

**Result**: Tests read like Gherkin scenarios—non-programmers can understand them

### 5. Nested Describe Blocks for IDE Navigation

**Practical Benefit**: Nested `describe` blocks create **collapsible sections** in your IDE.

**Example**: `tests/Feature/Authentication/LoginPageTest.php` has 3 main sections (Guest, Member, Admin), each collapsible independently.

**Why this matters**:
- Each `describe` block can be collapsed
- Focus on one actor/scenario at a time
- Navigate large test files efficiently
- IDE shows structure in outline view

**Best practice**: Group tests by actor or scenario, not by technical details

### 6. Testing Outcomes, Not Mechanics

**Principle**: Test **what users experience**, not **how the framework achieves it**.

**Examples**:
- ❌ Testing mechanics: `expect($response->status())->toBe(302)`
- ✅ Testing outcome: `expect(inertiaRoute($response))->toBe('dashboard')`

**Why**:
- Business rule: "User ends up at Dashboard"
- NOT: "Laravel sends HTTP 302 with Location header"
- Tests survive implementation changes
- Tests document user experience, not HTTP protocol

**Implementation**: See `inertiaRoute()` helper in `tests/Pest.php`—extracts semantic meaning from response

Tests now ask: **"Where did the user end up?"** not **"What HTTP status was returned?"**

### 7. Consistency = Readability

**Absolute Rule**: Pick a pattern and use it **everywhere**.

**Why**: Humans read by pattern recognition. Inconsistency breaks this.

**Example**: Every test in `LoginPageTest.php` uses `visitRoute()` with the same signature. You learn the pattern once, then scan effortlessly.

**Rule of thumb**: If a new pattern emerges, **update all existing tests** to match. Don't leave mixed styles.

### 8. Building a Testing Vocabulary

**Goal**: Create a **domain-specific language (DSL)** for testing your application.

**Current Vocabulary** (see `tests/Pest.php` and `tests/Helpers/`):
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

**Example**: See test "redirects to Projects and resets current project..." in `LoginPageTest.php`

The test teaches you:
- System has concept of "current project"
- Admins can manage multiple projects
- System validates admin access to current project
- Invalid access clears selection and shows project list

**You learn the business domain, not Laravel internals.**

---

## Full Example: LoginPageTest

**See**: `tests/Feature/Authentication/LoginPageTest.php`

**Key Characteristics**:
- 13 tests, all 2-3 lines each
- Consistent `visitRoute()` pattern throughout
- Tests read as business specifications
- Nested describe blocks (Guest, Member, Admin)
- Test names compose into full sentences
- Named arguments make intent clear

**What it demonstrates**:
- All 8 principles in action
- Clean separation of concerns (helpers vs tests)
- Domain vocabulary in use
- Executable business specifications

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
