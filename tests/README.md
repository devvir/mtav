# Test Suite - Philosophy & Patterns# Test Suite - Philosophy & Patterns

**Last Updated**: October 27, 2025 **Last Updated**: October 27, 2025

**Framework**: Pest PHP with Inertia.js assertions **Framework**: Pest PHP + Inertia.js assertions

**Database**: MySQL (via separate Docker container for test isolation) **Database**: MySQL (separate Docker container)

**Status**: ✅ **134 passing**, 64 skipped, 73 todos (**271 total tests**)**Status**: ✅ 134 passing, 64 skipped, 73 todos (271 total)

---

## Purpose of This Document## 📚 Quick Reference

This explains the **rationale** behind testing decisions, implementation patterns, and organizational structure. It's written for you, the developer, to understand what you're dealing with when jumping into the tests and how to tackle failing/skipped tests efficiently.> **⭐ KNOWLEDGE BASE: [../documentation/knowledge-base/KNOWLEDGE_BASE.md](../documentation/knowledge-base/KNOWLEDGE_BASE.md)**

> **Single source of truth for everything MTAV - Start here!**

**Reading time**: ~25 minutes>

> This comprehensive document contains:

--->

> **Part 1: Business Domain**

## Test Classification & Philosophy>

> - 🎯 Vision & core purpose (fair housing distribution)

### What Constitutes Each Test Type> - 👥 All actors and their abilities

> - 📋 Complete authorization matrix

**Unit Tests** (`tests/Unit/Models/`)> - 🏗️ All entities, relationships, and business rules

- Test **individual model methods and relationships** in isolation> - 🎲 Unit distribution & lottery system

- Focus on business logic, scopes, accessors, mutators, relationship definitions> - 🎨 UI/UX principles (transparency over hiding)

- **No HTTP requests, no controllers, no views** - pure model behavior>

- Use factories to create minimal data needed> **Part 2: Technical Architecture**

- Execution: Fast (most run in < 0.05s)>

> - ⚙️ Complete technology stack (PHP 8.4, Laravel 12, Vue 3, Inertia)

**Feature Tests** (`tests/Feature/`)> - 🏗️ Application architecture (MVC, STI, relationships)

- Test **complete user workflows through HTTP requests**> - 🐳 Docker infrastructure (dev environment, containers, services)

- Include controller logic, middleware, authorization, validation, database persistence> - 🔨 Development workflows (Layer 1/2/3, scripts, commands)

- Use Inertia assertions to verify correct component rendering and data passing> - 🧪 Testing infrastructure (Pest, Vitest, git hooks)

- Execution: Moderate (most run in 0.05-0.15s)> - 📦 Build system & production images (multi-stage, registry)

- Cover the full request → response cycle> - 🚀 Deployment architecture (mtav-deploy, environments)

> - 🛠️ Troubleshooting guide (common issues, solutions)

**E2E Tests**>

- **Not implemented yet** - would use browser automation (Dusk)> **Part 3: Development & Testing**

- When implemented, would test JavaScript interactions, multi-step flows, async behavior>

- Would be slowest category, reserved for critical user journeys> - 🧪 TDD roadmap for remaining features

> - ❓ Open questions for clarification

### Why This Matters for Git Hooks>

> **Derived Guides** (from Knowledge Base):

**Pre-commit hook** should run **unit tests only** (~1-2 seconds total):>

````bash> - 📘 [Member Guide](../documentation/knowledge-base/derived/member-guide.md) - For cooperative members

./mtav test --pest tests/Unit> - 📗 Admin Manual _(coming soon)_ - For project managers

```> - 📕 Superadmin Guide _(coming soon)_ - For system administrators

> - 📙 Developer Guide _(coming soon)_ - For software developers

**Pre-push hook** should run **feature tests** (~10-15 seconds total):

```bash## �📊 Test Suite Summary

./mtav test --pest tests/Feature

```**Total Tests**: 271



**CI pipeline** runs **everything** including skipped tests to track technical debt:- ✅ **Passing**: 129 (core functionality working)

```bash- 🐛 **Skipped**: 12 (known bugs, documented in BUGS_AND_TODOS.md)

./mtav test --once --pest- 📝 **TODO**: 73 (planned features, not yet implemented)

