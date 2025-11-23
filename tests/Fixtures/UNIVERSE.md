# Test Universe

## Overview

The Test Universe is a comprehensive, predictable test dataset that provides a stable foundation for feature testing. It's loaded once per test run and uses database transactions to ensure each test sees exactly the same data state, regardless of execution order.

## Design Principles

### Predictable IDs
- **Superadmins**: IDs 1-9 (currently only #1 exists)
- **Regular Admins**: IDs 10-99
- **Members**: IDs 100+

This ID scheme allows tests to reference specific entities without fragile lookups, while leaving room for expansion.

### Comprehensive Coverage
The universe includes edge cases and various entity states:
- Empty relationships (families with no members, unit types with no units)
- Inactive and soft-deleted entities
- Various project states (active, inactive, deleted)
- Different permission scenarios
- Unverified users for email testing

### Isolated Testing
Each entity type has examples in different states, allowing tests to focus on specific scenarios without interference from other test data.

## Usage

### Loading
The universe is automatically loaded via the `loadUniverse()` helper function, which:
1. Runs fresh migrations
2. Executes the `universe.sql` fixture
3. Provides a consistent baseline for all tests

### In Tests
```php
// Access entities directly by predictable IDs
$superadmin = User::find(1);
$regularAdmin = User::find(11);
$member = User::find(100);

// Use families, projects, units with known relationships
$activeProject = Project::find(1);
$inactiveProject = Project::find(4);
$deletedProject = Project::find(5);
```

### Data Exploration
To understand current universe contents:
```php
// Debug helpers in tests
dump('Projects:', Project::with('families.members')->get()->toArray());
dump('Families:', Family::with('unitType', 'members')->get()->toArray());
dump('Units:', Unit::with('type')->get()->toArray());
```

## Maintenance

### Adding New Entities
When adding new features that require test data:

1. **Add to `universe.sql`**: Include sample entities with predictable IDs
2. **Follow ID conventions**: Use appropriate ID ranges for entity types
3. **Include edge cases**: Add examples of empty, inactive, or problematic states
4. **Test isolation**: Ensure new data doesn't break existing tests

### Updating Structure
When models change:
1. Update the SQL fixture
2. Verify existing tests still pass
3. Add new test scenarios if needed

The documentation should **NOT** be updated with specific counts, IDs, or relationships - those details live in the SQL file and test code.

## Benefits

- **Speed**: One-time data load per test suite
- **Reliability**: Transactions ensure clean state between tests
- **Maintainability**: Predictable IDs reduce test fragility
- **Coverage**: Edge cases built into the foundation
- **Debugging**: Consistent data makes issues reproducible
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
