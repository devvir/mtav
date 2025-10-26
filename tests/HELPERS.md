# MTAV Test Suite - Helpers & CRUD Tests

## 🛠️ Test Helpers (DRY Principle)

All test helpers are loaded automatically from `tests/Helpers/` directory.

### User Helpers (`tests/Helpers/UserHelpers.php`)

```php
// Create users with specific roles
createSuperAdmin($attributes = [])      // Creates superadmin (id 999 by default)
createAdmin($attributes = [])           // Creates regular admin
createMember($attributes = [])          // Creates member

// Create users with relationships
createAdminWithProjects($projects, $attributes = [])
createMemberInProject($project, $family = null, $attributes = [])
```

**Example Usage:**

```php
it('allows superadmin to do anything', function () {
    $superadmin = createSuperAdmin();
    $project = createProject();

    expect($superadmin->can('update', $project))->toBeTrue();
});
```

### Project Helpers (`tests/Helpers/ProjectHelpers.php`)

```php
createProject($attributes = [])
createProjects($count = 3)
createProjectWithAdmin($admin = null, $attributes = [])
createProjectWithFamilies($familyCount = 3, $membersPerFamily = 2)
setCurrentProject($project)  // Sets current project in session
```

**Example Usage:**

```php
it('lists only managed projects', function () {
    $admin = createAdmin();
    $managedProject = createProjectWithAdmin($admin);
    $unmanagedProject = createProject();

    // Test will show only managedProject
});
```

### Family Helpers (`tests/Helpers/FamilyHelpers.php`)

```php
createFamily($attributes = [])
createFamilyInProject($project, $attributes = [])
createFamilyWithMembers($project = null, $memberCount = 3, $attributes = [])
```

**Example Usage:**

```php
it('creates family with members', function () {
    $project = createProject();
    $family = createFamilyWithMembers($project, memberCount: 5);

    expect($family->members)->toHaveCount(5);
});
```

### Inertia Helpers (`tests/Helpers/InertiaHelpers.php`)

```php
// Assertions
assertInertiaComponent($response, 'Component/Name')
assertInertiaPaginatedData($response, 'Component', 'propName', $expectedCount)
assertInertiaHas($response, 'Component', 'prop.path')
assertInertiaWhere($response, 'Component', 'prop', $value)
assertInertiaHasError($response, 'fieldName')

// Get data from Inertia response
getInertiaProp($response, 'prop.path')

// Authenticated requests (cleaner than test()->actingAs()->get())
inertiaGet($user, $uri)
inertiaPost($user, $uri, $data = [])
inertiaPatch($user, $uri, $data = [])
inertiaDelete($user, $uri)
```

**Example Usage:**

```php
it('shows family list', function () {
    $admin = createAdmin();
    $project = createProjectWithAdmin($admin);
    setCurrentProject($project);

    createFamilyInProject($project);
    createFamilyInProject($project);

    $response = inertiaGet($admin, route('families.index'));

    assertInertiaPaginatedData($response, 'Families/Index', 'families', 2);
});

it('validates email', function () {
    $admin = createAdmin();

    $response = inertiaPost($admin, route('members.store'), [
        'email' => 'invalid-email',
    ]);

    assertInertiaHasError($response, 'email');
});
```

## 🎯 CRUD Test Coverage

### Test Files Overview

- **ProjectControllerTest.php** - 30+ tests covering project CRUD
- **FamilyControllerCrudTest.php** - 40+ tests with project scope validation
- **MemberControllerCrudTest.php** - 60+ tests with critical family/project constraints
- **UnitControllerCrudTest.php** - 25+ tests for housing units
- **AdminControllerCrudTest.php** - 35+ tests for admin management

### Positive Test Cases (What Users CAN Do)

#### Superadmins Can:

✅ View/update/delete all projects
✅ Create/update/delete families in any project
✅ Create/update/delete members in any project
✅ Create/update/delete units in any project
✅ Create/delete admins and assign to any project
✅ Bypass all project scope restrictions

#### Admins Can:

✅ View/update/delete projects they manage
✅ View list of projects (if they manage 2+)
✅ Create families in projects they manage
✅ Create members in projects they manage
✅ Create/update/delete units in projects they manage
✅ Create other admins (only assign to projects they manage)
✅ Update themselves
✅ View all families/members/units (filtered by their projects)

#### Members Can:

✅ View their project details
✅ View all families/members/units
✅ Create other members (should be restricted to own family - TODO)
✅ Update themselves
✅ Delete themselves

### Negative Test Cases (What Users CANNOT Do)

#### Admins CANNOT:

❌ Create family in project they don't manage
❌ Create member in project they don't manage
❌ Update/delete resources from unmanaged projects
❌ Assign new admins to projects they don't manage
❌ View project list if they only manage 1 project
❌ Delete themselves
❌ Update/delete other admins

#### Members CANNOT:

❌ Create families
❌ Create members in different projects (validation TODO)
❌ Create members in different families (validation TODO)
❌ Update other members
❌ Delete other members
❌ Create/update/delete units
❌ Create/update/delete admins
❌ Update/delete projects
❌ View project list

#### Critical Validations (TODO):

🔨 Member cannot set family_id to different family when creating member
🔨 Member cannot set project_id to different project when creating member
🔨 Cannot create member with family from different project than target project
🔨 Family and project fields should be hidden for members in create form

## 🔍 Test Patterns

### Pattern 1: Testing Authorization

```php
it('allows X to do Y', function () {
    $user = createSomeUser();
    $resource = createResource();

    $response = inertiaGet($user, route('resource.show', $resource));

    assertInertiaComponent($response, 'Resource/Show');
});

it('denies X from doing Y', function () {
    $user = createSomeUser();
    $resource = createResource();

    $response = inertiaGet($user, route('resource.show', $resource));

    $response->assertForbidden();
});
```

### Pattern 2: Testing Project Scope

```php
it('shows only managed projects', function () {
    $admin = createAdmin();
    $managedProject = createProjectWithAdmin($admin);
    $unmanagedProject = createProject();

    $response = inertiaGet($admin, route('projects.index'));

    $projects = getInertiaProp($response, 'projects.data');
    $projectIds = collect($projects)->pluck('id')->toArray();

    expect($projectIds)->toContain($managedProject->id)
        ->not->toContain($unmanagedProject->id);
});
```

### Pattern 3: Testing CRUD Operations

```php
describe('Resource CRUD - Create/Store', function () {
    it('allows authorized user to create resource', function () {
        $user = createAuthorizedUser();

        $response = inertiaPost($user, route('resource.store'), [
            'name' => 'Test Resource',
        ]);

        expect(Resource::where('name', 'Test Resource')->exists())->toBeTrue();
        $response->assertRedirect(route('resource.show', Resource::first()->id));
    });

    it('denies unauthorized user from creating resource', function () {
        $user = createUnauthorizedUser();

        $response = inertiaPost($user, route('resource.store'), [
            'name' => 'Hack Attempt',
        ]);

        $response->assertForbidden();
        expect(Resource::where('name', 'Hack Attempt')->exists())->toBeFalse();
    });

    it('validates required fields', function () {
        $user = createAuthorizedUser();

        $response = inertiaPost($user, route('resource.store'), [
            'name' => '', // Invalid
        ]);

        assertInertiaHasError($response, 'name');
    });
});
```

### Pattern 4: Testing Data Filtering

```php
it('lists only resources from current project', function () {
    $admin = createAdmin();
    $project = createProjectWithAdmin($admin);
    setCurrentProject($project);

    $resource1 = createResourceInProject($project);
    $resource2 = createResourceInProject($project);
    $otherResource = createResource(); // Different project

    $response = inertiaGet($admin, route('resources.index'));

    assertInertiaPaginatedData($response, 'Resources/Index', 'resources', 2);
});
```

## 🚀 Running Tests

```bash
# Run all tests
mtav artisan test

# Run specific test file
mtav artisan test tests/Feature/Controllers/MemberControllerCrudTest.php

# Run specific test
mtav artisan test --filter "allows admin to create member"

# Run only CRUD tests
mtav artisan test tests/Feature/Controllers

# Run excluding TODOs
mtav artisan test --exclude-group=todo

# Run with coverage
mtav artisan test --coverage

# Run in watch mode (TDD)
mtav artisan test --watch
```

## 📝 Writing New Tests

### 1. Use Helpers for Setup

❌ Don't:

```php
it('some test', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $project = Project::factory()->create();
    $admin->projects()->attach($project, ['active' => true]);
    // ... lots of setup
});
```

✅ Do:

```php
it('some test', function () {
    $admin = createAdmin();
    $project = createProjectWithAdmin($admin);
    // Clean and clear!
});
```

### 2. Test Both Positive and Negative Cases

Always pair positive tests with negative tests:

```php
it('allows admin to update family', function () {
    // Test successful case
});

it('denies admin from updating family in unmanaged project', function () {
    // Test failure case
});
```

### 3. Use Descriptive Test Names

❌ Don't: `it('tests create')`
✅ Do: `it('allows admin to create family in project they manage')`

### 4. Mark Incomplete Tests with ->todo()

```php
test('member can only invite to own family', function () {
    // TODO: Implement validation
})->todo();
```

## 🎓 Key Takeaways

1. **Use helpers** - They make tests shorter, clearer, and easier to maintain
2. **Test authorization** - Always test both allowed and denied scenarios
3. **Test project scope** - Ensure admins can only act on managed projects
4. **Test validation** - Cover required fields, uniqueness, format, etc.
5. **Test data filtering** - Verify users only see data they should
6. **Use descriptive names** - Test names should explain what they verify
7. **Mark TODOs** - Use `->todo()` for tests identifying missing features

Happy testing! 🚀
