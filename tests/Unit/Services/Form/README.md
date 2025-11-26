# FormService Unit Tests

This directory contains comprehensive unit tests for the FormService, which is responsible for automatically generating form specifications from Laravel FormRequest validation rules.

## Purpose

The FormService is a static form generator that:
- Parses validation rules from FormRequest classes
- Generates appropriate form field specifications (inputs, selects, etc.)
- Determines field types, constraints, and options automatically
- Produces JSON-serializable form structures for the frontend

These tests validate the **generic form generation functionality** without requiring:
- User authentication/authorization
- Session state
- Project-specific business logic

## Test Files

### FormServiceRuleTest.php
**Purpose:** Tests the `Rule` class which parses Laravel validation rules into structured data.

**What it tests:** Rule parsing, constraint extraction, type detection, and normalization of various rule formats (strings, arrays, rule objects).

**Test Structure:**
- **Rule Parsing - String Rules** (14 tests)
  - `it parses required rule` - Validates that 'required' is correctly identified
  - `it parses nullable rule` - Validates that 'nullable' is correctly identified
  - `it parses string type` - Ensures 'string' rule sets type to 'string'
  - `it parses integer type` - Ensures 'integer' rule sets type to 'integer'
  - `it parses int type` - Ensures 'int' rule is normalized to 'integer' type
  - `it parses numeric type` - Ensures 'numeric' rule sets type to 'numeric'
  - `it parses boolean type` - Ensures 'boolean' rule sets type to 'boolean'
  - `it parses bool type` - Ensures 'bool' rule is normalized to 'boolean' type
  - `it parses array type` - Ensures 'array' rule sets type to 'array'
  - `it parses date type` - Ensures 'date' rule sets type to 'date'
  - `it parses email type` - Ensures 'email' rule sets type to 'email'
  - `it parses url type` - Ensures 'url' rule sets type to 'url'
  - `it parses file type` - Ensures 'file' rule sets type to 'file'
  - `it parses image type` - Ensures 'image' rule sets type to 'image'

- **Rule Parsing - Constraints** (5 tests)
  - `it parses min constraint` - Extracts minimum value from 'min:5' format
  - `it parses max constraint` - Extracts maximum value from 'max:100' format
  - `it parses between constraint` - Extracts min and max from 'between:1,10' format
  - `it parses size constraint` - Extracts exact size from 'size:50' format
  - `it parses in constraint` - Parses allowed values from 'in:draft,published,archived' format

- **Rule Parsing - Complex Rules** (4 tests)
  - `it parses combined pipe-delimited rules` - Handles 'required|string|max:255' format
  - `it parses array rules with objects` - Handles arrays containing rule objects
  - `it parses Enum rule objects` - Extracts enum cases and converts to 'in' format
  - `it parses exists rule objects` - Identifies database existence constraints

- **Rule Parsing - Numeric Parameters** (2 tests)
  - `it parses integer parameters` - Handles integer values in constraints
  - `it parses float parameters` - Handles decimal values in constraints

- **Rule Magic Getters** (2 tests)
  - `it returns null for undefined properties` - Accessing non-existent parsed properties returns null
  - `it checks property existence with isset` - isset() works correctly on Rule objects

- **Rule Array Conversion** (1 test)
  - `it converts to array with filtered values` - toArray() returns only non-null parsed values

- **Wildcard Rules** (2 tests)
  - `it stores wildcard rules for array fields` - Handles 'items.*' validation patterns
  - `it stores complex wildcard rules` - Handles nested wildcard patterns like 'items.*.tags.*'

**Total: 30 tests**

---

### FormServiceSpecTest.php
**Purpose:** Tests the `SpecFactory`, `SpecInput`, and `SpecSelect` classes which generate field specifications from parsed rules.

**What it tests:** Field type determination, spec generation for inputs and selects, label generation, option building, and database option inference.

**Test Structure:**
- **SpecFactory - Determines Spec Type** (10 tests)
  - `it creates SpecInput for string fields` - String type generates input spec
  - `it creates SpecInput for integer fields` - Integer type generates number input spec
  - `it creates SpecInput for numeric fields` - Numeric type generates number input spec
  - `it creates SpecInput for email fields` - Email type generates email input spec
  - `it creates SpecInput for date fields` - Date type generates date input spec
  - `it creates SpecSelect for boolean fields` - Boolean type generates select spec
  - `it creates SpecSelect for array fields` - Array type generates select spec
  - `it creates SpecSelect for in constraints` - 'in' constraint generates select spec
  - `it creates SpecSelect for enum constraints` - Enum rules generate select spec
  - `it creates SpecSelect for exists constraints` - Database existence rules generate select spec

- **SpecInput - Field Generation** (9 tests)
  - `it generates text input spec` - Creates input with type='text' for string fields
  - `it generates number input spec` - Creates input with type='number' for integer fields
  - `it generates email input spec` - Creates input with type='email' for email fields
  - `it generates date input spec` - Creates input with type='date' for date fields
  - `it converts between constraint to min and max` - 'between:1,10' becomes min=1, max=10 attributes
  - `it generates proper label from field name` - Converts 'user_name' to 'User Name'
  - `it removes is_ prefix from boolean field labels` - 'is_active' becomes 'Active'
  - `it removes _id suffix from field labels` - 'user_id' becomes 'User'
  - `it pluralizes _ids field labels` - 'category_ids' becomes 'Categories'

