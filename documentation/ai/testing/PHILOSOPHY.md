# Testing Philosophy & Infrastructure

## Core Principles

### The Universe Fixture

**Concept**: Single comprehensive SQL fixture loaded once, rolled back per test

**File**: `tests/_fixtures/universe.sql`

**Contains**:
- Multiple projects (2-3 typical)
- Families in each project
- Members in families
- Admins managing projects
- Units, unit types
- Complete relational data

**Loading**: Once per test suite (not per test)

**Benefits**:
- Fast test execution (no per-test setup)
- Consistent test data
- Real-world scenario complexity
- Complete relationships pre-established

### Transaction Rollback

**Mechanism**: Laravel's `RefreshDatabase` with transactions

**Pattern**:
```php
uses(RefreshDatabase::class);

beforeEach(function () {
    // Universe loaded automatically
    // Transaction started
});

afterEach(function () {
    // Transaction rolled back
    // Universe pristine for next test
});
```

**Guarantees**:
- Universe ALWAYS present
- Universe IMMUTABLE across tests
- No test pollution
- Parallel test execution safe

### The 2-Line Ideal

**Goal**: Most tests should be ~2 lines (excluding assertion)

**Pattern**:
```php
it('members can view families in their project', function () {
    $member = memberFromUniverse(); // Line 1: Get fixture data
    actingAs($member); // Line 2: Authenticate

    $response = get(route('families.index')); // Line 3: Action

    $response->assertOk(); // Assertion
});
```

**Why Possible**: Universe has all data pre-loaded

**Exceptions**: Tests for new features, edge cases, error conditions may need setup

---

## Test Suite Structure

### Directory Organization

```
tests/
├── Pest.php                      # Global test configuration
├── TestCase.php                  # Base test case
├── _fixtures/
│   └── universe.sql              # Comprehensive fixture
├── Concerns/                     # Shared test helpers
│   ├── AuthHelpers.php
│   ├── UniverseHelpers.php
│   └── AssertionHelpers.php
├── Feature/                      # Integration tests (DB, HTTP)
│   ├── Controllers/
│   ├── Policies/
│   ├── BusinessLogic/
│   ├── Scopes/                   # <- NEW: ProjectScope tests
│   └── Workflows/
├── Unit/                         # Isolated unit tests
│   ├── Models/
│   └── Services/
└── Arch/                         # Architecture tests
```

### Test Naming Conventions

**Feature Tests**: `{Model}{Action}Test.php`
- `FamilyIndexTest.php` - Index listing
- `FamilyCreateTest.php` - Create operations
- `FamilyUpdateTest.php` - Update operations
- `FamilyScopeTest.php` - Global scope behavior

**Unit Tests**: `{Class}Test.php`
- `UserTest.php` - User model
- `ProjectScopeTest.php` - ProjectScope trait

**Test Descriptions**: Natural language, present tense
```php
it('filters families to member project')
it('allows admins to create families in managed projects')
it('prevents members from seeing other projects')
```

---

## Universe Fixture Details

### Sample Universe Structure

**Projects**:
- Project 1: "Building Alpha" (active, 20 units)
- Project 2: "Building Beta" (active, 15 units)
- Project 3: "Building Gamma" (planned, 10 units)

**Families**:
- 10 families in Project 1
- 8 families in Project 2
- 5 families in Project 3

**Members**:
- 2-4 members per family
- At least one member per family with verified email
- Mix of verified/unverified invitations

**Admins**:
- Superadmin: `superadmin@example.com`
- Admin managing Project 1 only
- Admin managing Projects 2 & 3
- Admin with no projects (edge case)

**Units**:
- 2-3 unit types per project
- Units distributed across types
- Some units assigned to families
- Some units unassigned

### Accessing Universe Data

**Helper Functions** (in `tests/Concerns/UniverseHelpers.php`):

