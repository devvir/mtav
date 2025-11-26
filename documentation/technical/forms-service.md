# FormService System

## Overview

The FormService system automatically generates Vue form specifications from Laravel FormRequest validation rules, eliminating the need to manually define form schemas in the frontend.

## Architecture

```
FormService (factory)
    ↓
DataProvider (coordinates)
    ↓
Specs (generator) → Rules (parser) → Rule (individual parser)
    ↓                                      ↓
    ↓                                SpecFactory (determines type)
    ↓                                      ↓
    ↓                         ┌────────────┴────────────┐
    ↓                         ↓                         ↓
    ↓                    SpecInput                 SpecSelect
    ↓                         ↓                         ↓
    └─────────────────────────┴─────────────────────────┘
                              ↓
                        Form (presenter)
```

### Backend Components

#### 1. **FormService** (`app/Services/Form/FormService.php`)
Factory class that creates Form instances.

```php
FormService::make($model, FormType::CREATE);
FormService::make($model, FormType::UPDATE);
```

#### 2. **DataProvider** (`app/Services/Form/DataProvider.php`)
Coordinates the form generation process. Simple, generic API with only 3 public methods:

**Public API:**
- `specs(): array` - Converts FormRequest rules to frontend FormSpecs
- `formAction(): array` - Returns route and params for form submission
- `formTitle(): string` - Generates localized form title

**Key responsibilities:**
- Instantiates the appropriate FormRequest class
- Delegates to `Specs` class for spec generation
- Generates form action routes and titles

**App-specific assumptions** (to be made configurable for package extraction):
- FormRequest namespace: `App\Http\Requests\{Create|Update}{Model}Request`
- Controller namespace: `App\Http\Controllers\Resources\{Model}Controller`
- Translation keys: `general.create_entity`, `general.edit_entity`

#### 3. **Specs** (`app/Services/Form/Specs.php`)
Generates complete form specifications from FormRequest validation rules.

**Key method:**
```php
Specs::make(FormRequest $request, ?Model $model): self
```

**Responsibilities:**
- Parses validation rules from FormRequest
- Handles wildcard rules (e.g., `admins.*`)
- Merges wildcard rules into parent array fields
- Creates Rule objects for each field
- Delegates to SpecFactory for individual field specs

#### 4. **Rule** (`app/Services/Form/Lib/Rule.php`)
Parses individual Laravel validation rules into normalized format with magic getter access.

**Handles:**
- String rules: `'required|string|max:255'`
- Array rules: `['required', 'string', 'max:255']`
- Rule objects: `Rule::required()`, `Exists::class`
- Custom ValidationRule objects (e.g., `BelongsToProject`)

**Magic getter access:**
```php
$rule->type        // 'string', 'integer', 'boolean', 'array', etc.
$rule->required    // true/false
$rule->min         // numeric value or null
$rule->max         // numeric value or null
$rule->between     // [min, max] array or null
$rule->in          // array of allowed values or null
$rule->enum        // enum class name or null
$rule->exists      // model class name or null
$rule->customRules // array of custom rule instances
```

