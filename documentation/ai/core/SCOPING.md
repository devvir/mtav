# Project Scoping System

## Architecture Overview

MTAV implements a **two-level authorization system**:

1. **QUERY LEVEL (Primary)** - Global Scopes via `ProjectScope` trait
   - **What "exists" for this user?**
   - Automatically filters all queries to user's accessible projects
   - Defines the user's "universe" of resources
   - Guarantees: A resource may exist in DB but not "exist" for a user

2. **ACTION LEVEL (Secondary)** - Policies
   - **What can you DO with things that exist for you?**
   - Assumes resource already "exists" for user (scope filtering applied)
   - Checks: create/view/update/delete permissions
   - Simplified logic (no project-checking needed)

**Key Insight**: Global scopes make resources "invisible" if user shouldn't access them. Policies only check actions on "visible" resources.

---

## Global Scope Implementation

### ProjectScope Trait

**Applied to Models**: Family, Unit, Member, Admin, UnitType

**Mechanism**: Automatically adds `WHERE` clause to ALL queries on scoped models

**Code Pattern**:
```php
// In model that uses ProjectScope trait
protected static function booted()
{
    static::addGlobalScope('project', function (Builder $query) {
        $user = Auth::user();

        if (!$user || $user->isSuperadmin()) {
            return; // Superadmins bypass scope
        }

        if ($user->isAdmin()) {
            // Filter to managed projects only
            $managedProjectIds = $user->activeProjects()->pluck('id');
            $query->whereIn('project_id', $managedProjectIds);
        } else {
            // Members: filter to single active project
            $activeProject = $user->activeProjects()->first();
            $query->where('project_id', $activeProject->id);
        }
    });
}
```

**Effect**:
```php
// Without scope (raw SQL):
SELECT * FROM families; // Returns ALL families in database

// With scope (member logged in with project_id = 5):
SELECT * FROM families WHERE project_id = 5; // Returns ONLY their project's families

// With scope (admin managing projects 3, 5, 7):
SELECT * FROM families WHERE project_id IN (3, 5, 7); // Returns ONLY managed projects

// With scope (superadmin):
SELECT * FROM families; // Returns ALL families (scope bypassed)
```

### Bypassing Global Scopes

**When needed**: Admin operations, system maintenance, reporting

```php
// Temporarily remove scope
Family::withoutGlobalScope('project')->get();

// Remove all global scopes
Family::withoutGlobalScopes()->get();

// Check existence across all projects (superadmin)
Family::withoutGlobalScope('project')->find($id);
```

---

## Universe Definitions

### Member Universe

