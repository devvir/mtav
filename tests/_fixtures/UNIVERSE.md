# Test Universe Quick Reference

## Overview
This fixture provides a complete, self-documenting test dataset with predictable IDs and descriptive names.

## Quick Stats
- **5 Projects** (1-2 normal, 3 no types, 4 inactive, 5 deleted)
- **4 Admins** (various project assignments)
- **12 Unit Types** (3 per project except #3)
- **21 Units** (various distributions and states)
- **15 Families** (various member counts and states)
- **45 Members** (IDs 5-49, various states)

---

## Projects

| ID | Name | State | Unit Types | Families | Members |
|----|------|-------|------------|----------|---------|
| 1  | Project 1 | Active | 3 (#1-#3) | 12 (#1-#12) | 40 (#5-#40) |
| 2  | Project 2 | Active | 3 (#4-#6) | 3 (#13-#15) | 9 (#41-#49) |
| 3  | Project 3 (no unit types) | Active | **0** | 0 | 0 |
| 4  | Project 4 (inactive) | **Inactive** | 3 (#7-#9) | 0 | 0 |
| 5  | Project 5 (deleted) | **Soft-deleted** | 3 (#10-#12) | 0 | 0 |

---

## Admins

| ID | Email | Lastname | Projects Managed | Active In |
|----|-------|----------|------------------|-----------|
| 1  | admin1@example.com | 1 (no projects) | **None** | - |
| 2  | admin2@example.com | 2 (manages 1) | #1 | #1 ✓ |
| 3  | admin3@example.com | 3 (manages 2,3) | #2, #3 | #2 ✓, #3 ✓ |
| 4  | admin4@example.com | 4 (manages 2-,3+,4+) | #2, #3, #4 | #2 ✗, #3 ✓, #4 ✓ |

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

### Project #1 (12 families)
| Family ID | Unit Type | Members | Special Notes |
|-----------|-----------|---------|---------------|
| 1  | 1 | **0** | No members (edge case) |
| 2  | 1 | 1 (#5) | Member is inactive |
| 3  | 1 | 1 (#6) | Member is deleted |
| 4  | 1 | 3 (#7-#9) | 1 active, 1 inactive, 1 deleted |
| 5  | 2 | **10** (#10-#19) | Pagination tests |
| 6-8  | 2 | 3 each (#20-#28) | - |
| 9-12 | 3 | 3 each (#29-#40) | - |

### Project #2 (3 families)
| Family ID | Unit Type | Members |
|-----------|-----------|---------|
| 13-14 | 4 | 3 each (#41-#46) |
| 15    | 5 | 3 (#47-#49) |

---

## Members by State

### By Family Size
- **0 members**: Family #1
- **1 member**: Families #2, #3 (special states)
- **3 members**: Families #4, #6-#15 (33 members)
- **10 members**: Family #5 (pagination testing)

### By Activity State
| State | Member IDs | Count |
|-------|------------|-------|
| Active | #7, #10-#49 (except inactive/deleted) | 36 |
| Inactive (project_user) | #5, #8 | 2 |
| Soft-deleted | #6, #9 | 2 |

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
