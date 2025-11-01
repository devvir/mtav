# Test Universe Quick Reference

## Overview
This fixture provides a complete, self-documenting test dataset with predictable IDs and descriptive names.

## User ID Scheme
- **IDs 1-9**: Superadmins (currently only #1)
- **IDs 10-99**: Regular admins
- **IDs 100+**: Members and other non-admin users

## Quick Stats
- **5 Projects** (1-2 normal, 3 no types, 4 inactive, 5 deleted)
- **9 Admins** (1 superadmin, 7 verified admins, 1 unverified admin)
- **12 Unit Types** (3 per project except #3)
- **21 Units** (various distributions and states)
- **15 Families** (various member counts and states)
- **47 Members** (IDs 100-146, various states)
- **1 Unverified User** (ID 147 for email verification testing)

---

## Projects

| ID | Name | State | Unit Types | Families | Members |
|----|------|-------|------------|----------|---------|
| 1  | Project 1 | Active | 3 (#1-#3) | 12 (#1-#12) | 40 (#100-#135) |
| 2  | Project 2 | Active | 3 (#4-#6) | 3 (#13-#15) | 9 (#136-#144) |
| 3  | Project 3 (no unit types) | Active | **0** | 0 | 0 |
| 4  | Project 4 (inactive) | **Inactive** | 3 (#7-#9) | 2 (#22) | 1 (#145) |
| 5  | Project 5 (deleted) | **Soft-deleted** | 3 (#10-#12) | 1 (#23) | 1 (#146) |

---

## Admins

### Superadmins (IDs 1-9)
| ID | Email | Lastname | Projects Managed | Active In |
|----|-------|----------|------------------|-----------|
| 1  | superadmin1@example.com | 1 (no projects) | **None** | - |

### Regular Admins (IDs 10-99)
| ID | Email | Lastname | Projects Managed | Active In | Notes |
|----|-------|----------|------------------|-----------|-------|
| 10 | admin10@example.com | 10 (no projects) | **None** | - | |
| 11 | admin11@example.com | 11 (manages 1) | #1 | #1 ✓ | |
| 12 | admin12@example.com | 12 (manages 2,3) | #2, #3 | #2 ✓, #3 ✓ | |
| 13 | admin13@example.com | 13 (manages 2-,3+,4+) | #2, #3, #4 | #2 ✗, #3 ✓, #4 ✓ | |
| 14 | admin14@example.com | 14 (manages deleted 5) | #5 (deleted) | #5 ✓ | |
| 15 | admin15@example.com | 15 (manages 2, deleted 5) | #2, #5 | #2 ✓, #5 ✓ | |
| 16 | admin16@example.com | 16 (manages 2,3,4, deleted 5) | #2, #3, #4, #5 | All ✓ | |
| 17 | admin17@example.com | 17 (manages 1) | #1 | #1 ✓ | **Unverified** |

**Note**: Admin #17 is unverified (verified_at = NULL) but has full project access for testing invitation acceptance independently

**Password for all users**: `password` (bcrypt hash)

---

## Unit Types Distribution

| Project | Type IDs | Units Each | Total Units |
|---------|----------|------------|-------------|
| 1       | 1, 2, 3  | 0, 1, 2    | 3           |
| 2       | 4, 5, 6  | 2, 2, 2    | 6           |
| 3       | **None** | -          | 0           |
| 4       | 7, 8, 9  | 2, 2, 2    | 6           |
| 5       | 10, 11, 12 | 2, 2, 2  | 6           |

**Special Cases:**
- Type #1: Has **NO units** (edge case)
- Type #3: Has 1 active unit (#2) + 1 deleted unit (#3)

---

## Families by Project

### Project #1 (13 families)
| Family ID | Unit Type | Members | Special Notes |
|-----------|-----------|---------|---------------|
| 1  | 1 | **0** | No members (edge case) |
| 2  | 1 | 1 (#100) | Member is inactive |
| 3  | 1 | 1 (#101) | Member is deleted |
| 4  | 1 | 3 (#102-#104) | 1 active, 1 inactive, 1 deleted |
| 5  | 2 | **10** (#105-#114) | Pagination tests |
| 6-8  | 2 | 3 each (#115-#123) | - |
| 9-12 | 3 | 3 each (#124-#135) | - |
| 24 | 1 | 1 (#147) | Unverified member |

### Project #2 (3 families)
| Family ID | Unit Type | Members |
|-----------|-----------|---------|
| 13-14 | 4 | 3 each (#136-#141) |
| 15    | 5 | 3 (#142-#144) |

### Project #4 (1 family)
| Family ID | Unit Type | Members | Special Notes |
|-----------|-----------|---------|---------------|
| 22 | 7 | 1 (#145) | In inactive project |

### Project #5 (1 family)
| Family ID | Unit Type | Members | Special Notes |
|-----------|-----------|---------|---------------|
| 23 | 10 | 1 (#146) | In deleted project |

---

## Members by State

### By Family Size
- **0 members**: Family #1
- **1 member**: Families #2, #3, #22, #23 (special states)
- **3 members**: Families #4, #6-#15 (36 members)
- **10 members**: Family #5 (pagination testing)

### By Activity State
| State | Member IDs | Count |
|-------|------------|-------|
| Active | #102, #105-#144 (except inactive/deleted) | 40 |
| Inactive (project_user) | #100, #103 | 2 |
| Soft-deleted | #101, #104 | 2 |
| Unverified | #147 | 1 |

---

## Special Test Users

### Unverified Users
| ID | Email | Type | Purpose | Has Access |
|----|-------|------|---------|------------|
| 17 | admin17@example.com | Admin | Email verification testing (admin) | ✓ Manages Project #1 |
| 147 | unverified@example.com | Member | Email verification testing (member) | ✓ Family #24, Project #1 |

**Note**: Both unverified users have full project/family access to isolate email verification testing from authorization issues

---

## Common Test Scenarios

### Empty/Edge Cases
- Project with no unit types: **Project #3**
- Unit type with no units: **Type #1**
- Family with no members: **Family #1**

### State Variations
- Inactive project: **Project #4**
- Deleted project: **Project #5**
- Inactive member: **Member #5** (family #2) or **#8** (family #4)
- Deleted member: **Member #6** (family #3) or **#9** (family #4)
- Deleted unit: **Unit #3** (type #3)

### Admin Scenarios
- Admin with no projects: **Admin #1**
- Admin with 1 project: **Admin #2** (manages #1)
- Admin with multiple projects: **Admin #3** (manages #2, #3) or **Admin #4** (manages #2, #3, #4)
- Admin inactive in project: **Admin #4** (inactive in #2)

### Pagination Testing
- Family with 10 members: **Family #5** (members #10-#19)
- Project with 12 families: **Project #1** (families #1-#12)
- Project with 40 members: **Project #1** (members #5-#40, some inactive/deleted)

---

## Usage in Tests

```php
// Most tests can use the universe directly without arrangement
test('example test', function () {
    // Universe is already loaded via database transactions

    $project = Project::find(1); // Active project with full data
    $admin = User::find(2); // Admin managing project #1
    $member = User::find(10); // Active member in family #5

    // Make assertions...
});

// For tests that need to modify and can't rollback:
test('example test that modifies data', function () {
    // Manually reload universe in tearDown if needed
})->afterEach(fn() => loadUniverse());
```

---

## Password
All users (admins and members) use the same password: **`password`**

Hash: `$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`
