# Controller Actions & FormRequest Validation

This document describes all controller actions and their validation rules. Each table shows the FormRequest used and the validation rules applied.

---

## AdminController

| Action    | Status | FormRequest          | Validation Rules                                                                                                                                                                                                                                                                                                                          |
| --------- | ------ | -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `index`   | ✅     | `IndexAdminsRequest` | `project_id`: nullable, integer, exists in projects<br>`q`: nullable, string, max:255                                                                                                                                                                                                                                                     |
| `show`    | ✅     | None                 | No validation (route model binding only)                                                                                                                                                                                                                                                                                                  |
| `create`  | ✅     | None                 | No validation (form display only)                                                                                                                                                                                                                                                                                                         |
| `store`   | ✅     | `CreateAdminRequest` | `project_ids`: required, array of integers, each exists in projects<br>`email`: required, string, lowercase, email, max:255, unique in users<br>`firstname`: required, string, min:2, max:255<br>`lastname`: optional, string, min:2, max:255<br>**Custom:** Prevents privilege escalation - validates user manages all assigned projects |
| `edit`    | ✅     | None                 | No validation (form display only)                                                                                                                                                                                                                                                                                                         |
| `update`  | ✅     | `UpdateAdminRequest` | `firstname`: required, string, max:255<br>`lastname`: optional, string, max:255<br>`email`: required, email, max:255, unique (ignore current admin)                                                                                                                                                                                       |
| `destroy` | ✅     | None                 | No validation (route model binding only)                                                                                                                                                                                                                                                                                                  |
| `restore` | ❌     | **Not present**      | -                                                                                                                                                                                                                                                                                                                                         |

---

## FamilyController

| Action    | Status | FormRequest            | Validation Rules                                                                                                                                           |
| --------- | ------ | ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `index`   | ✅     | `IndexFamiliesRequest` | `project_id`: nullable, integer, exists in projects<br>`q`: nullable, string, max:255                                                                      |
| `show`    | ✅     | None                   | No validation (route model binding only)                                                                                                                   |
| `create`  | ✅     | None                   | No validation (form display only)                                                                                                                          |
| `store`   | ✅     | `CreateFamilyRequest`  | `name`: required, string, max:255<br>`project_id`: required, integer, exists in projects<br>`unit_type_id`: optional, integer, must belong to same project |
| `edit`    | ✅     | None                   | No validation (form display only)                                                                                                                          |
| `update`  | ✅     | `UpdateFamilyRequest`  | `name`: required, string, max:255<br>`unit_type_id`: integer, must belong to same project                                                                  |
| `destroy` | ✅     | None                   | No validation (route model binding only)                                                                                                                   |
| `restore` | ❌     | **Not present**        | -                                                                                                                                                          |

---

## LogController

| Action    | Status | FormRequest     | Validation Rules                         |
| --------- | ------ | --------------- | ---------------------------------------- |
| `index`   | ✅     | None            | No validation                            |
| `show`    | ✅     | None            | No validation (route model binding only) |
| `create`  | ❌     | **Not present** | -                                        |
| `store`   | ❌     | **Not present** | -                                        |
| `edit`    | ❌     | **Not present** | -                                        |
| `update`  | ❌     | **Not present** | -                                        |
| `destroy` | ❌     | **Not present** | -                                        |
| `restore` | ❌     | **Not present** | -                                        |

**Note:** Logs are read-only resources.

---

## MemberController

| Action    | Status | FormRequest               | Validation Rules                                                                                                                                                                                                                                                              |
| --------- | ------ | ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `index`   | ✅     | `IndexMembersRequest`     | `project_id`: nullable, integer, exists in projects<br>`q`: nullable, string, max:255                                                                                                                                                                                         |
| `show`    | ✅     | None                      | No validation (route model binding only)                                                                                                                                                                                                                                      |
| `create`  | ✅     | `ShowCreateMemberRequest` | `project_id`: nullable, integer, exists in projects                                                                                                                                                                                                                           |
| `store`   | ✅     | `CreateMemberRequest`     | `project_id`: required, integer, exists in projects<br>`family`: required, exists in families, must belong to same project<br>`email`: required, email, max:255, unique in users<br>`firstname`: required, string, between:2,80<br>`lastname`: optional, string, between:2,80 |
| `edit`    | ✅     | None                      | No validation (form display only)                                                                                                                                                                                                                                             |
| `update`  | ✅     | `UpdateMemberRequest`     | `firstname`: required, string, between:2,80<br>`lastname`: optional, string, between:2,80<br>`email`: required, email, max:255, unique (ignore current member)                                                                                                                |
| `destroy` | ✅     | None                      | No validation (route model binding only)                                                                                                                                                                                                                                      |
| `restore` | ❌     | **Not present**           | -                                                                                                                                                                                                                                                                             |

---

## ProjectController