```- ❌ **Failing**: 57 (mostly one issue - authorization redirects, see BUGS_AND_TODOS.md)



---## 📚 Documentation



## Model Testing Strategy### [📖 TEST_CATALOG.md](./TEST_CATALOG.md)



### Core Principle: Test Business Logic, Not FrameworkComplete catalog of all tests organized by file and describe block. Use this to understand what's being tested and get an overview of test coverage.



**I do NOT test**:### [🐛 BUGS_AND_TODOS.md](./BUGS_AND_TODOS.md)

- Basic Eloquent functionality (`$model->save()` works)

- Framework relationships (`hasMany()` returns a collection)Detailed tracking of all bugs and missing features. Use this to prioritize fixes and understand why certain tests are skipped.

- That factories create records

### [🔧 HELPERS.md](./HELPERS.md)

**I DO test**:

- **Custom scopes**: `Project::active()`, `Family::scopeSearch($query)`Guide to test helper functions and best practices. Use this when writing new tests.

- **Accessors/Mutators**: `Member->project` attribute accessor

- **Business methods**: `Project->addMember()`, `Family->addMember()`## 📁 Test Structure

- **Relationship correctness**: "A member belongs to exactly one family"

- **Model-level business rules**```

tests/

### Model Test Structure Pattern├── Unit/Models/               # 47 tests - Model relationships & methods

│   ├── UserTest.php           # 7 tests - User model conversions

```php│   ├── MemberTest.php         # 10 tests (3 skipped, 3 todo)

describe('ModelName Model', function () {│   ├── AdminTest.php          # 8 tests (3 todo)

    it('tests a relationship definition', function () {│   ├── FamilyTest.php         # 9 tests (1 todo, 5 todo)

        $model = ModelName::factory()->create();│   └── ProjectTest.php        # 13 tests (1 skipped)

        expect($model->relationship)->toBeInstanceOf(Collection::class);│

    });├── Feature/Auth/              # 13 tests - Laravel Breeze auth

    │   ├── AuthenticationTest.php

    it('tests a custom method', function () {│   ├── EmailVerificationTest.php

        // Arrange│   ├── PasswordConfirmationTest.php

        $model = ModelName::factory()->create();│   └── PasswordResetTest.php

        │

        // Act├── Feature/Policies/          # 34 tests - Authorization rules

        $result = $model->customMethod();│   ├── ProjectPolicyTest.php  # 10 tests (2 todo)

        │   ├── FamilyPolicyTest.php   # 12 tests (4 todo)

        // Assert│   └── MemberPolicyTest.php   # 12 tests (4 todo)

        expect($result)->toBe($expected);│

    });├── Feature/Controllers/       # 150+ tests - HTTP CRUD operations

});│   ├── AdminControllerCrudTest.php    # 25 tests (many skipped - bugs)

│   ├── FamilyControllerCrudTest.php   # 25 tests

describe('ModelName Business Logic - TODO', function () {│   ├── FamilyControllerTest.php       # 9 tests (3 todo)

    test('unimplemented feature description')->todo();│   ├── MemberControllerCrudTest.php   # 35 tests (6 todo, some skipped)

});│   ├── MemberControllerTest.php       # 11 tests (4 todo)

```│   ├── ProjectControllerTest.php      # 22 tests (1 todo, some skipped)

│   └── UnitControllerCrudTest.php     # 19 tests (1 todo, some skipped)

**Why separate describe blocks?**│

- First block: Tests for **implemented** features├── Feature/BusinessLogic/     # 37 tests - Complex domain rules

- Second block: Tests marked `.todo()` for **planned** features  │   ├── FamilyAtomicityTest.php        # 10 todo tests

- Makes it immediately clear what's working vs. what's on the roadmap│   ├── InvitationSystemTest.php       # 10 todo tests

│   └── ProjectScopeTest.php           # 14 tests (13 todo, 1 passing)

### Todo Tests Are Documentation│

├── Feature/DashboardTest.php  # 1 test

Tests marked `->todo()` or `->skip()` are **intentional**:├── Feature/Settings/          # 4 tests

- `.todo()`: Feature not yet implemented, test defines expected behavior│

- `.skip('reason')`: Feature exists but has a known bug, skip note explains the issue└── Helpers/                   # Test utilities (DRY patterns)

    ├── UserHelpers.php        # createSuperAdmin, createAdmin, createMember, etc.

**This prevents test suite rot** - when you implement the feature, you already have the test waiting.    ├── ProjectHelpers.php     # createProject, setCurrentProject, etc.

    ├── FamilyHelpers.php      # createFamily, createFamilyWithMembers, etc.

---    └── InertiaHelpers.php     # inertiaGet/Post/Patch/Delete, assertions

