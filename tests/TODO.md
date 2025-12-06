# Testing TODO

## Form Testing - Phase 1: Backend Submission (HIGH PRIORITY)

**Goal**: Test that form submissions work correctly with FormService-generated data structures.

**Location**: `tests/Feature/FormService/SubmitFormTest.php` (create)

**Coverage Needed**:
- [ ] **Admin**: Create (superadmin) and Update (self)
- [ ] **Project**: Create and Update (superadmin only)
- [ ] **Family**: Create and Update (admin + member)
- [ ] **Member**: Create and Update (admin + member)
- [ ] **Unit**: Create and Update
- [ ] **UnitType**: Create and Update
- [ ] **Event**: Create and Update (regular + lottery special case)

**Scope**: Happy path only - valid data submissions, different user roles, project-scoped entities.

**Out of Scope**: Validation errors, missing fields, invalid formats (defer to E2E phase).

---

## Form Testing - Phase 2: Frontend Rendering (MEDIUM PRIORITY)

**Goal**: Smoke test that form components render correctly.

**Location**: `tests/Frontend/Components/Forms/` (create directory)

**Technology**: Vitest + Vue Test Utils

**Test Files Needed** (one per entity):
- [ ] `AdminForm.test.ts`
- [ ] `ProjectForm.test.ts`
- [ ] `FamilyForm.test.ts`
- [ ] `MemberForm.test.ts`
- [ ] `UnitForm.test.ts`
- [ ] `UnitTypeForm.test.ts`
- [ ] `EventForm.test.ts`

**Coverage per entity**:
- Renders without errors
- Input fields have correct type
- Select fields have options
- Hidden fields are hidden
- Required fields marked
- Values pre-filled for update forms

## Test Infrastructure

**Completed**:
- ✅ Universe fixture loaded and working
- ✅ FormService test helpers (`tests/Helpers/formService.php`)

**Pending**:
- [ ] Create form submission helper: `buildFormData(array $specs, ?Model $model = null)`
- [ ] Create submission helper: `submitForm(string $route, array $data, ?int $entityId = null)`

---

*Last updated: 2 December 2025*