- **SpecSelect - Field Generation** (4 tests)
  - `it generates boolean select spec` - Creates select with 'true'/'false' string options
  - `it generates in constraint select spec` - Converts 'in:draft,published' to select options
  - `it generates enum select spec` - Extracts enum values and labels as select options
  - `it sets multiple flag for array fields` - Array type fields get multiple=true attribute
  - `it generates proper label for _ids fields` - Handles pluralization for multi-select fields

- **SpecSelect - Database Options** (2 tests)
  - `it infers model from _id field name` - 'user_id' infers User model for options
  - `it handles array fields with wildcard exists rules` - 'items.*' with exists rule fetches options

**Total: 26 tests**

---

### FormServiceTest.php
**Purpose:** Tests the main `FormService` class and `Form` output structure.

**What it tests:** FormService factory method, complete form JSON structure, spec generation integration, and title generation.

**Test Structure:**
- **FormService Factory** (2 tests)
  - `it creates a Form instance from model class string` - FormService::make(Model::class) returns Form instance
  - `it creates a Form instance from model instance` - FormService::make($model) returns Form instance for UPDATE forms

- **Form Output Structure** (4 tests)
  - `it generates correct CREATE form structure` - Validates presence of type, entity, action, title, and specs keys
  - `it generates correct UPDATE form structure` - Validates UPDATE form has correct type, entity, action route, and model ID param
  - `it generates specs for simple form fields` - Verifies individual field specs are properly generated
  - `it generates specs for complex mixed form` - Confirms form generation works with varied field types

- **Form Title Generation** (2 tests)
  - `it generates CREATE title` - Verifies title is generated as a string
  - `it generates UPDATE title` - Verifies UPDATE title is generated as a string

**Total: 8 tests**

---

## Sample Data Files

Located in `tests/Unit/Services/Form/FormRequests/`:

### TestModel.php
**Purpose:** Dummy Eloquent model for testing form generation.

**Structure:**
- Extends `Illuminate\Database\Eloquent\Model`
- Table: `test_models`
- Fillable fields: name, email, age, description
- No timestamps
- Guarded: empty array (allows all mass assignment for testing)

**Usage:** Provides a model class for FormService to work with during tests.

---

### CreateTestModelRequest.php
**Purpose:** Sample FormRequest for CREATE operations with various field types and validation rules.

**Validation Rules:**
- `name` - required, string, max:255
- `email` - required, email
- `age` - nullable, integer, between:1,120
- `description` - nullable, string

**Usage:** Tests basic input field generation with required/nullable fields, string/integer/email types, and min/max constraints.

---

### UpdateTestModelRequest.php
**Purpose:** Sample FormRequest for UPDATE operations.

**Validation Rules:**
- Same as CreateTestModelRequest

**Usage:** Demonstrates that UPDATE forms use the same validation rules as CREATE forms in this simple case.

---

### TestModelController.php
**Purpose:** Dummy controller for FormService reflection during UPDATE form generation.

**Why it exists:**
When generating UPDATE forms, the `DataProvider` class uses reflection to determine the route parameter name. It inspects the controller's `edit()` method signature (e.g., `edit(TestModel $testModel)`) to extract the parameter name (`$testModel`). This allows FormService to properly set route parameters like `test_models.update` with the model ID.

**Structure:**
- Single method: `edit(TestModel $testModel)`
- Method body is empty - only the signature matters for reflection

**Usage:** Required for UPDATE form tests to work, even though no controller methods are actually executed. The FormService uses it purely for type introspection.

---

## Running the Tests

```bash
# Run all FormService tests
mtav pest --filter FormService

# Run specific test file
mtav pest tests/Unit/Services/Form/FormServiceRuleTest.php
mtav pest tests/Unit/Services/Form/FormServiceSpecTest.php
mtav pest tests/Unit/Services/Form/FormServiceTest.php
```

## Test Coverage

**Total Tests:** 64 tests
**Total Assertions:** 152 assertions
**Success Rate:** 100% ✅

These tests cover:
- ✅ All basic validation rule types (string, integer, email, date, boolean, array, etc.)
- ✅ All constraint types (min, max, between, size, in)
- ✅ Complex rule formats (pipe-delimited strings, arrays, rule objects)
- ✅ Laravel rule objects (Enum, Exists)
- ✅ Wildcard validation patterns for nested arrays
- ✅ SpecFactory type determination logic
- ✅ SpecInput generation for all input types
- ✅ SpecSelect generation for all select types
- ✅ Label generation and transformation logic
- ✅ Database option inference from field names
- ✅ Complete Form JSON structure
- ✅ FormService factory pattern

## Notes

- **No Database Access:** Tests use reflection and rule parsing only, no queries are executed
- **No Authentication:** Tests don't require logged-in users or authorization checks
- **Minimal Route Mocking:** UPDATE tests use a simple mock route object with only a `setParameter()` method to satisfy the `DataProvider::injectModel()` requirement. This avoids the need for full HTTP request/route infrastructure while still testing UPDATE form generation.
- **Static Generation:** Forms are generated statically from FormRequest rules
- **Custom Rules:** Custom validation rules are not tested here (separate test file planned)
- **Project-Specific Logic:** App-specific form customizations are not covered in these generic tests