**Special detection:**
- `BelongsToProject` custom rule → Extracts model class via reflection
- Ignores unrecognized string rules (assumes they're Laravel rules not needed for form generation)

**Design principle:**
- **Fail-fast**: Throws `InvalidArgumentException` on invalid configuration
- **No defensive programming**: All parse methods assume valid structure
- **No error hiding**: Invalid code fails immediately during development

#### 5. **SpecFactory** (`app/Services/Form/Lib/SpecFactory.php`)
Factory that determines and instantiates the appropriate Spec class based on Rule analysis.

**Key logic:**
```php
SpecFactory::make(Rule $rule, ?Model $model): Spec
```

**Decision tree** (via `isSelect()` method):
- Boolean fields → SpecSelect (yes/no dropdowns)
- Enum or `in` constraints → SpecSelect
- Array type → SpecSelect (multi-select)
- `exists` rule → SpecSelect (foreign key lookups)
- `BelongsToProject` rule → SpecSelect (project-scoped)
- Everything else → SpecInput (text, numbers, dates, etc.)

#### 6. **Spec** (Abstract Base Class - `app/Services/Form/Lib/Spec.php`)
Abstract base class defining common Spec API and utilities.

**Key features:**
- Constructor extracts field name from Rule, calls `buildSpec()`
- `abstract protected function buildSpec()` - Forces implementation in subclasses
- `toArray()` and `jsonSerialize()` - Common serialization
- `generateLabel()` - Shared utility for converting field names to human-readable labels

**Label generation logic:**
- Removes `is_` prefix for boolean fields
- Removes `_id` or `_ids` suffix
- Converts snake_case to Title Case
- Pluralizes `_ids` fields

#### 7. **SpecInput** (`app/Services/Form/Lib/SpecInput.php`)
Generates input field specifications (text, number, email, etc.).

**Key logic:**
- Sets `element='input'`
- Determines HTML input type via match expression
- Applies validation constraints (min, max, required)
- Translates Laravel `between` rule to HTML `min` + `max` attributes

**Type mapping:**
```php
'integer', 'numeric' → 'number'
'email'             → 'email'
'url'               → 'url'
'date'              → 'date'
'file', 'image'     → 'file'
default             → 'text'
```

#### 8. **SpecSelect** (`app/Services/Form/Lib/SpecSelect.php`)
Generates select/dropdown field specifications with complex options logic.

**Key logic:**
- Sets `element='select'`
- Determines options from various sources (boolean, enum, database, etc.)
- Handles project-scoped options (grouped by project_id)
- Supports multi-select based on array type or relation type
- Automatically hides `project_id` for single-project users

**Options sources (in order of precedence):**
1. Boolean type → Yes/No options with translations
2. Enum constraint → Enum cases with labels
3. `in` constraint → Hardcoded value list
4. `exists` constraint → Database lookup
5. Array with wildcard rules → Database lookup via wildcard analysis
6. Field name inference → Infers model from `*_id` field name

**Project-scoped options behavior:**
- Returns options grouped by `project_id` for multi-project contexts
- Automatically adds `filteredBy: 'project_id'` to spec
- Single-project users see flat options (no grouping needed)
- `project_id` field shows only current project when one is selected

**App-specific logic** (to be extracted for package):
- `getProjectIdOptions()` - Special handling for project selection
- `getProjectScopedOptions()` - Options grouped by project
- `userHasOnlyOneProject()` - Single-project user detection
- `BelongsToProject` rule integration

#### 9. **Form** (`app/Services/Form/Form.php`)
JSON presenter that outputs the final form specification.

**Output structure:**
```json
{
  "type": "create",
  "action": "families.store",
  "title": "Create Family",
  "name": "Family",
  "specs": {
    "name": {
      "element": "input",
      "type": "text",
      "label": "Name",
      "required": true
    },
    "project_id": {
      "element": "select",
      "label": "Project",
      "options": {"1": "Project Alpha"},
      "selected": 1,
      "hidden": false,
      "required": true
    },
    "unit_type_id": {
      "element": "select",
      "label": "Unit Type",
      "options": {
        "1": {"10": "Type A", "11": "Type B"},
        "2": {"20": "Type C", "21": "Type D"}
      },
      "filteredBy": "project_id",
      "placeholder": "Please select a Project first"
    }
  }
}
```

#### 1. **Form Component** (`resources/js/components/forms/Form.vue`)
Main form component that renders specs automatically.

**Key features:**
- Reactive filtered options via `watch` on form data
- Updates dependent select options when parent changes
- Dynamic placeholders: "Please select a {parent} first" vs "Select an option"
- Auto-submits to configured route with proper HTTP method

**Filtered select logic:**
```typescript
const dependencies: [string, string, FilteredSelectOptions][] = // Array of tuples
const options = reactive<{ [key: string]: FilteredSelectOptions }>({});
const placeholders = reactive<{ [key: string]: string }>({});

watch(() => form, () => {
  dependencies.forEach(([name, filteredBy, allOptions]) => {
    const selectedParent = form[filteredBy] as string | number;
    options[name] = selectedParent ? allOptions[selectedParent] ?? [] : [];

#### 2. **FormSelect Component** (`resources/js/components/forms/FormSelect.vue`)
Select component with multi-select support and custom UI.

**Key features:**
- Supports single and multi-select modes
- Hidden prop for cosmetic hiding (uses CSS class, not v-if)
- Preserves form field in DOM for dependency chain
- Custom placeholder support

#### 3. **TypeScript Types** (`resources/js/components/forms/types/index.d.ts`)
```typescript
export type SelectOptions = { [key: string | number]: string | number };
export type FilteredSelectOptions = { [parentId: string | number]: SelectOptions };

export interface FormServiceData {
  type: FormType;
  action: [string, unknown];
  title: string;
  specs: FormSpecs;
}

export interface CommonSelectSpecs extends CommonElementSpecs {
  element: 'select';
  options: SelectOptions | FilteredSelectOptions;
  filteredBy?: string;
  placeholder?: string;
  hidden?: boolean; // CSS-only hiding
  selected?: string | number;
  // ... other fields
}
```
#### 2. **TypeScript Types** (`resources/js/components/forms/types/index.d.ts`)
```typescript
export type SelectOptions = { [key: string | number]: string | number };
export type FilteredSelectOptions = { [parentId: string | number]: SelectOptions };

export interface CommonSelectSpecs extends CommonElementSpecs {
  element: 'select';
  options: SelectOptions | FilteredSelectOptions;
  filteredBy?: string;
  placeholder?: string;
  // ... other fields
}
```

## Configuration

### Option Labels (`config/forms.php`)

```php
return [
    'optionLabel' => [
        User::class => fn (User $user) => "{$user->firstname} {$user->lastname}",
        UnitType::class => 'name',
        Project::class => 'name',
        // ...
    ],
];
```

**Supports:**
- **String field names**: `'name'` → uses model's `name` attribute
- **Closures**: Custom label building for complex cases

### Translations (`lang/{locale}/general.php`)

```php
return [
    'create_entity' => 'Create :entity',
    'edit_entity' => 'Edit :entity',
    'select_project_first' => 'Please select a Project first',
];
```

## Field Name Inference

The system automatically infers relationships from field names:

- `project_id` → `Project::class`
- `unit_type_id` → `UnitType::class`
- `member_ids` (array) → `Member::class` (multi-select)

## Special Rules Support

### BelongsToProject Custom Rule

When a field has the `BelongsToProject` validation rule:

1. **CREATE forms**:
   - Single-project context: Uses `Project::current()->relation()`
   - Multi-project context: Returns all options grouped by project_id
   - No project selected: Shows placeholder "Please select a Project first"

2. **UPDATE forms**:
   - Single-project context: Uses `$model->project->relation()`
   - Multi-project context: Returns all options grouped by project_id

### Array Fields with Wildcard Rules
### Controller

```php
use App\Services\Form\FormService;
use App\Services\Form\FormType;

public function create(): Response
{
    return inertia('CreateUpdate', [
        'form' => FormService::make(Family::class, FormType::CREATE),
    ]);
}

public function edit(Family $family): Response
{
    return inertia('CreateUpdate', [
        'form' => FormService::make($family, FormType::UPDATE),
    ]);
}
```

### Vue Component

Generic `CreateUpdate.vue` page that works for any entity:

```vue
<script setup lang="ts">
import type { FormServiceData } from '@/components/forms/types';

const props = defineProps<{
  form: FormServiceData;
}>();

const entity = computed(() => {
  const actionParts = props.form.action[0].split('.');
  const namespace = actionParts[0] as AppEntityNS;
  return entityFromNS(namespace);
});

const entityPluralLabel = computed(() => entityLabel(entity.value, 'plural'));
const routes = computed(() => entityRoutes(entity.value));
</script>

<template>
  <MainLayout :title="form.title">
    <template #breadcrumbs>
      <Breadcrumb :route="routes.index" :label="entityPluralLabel" />
      <Breadcrumb :route="routes[form.type]" :label="form.title" />
    </template>

    <Form v-bind="form" />
  </MainLayout>
</template>
```
    $form = FormService::make($family, FormType::UPDATE);

    return inertia('Families/Form', [
        'form' => $form,
    ]);
}
```

### Vue Component

```vue
<script setup lang="ts">
const props = defineProps<{
  form: {
    type: string;
    action: string;
    title: string;
    specs: FormSpecs;
  };
}>();
</script>

<template>
  <Form
    :type="form.type"
    :action="form.action"
    :title="form.title"
    :specs="form.specs"
  />
</template>
```

## How It Works: Multi-Project Option Filtering

### Backend Detection Logic

```php
protected function getProjectScopedOptions(): array
{
    $userProjects = Auth::user()?->projects ?? collect();

    // Single project: return flat options
    if ($userProjects->count() <= 1) {
        $project = Project::current() ?? $this->model->project;
        return $this->getOptionsForModels($project->unitTypes, UnitType::class);
    }

    // Multi-project: return grouped options
    $optionsByProject = [];
    foreach ($userProjects as $project) {
        $optionsByProject[$project->id] = $this->getOptionsForModels(
            $project->unitTypes,
            UnitType::class
        );
    }

    return $optionsByProject;
}
```

### Frontend Filtering Logic

```typescript
const filteredOptions = computed(() => {
  return Object.fromEntries(
    Object.entries(props.specs).map(([name, spec]) => {
      if (!spec.filteredBy) return [name, spec.options];

      const parentValue = form[spec.filteredBy];
      const grouped = spec.options as Record<string, any>;

      return [name, grouped[String(parentValue)] ?? {}];
    }),
  );
});
```

**Result**: When user selects a project, all dependent selects (unit types, families, etc.) automatically show only options for that project.

## Design Decisions

### Why No Try/Catch Blocks?

## Configuration

### Namespaces (`config/forms.php`)

The FormService uses configurable namespaces to locate models, controllers, and form requests. These are already set up and ready for package extraction:

```php
'namespaces' => [
    'models' => 'App\\Models',
    'controllers' => 'App\\Http\\Controllers\\Resources',
    'formrequests' => 'App\\Http\\Requests',
],
```

When extracted as a package, this configuration will be published as-is, allowing developers to customize the namespaces to match their application structure.

## TODOs for Composer Package Extraction

The FormService is designed to be generic and reusable. To convert it into a standalone Laravel package, the following app-specific dependencies need to be made configurable:

### 1. **Ability to Merge/Override Specs in Form.php**

Add a `merge()` method to allow custom spec overrides:

```php
$formSpecs = FormService::make($family, FormType::UPDATE)->merge([
    'project_ids' => [$family->project_id => 'Current project']
]);
```

This will enable overriding automatic specs with custom exceptions when needed.

### 2. **Extract Translation Keys**

Move form-specific translations from `lang/{locale}/general.php` to package-specific file:

```php
// lang/en/form-service.php
return [
    'create_entity' => 'Create :entity',
    'edit_entity' => 'Edit :entity',
    'select_parent_first' => 'Please select a :parent first',
    'select_option' => 'Select an option',
];
```

Update `DataProvider::formTitle()` to use package translations.

### 2. **Injectable Custom Rules**

Make custom rule detection (like `BelongsToProject`) configurable:

```php
// config/form-service.php
return [
    'custom_rules' => [
        BelongsToProject::class => [
            'handler' => ProjectScopedOptionsHandler::class,
            'method' => 'getModelClass', // Method to extract model class
        ],
    ],
];
```

### 3. **Configurable Option Label Resolution**

The `config/forms.php` already contains this configuration and will be published as-is:

```php
// config/form-service.php
return [
    'option_labels' => [
        User::class => fn (User $user) => "{$user->firstname} {$user->lastname}",
        // ... other models
    ],
];
```

### 4. **Authorization Handling**

Add configuration for bypassing FormRequest authorization during spec generation:

```php
// config/form-service.php
return [
    'bypass_authorization' => true, // Skip authorize() when generating specs
];
```

### 5. **Service Provider**

Create package service provider to:
- Publish config files
- Publish language files
- Register FormService as singleton
- Provide artisan commands for generating FormRequests

### 6. **Testing Infrastructure**

Add comprehensive tests:
- Unit tests for each component (Rules, Rule, Spec, DataProvider)
- Integration tests for full form generation
- Tests for different Laravel versions
- Tests for various validation rule combinations

### 7. **Documentation**

Create package-specific documentation:
- Installation guide
- Configuration reference
- Usage examples
- Migration guide from manual forms
- API reference

## Pending Features

### 1. **Threshold-Based Loading Strategy**

Add config to switch between pre-loading and dynamic loading:

```php
// config/form-service.php
'preload_threshold' => 10, // Max projects before switching to dynamic loading
'preload_option_limit' => 500, // Max total options before switching
```

### 2. **Smart Caching**

Cache generated specs per user/project combination to avoid regenerating on every request.

### 3. **Validation Error Translation**

Automatically map backend validation errors to frontend field names for better UX.

The payload is minimal: `id` + `label` string per option.

## Pending Features

### 1. **Threshold-Based Loading Strategy**

Add config to switch between pre-loading and dynamic loading:

```php
// config/forms.php
'preload_threshold' => 10, // Max projects before switching to dynamic loading
'preload_option_limit' => 500, // Max total options before switching
```

### 2. **Authorization Handling**

Currently, FormService generates specs without authorization context. Forms extending `ProjectScopedRequest` may fail during spec generation.

**Solution**: Mock or bypass `authorize()` during spec generation.

### 3. **Smart Caching**

Cache generated specs per user/project combination to avoid regenerating on every request.

## Notes

- Only active projects are included in multi-project option lists
- Empty select options automatically show as disabled with placeholder
- Form titles use `Str::headline()` to convert camelCase to "Proper Case"
- All user-facing text is localized via Laravel's `__()` helper
- Relations are accessed as properties (not `->get()`) for automatic caching