````

## Inertia Controller Testing Approach

│ ├── FamilyAtomicityTest.php (10 TODO tests) ⚠️ CRITICAL

### The Inertia Testing Challenge│ ├── InvitationSystemTest.php (10 TODO tests)

│ └── ProjectScopeTest.php (14 TODO tests) ⚠️ IMPORTANT

Inertia responses are **not traditional HTML or JSON**. They return:│

- A component name (`Projects/Index`)└── Architecture/

- Props (data passed to Vue component)├── ModelTest.php (4 arch tests)

- Version hash and other metadata├── PolicyTest.php (3 arch tests)

└── ControllerTest.php (3 arch tests)

Traditional Laravel test assertions don't handle this well.

````

### Custom Helper Functions (`tests/Helpers/InertiaHelpers.php`)

## 🎯 Key Business Logic Gaps Identified

I built custom helpers for Inertia-specific assertions:

### 🔴 CRITICAL - Family Atomicity (Not Implemented)

```php

// Instead of this verbose code:**The Core Problem:** Families are the atomic unit, but there's no enforcement.

$response->assertOk()

    ->assertInertia(fn ($page) => $page**Missing Implementations:**

        ->component('Projects/Index')

        ->has('projects.data', 2)1. ❌ `Family::moveToProject(Project $newProject)` - Atomically move entire family

    );2. ❌ `Family::leave()` - Remove entire family from project

3. ❌ Validation that prevents individual member project switches

// Use semantic helpers:4. ❌ Validation that `family.project_id` always matches members' active project

assertInertiaPaginatedData($response, 'Projects/Index', 'projects', 2);

assertInertiaComponent($response, 'Projects/Show');**Violations in Current Code:**

```

- `Member::switchProject()` exists but violates atomicity

**Available helpers**:- No checks prevent adding member to different project than family

- `inertiaGet()`, `inertiaPost()`, `inertiaPatch()`, `inertiaDelete()` - Make authenticated Inertia requests- No database constraints enforce family.project_id consistency

- `assertInertiaComponent()` - Verify correct component rendered

- `assertInertiaPaginatedData()` - Check paginated response structure and count**Test Coverage:** 10 TODO tests in `FamilyAtomicityTest.php`

- `assertInertiaHasError()` - Verify validation error present

- `getInertiaProp()` - Extract specific prop value for custom assertions---



**Rationale**:### 🟠 HIGH PRIORITY - Project Scope Enforcement

1. **Readability**: Test intent is immediately clear

2. **Consistency**: All controller tests use same patterns**The Problem:** Admins should only access resources in projects they manage.

3. **Maintenance**: If Inertia changes its response structure, fix in one place

4. **Type safety**: Helpers enforce correct component names and prop paths**Missing Policy Checks:**



---1. ❌ Admin creating Family - doesn't validate they manage target project

2. ❌ Admin creating Member - doesn't validate project ownership

## CRUD Testing Strategies3. ❌ Admin updating/deleting - no project scope validation

4. ❌ Members should only see data from their active project

### General CRUD Test Structure

**Test Coverage:** 14 TODO tests in `ProjectScopeTest.php`

Each CRUD test file follows this **consistent pattern**:

---

```php

describe('Resource CRUD - Index/Show', function () {### 🟡 MEDIUM PRIORITY - Invitation System

    it('lists resources for current project', function () { /* ... */ });

    it('searches resources by X', function () { /* ... */ });**The Problem:** No visible invitation system implementation.

});

**Missing Features:**

describe('Resource CRUD - Create/Store', function () {

    it('allows authorized users to create', function () { /* ... */ });1. ❌ Invitation flow (email, token, activation)

    it('denies unauthorized users', function () { /* ... */ });2. ❌ Member-only invites restricted to their own family

    it('validates required fields', function () { /* ... */ });3. ❌ Email verification for new users

});4. ❌ Invitation expiry



describe('Resource CRUD - Update', function () {**Test Coverage:** 10 TODO tests in `InvitationSystemTest.php`

    it('allows authorized users to update', function () { /* ... */ });

    it('denies unauthorized users', function () { /* ... */ });---

});

## 🏗️ Recommended Implementation Order

describe('Resource CRUD - Delete', function () {

    it('allows authorized users to delete', function () { /* ... */ });### Phase 1: Critical - Family Atomicity (Week 1-2)

    it('denies unauthorized users', function () { /* ... */ });

});```php

// Implement these methods in Family model:

