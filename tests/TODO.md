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

---

## Authentication E2E Testing (FUTURE - LOW PRIORITY)

**Goal**: Full user workflow testing with real browser interaction.

**Technology**: Pest Browser Testing (already decided over Cypress)

**Deferred Scenarios** (from Phase 1):
- [ ] Validation error messages in UI
- [ ] Validation edge cases (missing fields, invalid formats)
- [ ] Authorization failures (unauthorized access attempts)
- [ ] Multi-step workflows

**Additional Auth Workflows**:
- All checkboxes in `tests/AUTH_TESTING.md` are ✅, but need E2E verification:
  - Login/logout flows
  - Password reset complete flow
  - Email verification flow
  - Registration/invitation flow
  - Rate limiting & throttling
  - Security & edge cases
  - UI/UX validation

---

## Test Infrastructure

**Completed**:
- ✅ Pest Browser Testing setup documented (`documentation/ai/testing/BROWSER_TESTING.md`)
- ✅ Universe fixture loaded and working
- ✅ FormService test helpers (`tests/Helpers/formService.php`)

**Pending**:
- [ ] Add `tests/Browser/Screenshots` to `.gitignore` (when starting E2E tests)
- [ ] Create form submission helper: `buildFormData(array $specs, ?Model $model = null)`
- [ ] Create submission helper: `submitForm(string $route, array $data, ?int $entityId = null)`

---

*Last updated: 2 December 2025*