```php
// Get specific entities
function memberFromUniverse(int $projectId = 1): Member;
function adminFromUniverse(int $projectId = 1): Admin;
function superadminFromUniverse(): Admin;
function familyFromUniverse(int $projectId = 1): Family;
function projectFromUniverse(int $id = 1): Project;

// Get collections
function membersFromProject(int $projectId): Collection;
function familiesFromProject(int $projectId): Collection;
function unitsFromProject(int $projectId): Collection;

// Get edge cases
function emptyFamily(): Family; // Family with no members
function unassignedUnit(): Unit; // Unit without family
function adminWithMultipleProjects(): Admin;
```

**Usage**:
```php
it('members see only their project families', function () {
    $member = memberFromUniverse(projectId: 1);
    actingAs($member);

    $families = Family::all();

    expect($families)
        ->each->project_id->toBe(1);
});
```

---

## Test Patterns

### Scope Testing Pattern

**Purpose**: Verify global scope filters correctly for each user type

**Pattern**:
```php
it('filters to member project', function () {
    $member = memberFromUniverse(1); // Project 1
    $otherFamily = familyFromUniverse(2); // Project 2

    actingAs($member);

    expect(Family::all()->pluck('id'))
        ->not->toContain($otherFamily->id);
});

it('filters to admin managed projects', function () {
    $admin = adminFromUniverse(1); // Manages only Project 1
    $otherFamily = familyFromUniverse(2); // Project 2

    actingAs($admin);

    expect(Family::all()->pluck('project_id'))
        ->each->toBe(1)
        ->not->toContain(2);
});

it('shows all for superadmin', function () {
    $superadmin = superadminFromUniverse();

    actingAs($superadmin);

    $allProjectIds = Project::pluck('id');
    $familyProjectIds = Family::all()->pluck('project_id')->unique();

    expect($familyProjectIds->sort()->values())
        ->toEqual($allProjectIds->sort()->values());
});
```

### Authorization Testing Pattern

**Purpose**: Verify policies work correctly (assumes scope already filtered)

**Pattern**:
```php
it('allows admins to update families', function () {
    $admin = adminFromUniverse(1);
    $family = familyFromUniverse(1); // Same project

    actingAs($admin);

    expect($admin->can('update', $family))->toBeTrue();
});

it('allows members to update own family', function () {
    $member = memberFromUniverse(1);
    $ownFamily = $member->family;

    actingAs($member);

    expect($member->can('update', $ownFamily))->toBeTrue();
});

it('prevents members from updating other families', function () {
    $member = memberFromUniverse(1);
    $otherFamily = familyFromUniverse(1); // Same project, different family

    actingAs($member);

    expect($member->can('update', $otherFamily))->toBeFalse();
});
```

### Controller Testing Pattern

**Purpose**: Test HTTP layer (routing, validation, responses)

**Pattern**:
```php
it('returns families index for members', function () {
    $member = memberFromUniverse(1);

    actingAs($member)
        ->get(route('families.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Families/Index')
            ->has('families.data', 10) // 10 families in Project 1
            ->where('families.data.0.project_id', 1)
        );
});

it('validates family creation', function () {
    $admin = adminFromUniverse(1);

    actingAs($admin)
        ->post(route('families.store'), [
            'name' => '', // Invalid - required
            'project_id' => 1,
        ])
        ->assertSessionHasErrors(['name']);
});
```

### Business Logic Testing Pattern

**Purpose**: Test complex workflows, multi-step processes

**Pattern**:
```php
it('switches family to different project', function () {
    $admin = adminWithMultipleProjects(); // Manages 1 & 2
    $family = familyFromUniverse(1);
    $members = $family->members;

    actingAs($admin);

    // Action
    put(route('families.update', $family), [
        'project_id' => 2,
        'name' => $family->name,
    ]);

    // Verify
    expect($family->fresh()->project_id)->toBe(2);

    // Verify member pivots updated
    foreach ($members as $member) {
        $activePivot = $member->fresh()
            ->projects()
            ->wherePivot('active', true)
            ->first();

        expect($activePivot->id)->toBe(2);
    }
});
```

