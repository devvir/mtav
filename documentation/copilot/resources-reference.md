# API Resources Reference

**Last Updated:** 2025-10-29

This document provides a complete overview of all API Resource classes in the MTAV system. Resources transform Eloquent models into JSON responses for the frontend.

## Table of Contents

- [Resource Architecture](#resource-architecture)
- [Resource Classes](#resource-classes)
- [Shared Traits](#shared-traits)
- [Common Patterns](#common-patterns)
- [Resource Relationships](#resource-relationships)

---

## Resource Architecture

**Location:** `app/Http/Resources/`

**Purpose:** Transform Eloquent models into consistent JSON API responses for Inertia.js frontend.

**Laravel Package:** `laravel-resource-tools` (custom package by devvir)

- Provides `ResourceSubsets` trait for conditional field inclusion
- Provides `WithResourceAbilities` trait for authorization data

**Key Principles:**

1. Resources handle data transformation and formatting
2. Resources determine which relationships to include
3. Resources provide authorization metadata (via `WithResourceAbilities`)
4. Resources use `whenLoaded()` and `whenCounted()` to avoid N+1 queries

---

## Resource Classes

### UserResource (Base Class)

**File:** `app/Http/Resources/UserResource.php`

**Purpose:** Base resource for all user types (Admin, Member). Provides common user fields.

**Traits:**

- `ResourceSubsets` - Conditional field inclusion
- `WithResourceAbilities` - Authorization metadata

**Fields:**

| Field           | Type   | Source                                  | Notes                                                           |
| --------------- | ------ | --------------------------------------- | --------------------------------------------------------------- |
| `id`            | int    | `$this->id`                             |                                                                 |
| `email`         | string | `$this->email`                          |                                                                 |
| `phone`         | string | `$this->phone`                          | Empty string if null                                            |
| `firstname`     | string | `$this->firstname`                      | Empty string if null                                            |
| `lastname`      | string | `$this->lastname`                       | Empty string if null                                            |
| `name`          | string | Computed                                | `firstname + lastname` trimmed                                  |
| `avatar`        | string | Computed                                | Avatar URL (see below)                                          |
| `is_verified`   | bool   | `$this->verified_at`                    | Cast to bool                                                    |
| `is_admin`      | bool   | `$this->isAdmin()`                      |                                                                 |
| `is_superadmin` | bool   | `$this->isSuperadmin()`                 |                                                                 |
| `legal_id`      | string | `$this->legal_id`                       | Empty string if null<br>⚠️ TODO: Review if should be admin-only |
| `created_at`    | string | `$this->created_at->toDateTimeString()` |                                                                 |
| `created_ago`   | string | `$this->created_at->diffForHumans()`    | Human-readable time                                             |

**Relationships:**

| Relationship | Condition                | Format             |
| ------------ | ------------------------ | ------------------ |
| `projects`   | `whenLoaded('projects')` | Project collection |

**Avatar Resolution:**

Uses placeholder avatar service based on email:

```php
private function resolveAvatar(): string
{
    return $this->avatar
        ?? "https://i.pravatar.cc/64?u={$this->email}";
}
```

**Commented Alternatives:**

- Letter avatars: `https://avi.avris.it/letter-64/{encodedName}.png`
- UI Avatars: `https://ui-avatars.com/api/?name={urlEncodedName}&background=random&rounded=true`

---

### AdminResource

**File:** `app/Http/Resources/AdminResource.php`

**Extends:** `UserResource`

**Purpose:** Resource for Admin users.

**Traits:**

- Inherits: `ResourceSubsets`, `WithResourceAbilities`
- Re-declares: Same traits (for clarity/IDE support)

**Fields:**

- All fields from `UserResource`
- Space reserved for Admin-specific fields (currently none)

**Structure:**

```php
public function toArray(Request $request): array
{
    $base = parent::toArray($request);

    return [
        ...$base,
        // Add here Admin-specific resource fields
    ];
}
```

**Usage:**

- Used in `AdminController` responses
- Automatically includes authorization abilities via `WithResourceAbilities`
- Inherits all user fields and relationships

---

### MemberResource

**File:** `app/Http/Resources/MemberResource.php`

**Extends:** `UserResource`

**Purpose:** Resource for Member users.

**Traits:**

- Inherits: `ResourceSubsets`, `WithResourceAbilities`
- Re-declares: Same traits (for clarity/IDE support)

**Fields:**

- All fields from `UserResource`

**Relationships (in addition to UserResource):**

| Relationship | Condition                | Format               | Notes                                                  |
| ------------ | ------------------------ | -------------------- | ------------------------------------------------------ |
| `project`    | `whenLoaded('projects')` | First project object | Members belong to one project via family               |
| `family`     | `whenLoaded('family')`   | Family object        | Defaults to `['id' => $this->family_id]` if not loaded |

**Structure:**

```php
public function toArray(Request $request): array
{
    $base = parent::toArray($request);

    return [
        ...$base,
        'project' => $this->whenLoaded('projects', fn () => $this->projects->first()),
        'family' => $this->whenLoaded('family', default: ['id' => $this->family_id]),
    ];
}
```

**Important:**

- `project` is extracted as first item from `projects` relationship (members have one project via family)
- `family` provides fallback with just ID if relationship not loaded (avoids extra query)

---

### ProjectResource

**File:** `app/Http/Resources/ProjectResource.php`

**Purpose:** Resource for Project model.

**Traits:**

- `WithResourceAbilities` - Authorization metadata

**Note:** Does NOT use `ResourceSubsets` (not needed for projects).

**Fields:**

| Field         | Type   | Source               | Notes                   |
| ------------- | ------ | -------------------- | ----------------------- |
| `id`          | int    | `$this->id`          |                         |
| `name`        | string | `$this->name`        |                         |
| `description` | string | `$this->description` |                         |
| `active`      | bool   | `$this->active`      |                         |
| `created_at`  | Carbon | `$this->created_at`  | Not converted to string |

**Relationships:**

| Relationship     | Type       | Condition                 | Default/Fallback                                    |
| ---------------- | ---------- | ------------------------- | --------------------------------------------------- |
| `admins`         | Collection | `whenLoaded('admins')`    | -                                                   |
| `admins_count`   | int        | `whenCounted('admins')`   | Falls back to `$this->admins?->count()` if loaded   |
| `members`        | Collection | `whenLoaded('members')`   | -                                                   |
| `members_count`  | int        | `whenCounted('members')`  | Falls back to `$this->members?->count()` if loaded  |
| `families`       | Collection | `whenLoaded('families')`  | -                                                   |
| `families_count` | int        | `whenCounted('families')` | Falls back to `$this->families?->count()` if loaded |

**Count Pattern:**

```php
'admins_count' => $this->whenCounted(
    'admins',
    default: fn () => $this->whenLoaded('admins', fn () => $this->admins?->count())
),
```

This pattern:

1. First checks if count was explicitly loaded via `withCount()`
2. Falls back to counting loaded relationship if available
3. Avoids extra queries while supporting both patterns

---

### FamilyResource

**File:** `app/Http/Resources/FamilyResource.php`

**Purpose:** Resource for Family model.

**Traits:**

- `ResourceSubsets` - Conditional field inclusion
- `WithResourceAbilities` - Authorization metadata

**Fields:**

| Field         | Type   | Source                                  | Notes                  |
| ------------- | ------ | --------------------------------------- | ---------------------- |
| `id`          | int    | `$this->id`                             |                        |
| `name`        | string | `$this->name`                           |                        |
| `avatar`      | string | Computed                                | Avatar URL (see below) |
| `created_at`  | string | `$this->created_at->toDateTimeString()` |                        |
| `created_ago` | string | `$this->created_at->diffForHumans()`    | Human-readable time    |

**Relationships:**

| Relationship | Type       | Condition                | Default/Fallback                |
| ------------ | ---------- | ------------------------ | ------------------------------- |
| `members`    | Collection | `whenLoaded('members')`  | -                               |
| `project`    | Object     | `whenLoaded('project')`  | `['id' => $this->project_id]`   |
| `unit_type`  | Object     | `whenLoaded('unitType')` | `['id' => $this->unit_type_id]` |

**Avatar Resolution:**

Uses Dicebear identicon generator based on family name:

```php
private function resolveAvatar(): string
{
    return $this->avatar
        ?? "https://api.dicebear.com/9.x/identicon/svg?seed=={$this->name}";
}
```

**Commented Alternatives:**

- Pravatar: `https://i.pravatar.cc/64?u={$this->name}`
- UI Avatars: `https://ui-avatars.com/api/?name={urlEncodedName}&background=random`

**Fallback Pattern:**

- `project` and `unit_type` provide minimal data (`['id' => ...]`) if relationship not loaded
- Avoids N+1 queries while ensuring frontend always has the foreign key

---

### UnitResource

**File:** `app/Http/Resources/UnitResource.php`

**Purpose:** Resource for Unit model.

**Traits:**

- `ResourceSubsets` - Conditional field inclusion
- `WithResourceAbilities` - Authorization metadata

**Fields:**

| Field        | Type         | Source                                   | Notes                         |
| ------------ | ------------ | ---------------------------------------- | ----------------------------- |
| `id`         | int          | `$this->id`                              |                               |
| `name`       | string       | `$this->name`                            | Computed from number and type |
| `number`     | string       | `$this->number`                          | Unit number                   |
| `created_at` | string       | `$this->created_at->toDateTimeString()`  |                               |
| `deleted_at` | string\|null | `$this->deleted_at?->toDateTimeString()` | Only if soft-deleted          |

**Relationships:**

| Relationship | Type   | Condition               | Default/Fallback                |
| ------------ | ------ | ----------------------- | ------------------------------- |
| `type`       | Object | `whenLoaded('type')`    | `['id' => $this->unit_type_id]` |
| `project`    | Object | `whenLoaded('project')` | `['id' => $this->project_id]`   |
| `family`     | Object | `whenLoaded('family')`  | `['id' => $this->family_id]`    |

**Note:**

- Relationship name is `type` in resource, but model uses `unitType()` relationship
- ⚠️ TODO: Rename `unit_type` to `type` throughout (model, controller, TypeScript types)
- All relationships use fallback pattern with just ID

---

### UnitTypeResource

**File:** `app/Http/Resources/UnitTypeResource.php`

**Purpose:** Resource for UnitType model.

**Traits:**

- `ResourceSubsets` - Conditional field inclusion
- `WithResourceAbilities` - Authorization metadata

**Fields:**

| Field         | Type   | Source                                  | Notes                                  |
| ------------- | ------ | --------------------------------------- | -------------------------------------- |
| `id`          | int    | `$this->id`                             |                                        |
| `name`        | string | `$this->name`                           | Type name (e.g., "Apartment", "House") |
| `description` | string | `$this->description`                    |                                        |
| `created_at`  | string | `$this->created_at->toDateTimeString()` |                                        |

**Relationships:**

| Relationship     | Type   | Condition                 | Default/Fallback                          |
| ---------------- | ------ | ------------------------- | ----------------------------------------- |
| `project`        | Object | `whenLoaded('project')`   | `['id' => $this->project_id]`             |
| `units_count`    | int    | `whenCounted('units')`    | Falls back to `$this->units?->count()`    |
| `families_count` | int    | `whenCounted('families')` | Falls back to `$this->families?->count()` |

**Count Pattern:**

- Same as `ProjectResource` - prefers `withCount()` but falls back to loaded relationship count
- ⚠️ TODO: Use `whenCounted()` instead of manual `isset` check (if any remain)

---

## Shared Traits

### ResourceSubsets

**Package:** `devvir/laravel-resource-tools`

**Purpose:** Enables conditional inclusion of resource fields based on request parameters.

**Usage Example:**

```php
// Request: /api/members?fields=id,name,email
// Response only includes: id, name, email

// Request: /api/members
// Response includes: all fields
```

**Benefits:**

- Frontend can request only needed fields
- Reduces payload size
- Improves performance for large collections

---

### WithResourceAbilities

**Package:** `devvir/laravel-resource-tools`

**Purpose:** Automatically includes authorization metadata in resource responses.

**Output Example:**

```json
{
  "id": 1,
  "name": "John Doe",
  "abilities": {
    "view": true,
    "update": true,
    "delete": false
  }
}
```

**How It Works:**

- Automatically checks all policy methods for the resource model
- Includes results in `abilities` key
- Frontend uses this for conditional UI (show/hide edit button, etc.)

**Benefits:**

- Single source of truth for authorization (policies)
- Frontend doesn't need to duplicate authorization logic
- Consistent authorization checks across API and UI

---

## Common Patterns

### 1. Relationship Loading with Fallbacks

**Pattern:** Always provide a fallback value to avoid N+1 queries.

**Example:**

```php
'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
```

**Benefits:**

- If relationship is loaded, return full object
- If not loaded, return minimal data (just the ID)
- Frontend always has the foreign key available
- Avoids triggering lazy loading (N+1 queries)

---

### 2. Count with Dual Fallback

**Pattern:** Prefer `withCount()`, but support loaded relationships.

**Example:**

```php
'admins_count' => $this->whenCounted(
    'admins',
    default: fn () => $this->whenLoaded('admins', fn () => $this->admins?->count())
),
```

**Benefits:**

- First checks for explicitly loaded count (most efficient)
- Falls back to counting loaded relationship
- Supports both eager loading patterns
- Never triggers additional queries

---

### 3. Human-Readable Timestamps

**Pattern:** Provide both machine-readable and human-readable timestamp formats.

**Example:**

```php
'created_at' => $this->created_at->toDateTimeString(),
'created_ago' => $this->created_at->diffForHumans(),
```

**Usage:**

- `created_at`: "2025-10-29 14:30:00" - for sorting, filtering, exact display
- `created_ago`: "2 hours ago" - for user-friendly relative time

---

### 4. Nullable Field Safety

**Pattern:** Provide sensible defaults for nullable fields.

**Example:**

```php
'phone' => $this->phone ?? '',
'lastname' => $this->lastname ?? '',
'deleted_at' => $this->deleted_at?->toDateTimeString(),
```

**Benefits:**

- Frontend always receives consistent data types
- Reduces null checking in frontend code
- Use empty string for optional text fields
- Use null/omit for truly optional fields (like `deleted_at`)

---

### 5. Computed Fields

**Pattern:** Calculate derived fields in the resource.

**Examples:**

**Full Name:**

```php
$fullName = trim($this->firstname.' '.($this->lastname ?? ''));
return ['name' => $fullName];
```

**Avatar URLs:**

```php
private function resolveAvatar(): string
{
    return $this->avatar ?? "https://i.pravatar.cc/64?u={$this->email}";
}
```

**Benefits:**

- Consistent computation logic across all API responses
- Keep model classes clean (no presentation logic)
- Easy to update avatar service or name format

---

## Resource Relationships

### Relationship Loading Strategy

**Eager Loading in Controllers:**

```php
public function index()
{
    $members = Member::with(['family', 'projects'])
        ->withCount(['logs'])
        ->get();

    return MemberResource::collection($members);
}

public function show(Member $member)
{
    $member->load(['family.project', 'projects.admins']);

    return new MemberResource($member);
}
```

**Key Points:**

1. Always eager load relationships that will be included in response
2. Use `with()` for full relationship loading
3. Use `withCount()` for count-only loading (more efficient)
4. Use nested loading syntax (`family.project`) for deep relationships

---

### Relationship Inclusion Decision Tree

**Include Full Relationship When:**

- Frontend needs relationship data (names, attributes, etc.)
- Relationship is small/cheap to load
- Example: `$this->whenLoaded('family')`

**Include Count Only When:**

- Frontend only needs the count
- Relationship could be large
- Example: `$this->whenCounted('members')`

**Include Fallback ID When:**

- Frontend needs foreign key but not full object
- Reduces payload size
- Example: `default: ['id' => $this->project_id]`

**Omit Entirely When:**

- Relationship not relevant to current endpoint
- Very expensive to load
- Not needed by frontend

---

## Resource Collections

### Collection Wrapping

**Default Behavior:**

```json
{
  "data": [
    { "id": 1, "name": "..." },
    { "id": 2, "name": "..." }
  ]
}
```

**Without Wrapping:**

```php
return MemberResource::collection($members)->withoutWrapping();
```

Result:

```json
[
  { "id": 1, "name": "..." },
  { "id": 2, "name": "..." }
]
```

**Pagination:**

```php
return MemberResource::collection($members->paginate(15));
```

Result:

```json
{
  "data": [...],
  "links": {...},
  "meta": {
    "current_page": 1,
    "total": 100,
    ...
  }
}
```

---

## Frontend Integration

### Inertia.js Integration

Resources are automatically transformed for Inertia responses:

```php
// Controller
return Inertia::render('Members/Index', [
    'members' => MemberResource::collection($members),
]);
```

```typescript
// Frontend (TypeScript)
interface Member {
  id: number;
  name: string;
  email: string;
  family: { id: number; name?: string };
  abilities: {
    view: boolean;
    update: boolean;
    delete: boolean;
  };
}

const props = defineProps<{
  members: Member[];
}>();
```

### Authorization in Frontend

Resources automatically include abilities:

```vue
<template>
  <div v-for="member in members" :key="member.id">
    <h3>{{ member.name }}</h3>

    <!-- Use abilities from resource -->
    <button v-if="member.abilities.update" @click="edit(member)">Edit</button>

    <button v-if="member.abilities.delete" @click="destroy(member)">Delete</button>
  </div>
</template>
```

**Benefits:**

- Authorization logic stays in Laravel policies
- Frontend gets pre-computed authorization
- Consistent authorization across backend and frontend
- No API calls needed to check permissions

---

## Performance Considerations

### N+1 Query Prevention

**Always Eager Load:**

```php
// ❌ Bad - N+1 queries
$members = Member::all();
return MemberResource::collection($members);
// Each resource will lazy-load family, causing N+1

// ✅ Good - Single query with joins
$members = Member::with(['family', 'projects'])->get();
return MemberResource::collection($members);
```

**Use Query Counts:**

```php
// ✅ Efficient - Just count, don't load all relationships
$projects = Project::withCount(['admins', 'members', 'families'])->get();
return ProjectResource::collection($projects);
```

### Conditional Loading

**Load Based on Request:**

```php
public function index(Request $request)
{
    $query = Member::query();

    // Only load family if requested
    if ($request->boolean('include_family')) {
        $query->with('family');
    }

    return MemberResource::collection($query->get());
}
```

### Resource Caching

**For Expensive Computations:**

```php
public function toArray(Request $_): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,

        // Cache expensive operation
        'stats' => Cache::remember(
            "member.{$this->id}.stats",
            3600,
            fn () => $this->computeExpensiveStats()
        ),
    ];
}
```

---

## Testing Resources

**Test Location:** `tests/Unit/Resources/` or `tests/Feature/`

**What to Test:**

1. **Field Inclusion:**

```php
test('member resource includes all expected fields', function () {
    $member = Member::factory()->create();
    $resource = new MemberResource($member);

    $data = $resource->toArray(request());

    expect($data)->toHaveKeys([
        'id', 'name', 'email', 'family', 'project', 'abilities'
    ]);
});
```

2. **Relationship Loading:**

```php
test('member resource includes loaded family', function () {
    $member = Member::factory()->create();
    $member->load('family');

    $resource = new MemberResource($member);
    $data = $resource->toArray(request());

    expect($data['family'])->toHaveKey('name');
});
```

3. **Fallback Behavior:**

```php
test('member resource provides family id when not loaded', function () {
    $member = Member::factory()->create();
    // Don't load family

    $resource = new MemberResource($member);
    $data = $resource->toArray(request());

    expect($data['family'])->toBe(['id' => $member->family_id]);
});
```

4. **Authorization Abilities:**

```php
test('member resource includes correct abilities for admin', function () {
    $admin = createAdmin();
    $member = createMember();

    actingAs($admin);

    $resource = new MemberResource($member);
    $data = $resource->toArray(request());

    expect($data['abilities'])->toMatchArray([
        'view' => true,
        'update' => true,
        'delete' => true,
    ]);
});
```

---

## Best Practices

### 1. Keep Resources Lean

**Do:**

- Transform model data to frontend format
- Include authorization metadata
- Provide relationship fallbacks
- Format timestamps and computed fields

**Don't:**

- Perform business logic
- Make external API calls
- Execute heavy computations (without caching)
- Include sensitive data without authorization checks

### 2. Use Type Hints

**Always type-hint the request:**

```php
public function toArray(Request $request): array
{
    // ...
}
```

**Benefits:**

- IDE autocompletion
- Type safety
- Clear method signature

### 3. Document Resource Shape

**Add PHPDoc or TypeScript types:**

```php
/**
 * @property int $id
 * @property string $name
 * @property array{id: int, name?: string} $family
 */
class MemberResource extends JsonResource
{
    // ...
}
```

### 4. Consistent Naming

**Follow Laravel conventions:**

- Use snake_case for JSON keys (matches DB columns)
- Use camelCase in frontend TypeScript types
- Use relationship names that match model methods

### 5. Version Resources

**For API versioning:**

```php
// app/Http/Resources/V1/MemberResource.php
namespace App\Http\Resources\V1;

// app/Http/Resources/V2/MemberResource.php
namespace App\Http\Resources\V2;
```

---

## TODO Items

From code comments and analysis:

1. **UserResource:**
   - ⚠️ Review why `legal_id` is admin-only, adjust if needed
   - Consider making it conditional based on user role

2. **UnitResource:**
   - ⚠️ Rename `unit_type` to `type` throughout system
   - Update Unit model, seeder, controller, TS types
   - Align resource relationship name with desired property name

3. **UnitTypeResource:**
   - ⚠️ Use `whenCounted()` instead of manual `isset` check (if any remain)
   - Ensure consistent count pattern across all resources

4. **General:**
   - Add Resource tests to test suite
   - Document frontend TypeScript types
   - Consider extracting avatar resolution to service class
   - Review and optimize avatar service URLs (consider CDN/local storage)

---

**Document Maintenance:**

- Update this document when adding/modifying resources
- Keep TypeScript type definitions in sync
- Document any new relationship loading patterns
- Update performance considerations based on real-world usage