**Scope**: Single active project ONLY (family's project)

**Automatic Filtering**:
- All `Family::` queries → `WHERE project_id = {family.project_id}`
- All `Member::` queries → `WHERE project_id = {family.project_id}`
- All `Unit::` queries → `WHERE project_id = {family.project_id}`

**Guarantees**:
- `Family::all()` returns ONLY families in member's project
- `Member::find($other_project_member)` returns `null`
- Cannot see/access other projects even if they exist

**Context**:
- Always in single-project context
- Current project auto-set to `family.project_id`
- No project switching UI for members

### Admin Universe

**Scope**: Managed projects ONLY (where `project_user.active = true`)

**Automatic Filtering**:
- All `Family::` queries → `WHERE project_id IN ({managed_project_ids})`
- All `Member::` queries → `WHERE project_id IN ({managed_project_ids})`
- All `Admin::` queries → `WHERE project_id IN ({managed_project_ids})`

**Guarantees**:
- `Family::all()` returns families from ALL managed projects
- `Project::find($unmanaged_project)` returns `null`
- Cannot see/access unmanaged projects

**Context**:
- **Managing 1 project**: Single-project context (automatic)
- **Managing 2+ projects**: Multi-project context (must select project)
- Can switch between managed projects

### Superadmin Universe

**Scope**: EVERYTHING (no filtering)

**Bypass Mechanism**:
```php
// In ProjectScope trait
if ($user->isSuperadmin()) {
    return; // Don't apply scope
}
```

**Guarantees**:
- `Family::all()` returns ALL families (all projects)
- `Project::all()` returns ALL projects
- Full database visibility

**Context**:
- Starts in multi-project context
- Can select any project (not limited to managed)

---

## Current Project State

### state('project') - NOT Standard Session

**Purpose**: Track which single project user is currently viewing/managing

**Auto-Selection on Login**:

1. **Members**:
   - Current project auto-set to `family.project_id`
   - No manual switching possible

2. **Admin managing 1 project**:
   - Current project auto-set to that project
   - Landing: Dashboard (single-project context)

3. **Admin managing 2+ projects**:
   - No current project (multi-project context)
   - Landing: Projects index
   - Must select project to enter single-project context

4. **Superadmins**:
   - No current project (multi-project context)
   - Landing: Projects index
   - Can select any project

### Multi-Project Context

**Available Routes**:
- `projects.index` - List all accessible projects
- `members.index` - ALL members across ALL accessible projects

**Hidden Routes** (require single-project):
- `dashboard` - Project-specific dashboard
- `gallery` - Project-specific media gallery

**Navigation**:
- Projects link visible
- Members link visible (shows all members)
- Dashboard, Gallery links hidden

### Single-Project Context

**Set via**: `Project::current()` or `state('project')`

**Effect on Indexes**:
- All indexes additionally filtered to current project
- This is EXTRA filtering on top of global scope

**Available Routes**:
- All multi-project routes PLUS
- `dashboard` - Current project dashboard
- `gallery` - Current project gallery
- `families.index`, `units.index`, etc. - Filtered to current project

---

## Authorization Matrix

**Legend**:
- ✅ = Allowed
- ❌ = Denied (403)
- 🔍 = Implicit filtering (global scope)

### Project Operations

| Action      | Superadmin | Admin (managed) | Admin (other) | Member  |
| ----------- | ---------- | --------------- | ------------- | ------- |
| **Scope** 🔍 | All        | Managed only    | N/A           | N/A     |
| **viewAny** | ✅ Always  | ✅ If manages 2+ | ❌ Can't query | ❌ 403  |
| **view**    | ✅ Always  | ✅ Any (scoped)  | ❌ Can't query | ❌ 403  |
| **create**  | ✅ Always  | ❌ 403          | ❌ 403        | ❌ 403  |
| **update**  | ✅ Always  | ✅ Any (scoped)  | ❌ Can't query | ❌ 403  |
| **delete**  | ✅ Always  | ✅ Any (scoped)  | ❌ Can't query | ❌ 403  |

**Note**: "Can't query" means global scope returns `null` - policy never reached.

### Family Operations

| Action      | Superadmin | Admin          | Member (own)    | Member (other) |
| ----------- | ---------- | -------------- | --------------- | -------------- |
| **Scope** 🔍 | All        | Managed only   | Own project     | Own project    |
| **viewAny** | ✅ Always  | ✅ Any (scoped) | ✅ Any (scoped) | ✅ Any (scoped) |
| **view**    | ✅ Always  | ✅ Any (scoped) | ✅ Any (scoped) | ✅ Any (scoped) |
| **create**  | ✅ Always  | ✅ Any          | ❌ 403          | ❌ 403         |
| **update**  | ✅ Always  | ✅ Any (scoped) | ✅ Own family   | ❌ Can't query |
| **delete**  | ✅ Always  | ✅ Any (scoped) | ❌ 403          | ❌ 403         |

### Member Operations

| Action      | Superadmin  | Admin          | Member (self)   | Member (other) |
| ----------- | ----------- | -------------- | --------------- | -------------- |
| **Scope** 🔍 | All         | Managed only   | Own project     | Own project    |
| **viewAny** | ✅ Always   | ✅ Any (scoped) | ✅ Any (scoped) | ✅ Any (scoped) |
| **view**    | ✅ Always   | ✅ Any (scoped) | ✅ Any (scoped) | ✅ Any (scoped) |
| **create**  | ✅ Anywhere | ✅ Any          | ✅ Invite       | ❌ Can't query |
| **update**  | ✅ Always   | ✅ Any (scoped) | ✅ Self only    | ❌ Can't query |
| **delete**  | ✅ Always   | ✅ Any (scoped) | ✅ Self only    | ❌ Can't query |

### Admin Operations

| Action      | Superadmin  | Admin (self) | Admin (other)  |
| ----------- | ----------- | ------------ | -------------- |
| **Scope** 🔍 | All         | Managed only | Managed only   |
| **viewAny** | ✅ Always   | ✅ Any       | ✅ Any (scoped) |
| **view**    | ✅ Always   | ✅ Any       | ✅ Any (scoped) |
| **create**  | ✅ Anywhere | ✅ Validated | ✅ Validated    |
| **update**  | ✅ Always   | ✅ Self only | ❌ 403         |
| **delete**  | ✅ Always   | ❌ 403       | ❌ 403         |

**Note**: Admins CAN create other admins, but can only assign them to projects they themselves manage.

---

## Policy Implementation Patterns

### Before: Without Global Scopes (Complex)

```php
public function update(User $user, Family $family): bool
{
    // Must check project access manually
    if ($user->isAdmin()) {
        $managedProjectIds = $user->activeProjects()->pluck('id');
        if (!in_array($family->project_id, $managedProjectIds)) {
            return false; // Not in admin's managed projects
        }
        return true;
    }

    // Members check own family
    return $user->family_id === $family->id;
}
```

### After: With Global Scopes (Simple)

```php
public function update(User $user, Family $family): bool
{
    // Global scope guarantees $family is in user's accessible projects
    // Just check action permission
    return $user->isAdmin() || ($user->family_id === $family->id);
}
```

**Why?**: If `$family` made it to the policy, global scope already verified access.

---

## Special Scoping Cases

### Empty Families Filter

**Additional Scope**: `Family::withMembers()`

**Purpose**: Hide empty families from member listings

**Implementation**:
```php
public function scopeWithMembers($query)
{
    return $query->whereHas('members');
}
```

**Usage**:
- **Index listing** (`families.index`): Apply `->withMembers()` filter
- **Create Member form**: DON'T apply filter (admins need to see empty families)

**Rationale**: Members don't need to see empty families (not actionable), but admins need them for member creation.

### Cross-Project Operations (Admin Only)

**Operation**: Switch family between admin's managed projects

**Validation**:
```php
// In FamilyUpdateRequest
public function rules()
{
    return [
        'project_id' => [
            'required',
            'exists:projects,id',
            new BelongsToManagedProject($this->user()), // Custom rule
        ],
        // ...
    ];
}

class BelongsToManagedProject implements Rule
{
    public function passes($attribute, $value)
    {
        return $this->user
            ->activeProjects()
            ->where('id', $value)
            ->exists();
    }
}
```

**Effect**: Admin can move family from Project A to Project B, but ONLY if they manage both.

### Superadmin Emergency Bypass

**Use Case**: Data correction, cross-project intervention

**Pattern**:
```php
// Temporarily bypass scope
$allFamilies = Family::withoutGlobalScope('project')->get();

// Fix data
$family->update(['project_id' => $correctProjectId]);

// Scope auto-applies on next query
```

---

## Testing Scopes

### Scope Isolation Tests

```php
it('members only see their project families', function () {
    $member = Member::factory()->create(); // project_id = 1
    $otherFamily = Family::factory()->create(['project_id' => 2]);

    actingAs($member);

    expect(Family::all()->pluck('id'))
        ->not->toContain($otherFamily->id);
});

it('admins only see managed project families', function () {
    $admin = Admin::factory()->create();
    $admin->projects()->attach([1, 3]); // Manages 1 and 3
    $family2 = Family::factory()->create(['project_id' => 2]);

    actingAs($admin);

    expect(Family::all()->pluck('project_id'))
        ->not->toContain(2);
});

it('superadmins see all families', function () {
    $superadmin = createSuperadmin();
    $families = Family::factory()->count(10)->create(); // Various projects

    actingAs($superadmin);

    expect(Family::all()->count())
        ->toBe(10);
});
```

### Policy Tests (Assume Scope Applied)

```php
it('admins can update any family (within scope)', function () {
    $admin = Admin::factory()->create();
    $family = Family::factory()->create(['project_id' => 1]);
    $admin->projects()->attach(1);

    actingAs($admin);

    // Scope ensures we can only query family (already filtered)
    // Policy just checks if admins can update
    expect($admin->can('update', $family))->toBeTrue();
});

it('members can update own family only', function () {
    $member = Member::factory()->create();
    $ownFamily = $member->family;
    $otherFamily = Family::factory()->create(['project_id' => $member->family->project_id]);

    actingAs($member);

    expect($member->can('update', $ownFamily))->toBeTrue();
    expect($member->can('update', $otherFamily))->toBeFalse();
});
```

---

## Common Gotchas

### 1. Forgetting Scope Bypass in Superadmin Operations

❌ **Wrong**:
```php
// Superadmin trying to see all families
$families = Family::all(); // Still applies scope if not bypassed in trait!
```

✅ **Correct**:
```php
// ProjectScope trait checks isSuperadmin() and returns early
// No code needed - trait handles it
$families = Family::all(); // Returns ALL families for superadmins
```

### 2. Manual Project Checks in Policies

❌ **Redundant**:
```php
public function update(User $user, Family $family): bool
{
    // DON'T do this - scope already filtered
    if (!$user->activeProjects()->contains($family->project_id)) {
        return false;
    }

    return $user->isAdmin();
}
```

✅ **Simplified**:
```php
public function update(User $user, Family $family): bool
{
    // Scope guarantees access - just check action
    return $user->isAdmin();
}
```

### 3. Assuming Null Return = 404

❌ **Wrong Interpretation**:
```php
$family = Family::find($id); // Returns null
// Assume "family doesn't exist" → 404
```

✅ **Correct Interpretation**:
```php
$family = Family::find($id); // Returns null
// Family exists but scope filtered it out → 403 (not 404)
// Or check with withoutGlobalScope to differentiate:
$reallyExists = Family::withoutGlobalScope('project')->find($id);
if (!$reallyExists) {
    return response()->json(['error' => 'Not found'], 404);
}
return response()->json(['error' => 'Forbidden'], 403);
```

---

## Summary

**Global Scopes = Universe Definition**
- Automatically filter queries to user's accessible projects
- Make unauthorized resources "invisible"
- Simplify policy logic

**Policies = Action Permissions**
- Assume resource is accessible (scope already filtered)
- Check what user can DO, not what they can SEE
- Simplified, focused authorization logic

**Together = Two-Level Security**
- First level: Can you see it? (Scope)
- Second level: Can you do X with it? (Policy)
- Clean separation of concerns
