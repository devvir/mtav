# Policy Authorization Matrix

This document describes the authorization logic for each resource in the application. All policies follow Laravel's standard policy methods: `viewAny`, `view`, `create`, `update`, `delete`, and `restore`.

**Note:** Super admins bypass all policies and have unrestricted access to all actions.

---

## Admin Policy

| Method    | Defined | Authorization Logic                                                                                                                                                                  |
| --------- | ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `viewAny` | ✅      | **All users** - Returns `true`                                                                                                                                                       |
| `view`    | ✅      | **All admins** - Returns `true` for any admin<br>**Members** - Can view admins in their project<br>**⚠️ Partial validation:** Full check (admins share projects) in ShowAdminRequest |
| `create`  | ✅      | **Any admin** - `$user->isAdmin()`<br>**⚠️ Partial validation:** Full privilege escalation check in CreateAdminRequest                                                               |
| `update`  | ✅      | **Super admin OR self** - `$user->isSuperAdmin()` OR `$user->is($admin)`                                                                                                             |
| `delete`  | ✅      | **Super admin OR self** - `$user->isSuperAdmin()` OR `$user->is($admin)`                                                                                                             |
| `restore` | ✅      | **Any admin** - `$user->isAdmin()`                                                                                                                                                   |

**Special Notes:**

- **N+1 Optimization:** `view` method uses partial validation to avoid N+1 queries on collections
- **Additional FormRequest check:** ShowAdminRequest ensures admins only view other admins who share projects (throws 403)
- **Privilege escalation prevention:** CreateAdminRequest validates admins can only assign projects they manage
- Admins can update/delete themselves
- Only super admins can update/delete other admins

---

## Family Policy

| Method    | Defined | Authorization Logic                                                                            |
| --------- | ------- | ---------------------------------------------------------------------------------------------- |
| `viewAny` | ✅      | **All users** - Returns `true`                                                                 |
| `view`    | ✅      | **Members:** Must be in same project as family<br>**Admins:** Must manage the family's project |
| `create`  | ✅      | **Any admin** - `$user->isAdmin()`                                                             |
| `update`  | ✅      | **Member of family OR admin managing project**                                                 |
| `delete`  | ✅      | **Admin managing project** - `$user->asAdmin()?->manages($family->project_id)`                 |
| `restore` | ✅      | **Admin managing project** - `$user->asAdmin()?->manages($family->project_id)`                 |

**Special Notes:**

- Members can only view families in their project
- Members can update their own family
- Only admins can delete/restore families

---

## Log Policy

| Method    | Defined | Authorization Logic                            |
| --------- | ------- | ---------------------------------------------- |
| `viewAny` | ✅      | **All users** - Returns `true`                 |
| `view`    | ✅      | **All users** - Returns `true`                 |
| `create`  | ❌      | **Not defined** - Defaults to super admin only |
| `update`  | ❌      | **Not defined** - Defaults to super admin only |
| `delete`  | ❌      | **Not defined** - Defaults to super admin only |
| `restore` | ❌      | **Not defined** - Defaults to super admin only |

**Special Notes:**

- Logs are read-only for all users
- Only super admins can create/update/delete logs

---

## Member Policy

| Method    | Defined | Authorization Logic                                                                                                                                 |
| --------- | ------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| `viewAny` | ✅      | **All users** - Returns `true`                                                                                                                      |
| `view`    | ✅      | **All users** - Returns `true`<br>**⚠️ Note:** Project-based filtering happens at query level in controllers                                        |
| `create`  | ✅      | **All users** - Returns `true` (public registration)                                                                                                |
| `update`  | ✅      | **Self OR admin managing member's project(s)**<br>**⚠️ Query optimization:** Checks self first, then admin, then executes query only when necessary |
| `delete`  | ✅      | **Self OR admin managing member's project(s)**<br>**⚠️ Query optimization:** Checks self first, then admin, then executes query only when necessary |
| `restore` | ✅      | **Admin AND member is soft-deleted**                                                                                                                |

**Special Notes:**

- **N+1 Optimization:** View returns true for all; project filtering at query level
- **Query optimization:** Update/delete check `$user->is($member)` first to avoid queries
- Anyone can create a member (public registration)
- Members can update/delete themselves
- Admins can only update/delete members in projects they manage
- Update/delete execute 1 query only when admin is editing another member

---

## Project Policy

| Method    | Defined | Authorization Logic                                                     |
| --------- | ------- | ----------------------------------------------------------------------- |
| `viewAny` | ✅      | **Super admin OR admin managing 2+ projects**                           |
| `view`    | ✅      | **Admin managing the project** - `$user->asAdmin()?->manages($project)` |
| `create`  | ✅      | **Always returns `false`** - Only super admins can (bypass)             |
| `update`  | ✅      | **Admin managing the project** - `$user->asAdmin()?->manages($project)` |
| `delete`  | ✅      | **Admin managing the project** - `$user->asAdmin()?->manages($project)` |
| `restore` | ✅      | **Admin managing soft-deleted project**                                 |

**Special Notes:**

- `create` returns `false` because only super admins (who bypass policies) can create projects
- Admins with only 1 project cannot access the projects index
- Members cannot access any project-level operations

---

## Unit Policy

| Method    | Defined | Authorization Logic                                                              |
| --------- | ------- | -------------------------------------------------------------------------------- |
| `viewAny` | ✅      | **All users** - Returns `true`                                                   |
| `view`    | ✅      | **All users** - Returns `true`                                                   |
| `create`  | ✅      | **Any admin** - `$user->isAdmin()`                                               |
| `update`  | ✅      | **Admin managing unit's project** - `$user->asAdmin()?->manages($unit->project)` |
| `delete`  | ✅      | **Admin managing unit's project** - `$user->asAdmin()?->manages($unit->project)` |
| `restore` | ❌      | **Not defined** - Defaults to super admin only                                   |

**Special Notes:**

- All users can view units
- Only admins can create/update/delete units
- Restore not implemented (defaults to super admin only)

---

## UnitType Policy

| Method    | Defined | Authorization Logic                                                                          |
| --------- | ------- | -------------------------------------------------------------------------------------------- |
| `viewAny` | ✅      | **All users** - Returns `true`                                                               |
| `view`    | ✅      | **Admins:** Must manage the unit type's project<br>**Members:** Must be in same project      |
| `create`  | ✅      | **Any admin** - `$user->isAdmin()`                                                           |
| `update`  | ✅      | **Admin managing unit type's project** - `$user->asAdmin()?->manages($unitType->project_id)` |
| `delete`  | ✅      | **Admin managing unit type's project** - `$user->asAdmin()?->manages($unitType->project_id)` |
| `restore` | ❌      | **Not defined** - Defaults to super admin only                                               |

**Special Notes:**

- Members can only view unit types in their project
- Only admins can create/update/delete unit types
- Restore not implemented (defaults to super admin only)

---

## Authorization Flow

1. **Super admins** bypass all policy checks
2. **Policy methods** are checked in the following order:
   - `before()` method (if defined) - runs before all other checks
   - Specific method (`view`, `update`, etc.)
   - If method not defined, defaults to denying access (except for super admins)

3. **Common patterns:**
   - `$user->isAdmin()` - Any admin (including super admin)
   - `$user->isSuperAdmin()` - Only super admins
   - `$user->asAdmin()->manages($project)` - Admin managing specific project
   - `$user->is($model)` - User owns the model
