# Authorization Policies Reference

**Last Updated:** 2025-10-29

This document provides a complete overview of all authorization policies in the MTAV system. Due to N+1 query optimization concerns, some authorization logic has been "outsourced" from Policy classes to FormRequest objects.

## Table of Contents

- [Policy Architecture](#policy-architecture)
- [Superadmin Bypass](#superadmin-bypass)
- [Policy Classes](#policy-classes)
- [Outsourced Policies (FormRequests)](#outsourced-policies-formrequests)
- [Authorization Summary by Model](#authorization-summary-by-model)

---

## Policy Architecture

**Location:** `app/Policies/`

All policies follow Laravel's standard authorization pattern:

- Policies are automatically discovered via naming convention (`ModelPolicy`)
- Each policy method receives the authenticated `User` and optionally the target model instance
- Methods return `bool` or can return `null` to defer to other checks

### Superadmin Bypass

**Implementation:** `app/Providers/AppServiceProvider.php` (via `Gate::before()`)

```php
Gate::before(function (User $user) {
    return $user->isSuperAdmin() ?: null;
});
```

**Impact:**

- ALL policies are bypassed for superadmins
- Returns `true` before individual policy methods run
- No need to implement superadmin logic in individual policies
- Superadmin authorization happens at framework level, not policy level

---

## Policy Classes

### AdminPolicy

**File:** `app/Policies/AdminPolicy.php`

| Method      | Authorization Logic                                                              | Notes                                                                     |
| ----------- | -------------------------------------------------------------------------------- | ------------------------------------------------------------------------- |
| `viewAny()` | Always `true`                                                                    | Anyone can view the admin list                                            |
| `view()`    | **Partial check** - Admins always pass, Members can view admins in their project | ⚠️ Full constraint for Admin-viewing-Admin enforced in `ShowAdminRequest` |
| `create()`  | Only Admins                                                                      |                                                                           |
| `update()`  | Superadmin OR self                                                               |                                                                           |
| `delete()`  | Superadmin OR self                                                               |                                                                           |
| `restore()` | Only Superadmin                                                                  |                                                                           |

**N+1 Optimization:**

- `view()` only performs partial validation to avoid N+1 queries when loading Admin collections
- For Admins viewing other Admins, the "overlapping managed projects" constraint is enforced in `ShowAdminRequest`

---

### MemberPolicy

**File:** `app/Policies/MemberPolicy.php`

| Method      | Authorization Logic | Notes                                                           |
| ----------- | ------------------- | --------------------------------------------------------------- |
| `viewAny()` | Always `true`       | Anyone can view the member list                                 |
| `view()`    | Always `true`       | ⚠️ Full constraint enforced in `ShowMemberRequest`              |
| `create()`  | Always `true`       | Anyone can create members                                       |
| `update()`  | Self OR Admin       | ⚠️ Full constraint for Admins enforced in `UpdateMemberRequest` |
| `delete()`  | Self OR Admin       | ⚠️ Full constraint for Admins enforced in `DeleteMemberRequest` |
| `restore()` | Only Admins         | ⚠️ Full constraint enforced in `RestoreMemberRequest`           |

**N+1 Optimization:**

- All methods marked with ⚠️ only perform partial validation
- Full "admin must manage member's project" constraint enforced in respective FormRequests

---

### FamilyPolicy

**File:** `app/Policies/FamilyPolicy.php`

| Method      | Authorization Logic                             | Notes |
| ----------- | ----------------------------------------------- | ----- |
| `viewAny()` | Always `true`                                   |       |
| `view()`    | Members: same project / Admins: manages project |       |
| `create()`  | Only Admins                                     |       |
| `update()`  | Own family OR Admin managing project            |       |
| `delete()`  | Only Admin managing project                     |       |
| `restore()` | Only Admin managing project                     |       |

**Implementation Details:**

- Uses `$user->asMember()` and `$user->asAdmin()` casts to access role-specific data
- Project scoping ensures Members can only see families in their project
- Admins must manage the family's project for create/update/delete/restore

---

### ProjectPolicy

**File:** `app/Policies/ProjectPolicy.php`

| Method      | Authorization Logic                        | Notes                                                    |
| ----------- | ------------------------------------------ | -------------------------------------------------------- |
| `viewAny()` | Superadmin OR (Admin managing 2+ projects) | Index only makes sense if user manages multiple projects |
| `view()`    | Only Admin managing the project            |                                                          |
| `create()`  | Always `false`                             | Only Superadmins can create (bypass via Gate)            |
| `update()`  | Only Admin managing the project            |                                                          |
| `delete()`  | Only Admin managing the project            |                                                          |
| `restore()` | Admin managing the project                 |                                                          |

**Important:**

- Members cannot perform any Project CRUD operations (view/create/update/delete/restore)
- Only Admins can view/update/delete/restore projects they manage
- Project creation is superadmin-only (returns `false` but superadmin bypasses via Gate)
- `viewAny()` prevents showing project index to single-project admins (no point if they only manage one)

---

### UnitPolicy

**File:** `app/Policies/UnitPolicy.php`

| Method      | Authorization Logic           | Notes |
| ----------- | ----------------------------- | ----- |
| `viewAny()` | Always `true`                 |       |
| `view()`    | Always `true`                 |       |
| `create()`  | Only Admins                   |       |
| `update()`  | Admin managing unit's project |       |
| `delete()`  | Admin managing unit's project |       |
| `restore()` | Admin managing unit's project |       |

**Implementation:**

- No Member-specific restrictions on viewing
- All mutations require admin managing the unit's project

---

### UnitTypePolicy

**File:** `app/Policies/UnitTypePolicy.php`

| Method      | Authorization Logic                             | Notes |
| ----------- | ----------------------------------------------- | ----- |
| `viewAny()` | Always `true`                                   |       |
| `view()`    | Admins: manages project / Members: same project |       |
| `create()`  | Only Admins                                     |       |
| `update()`  | Admin managing unit type's project              |       |
| `delete()`  | Admin managing unit type's project              |       |
| `restore()` | Admin managing unit type's project              |       |

**Implementation:**

- Members can only view unit types from their project
- All mutations require admin managing the unit type's project

---

### LogPolicy

**File:** `app/Policies/LogPolicy.php`

| Method      | Authorization Logic | Notes |
| ----------- | ------------------- | ----- |
| `viewAny()` | Always `true`       |       |
| `view()`    | Always `true`       |       |

**Important:**

- Logs are **immutable** - no create/update/delete/restore policy methods exist
- No create/update/delete/restore controller actions exist
- Logs are automatically created by the system via Eloquent model events
- Logs are permanent audit records that cannot be modified or deleted by any user

---

## Outsourced Policies (FormRequests)

Due to N+1 query concerns when loading collections with policy checks, some authorization logic has been moved to FormRequest objects. These requests perform the **full authorization check** that the Policy class only partially validates.

**Pattern:**

- Policy returns `true` or performs basic checks
- FormRequest's `prepareForValidation()` method performs the full check with proper eager loading
- Uses `OverlappingProjectsConstraint` trait for consistent validation
- Throws `abort(403)` if authorization fails

### OverlappingProjectsConstraint Trait

**File:** `app/Http/Requests/Concerns/OverlappingProjectsConstraint.php`

**Purpose:** Centralized logic for validating that authenticated user and target user have overlapping projects.

**Implementation:**

```php
trait OverlappingProjectsConstraint
{
    protected function validateOverlap(User $authUser, User $user, ?string $error = null): void
    {
        if ($authUser->isSuperAdmin() || $authUser->is($user)) {
            return;
        }

        $overlappingProjects = $authUser->projects->find(
            $user->projects()->pluck('projects.id')
        );

        if ($overlappingProjects->isEmpty()) {
            abort(403, $error ?? __('You are not allowed to perform this action this project.'));
        }
    }
}
```

**How It Works:**

- Members' projects: 0 or 1 (their active project, or none)
- Admins' projects: All projects the admin manages (0+)
- Validates that `$authUser` and `$user` share at least one project
- Automatically allows superadmins and self-access
- Provides customizable error message

**Why This Pattern:**

- User ↔ Project is many-to-many relationship
- No efficient way to check for collections without N+1 queries
- Policies do best-attempt restriction
- FormRequests handle final validation with trait

---

### ShowAdminRequest

**File:** `app/Http/Requests/ShowAdminRequest.php`

**Trait:** `OverlappingProjectsConstraint`

**Full Authorization Logic:**

- Members can see admins who manage their project
- Admins can only see admins with overlapping managed projects

**Why Outsourced:**

- Checking "overlapping projects" requires loading `$admin->projects()`
- Would cause N+1 when displaying admin collections
- Policy's `view()` allows admins through; this request enforces the constraint

**Implementation:**

```php
use OverlappingProjectsConstraint;

protected function prepareForValidation(): void
{
    $this->validateOverlap(
        $this->user(),
        $this->route('admin'),
        __('You can only view admins from projects you have access to.')
    );
}
```

---

### ShowMemberRequest

**File:** `app/Http/Requests/ShowMemberRequest.php`

**Trait:** `OverlappingProjectsConstraint`

**Full Authorization Logic:**

- Members can see other members in their project
- Admins can only see members in projects they manage

**Why Outsourced:**

- Checking "shared projects" requires loading `$member->projects()`
- Would cause N+1 when displaying member collections
- Policy's `view()` returns `true`; this request enforces the constraint

**Implementation:**

```php
use OverlappingProjectsConstraint;

protected function prepareForValidation(): void
{
    $this->validateOverlap(
        $this->user(),
        $this->route('member'),
        __('You can only view members from projects you have access to.')
    );
}
```

---

### UpdateMemberRequest

**File:** `app/Http/Requests/UpdateMemberRequest.php`

**Trait:** `OverlappingProjectsConstraint`

**Full Authorization Logic:**

- Members can update themselves (no constraint)
- Admins can only update members in projects they manage

**Why Outsourced:**

- Same N+1 concern as `ShowMemberRequest`
- Policy's `update()` allows any admin; this request enforces project constraint

**Implementation:**

```php
use OverlappingProjectsConstraint;

protected function prepareForValidation(): void
{
    $this->validateOverlap(
        $this->user(),
        $this->route('member'),
        __('You can only update members from projects you have access to.')
    );
}
```

**Note:** The trait's `validateOverlap()` automatically handles self-access check, so no special case needed.

---

### DeleteMemberRequest

**File:** `app/Http/Requests/DeleteMemberRequest.php`

**Trait:** `OverlappingProjectsConstraint`

**Full Authorization Logic:**

- Members can delete themselves (no constraint)
- Admins can only delete members in projects they manage

**Why Outsourced:**

- Same N+1 concern and logic pattern as `UpdateMemberRequest`

**Implementation:**

```php
use OverlappingProjectsConstraint;

protected function prepareForValidation(): void
{
    $this->validateOverlap(
        $this->user(),
        $this->route('member'),
        __('You can only delete members from projects you have access to.')
    );
}
```

**Note:** Self-access is automatically handled by the trait.

---

### RestoreMemberRequest

**File:** `app/Http/Requests/RestoreMemberRequest.php`

**Trait:** `OverlappingProjectsConstraint`

**Full Authorization Logic:**

- Members cannot restore Members
- Admins can only restore members in projects they manage

**Why Outsourced:**

- Same N+1 concern and logic pattern as other Member requests
- Policy's `restore()` allows any admin; this request enforces project constraint

**Implementation:**

```php
use OverlappingProjectsConstraint;

protected function prepareForValidation(): void
{
    $this->validateOverlap(
        $this->user(),
        $this->route('member'),
        __('You can only restore members from projects you have access to.')
    );
}
```

---

### ProjectScopedRequest (Abstract Base)

**File:** `app/Http/Requests/ProjectScopedRequest.php`

**Authorization Logic:**

- Context integrity: If current project is set AND project_id provided, they must match
- User authorization: User must have access to requested project

**Purpose:**

- Base class for requests that create/update project-scoped resources
- Automatically injects current project if no `project_id` provided
- Ensures project context integrity across the application

```php
public function authorize(): bool
{
    $currentProject = Project::current();
    $requestedProjectId = $this->input('project_id');

    // Context integrity check
    if ($currentProject && $requestedProjectId && (int) $requestedProjectId !== $currentProject->id) {
        return false;
    }

    // Authorization check
    return !$requestedProjectId || $this->userCanAccessProject($requestedProjectId);
}
```

---

## Authorization Summary by Model

### Admin

| Action  | Policy Class           | FormRequest      | Authorization                                                        |
| ------- | ---------------------- | ---------------- | -------------------------------------------------------------------- |
| Index   | AdminPolicy::viewAny() | -                | Anyone                                                               |
| Show    | AdminPolicy::view()    | ShowAdminRequest | Members: admin manages their project<br>Admins: overlapping projects |
| Create  | AdminPolicy::create()  | -                | Only Admins                                                          |
| Update  | AdminPolicy::update()  | -                | Superadmin or self                                                   |
| Delete  | AdminPolicy::delete()  | -                | Superadmin or self                                                   |
| Restore | AdminPolicy::restore() | -                | Only Superadmin                                                      |

### Member

| Action  | Policy Class            | FormRequest          | Authorization                 |
| ------- | ----------------------- | -------------------- | ----------------------------- |
| Index   | MemberPolicy::viewAny() | -                    | Anyone                        |
| Show    | MemberPolicy::view()    | ShowMemberRequest    | Shared projects               |
| Create  | MemberPolicy::create()  | -                    | Anyone                        |
| Update  | MemberPolicy::update()  | UpdateMemberRequest  | Self or admin manages project |
| Delete  | MemberPolicy::delete()  | DeleteMemberRequest  | Self or admin manages project |
| Restore | MemberPolicy::restore() | RestoreMemberRequest | Admin manages project         |

### Family

| Action  | Policy Class            | Authorization                                    |
| ------- | ----------------------- | ------------------------------------------------ |
| Index   | FamilyPolicy::viewAny() | Anyone                                           |
| Show    | FamilyPolicy::view()    | Members: same project<br>Admins: manages project |
| Create  | FamilyPolicy::create()  | Only Admins                                      |
| Update  | FamilyPolicy::update()  | Own family or admin manages project              |
| Delete  | FamilyPolicy::delete()  | Admin manages project                            |
| Restore | FamilyPolicy::restore() | Admin manages project                            |

### Project

| Action  | Policy Class             | Authorization                                         |
| ------- | ------------------------ | ------------------------------------- |
| Index   | ProjectPolicy::viewAny() | Superadmin or admin managing 2+ projects |
| Show    | ProjectPolicy::view()    | Admin manages project                 |
| Create  | ProjectPolicy::create()  | Only Superadmin (via Gate bypass)     |
| Update  | ProjectPolicy::update()  | Admin manages project                 |
| Delete  | ProjectPolicy::delete()  | Admin manages project                 |
| Restore | ProjectPolicy::restore() | Admin manages project                 |

### Unit

| Action  | Policy Class          | Authorization         |
| ------- | --------------------- | --------------------- |
| Index   | UnitPolicy::viewAny() | Anyone                |
| Show    | UnitPolicy::view()    | Anyone                |
| Create  | UnitPolicy::create()  | Only Admins           |
| Update  | UnitPolicy::update()  | Admin manages project |
| Delete  | UnitPolicy::delete()  | Admin manages project |
| Restore | UnitPolicy::restore() | Admin manages project |

### UnitType

| Action  | Policy Class              | Authorization                                    |
| ------- | ------------------------- | ------------------------------------------------ |
| Index   | UnitTypePolicy::viewAny() | Anyone                                           |
| Show    | UnitTypePolicy::view()    | Admins: manages project<br>Members: same project |
| Create  | UnitTypePolicy::create()  | Only Admins                                      |
| Update  | UnitTypePolicy::update()  | Admin manages project                            |
| Delete  | UnitTypePolicy::delete()  | Admin manages project                            |
| Restore | UnitTypePolicy::restore() | Admin manages project                            |

### Log

| Action  | Policy Class         | Authorization                                       |
| ------- | -------------------- | --------------------------------------------------- |
| Index   | LogPolicy::viewAny() | Anyone                                              |
| Show    | LogPolicy::view()    | Anyone                                              |
| Create  | ❌ N/A               | Auto-created by system via Eloquent events          |
| Update  | ❌ N/A               | Logs are immutable - no policy or controller action |
| Delete  | ❌ N/A               | Logs are immutable - no policy or controller action |
| Restore | ❌ N/A               | Logs are immutable - no policy or controller action |

---

## Design Patterns & Best Practices

### 1. N+1 Query Prevention with OverlappingProjectsConstraint Trait

**Problem:** Loading collections with policy checks can cause N+1 queries when policies need to load relationships.

**Solution:**

- Policy methods perform **partial** validation (basic checks)
- FormRequest uses `OverlappingProjectsConstraint` trait for **full** validation
- Trait centralizes the "overlapping projects" check logic
- Documented with `⚠️ IMPORTANT` comments in policy methods

**Pattern:**

```php
// Policy - allows any admin (partial check)
public function update(User $user, Member $member): bool
{
    return $user->is($member) || $user->isAdmin();
}

// FormRequest - uses trait for full validation
use OverlappingProjectsConstraint;

protected function prepareForValidation(): void
{
    $this->validateOverlap(
        $this->user(),
        $this->route('member'),
        __('You can only update members from projects you have access to.')
    );
}
```

**Benefits of Trait Refactoring:**

- **DRY:** Single source of truth for overlapping project validation
- **Consistency:** Same logic across Show/Update/Delete/Restore operations
- **Maintainability:** Changes to validation logic only need to be made once
- **Clarity:** Clean, declarative code in FormRequests
- **Flexibility:** Custom error messages per use case

**Trait Implementation:**

```php
protected function validateOverlap(User $authUser, User $user, ?string $error = null): void
{
    // Automatically allows superadmins and self-access
    if ($authUser->isSuperAdmin() || $authUser->is($user)) {
        return;
    }

    // Validate overlapping projects
    $overlappingProjects = $authUser->projects->find(
        $user->projects()->pluck('projects.id')
    );

    if ($overlappingProjects->isEmpty()) {
        abort(403, $error ?? __('You are not allowed to perform this action this project.'));
    }
}
```

### 2. Role-Based Model Casts

**Pattern:** Use `$user->asAdmin()` and `$user->asMember()` casts to access role-specific data.

**Benefits:**

- Type safety in policies
- Cleaner code (no manual STI checks)
- IDE autocompletion for role-specific methods

**Example:**

```php
public function view(User $user, Family $family): bool
{
    return $user->isMember()
        ? $user->asMember()->family->project_id === $family->project_id
        : $user->asAdmin()->manages($family->project_id);
}
```

### 3. Superadmin Bypass at Framework Level

**Pattern:** All superadmin authorization happens via `Gate::before()`, not in individual policies.

**Benefits:**

- DRY - no repetitive `$user->isSuperAdmin()` checks
- Consistent behavior across all policies
- Policies focus on normal user authorization logic

**Note:** Some policies still check `isSuperAdmin()` for specific business logic (e.g., AdminPolicy::update allows superadmin OR self).

### 4. Project Scoping

**Pattern:** Most resources are scoped to projects. Admins must manage the project to perform actions.

**Common Check:**

```php
$user->asAdmin()?->manages($resource->project_id)
```

**Special Cases:**

- Members use their family's project for scoping
- Admins use their managed projects collection
- Current project is tracked via session for proper context

### 5. Policy Concerns vs Runtime State

**Important Principle:** Policies should only check **authorization** (ability), not **resource state**.

**✅ Correct - Policy checks ability:**
```php
public function restore(User $user, Project $project): bool
{
    return $user->asAdmin()?->manages($project);
}
```

**❌ Incorrect - Policy checks current state:**
```php
public function restore(User $user, Project $project): bool
{
    // Don't check if soft-deletable - we know this at code time
    // Don't check if currently deleted - that's runtime state, not authorization
    return $user->asAdmin()?->manages($project)
        && $project->isSoftDeletable() && $project->deleted_at;
}
```

**Why:**
- Whether a model uses soft deletes is known at development time, not runtime
- Whether a specific instance is currently deleted is state, not permission
- The policy answers: "Can this user restore this type of resource?" not "Is this specific instance currently restorable?"
- If the resource isn't deleted now but gets deleted in a minute, the policy should still return the same answer

**State checks belong in:**
- Controllers (before calling restore)
- Request validation
- Business logic layer

---

## Testing Policies

**Test Location:** `tests/Feature/PolicyTest.php`

Policy tests should verify:

1. Superadmin bypass works for all policies
2. Role-specific authorization (Admin vs Member)
3. Project scoping constraints
4. Self-access patterns (e.g., user can update self)
5. Outsourced FormRequest authorization

**Example Test Pattern:**

```php
test('admin can only update members in projects they manage', function () {
    $admin = createAdminWithProjects(2);
    $member = createMemberInProject($admin->projects->first());
    $otherMember = createMemberInProject(Project::factory()->create());

    expect($admin->can('update', $member))->toBeTrue();
    expect($admin->can('update', $otherMember))->toBeFalse();
});
```

---

## Future Considerations

### Potential Optimizations

1. **Cache Project Memberships:** For frequently-checked "admin manages project" constraints, consider caching admin-project relationships.

2. **Batch Policy Checks:** If checking policies for large collections, consider batch loading relationships upfront.

3. **Policy Consolidation:** Review if more outsourced policies could be consolidated back into Policy classes with better eager loading strategies.

### Known Issues

- Some policy tests are currently failing (see todo list)
- Need to investigate why FamilyPolicy and ProjectPolicy tests fail for managing admins
- Middleware cast initialization issue affects policy checks in some contexts

---

**Document Maintenance:**

- Update this document when adding/modifying policies
- Add new outsourced policies to the "Outsourced Policies" section
- Keep the "Authorization Summary" table current with any policy changes
