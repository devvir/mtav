# Test Suite - Philosophy & Patterns

**Last Updated**: October 27, 2025
**Framework**: Pest PHP + Inertia.js assertions
**Database**: MySQL (separate Docker container)
**Status**: ✅ 134 passing, 64 skipped, 73 todos (271 total)

---

## 📚 Quick Reference

> **⭐ KNOWLEDGE BASE: [../documentation/knowledge-base/KNOWLEDGE_BASE.md](../documentation/knowledge-base/KNOWLEDGE_BASE.md)**
> **Single source of truth for everything MTAV - Start here!**
>
> This comprehensive document contains:
>
> **Part 1: Business Domain**
>
> - 🎯 Vision & core purpose (fair housing distribution)
> - 👥 All actors and their abilities
> - 📋 Complete authorization matrix
> - 🏗️ All entities, relationships, and business rules
> - 🎲 Unit distribution & lottery system
> - 🎨 UI/UX principles (transparency over hiding)
>
> **Part 2: Technical Architecture**
>
> - ⚙️ Complete technology stack (PHP 8.4, Laravel 12, Vue 3, Inertia)
> - 🏗️ Application architecture (MVC, STI, relationships)
> - 🐳 Docker infrastructure (dev environment, containers, services)
> - 🔨 Development workflows (Layer 1/2/3, scripts, commands)
> - 🧪 Testing infrastructure (Pest, Vitest, git hooks)
> - 📦 Build system & production images (multi-stage, registry)
> - 🚀 Deployment architecture (mtav-deploy, environments)
> - 🛠️ Troubleshooting guide (common issues, solutions)
>
> **Part 3: Development & Testing**
>
> - 🧪 TDD roadmap for remaining features
> - ❓ Open questions for clarification
>
> **Derived Guides** (from Knowledge Base):
>
> - 📘 [Member Guide](../documentation/knowledge-base/derived/member-guide.md) - For cooperative members
> - 📗 Admin Manual _(coming soon)_ - For project managers
> - 📕 Superadmin Guide _(coming soon)_ - For system administrators
> - 📙 Developer Guide _(coming soon)_ - For software developers

## �📊 Test Suite Summary

**Total Tests**: 271

- ✅ **Passing**: 129 (core functionality working)
- 🐛 **Skipped**: 12 (known bugs, documented in BUGS_AND_TODOS.md)
- 📝 **TODO**: 73 (planned features, not yet implemented)
- ❌ **Failing**: 57 (mostly one issue - authorization redirects, see BUGS_AND_TODOS.md)

## 📚 Documentation

### [📖 TEST_CATALOG.md](./TEST_CATALOG.md)

Complete catalog of all tests organized by file and describe block. Use this to understand what's being tested and get an overview of test coverage.

### [🐛 BUGS_AND_TODOS.md](./BUGS_AND_TODOS.md)

Detailed tracking of all bugs and missing features. Use this to prioritize fixes and understand why certain tests are skipped.

### [🔧 HELPERS.md](./HELPERS.md)

Guide to test helper functions and best practices. Use this when writing new tests.

## 📁 Test Structure