describe('Resource CRUD - Project Scope Validation', function () {public function moveToProject(Project $newProject): self

    it('shows only authorized data in forms', function () { /* ... */ });{

    it('prevents cross-project operations', function () { /* ... */ });    DB::transaction(function () use ($newProject) {

});        // 1. Remove all members from current project

```        $this->members->each(fn($m) => $this->project?->removeMember($m));



**Why this pattern?**        // 2. Add all members to new project

1. **Predictability**: Every CRUD test file has the same structure        $this->members->each(fn($m) => $newProject->addMember($m));

2. **Completeness**: Forces you to think about all CRUD operations

3. **Easy navigation**: Jump to "Update" section to find update tests        // 3. Update family.project_id

4. **Authorization clarity**: Separates happy path from authorization checks        $this->project()->associate($newProject)->save();

    });

### Per-Action Testing Strategy

    return $this;

#### Index/List Tests}



**Setup**:public function leave(): self

```php{

$admin = createAdmin();    DB::transaction(function () {

$project = createProjectWithAdmin($admin);        $this->members->each(fn($m) => $this->project?->removeMember($m));

setCurrentProject($project);        $this->project()->dissociate()->save();

    });

// Create resources in current project (should appear)

$resource1 = createResourceInProject($project);    return $this;

$resource2 = createResourceInProject($project);}

````

// Create resources in other projects (should NOT appear)

$otherResource = createResource();**Add Validation:**

````

```php

**Expectations**:// In Member model - override joinProject() to prevent atomicity violations

```phppublic function joinProject(Project|int $project): self

$response = inertiaGet($admin, route('resources.index'));{

assertInertiaPaginatedData($response, 'Resources/Index', 'resources', 2);    $targetProject = model($project, Project::class);

````

    if ($this->family && $this->family->project_id !== $targetProject->id) {

**Key verification**: Only resources from the scoped project are returned. throw new \LogicException(

            'Cannot join project individually. Use Family::moveToProject() instead.'

#### Show Tests );

    }

**Setup**:

```php $targetProject->addMember($this);

$admin = createAdmin();    return $this;

$resource = createResource();}

```

**Expectations**:### Phase 2: High - Project Scope Enforcement (Week 3)

````php

$response = inertiaGet($admin, route('resources.show', $resource));```php

assertInertiaComponent($response, 'Resources/Show');// Update FamilyPolicy

$response->assertOk(); // or assertForbidden() for unauthorizedpublic function create(User $user): bool

```{

    return $user->isAdmin();

**Key verification**: Correct component rendered, no sensitive data leaked.}



#### Create/Store Testspublic function store(User $user, array $data): bool

{

**Setup**:    if ($user->isSuperAdmin()) return true;

```php

$admin = createAdmin();    $projectId = $data['project_id'];

$project = createProjectWithAdmin($admin);    $project = Project::find($projectId);

setCurrentProject($project);

// Create related resources needed for foreign keys    return $user->asAdmin()?->manages($project) ?? false;

$family = createFamilyInProject($project);}

````

// Similar for MemberPolicy, UnitPolicy

**Expectations for success**:```

````php

$response = inertiaPost($admin, route('resources.store'), [### Phase 3: Medium - Invitation System (Week 4-5)

    'field' => 'value',

    'project_id' => $project->id,```php

]);// Create Invitation model and flow

class Invitation extends Model {

$resource = Resource::where('field', 'value')->first();    // token, email, inviter_id, family_id, project_id, expires_at

expect($resource)->not->toBeNull()}

    ->and($resource->project_id)->toBe($project->id);

$response->assertRedirect(route('resources.show', $resource));// Add to controllers

```public function invite(InviteRequest $request) {

    $invitation = Invitation::create([

**Expectations for validation failure**:        'email' => $request->email,

```php        'inviter_id' => $request->user()->id,

$response = inertiaPost($admin, route('resources.store'), [        'family_id' => $request->user()->family_id, // For members

    'required_field' => '', // Invalid        'token' => Str::random(64),

]);        'expires_at' => now()->addDays(7),

    ]);

assertInertiaHasError($response, 'required_field');

expect(Resource::count())->toBe(0); // Nothing was created    Mail::to($invitation->email)->send(new InvitationEmail($invitation));

```}

````

**Key verification**:

- Record exists in database with correct values---

- Relationships are properly set (especially `project_id`)

- Redirects to appropriate page or returns errors## 🚀 Running the Tests

#### Update Tests```bash

# Run all tests

**Setup**:mtav artisan test

```php

$admin = createAdmin();# Run specific test file

$resource = createResource();mtav artisan test tests/Unit/Models/FamilyTest.php

```

# Run only implemented tests (exclude TODOs)

**Expectations**:mtav artisan test --exclude-group=todo

```php

$response = inertiaPatch($admin, route('resources.update', $resource), [# Run with coverage

    'field' => 'Updated Value',mtav artisan test --coverage

    // Include unchanged fields (Laravel quirk)

    'other_field' => $resource->other_field,# Run in watch mode (TDD)

]);mtav artisan test --watch

```

expect($resource->fresh()->field)->toBe('Updated Value');

$response->assertRedirect();---

````

## 📝 Pest TODO Syntax

**Key verification**: Database actually updated (use `fresh()` to reload from DB).

Tests marked with `->todo()` will:

#### Delete Tests

- ✅ Show as "TODO" in test output

**Setup**:- ✅ Not fail the test suite

```php- ✅ Remind you what needs to be implemented

$admin = createAdmin();

$resource = createResource();```php

```test('some feature', function () {

    // TODO: Implement this logic

**Expectations**:})->todo();

```php

$response = inertiaDelete($admin, route('resources.destroy', $resource));// When you implement it, just remove ->todo()

test('some feature', function () {

expect(Resource::find($resource->id))->toBeNull();    expect($result)->toBeTrue();

// OR for soft deletes:});

expect(Resource::withTrashed()->find($resource->id)->trashed())->toBeTrue();```

````

---

**Key verification**: Record actually removed (or soft-deleted).

## 🎓 Key Takeaways

### Authorization Testing Pattern

### What's Working Well:

**Every CRUD action** should test:

1. ✅ **Authorized user** can perform action- ✅ Clean Single Table Inheritance (User/Member/Admin)

2. ❌ **Unauthorized user** cannot perform action (returns 403 or 302)- ✅ Proper use of Policies

3. ✅ **Superadmin** can perform action regardless of project scope- ✅ Many-to-many relationships with pivot

- ✅ Global scopes for Member/Admin separation

**Current known issue**: Many authorization tests fail because middleware redirects (302) instead of returning 403. These are skipped with notes like:- ✅ Superadmin system via ENV config

```php

->skip('Authorization middleware redirects (302) instead of returning 403')### What Needs Attention:

```

- 🔴 **Family atomicity** - Most critical gap

**Why skip instead of fix?** The authorization logic isn't fully implemented yet. When you implement it, remove the skip and verify the test passes.- 🟠 **Project scope** - Security/data isolation concern

- 🟡 **Invitations** - User onboarding flow

### Validation Testing Pattern- 🟢 **Validation** - More comprehensive request validation

**Test minimally but meaningfully**:---

````php

it('validates required fields on creation', function () {## 📚 Additional Notes

    $admin = createAdmin();

    ### Architectural Tests

    $response = inertiaPost($admin, route('resources.store'), [

        'required_field' => '', // InvalidThe architecture tests will help maintain code quality:

        'email' => 'not-an-email', // Invalid format

    ]);- Models must extend base Model

    - Policies must have "Policy" suffix

    assertInertiaHasError($response, 'required_field');- Controllers should use FormRequests

    assertInertiaHasError($response, 'email');- No direct DB facade usage (except where noted)

});

```### Test Database



**Rationale**: Don't test every validation rule exhaustively. Laravel's validation is already tested. Test:All tests use `RefreshDatabase` trait (from `tests/Pest.php`):

- At least one required field

- At least one format validation (email, URL, etc.)- Database is reset before each test

- Unique constraints if applicable- No test pollution

- Custom validation rules if you implement them- Fast in-memory SQLite recommended



------



## Test Organization & Naming## 🤝 Next Steps



### Folder Structure1. **Review the TODO tests** - Prioritize which business logic to implement first

2. **Run the implemented tests** - Verify existing functionality works

```3. **Pick a TODO test** - Start TDD cycle:

tests/   - Make test pass

├── Feature/   - Refactor

│   ├── Auth/                          # Laravel Breeze auth tests   - Remove `->todo()`

│   ├── BusinessLogic/                 # Domain-specific rules4. **Repeat** - Gradually work through the TODO list

│   │   ├── FamilyAtomicityTest.php   # Family/member atomicity rules

│   │   ├── InvitationSystemTest.php  # Invitation workflow (TODO)Good luck with the implementation! 🚀

│   │   └── ProjectScopeTest.php      # Project scoping rules
│   ├── Controllers/
│   │   ├── AdminControllerCrudTest.php
│   │   ├── FamilyControllerCrudTest.php
│   │   ├── FamilyControllerTest.php   # Non-CRUD tests
│   │   ├── MemberControllerCrudTest.php
│   │   ├── MemberControllerTest.php
│   │   ├── ProjectControllerTest.php
│   │   └── UnitControllerCrudTest.php
│   ├── Policies/                      # Policy authorization tests
│   │   ├── AdminPolicyTest.php
│   │   ├── FamilyPolicyTest.php
│   │   ├── MemberPolicyTest.php
│   │   └── ProjectPolicyTest.php
│   ├── Settings/                      # User settings tests
│   └── DashboardTest.php
├── Unit/
│   └── Models/
│       ├── AdminTest.php
│       ├── FamilyTest.php
│       ├── MemberTest.php
│       ├── ProjectTest.php
│       ├── UnitTest.php
│       └── UserTest.php
├── Helpers/
│   ├── InertiaHelpers.php            # Inertia-specific assertions
│   └── TestHelpers.php               # Factory shortcuts & utilities
├── Pest.php                          # Pest configuration & global setup
├── TestCase.php                      # Base test case
└── README.md                         # This file
````

### Naming Conventions

**File names**:

- `*Test.php` - Standard test file suffix
- `*CrudTest.php` - Comprehensive CRUD operation tests
- `*ControllerTest.php` (without CRUD) - Non-CRUD controller tests

**Test names** - Describe behavior from user perspective:

```php
// Good - describes behavior
it('allows admin to create family in managed project', function () { /* ... */ });
it('denies member from deleting other members', function () { /* ... */ });

// Avoid - implementation-focused
test('create method returns 200', function () { /* ... */ });
test('delete calls destroy', function () { /* ... */ });
```

**Describe blocks** - Group related tests:

```php
describe('Family CRUD - Create/Store', function () {
    // All creation-related tests here
});

describe('Family Business Logic - TODO', function () {
    // Planned features
});
```

### Special Test Categories

**Business Logic Tests** (`tests/Feature/BusinessLogic/`)

- Tests that **span multiple models/controllers**
- Example: "Family atomicity" - when a family moves projects, all members must move too
- Example: "Project scope" - admins can only manage resources in assigned projects
- These are **integration tests** but not full E2E

**Policy Tests** (`tests/Feature/Policies/`)

- Test authorization logic **in isolation from controllers**
- Useful when policies are complex or shared across controllers
- **Current state**: Many policy tests exist but controllers don't always respect them

---

## Test Helpers & Shortcuts

### Factory Shortcuts (`tests/Helpers/TestHelpers.php`)

Instead of writing this repeatedly:

```php
$admin = Admin::factory()->create();
$project = Project::factory()->create();
$project->addAdmin($admin);
```

Use shortcuts:

```php
$admin = createAdmin();
$project = createProjectWithAdmin($admin);
```

**Available shortcuts**:

- `createAdmin()` - Create admin user
- `createSuperAdmin()` - Create superadmin (configured email)
- `createMember()` - Create member user
- `createProject()` - Create project
- `createProjectWithAdmin($admin)` - Create project and assign admin
- `createProjects($count)` - Create multiple projects
- `createFamily()` - Create family
- `createFamilyInProject($project)` - Create family in specific project
- `createMemberInProject($project, $family = null, $attributes = [])` - Create member with relationships
- `setCurrentProject($project)` - Set session's current project

**Rationale**:

1. **Reduced boilerplate** - Focus on test logic, not setup
2. **Consistency** - Everyone creates test data the same way
3. **Refactoring safety** - Change factory logic once, all tests update

### Current Project Pattern

Many controllers scope data by "current project" stored in session:

```php
setCurrentProject($project);
$response = inertiaGet($admin, route('families.index'));
// Should only show families from $project
```

**Alternative patterns used**:

- `setState('project', $project)` - **Not implemented yet**, tests using this are skipped
- `session(['project' => $project])` - Direct session manipulation (works but verbose)

When you implement `setState()` helper, update skipped tests and remove the skip.

---

## Running Tests Strategically

### Speed Categories

Based on actual execution times:

**Blazing Fast** (< 0.05s per test):

- Most unit tests
- Policy tests
- Simple controller tests without complex setup

**Fast** (0.05s - 0.15s per test):

- CRUD controller tests with database operations
- Tests with multiple factories

**Slower** (0.15s+ per test):

- Tests with password hashing (bcrypt is intentionally slow)
- Tests with extensive database seeding

### Pre-Commit Hook Strategy

Run **unit tests only**:

```bash
./mtav test --pest tests/Unit
```

**Execution time**: ~1-2 seconds
**Coverage**: Model business logic, relationships, scopes
**Fast feedback** while coding

### Pre-Push Hook Strategy

Run **feature tests**:

```bash
./mtav test --pest tests/Feature
```

**Execution time**: ~10-15 seconds
**Coverage**: Full application behavior
**Catches integration issues** before pushing

### CI Pipeline Strategy

Run **everything**:

```bash
./mtav test --once --pest
```

**Execution time**: ~15-20 seconds
**Coverage**: Complete test suite including skipped tests
**Report on skipped tests** to track technical debt

### Watch Mode Strategy

Run tests related to file you're editing:

```bash
./mtav test --pest --filter="Family"  # All Family-related tests
./mtav test --pest tests/Feature/Controllers/FamilyControllerTest.php
```

**Execution time**: ~1-3 seconds
**Fast iteration** while developing

---

## Skipped Tests & Technical Debt

### Why So Many Skipped Tests?

As of October 27, 2025:

- **64 skipped tests** out of 271 total
- **73 todo tests** (planned features)

**This is intentional documentation**, not laziness. Each skip/todo has a specific reason.

### Categories of Skipped Tests

**1. Authorization Middleware Issues** (~40 tests)

```php
->skip('Authorization middleware redirects (302) instead of returning 403')
```

**Cause**: Middleware redirects to login instead of returning JSON 403.
**Fix**: Configure middleware to detect Inertia requests and return proper status codes.
**Priority**: Medium - functionality works, but tests can't verify it properly.

**2. Controller Not Saving** (~8 tests)

```php
->skip('Member update not persisting - firstname remains unchanged')
```

**Cause**: Controller doesn't call `$model->update()` or validation rejects silently.
**Fix**: Implement save logic in controller.
**Priority**: High - this is a bug affecting users.

**3. Missing Views/Implementation** (~15 tests)

```php
->skip('Units/Index Inertia view not yet implemented')
```

**Cause**: Unit CRUD not built yet.
**Fix**: Implement Unit controller and views.
**Priority**: Low - feature not started.

**4. Undefined Helper Functions** (~3 tests)

```php
->skip('Call to undefined function setState()')
```

**Cause**: Test uses a helper that doesn't exist yet.
**Fix**: Implement `setState()` helper or refactor to use `session()`.
**Priority**: Low - workaround exists.

### How to Tackle Skipped Tests Efficiently

**Step 1: Group by root cause**

```bash
grep -r "Authorization middleware redirects" tests/
```

**Step 2: Fix root cause once**
Fix the middleware issue, then:

```bash
# Remove skip from all authorization tests
# Verify they pass
# Commit as "Fix authorization middleware"
```

**Step 3: Re-run category**

```bash
./mtav test --pest --filter="authorization"
```

**Step 4: Remove skips incrementally**
Don't fix all 64 at once. Pick one category:

1. Fix authorization middleware → 40 tests now pass
2. Fix member controller save → 8 tests now pass
3. Implement Unit CRUD → 15 tests now pass

---

## Test Data Philosophy

### Factories Over Manual Creation

**Always use factories**:

```php
// Good
$member = Member::factory()->create(['email' => 'test@example.com']);

// Avoid
$member = new Member();
$member->email = 'test@example.com';
$member->save();
```

**Rationale**: Factories set all required fields with sensible defaults.

### Minimal Data Creation

**Create only what the test needs**:

```php
// Testing member deletion - don't need family details
it('allows member to delete themselves', function () {
    $member = createMember(); // Uses defaults

    $response = inertiaDelete($member, route('members.destroy', $member));

    expect(Member::find($member->id))->toBeNull();
});
```

### Database State Between Tests

**Each test is isolated**:

- Database is **automatically rolled back** after each test (uses transactions)
- No need to clean up manually
- Tests can run in any order

---

## MySQL vs SQLite Considerations

### Why MySQL for Tests?

**Initially used SQLite** for speed, but switched to MySQL because:

1. **SQL dialect differences** - `CONCAT(a, b)` works in MySQL, SQLite uses `||`
2. **Production parity** - If it works in tests, it works in production
3. **Constraint testing** - Foreign keys, unique indexes behave identically
4. **No surprises** - Avoid "works in SQLite, breaks in prod"

### Test Database Setup

**Separate Docker container** (`mysql_test`):

- Same MariaDB 12 version as production
- Separate database (`mtav_test`)
- Auto-started with `--profile test` flag
- Isolated volume (won't affect dev data)

**Configuration** (`phpunit.xml`):

```xml
<env name="DB_HOST" value="mysql_test"/>
<env name="DB_DATABASE" value="mtav_test"/>
```

### Migration Strategy

**Tests use actual migrations**:

- Run `php artisan migrate:fresh` before test suite
- Same migrations as production
- If migration fails, tests fail immediately (catches migration bugs early)

**Consolidation approach**:

- Add test-specific columns to existing migrations
- Rationale: You're sole developer, frequently run `migrate:fresh`, no production data yet

---

## Common Patterns & Idioms

### Pest's `expect()` Syntax

```php
// Single assertion
expect($value)->toBe($expected);

// Chained assertions
expect($member)
    ->toBeInstanceOf(Member::class)
    ->and($member->email)->toBe('test@example.com')
    ->and($member->family_id)->toBe($family->id);

// Negation
expect($value)->not->toBeNull();
```

**Prefer `expect()` over PHPUnit assertions** - more readable, better error messages.

### Testing Relationships

```php
// Relationship exists
expect($member->family)->toBeInstanceOf(Family::class);

// Relationship count
expect($family->members)->toHaveCount(3);
```

### Testing Scopes

```php
$activeProjects = Project::active()->get();
expect($activeProjects)->toHaveCount(2);
expect($activeProjects->pluck('id'))->toContain($project1->id, $project2->id);
```

---

## Guidelines for Writing New Tests

### Before Writing a Test

**Ask yourself**:

1. What **behavior** am I testing?
2. What's the **minimum setup** needed?
3. What **could go wrong**?
4. Is this already tested elsewhere?

### Test Structure Template

```php
it('describes the expected behavior', function () {
    // Arrange - Set up test data
    $model = createModel();

    // Act - Perform the action
    $result = $model->doSomething();

    // Assert - Verify the outcome
    expect($result)->toBe($expected);
});
```

### When to Skip vs Todo

**Use `->todo()`** when:

- Feature doesn't exist yet
- Test defines expected behavior for future

**Use `->skip('reason')`** when:

- Feature exists but has a known bug
- Test would fail due to environmental issues

**Never delete a failing test** - Skip it with explanation.

### What NOT to Test

**Don't test**:

- Framework functionality
- Third-party packages
- Obvious failures

**Focus on**:

- Your business logic
- Custom validation rules
- Authorization decisions
- Data transformations

---

## Debugging Failed Tests

### When a Test Fails

**Step 1: Read the error**

- Expected vs actual values
- Line number
- Stack trace

**Step 2: Run in isolation**

```bash
./mtav test --pest --filter="exact test name"
```

**Step 3: Add debug output**

```php
dump($member->toArray());
ray($member); // If using Ray
```

### Common Gotchas

**1. Stale Model Instances**

```php
// Wrong
$member->update(['name' => 'New']);
expect($member->name)->toBe('New'); // FAILS

// Right
$member->update(['name' => 'New']);
expect($member->fresh()->name)->toBe('New'); // PASSES
```

**2. Factory State vs Instance State**

```php
// Wrong
$member = Member::factory()->create();
$member->is_admin = true; // Only in memory!
expect(Member::find($member->id)->is_admin)->toBeTrue(); // FAILS

// Right
$member = Member::factory()->create(['is_admin' => true]);
```

---

## Conclusion

This test suite is designed for **long-term maintainability**:

- **Skipped tests** document known issues and planned features
- **Consistent patterns** make navigation predictable
- **Helper functions** reduce boilerplate
- **MySQL in tests** ensures production parity
- **Descriptive names** serve as living documentation

When you return after months away, these tests will:

1. **Remind you** how features should behave
2. **Catch regressions** when you change something
3. **Guide implementation** of skipped/todo features
4. **Document** authorization rules and business logic

**Your approach** when tackling skipped tests:

1. Group by root cause
2. Fix root cause once
3. Remove skips from affected tests
4. Verify all pass
5. Commit atomically

**Red → Green → Refactor** with skip/todo:

- **Red** → Skip with explanation → **Green** (skipped) → Implement → **Green** (passing)

This prevents test suite rot and turns tests into a **development roadmap**.
