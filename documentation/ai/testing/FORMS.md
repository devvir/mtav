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

**Phase 1: Form Generation** ✅
- Form generation tests (`CreateFormTest.php`, `UpdateFormTest.php`)
- Field specs validation
- Project scoping
- Hidden fields
- Role-based forms

**Phase 2: Form Submission** ✅
- CREATE form submission tests (`CreateFormSubmissionTest.php`)
- UPDATE form submission tests (`UpdateFormSubmissionTest.php`)
- Empty/default submissions
- Valid data submissions
- Validation error handling
- Security/malicious data testing

### Test Helpers

**Form Submission Helpers** (`tests/Helpers/formService.php`):

```php
// Extract form specs from response
extractFormSpecs(TestResponse $response): array

// Extract complete form data (specs, action, etc)
extractFormData(TestResponse $response): array

// Generate "empty" form data (as-received values)
generateEmptyFormData(array $specs): array

// Generate valid form data from specs
generateValidFormData(array $specs, array $overrides = []): array

// Generate invalid form data for testing validation
generateInvalidFormData(array $specs, string $invalidField, string $invalidationType): array
```

**Data Providers** (`tests/DataProviders/FormSubmissionProvider.php`):
- `createEmptySubmissions()` - Empty form submissions for all entities
- `updateDefaultSubmissions()` - Update without changes for all entities
- `createValidSubmissions()` - Valid CREATE data for all entities
- `updateValidSubmissions()` - Valid UPDATE data for all entities
- `createInvalidSubmissions()` - Invalid field test cases
- `createMaliciousSubmissions()` - Security test cases

### Form Submission Test Coverage

**CREATE Tests:**
1. ✅ Empty submissions (rejects when required fields missing)
2. ✅ Valid submissions with predefined data
3. ✅ Valid submissions with spec-generated data
4. ✅ Invalid field submissions (too short, too long, wrong format, etc.)
5. ✅ Malicious submissions (unauthorized project access, etc.)

**UPDATE Tests:**
1. ✅ Default submissions (no changes - entity unchanged)
2. ✅ Valid updates with specific changes
3. ✅ Valid updates with spec-generated data
4. ✅ Invalid field updates
5. ✅ Malicious updates (unauthorized data)

**Covered Entities:**
- Admin (create/update)
- Family (create/update)
- Member (create/update)
- Unit (create/update)
- UnitType (create/update)
- Event (create/update)

### Completed: Form Submission Tests (Phase 2)

Form submission tests have been implemented using the specs-as-source-of-truth approach. All required test scenarios are covered with helpers and data providers for comprehensive validation of form submission logic.

### Pending: Future Phases
- Phase 3: Frontend component rendering (smoke tests)
- Phase 4: E2E validation/edge cases (future)
- Phase 3: Frontend component rendering (smoke tests)
- Phase 4: E2E validation/edge cases (future)

## Configuration

**File**: `config/forms.php`
- Namespace definitions (models, controllers, requests)
- Custom option labels per model
- Default label strategy

## Key Principle

**Any change to FormRequest validation rules automatically propagates to form generation.** No manual form updates needed.

---

## Form Submission Testing Philosophy

Form submission tests follow a **specs-as-source-of-truth** approach:

1. **Extract specs** from the form endpoint
2. **Generate test data** based on specs (respecting types, constraints, options)
3. **Submit to the action route** defined in specs
4. **Validate results** (HTTP response + database side effects)

This ensures:
- Tests are resilient to validation rule changes
- Form structure and submission logic stay in sync
- No duplication of validation logic in tests
- Comprehensive coverage through data providers

**Key Testing Scenarios:**

| Scenario | Purpose | Expected Result |
|----------|---------|-----------------|
| Empty submission | Test required field validation | Validation errors |
| Valid submission | Test happy path | Success + DB record created/updated |
| Invalid fields | Test constraint validation | Specific field errors |
| Malicious data | Test security/authorization | Authorization errors |
| Default update | Test idempotent updates | No changes to entity |

---

*Last updated: 6 December 2025*