---

## Test Helpers

### Auth Helpers

```php
// tests/Concerns/AuthHelpers.php

function actingAsMember(int $projectId = 1): TestCase
{
    return test()->actingAs(memberFromUniverse($projectId));
}

function actingAsAdmin(int $projectId = 1): TestCase
{
    return test()->actingAs(adminFromUniverse($projectId));
}

function actingAsSuperadmin(): TestCase
{
    return test()->actingAs(superadminFromUniverse());
}
```

**Usage**:
```php
it('members can view families', function () {
    actingAsMember(1)
        ->get(route('families.index'))
        ->assertOk();
});
```

### Assertion Helpers

```php
// tests/Concerns/AssertionHelpers.php

function assertScopedToProject(Collection $models, int $projectId): void
{
    expect($models)
        ->each->project_id->toBe($projectId);
}

function assertContainsOnly(Collection $models, array $ids): void
{
    expect($models->pluck('id')->sort()->values())
        ->toEqual(collect($ids)->sort()->values());
}

function assertInertiaHasPagination(TestResponse $response, string $key): void
{
    $response->assertInertia(fn ($page) => $page
        ->has("{$key}.data")
        ->has("{$key}.links")
        ->has("{$key}.meta")
    );
}
```

---

## Running Tests

### Full Suite

```bash
./mtav test            # Run all tests
./mtav test --parallel # Parallel execution
./mtav test --coverage # With coverage report
```

### Filtered Runs

```bash
./mtav test --filter=FamilyScope      # Specific test file
./mtav test --filter="filters families" # Specific test case
./mtav test tests/Feature/Scopes/     # Specific directory
```

### Watch Mode

```bash
./mtav test --watch   # Auto-run on file changes
```

### Coverage

```bash
./mtav test --coverage --min=80  # Require 80% coverage
```

---

## Test Data Philosophy

### Universe = Source of Truth

**Principle**: The universe fixture is the canonical test data

**Implications**:
- Don't create new data unless testing creation
- Don't modify universe data (it's immutable via rollback)
- Use universe entities for all relationship tests

**Example**:
```php
// ❌ BAD: Creating new data when universe has it
it('members can view families', function () {
    $project = Project::factory()->create();
    $family = Family::factory()->create(['project_id' => $project->id]);
    $member = Member::factory()->create(['family_id' => $family->id]);

    actingAs($member);
    // ... test
});

// ✅ GOOD: Using universe data
it('members can view families', function () {
    $member = memberFromUniverse(1);

    actingAs($member);
    // ... test
});
```

### When to Create New Data

**Valid scenarios**:
1. Testing creation logic itself
2. Testing edge cases not in universe
3. Testing error conditions (invalid data)
4. Testing with specific data patterns

**Pattern**:
```php
it('creates family with valid data', function () {
    $admin = adminFromUniverse(1);
    $project = projectFromUniverse(1);

    actingAs($admin)
        ->post(route('families.store'), [
            'name' => 'New Family',
            'project_id' => $project->id,
        ])
        ->assertCreated();

    expect(Family::where('name', 'New Family')->exists())
        ->toBeTrue();
});
```

---

## Testing ProjectScope Specifically

### Scope Test Organization

**File**: `tests/Feature/Scopes/ProjectScopeTest.php`

**Structure**:
```php
describe('ProjectScope for Members', function () {
    it('filters families to member project');
    it('filters members to member project');
    it('filters units to member project');
    it('returns null for other project family');
});

describe('ProjectScope for Admins', function () {
    it('filters families to managed projects');
    it('filters members to managed projects');
    it('filters to all managed projects for multi-project admin');
    it('returns null for unmanaged project family');
});

describe('ProjectScope for Superadmins', function () {
    it('shows all families regardless of project');
    it('shows all members regardless of project');
    it('bypasses scope entirely');
});

describe('ProjectScope Bypass', function () {
    it('can bypass scope with withoutGlobalScope');
    it('can bypass all scopes with withoutGlobalScopes');
});
```