```
tests/
├── Unit/Models/               # 47 tests - Model relationships & methods
│   ├── UserTest.php           # 7 tests - User model conversions
│   ├── MemberTest.php         # 10 tests (3 skipped, 3 todo)
│   ├── AdminTest.php          # 8 tests (3 todo)
│   ├── FamilyTest.php         # 9 tests (1 todo, 5 todo)
│   └── ProjectTest.php        # 13 tests (1 skipped)
│
├── Feature/Auth/              # 13 tests - Laravel Breeze auth
│   ├── AuthenticationTest.php
│   ├── EmailVerificationTest.php
│   ├── PasswordConfirmationTest.php
│   └── PasswordResetTest.php
│
├── Feature/Policies/          # 34 tests - Authorization rules
│   ├── ProjectPolicyTest.php  # 10 tests (2 todo)
│   ├── FamilyPolicyTest.php   # 12 tests (4 todo)
│   └── MemberPolicyTest.php   # 12 tests (4 todo)
│
├── Feature/Controllers/       # 150+ tests - HTTP CRUD operations
│   ├── AdminControllerCrudTest.php    # 25 tests (many skipped - bugs)
│   ├── FamilyControllerCrudTest.php   # 25 tests
│   ├── FamilyControllerTest.php       # 9 tests (3 todo)
│   ├── MemberControllerCrudTest.php   # 35 tests (6 todo, some skipped)
│   ├── MemberControllerTest.php       # 11 tests (4 todo)
│   ├── ProjectControllerTest.php      # 22 tests (1 todo, some skipped)
│   └── UnitControllerCrudTest.php     # 19 tests (1 todo, some skipped)
│
├── Feature/BusinessLogic/     # 37 tests - Complex domain rules
│   ├── FamilyAtomicityTest.php        # 10 todo tests
│   ├── InvitationSystemTest.php       # 10 todo tests
│   └── ProjectScopeTest.php           # 14 tests (13 todo, 1 passing)
│
├── Feature/DashboardTest.php  # 1 test
├── Feature/Settings/          # 4 tests
│
└── Helpers/                   # Test utilities (DRY patterns)
    ├── UserHelpers.php        # createSuperAdmin, createAdmin, createMember, etc.
    ├── ProjectHelpers.php     # createProject, setCurrentProject, etc.
    ├── FamilyHelpers.php      # createFamily, createFamilyWithMembers, etc.
    └── InertiaHelpers.php     # inertiaGet/Post/Patch/Delete, assertions
```

│ ├── FamilyAtomicityTest.php (10 TODO tests) ⚠️ CRITICAL
│ ├── InvitationSystemTest.php (10 TODO tests)
│ └── ProjectScopeTest.php (14 TODO tests) ⚠️ IMPORTANT
│
└── Architecture/
├── ModelTest.php (4 arch tests)
├── PolicyTest.php (3 arch tests)
└── ControllerTest.php (3 arch tests)

````

## 🎯 Key Business Logic Gaps Identified

### 🔴 CRITICAL - Family Atomicity (Not Implemented)

**The Core Problem:** Families are the atomic unit, but there's no enforcement.

**Missing Implementations:**

1. ❌ `Family::moveToProject(Project $newProject)` - Atomically move entire family
2. ❌ `Family::leave()` - Remove entire family from project
3. ❌ Validation that prevents individual member project switches
4. ❌ Validation that `family.project_id` always matches members' active project

**Violations in Current Code:**

- `Member::switchProject()` exists but violates atomicity
- No checks prevent adding member to different project than family
- No database constraints enforce family.project_id consistency

**Test Coverage:** 10 TODO tests in `FamilyAtomicityTest.php`

---

### 🟠 HIGH PRIORITY - Project Scope Enforcement

**The Problem:** Admins should only access resources in projects they manage.

**Missing Policy Checks:**

1. ❌ Admin creating Family - doesn't validate they manage target project
2. ❌ Admin creating Member - doesn't validate project ownership
3. ❌ Admin updating/deleting - no project scope validation
4. ❌ Members should only see data from their active project

**Test Coverage:** 14 TODO tests in `ProjectScopeTest.php`

---

### 🟡 MEDIUM PRIORITY - Invitation System

**The Problem:** No visible invitation system implementation.

**Missing Features:**

1. ❌ Invitation flow (email, token, activation)
2. ❌ Member-only invites restricted to their own family
3. ❌ Email verification for new users
4. ❌ Invitation expiry

**Test Coverage:** 10 TODO tests in `InvitationSystemTest.php`

---

## 🏗️ Recommended Implementation Order

### Phase 1: Critical - Family Atomicity (Week 1-2)

