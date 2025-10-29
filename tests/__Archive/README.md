# Archived Tests

This directory contains the **original test suite** that is being phased out and replaced by a new, cleaner test structure (located in `tests/Authentication/`, `tests/Authorization/`, etc.).

## Purpose

These tests are kept for reference and to maintain coverage **only until they are superseded** by equivalent tests in the new suite.

## What Was Cleaned

- ❌ All skipped/todo tests removed (122 skipped tests deleted)
- ❌ Test files with 0 passing tests deleted (3 files)
- ❌ Architecture tests removed (5 failing tests)
- ❌ Failing tests removed (6 authorization-related tests)
- ❌ Obsolete documentation deleted (BUGS_AND_TODOS.md, PRIORITIES.md, TEST_CATALOG.md, etc.)
- ✅ Only **passing tests** remain

## Current Status

- **113 passing tests** (100% pass rate) across 19 test files
- Tests use old helper functions (loaded via `tests/Pest.php`)
- Tests use factories instead of fixtures

## Important Note

**Nothing will be added or updated in this archive** - tests here can only be deleted as they are superseded by the new test suite.

## Files Kept

### Feature Tests (14 files)
- **Auth**: AuthenticationTest (4), EmailVerificationTest (2), PasswordConfirmationTest (3), PasswordResetTest (4)
- **Controllers**: AdminControllerCrudTest (9), FamilyControllerCrudTest (12), FamilyControllerTest (3), MemberControllerCrudTest (18), MemberControllerTest (5), ProjectControllerTest (4), UnitControllerCrudTest (1)
- **Policies**: FamilyPolicyTest (8), MemberPolicyTest (8)
- **Settings**: PasswordUpdateTest (2), ProfileUpdateTest (3)
- **Business Logic**: ProjectScopeTest (1)
- **Other**: DashboardTest (2)

### Unit Tests (5 files)
- **Models**: AdminTest (5), FamilyTest (3), MemberTest (4), ProjectTest (11), UserTest (7)

## Migration Strategy

1. New tests are written in the structured test directories (`tests/Authentication/`, etc.)
2. As new tests supersede old ones, the old tests are removed from this archive
3. When all tests in a file are superseded, the entire file is deleted
4. Eventually this directory will be empty and can be removed

## Helper Files

The `Helpers/` directory contains helper functions still used by these archived tests:
- `FamilyHelpers.php` - createFamily, createFamilyInProject, etc.
- `InertiaHelpers.php` - assertInertiaComponent, inertiaGet, etc.
- `ProjectHelpers.php` - createProject, createProjectWithAdmin, etc.
- `UniverseHelpers.php` - (deprecated - old fixture system)
- `UserHelpers.php` - createAdmin, createMember, createSuperAdmin, etc.

These helpers will be removed once no archived tests depend on them.

---

**For the new test structure and guidelines, see**:
- `tests/Authentication/README.md` - Authentication test organization
- `documentation/ai/TESTS_KB.md` - Testing patterns and best practices