### Key Tests to Write

**Member Scope Tests**:
```php
it('filters Family queries to member project', function () {
    $member = memberFromUniverse(1);
    $project1Families = familiesFromProject(1)->pluck('id');

    actingAs($member);

    $queriedFamilies = Family::all()->pluck('id');

    expect($queriedFamilies->sort()->values())
        ->toEqual($project1Families->sort()->values());
});

it('filters Member queries to member project', function () {
    $member = memberFromUniverse(1);

    actingAs($member);

    $members = Member::all();

    assertScopedToProject($members, 1);
});

it('filters Unit queries to member project', function () {
    $member = memberFromUniverse(1);

    actingAs($member);

    $units = Unit::all();

    assertScopedToProject($units, 1);
});

it('returns null when finding other project family', function () {
    $member = memberFromUniverse(1); // Project 1
    $otherFamily = familyFromUniverse(2); // Project 2

    actingAs($member);

    $found = Family::find($otherFamily->id);

    expect($found)->toBeNull();
});
```

**Admin Scope Tests**:
```php
it('filters to single managed project', function () {
    $admin = adminFromUniverse(1); // Manages only Project 1

    actingAs($admin);

    $families = Family::all();

    assertScopedToProject($families, 1);
});

it('filters to all managed projects for multi-project admin', function () {
    $admin = adminWithMultipleProjects(); // Manages 1 & 2

    actingAs($admin);

    $families = Family::all();
    $projectIds = $families->pluck('project_id')->unique()->sort()->values();

    expect($projectIds)->toEqual(collect([1, 2]));
});

it('excludes unmanaged projects', function () {
    $admin = adminFromUniverse(1); // Manages only 1, not 2
    $project2Family = familyFromUniverse(2);

    actingAs($admin);

    expect(Family::all()->pluck('id'))
        ->not->toContain($project2Family->id);
});
```

**Superadmin Scope Tests**:
```php
it('bypasses scope for superadmin', function () {
    $superadmin = superadminFromUniverse();

    actingAs($superadmin);

    $allProjects = Project::count();
    $familyProjects = Family::all()->pluck('project_id')->unique()->count();

    // Should see families from all projects
    expect($familyProjects)->toBe($allProjects);
});

it('superadmin can find any family', function () {
    $superadmin = superadminFromUniverse();
    $family1 = familyFromUniverse(1);
    $family2 = familyFromUniverse(2);

    actingAs($superadmin);

    expect(Family::find($family1->id))->not->toBeNull();
    expect(Family::find($family2->id))->not->toBeNull();
});
```

**Scope Bypass Tests**:
```php
it('can bypass scope with withoutGlobalScope', function () {
    $member = memberFromUniverse(1);
    $project2Family = familyFromUniverse(2);

    actingAs($member);

    // Without bypass - null
    expect(Family::find($project2Family->id))->toBeNull();

    // With bypass - found
    $found = Family::withoutGlobalScope('project')->find($project2Family->id);
    expect($found)->not->toBeNull();
    expect($found->id)->toBe($project2Family->id);
});
```

---

## Test Execution Environment

### Docker Integration

**Running in Container**:
```bash
./mtav test           # Executes: docker compose exec php php artisan test
```

**Benefits**:
- Isolated environment
- Consistent PHP version
- Same environment as production
- Database available

### Database State

**Before Suite**: Universe loaded from SQL
**Before Each Test**: Transaction started
**After Each Test**: Transaction rolled back
**After Suite**: Database state unchanged

**Configuration** (`phpunit.xml`):
```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="mtav_test"/>
```

---

## Summary

**Universe Fixture** = Comprehensive, immutable test data
**Transaction Rollback** = Fast, isolated test execution
**2-Line Ideal** = Simple, focused tests
**Scope Testing** = Verify global scope filters correctly
**Helper Functions** = Easy access to universe data
**Test Organization** = Clear structure, easy navigation
