# API Resources Reference

**Last Updated:** 2025-11-13

This document provides architectural patterns and key principles for API Resources in the MTAV system.

## Key Principles

### Automatic Resource Transformation

**Critical:** Models automatically convert to their JsonResource representation when sent to the frontend.

- **Never use** `JsonResource::make()` or similar constructs
- Simply return the model/collection from controllers
- Laravel/Inertia handles the transformation automatically

### Resource Architecture

**Location:** `app/Http/Resources/`
**Naming:** `{Model}Resource.php`
**Base Class:** `JsonResource` (custom base class in same directory)

### Required Traits

**Every resource MUST use these traits:**

```php
use ResourceSubsets;        // Conditional field inclusion
use WithResourceAbilities;  // Authorization metadata
```

**Package:** `devvir/laravel-resource-tools` (custom package)

### Standard Patterns

#### Date Formatting

**All dates follow this pattern:**

```php
'created_at'  => $this->created_at,
'created_ago' => $this->created_at->diffForHumans(),
'deleted_at'  => $this->deleted_at,
```

#### Relationship Loading

**Always provide fallbacks to avoid N+1 queries:**

```php
'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
```

#### Count Fields

**Use the custom helper from base JsonResource:**

```php
'projects_count' => $this->whenCountedOrLoaded('projects'),
```

**This pattern:**
1. Prefers explicitly loaded counts (`withCount()`)
2. Falls back to counting loaded relationships
3. Never triggers additional queries

#### Nullable Fields

**Provide sensible defaults:**

```php
'phone' => $this->phone ?? '',           // Empty string for optional text
'about' => $this->about ?? null,         // Null for truly optional fields
```

### Authorization Integration

**Automatic Policy Integration:**
- `WithResourceAbilities` trait automatically includes authorization metadata
- Frontend receives `abilities` object with policy results
- No need to manually check policies in resources

**Example output:**
```json
{
  "id": 1,
  "name": "John",
  "abilities": {
    "view": true,
    "update": false,
    "delete": false
  }
}
```

### Resource Inheritance

**UserResource serves as base class:**
- `AdminResource extends UserResource`
- `MemberResource extends UserResource`

**Pattern for extending:**
```php
public function toArray(Request $request): array
{
    $base = parent::toArray($request);

    return [
        ...$base,
        // Add specific fields here
    ];
}
```

### Sensitive Data Handling

**Pattern for conditional field inclusion:**

```php
protected function sensitiveData(Request $request): array
{
    if (!$request->user()?->isAdmin()) {
        return [];
    }

    return [
        'sensitive_field' => $this->sensitive_field,
    ];
}
```

## Quick Reference

**Current Resources:**
- `UserResource` (base class)
- `AdminResource`, `MemberResource` (extend UserResource)
- `ProjectResource`, `FamilyResource`
- `UnitResource`, `UnitTypeResource`
- `EventResource`, `LogResource`

**To understand any specific resource:**
1. Check `app/Http/Resources/{Model}Resource.php`
2. Look for the standard patterns above
3. Check what traits are used
4. Examine relationship loading strategy

**Key Files:**
- Base class: `app/Http/Resources/JsonResource.php`
- Custom traits: `vendor/devvir/laravel-resource-tools/`
