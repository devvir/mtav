# Archived Tests - Unit Tests Only

This directory contains **4 unit test files with 24 passing tests** that validate critical model methods not yet fully replicated in the modern test suite.

## Current Status

- **✅ 24 passing tests** - All green
- **✅ Self-contained** - Uses only universe.sql fixture + factories + Pest assertions
- **✅ Active & Valuable** - Tests fill critical coverage gaps for authorization model methods
- **✅ No external dependencies** - All legacy helper files removed (not needed)

## Important Note

**Only unit tests remain** - Feature tests (AuthenticationTest, PasswordResetTest, ProjectControllerTest) were completely removed as they're superseded by modern tests.

## Test Overview

All tests use the **universe.sql fixture** (pre-populated test database):
- Projects: 5 (3 active, 1 inactive, 1 deleted)
- Admins: 19 with various project assignments
- Members: 50 across multiple families
- Families: 25 with different membership states

### `AdminTest.php` (4 tests) ✅
**Tests critical `manages()` authorization method:**
- Validates that admins can only manage assigned projects
- Validates superadmin behavior (manages all)
- **Why critical:** `manages()` is used throughout authorization policies (FamilyPolicy, MemberPolicy, etc.)

### `ProjectTest.php` (10 tests) ✅
**Tests core Project model methods:**
- `addMember()` / `removeMember()` - Member assignment operations
- `addAdmin()` - Admin assignment
- `hasMember()` / `hasAdmin()` - Query helpers for authorization checks
- Relationship loading and alphabetical scoping
- **Why critical:** These are fundamental model methods used in CRUD flows but not tested in modern Feature tests

### `FamilyTest.php` (3 tests) ✅
**Tests Family model relationships:**
- `addMember()` - Adding members to families
- **Why critical:** Family membership is core domain logic with no modern unit-level tests

### `UserTest.php` (7 tests) ✅
**Tests User role conversions and filtering:**
- `asMember()` / `asAdmin()` - Type conversions
- `isMember()` / `isAdmin()` / `isSuperadmin()` - Role detection
- Active project filtering (only active projects returned)
- **Why critical:** Foundational for authorization and session management

## What Value Do These Tests Provide?

The modern Feature test suite indirectly validates these methods through integration testing. However:

| Method | Modern Test Coverage | Archive Test Coverage |
|--------|------|------|
| `manages()` | Implicit in authorization tests | ✅ Direct unit tests |
| `hasMember()` / `hasAdmin()` | None | ✅ Direct unit tests |
| `addMember()` / `addAdmin()` | Form submission tests | ✅ Direct model tests |
| `asMember()` / `asAdmin()` | Implicit in policies | ✅ Direct unit tests |
| `isMember()` / `isAdmin()` | Implicit in features | ✅ Direct unit tests |

**Gap filled:** These tests provide **direct unit-level coverage** of fundamental model logic that is only tested indirectly (if at all) in the modern suite.

## Run Tests

```bash
mtav pest --testsuite Archive
```

## Cleanup History

**December 20, 2025:**
- ✅ Deleted 3 feature test files (now covered by modern tests)
  - AuthenticationTest → replaced by `tests/Feature/Authentication/VisitLoginPageTest.php`
  - PasswordResetTest → was incomplete (only 1 trivial test)
  - ProjectControllerTest → replaced by `tests/Feature/Healthcheck/ProjectsHealthcheckTest.php`
- ✅ Deleted all 5 helper files (unit tests don't need them)
  - UserHelpers.php
  - ProjectHelpers.php
  - FamilyHelpers.php
  - InertiaHelpers.php
  - UniverseHelpers.php
