# FormService Architecture

**See Also**: `PHILOSOPHY.md` for general testing patterns.

## Overview

FormService auto-generates frontend form specifications from Laravel FormRequest validation rules. Single source of truth: backend validation rules define both validation and form structure.

## Core Flow

```
FormRequest (rules)
  ↓
FormService::make(Model, FormType)
  ↓
DataProvider (resolves FormRequest, builds action/title)
  ↓
Specs (parses rules → Rule objects)
  ↓
SpecFactory (Input or Select?)
  ↓
SpecInput / SpecSelect (generate field specs)
  ↓
Frontend receives form structure
```

## Output Structure

```php
[
    'type' => 'create' | 'update',
    'entity' => 'admin' | 'family' | ...,
    'action' => ['route' => '...', 'params' => ...],
    'title' => 'Create Entity' (localized),
    'specs' => [
        'field_name' => [
            'element' => 'input' | 'select',
            'label' => 'Human Readable',
            'required' => true | false,

            // Inputs:
            'type' => 'text' | 'email' | 'number' | ...,
            'min' => ..., 'max' => ...,
            'value' => mixed,

            // Selects:
            'multiple' => true | false,
            'options' => [...],
            'selected' => mixed,
            'hidden' => true,          // For auto-hidden project_id
            'filteredBy' => 'project_id', // For project-scoped
        ],
    ],
]
```

## Smart Features

### Project Scoping
Use `BelongsToProject` rule for grouped options:
```php
'unit_type_id' => [
    'required',
    new BelongsToProject(UnitType::class, 'project_id'),
]
```
Generates: `[project_id => [unit_type_id => label, ...]]`

### Auto-Hidden Fields
`project_id` automatically hidden when user has only one project (still present for validation, just not displayed).

### Enum Handling
```php
'type' => ['required', Rule::enum(EventType::class)]
```
Auto-generates select with enum cases, uses `Enum::label()` if available.

### Relationship Inference
Foreign keys (`_id`, `_ids`) automatically:
- Infer model class (`unit_type_id` → `UnitType`)
- Populate options from related model
- Determine if multi-select (for `_ids`)

### Custom Labels
Auto-generated from field names:
- `snake_case` → `Title Case`
- Remove `is_` prefix (booleans)
- Remove `_id`/`_ids` suffix
- Pluralize `*_ids` labels

Customize per model in `config/forms.php`:
```php
'optionLabel' => [
    User::class => fn($u) => "{$u->firstname} {$u->lastname}",
    Unit::class => 'identifier',
]
```

## Covered Entities

| Entity | Create Fields | Update Fields | Notes |
|--------|--------------|---------------|-------|
| Admin | email, firstname, lastname, project_ids | email, firstname, lastname | project_ids only on create |
| Project | name, description, organization, admins | name, description, organization | Superadmin only |
| Family | name, project_id, unit_type_id | Same + values | project_id hidden in project scope |
| Member | email, firstname, lastname, project_id, family_id | email, firstname, lastname | family_id filtered by project |
| Unit | identifier, unit_type_id | identifier, project_id, unit_type_id | Project-scoped |
| UnitType | name, description | Same + values | Simple, no relations |
| Event | title, description, type, location, start_date, end_date, is_published, project_id | Same + values | Lottery: limited fields |

## Test Helpers

**`assertFormGeneration()`** (`tests/Helpers/formService.php`):
```php
assertFormGeneration(
    $response,              // TestResponse from controller
    FormType::CREATE,       // or UPDATE
    'admin',               // entity name
    specs: [...]           // expected field specs
);
```

Asserts:
- Correct Inertia component
- Form type/entity/action/title match
- Specs match exactly

## Usage Pattern

### Backend (Controller)
```php
public function create(): Response {
    return Inertia::render('Things/Create', [
        'form' => FormService::make(Thing::class, FormType::CREATE),
    ]);
}
```

### Frontend (Vue)
```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

interface Props {
    form: FormData;
}

const props = defineProps<Props>();
const form = useForm(/* values from props.form */);

function submit() {
    form.post(route(props.form.action.route, props.form.action.params));
}
</script>
```

## Testing Strategy

### Existing Coverage
- ✅ Form generation tests (`CreateFormTest.php`, `UpdateFormTest.php`)
- ✅ Field specs validation
- ✅ Project scoping
- ✅ Hidden fields
- ✅ Role-based forms

### Pending (see `tests/TODO.md`)
- Phase 1: Backend form submission (happy path)
- Phase 2: Frontend component rendering (smoke tests)
- Phase 3: E2E validation/edge cases (future)

## Configuration

**File**: `config/forms.php`
- Namespace definitions (models, controllers, requests)
- Custom option labels per model
- Default label strategy

## Key Principle

**Any change to FormRequest validation rules automatically propagates to form generation.** No manual form updates needed.

---

*Last updated: 2 December 2025*