| Action    | Status | FormRequest            | Validation Rules                                                                                                                                        |
| --------- | ------ | ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `index`   | ✅     | `IndexProjectsRequest` | `q`: nullable, string, max:255<br>`showAll`: nullable, boolean                                                                                          |
| `show`    | ✅     | None                   | No validation (route model binding only)                                                                                                                |
| `create`  | ✅     | None                   | No validation (form display only)                                                                                                                       |
| `store`   | ✅     | `CreateProjectRequest` | `name`: required, string, max:255, unique in projects<br>`description`: required, string, max:65535<br>`admins`: required, array, each exists in admins |
| `edit`    | ✅     | None                   | No validation (form display only)                                                                                                                       |
| `update`  | ✅     | `UpdateProjectRequest` | **No rules defined** - Empty validation                                                                                                                 |
| `destroy` | ✅     | None                   | No validation (route model binding only)                                                                                                                |
| `restore` | ❌     | **Not present**        | -                                                                                                                                                       |

**Note:** `UpdateProjectRequest` has no validation rules defined.

---

## UnitController

| Action    | Status | FormRequest         | Validation Rules                                                                                                                                                                                                                                                 |
| --------- | ------ | ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `index`   | ✅     | `IndexUnitsRequest` | `project_id`: required, integer, exists in projects<br>`q`: nullable, string, max:255                                                                                                                                                                            |
| `show`    | ✅     | None                | No validation (route model binding only)                                                                                                                                                                                                                         |
| `create`  | ✅     | None                | No validation (form display only)                                                                                                                                                                                                                                |
| `store`   | ✅     | `CreateUnitRequest` | `name`: required, string, max:255<br>`number`: optional, string, max:255<br>`project_id`: required, integer, exists in projects<br>`unit_type_id`: required, integer, must belong to same project<br>`family_id`: optional, integer, must belong to same project |
| `edit`    | ✅     | None                | No validation (form display only)                                                                                                                                                                                                                                |
| `update`  | ✅     | `UpdateUnitRequest` | `name`: sometimes required, string, max:255<br>`number`: optional, string, max:255<br>`unit_type_id`: sometimes required, integer, must belong to same project<br>`family_id`: optional, integer, must belong to same project                                    |
| `destroy` | ✅     | None                | No validation (route model binding only)                                                                                                                                                                                                                         |
| `restore` | ❌     | **Not present**     | -                                                                                                                                                                                                                                                                |

---

## UnitTypeController

| Action    | Status | FormRequest             | Validation Rules                                                                                                            |
| --------- | ------ | ----------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `index`   | ✅     | `IndexUnitTypesRequest` | `project_id`: nullable, integer, exists in projects<br>`q`: nullable, string, max:255                                       |
| `show`    | ❌     | **Not present**         | -                                                                                                                           |
| `create`  | ❌     | **Not present**         | -                                                                                                                           |
| `store`   | ✅     | `CreateUnitTypeRequest` | `name`: required, string, max:255<br>`description`: optional, string<br>`project_id`: nullable, integer, exists in projects |
| `edit`    | ❌     | **Not present**         | -                                                                                                                           |
| `update`  | ✅     | `UpdateUnitTypeRequest` | `name`: required, string, max:255<br>`description`: optional, string                                                        |
| `destroy` | ✅     | `DeleteUnitTypeRequest` | **No field rules**<br>**Custom validation:** Prevents deletion if unit type has assigned families or units                  |
| `restore` | ❌     | **Not present**         | -                                                                                                                           |

**Note:** UnitTypes use inline creation/editing (no separate create/edit views).

---

## ProjectScopedRequest

Several FormRequests extend `ProjectScopedRequest`, which provides:

- **Auto-injection:** If `project_id` is missing and user manages only 1 project, it's auto-injected
- **Authorization:** Validates user has access to the specified project (admin must manage it, member must belong to it)

**FormRequests extending ProjectScopedRequest:**

- `IndexAdminsRequest`
- `IndexFamiliesRequest`
- `CreateFamilyRequest`
- `IndexMembersRequest`
- `ShowCreateMemberRequest`
- `CreateMemberRequest`
- `IndexUnitsRequest`
- `CreateUnitRequest`
- `IndexUnitTypesRequest`
- `CreateUnitTypeRequest`

---

## Custom Validation Rules

### BelongsToProject Rule

Used to ensure related models (families, unit types) belong to the same project. Applied in:

- `CreateFamilyRequest` (unit_type_id)
- `UpdateFamilyRequest` (unit_type_id)
- `CreateMemberRequest` (family)
- `CreateUnitRequest` (unit_type_id, family_id)
- `UpdateUnitRequest` (unit_type_id, family_id)

### Custom Validation Logic

**CreateAdminRequest:**

- Prevents privilege escalation by validating that the current user manages all projects they're assigning to the new admin
- Super admins bypass this check

**DeleteUnitTypeRequest:**

- Prevents deletion if the unit type is assigned to any families or units
- Implemented in FormRequest to avoid N+1 queries in Policy (which would affect resource collections)

---

## Standard Patterns

1. **Index actions** use `Index{Resource}Request` with optional `project_id` and `q` (search) parameters
2. **Show/Edit actions** typically have no FormRequest (just route model binding)
3. **Create actions** (form display) may use FormRequest for filtering available options
4. **Store/Update actions** use dedicated `Create{Resource}Request` / `Update{Resource}Request`
5. **Destroy actions** typically have no FormRequest unless custom validation is needed (e.g., DeleteUnitTypeRequest)
6. **Restore actions** are not implemented for most resources