```php
// Implement these methods in Family model:
public function moveToProject(Project $newProject): self
{
    DB::transaction(function () use ($newProject) {
        // 1. Remove all members from current project
        $this->members->each(fn($m) => $this->project?->removeMember($m));

        // 2. Add all members to new project
        $this->members->each(fn($m) => $newProject->addMember($m));

        // 3. Update family.project_id
        $this->project()->associate($newProject)->save();
    });

    return $this;
}

public function leave(): self
{
    DB::transaction(function () {
        $this->members->each(fn($m) => $this->project?->removeMember($m));
        $this->project()->dissociate()->save();
    });

    return $this;
}
````

**Add Validation:**

```php
// In Member model - override joinProject() to prevent atomicity violations
public function joinProject(Project|int $project): self
{
    $targetProject = model($project, Project::class);

    if ($this->family && $this->family->project_id !== $targetProject->id) {
        throw new \LogicException(
            'Cannot join project individually. Use Family::moveToProject() instead.'
        );
    }

    $targetProject->addMember($this);
    return $this;
}
```

### Phase 2: High - Project Scope Enforcement (Week 3)

```php
// Update FamilyPolicy
public function create(User $user): bool
{
    return $user->isAdmin();
}

public function store(User $user, array $data): bool
{
    if ($user->isSuperAdmin()) return true;

    $projectId = $data['project_id'];
    $project = Project::find($projectId);

    return $user->asAdmin()?->manages($project) ?? false;
}

// Similar for MemberPolicy, UnitPolicy
```

### Phase 3: Medium - Invitation System (Week 4-5)

```php
// Create Invitation model and flow
class Invitation extends Model {
    // token, email, inviter_id, family_id, project_id, expires_at
}

// Add to controllers
public function invite(InviteRequest $request) {
    $invitation = Invitation::create([
        'email' => $request->email,
        'inviter_id' => $request->user()->id,
        'family_id' => $request->user()->family_id, // For members
        'token' => Str::random(64),
        'expires_at' => now()->addDays(7),
    ]);

    Mail::to($invitation->email)->send(new InvitationEmail($invitation));
}
```

---

## 🚀 Running the Tests

```bash
# Run all tests
mtav artisan test

# Run specific test file
mtav artisan test tests/Unit/Models/FamilyTest.php

# Run only implemented tests (exclude TODOs)
mtav artisan test --exclude-group=todo

# Run with coverage
mtav artisan test --coverage

# Run in watch mode (TDD)
mtav artisan test --watch
```

---

## 📝 Pest TODO Syntax

Tests marked with `->todo()` will:

- ✅ Show as "TODO" in test output
- ✅ Not fail the test suite
- ✅ Remind you what needs to be implemented

```php
test('some feature', function () {
    // TODO: Implement this logic
})->todo();

// When you implement it, just remove ->todo()
test('some feature', function () {
    expect($result)->toBeTrue();
});
```

---

## 🎓 Key Takeaways

### What's Working Well:

- ✅ Clean Single Table Inheritance (User/Member/Admin)
- ✅ Proper use of Policies
- ✅ Many-to-many relationships with pivot
- ✅ Global scopes for Member/Admin separation
- ✅ Superadmin system via ENV config

### What Needs Attention:

- 🔴 **Family atomicity** - Most critical gap
- 🟠 **Project scope** - Security/data isolation concern
- 🟡 **Invitations** - User onboarding flow
- 🟢 **Validation** - More comprehensive request validation

---

## 📚 Additional Notes

### Architectural Tests

The architecture tests will help maintain code quality:

- Models must extend base Model
- Policies must have "Policy" suffix
- Controllers should use FormRequests
- No direct DB facade usage (except where noted)

### Test Database

All tests use `RefreshDatabase` trait (from `tests/Pest.php`):

- Database is reset before each test
- No test pollution
- Fast in-memory SQLite recommended

---

## 🤝 Next Steps

1. **Review the TODO tests** - Prioritize which business logic to implement first
2. **Run the implemented tests** - Verify existing functionality works
3. **Pick a TODO test** - Start TDD cycle:
   - Make test pass
   - Refactor
   - Remove `->todo()`
4. **Repeat** - Gradually work through the TODO list

Good luck with the implementation! 🚀
