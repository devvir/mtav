# Quick Start - Nov 3rd MVP TDD Workflow

**Date**: October 27, 2025
**For**: Tomorrow's implementation session

---

## Ask Me: "What's Next?"

I'll respond with:

1. **Priority level** (P0, P1, P2, P3)
2. **Feature name** from WORK_QUEUE.md
3. **Test file** to create/update
4. **Specific tests** to enable
5. **Implementation steps**

---

## Current Priority Order

### 🔴 P0 - CRITICAL (Start Here Tomorrow)

**P0-1: Member Confirmation Flow**

```bash
# Tests don't exist yet - need to create
# File: tests/Feature/Auth/MemberConfirmationTest.php
# Status: TO CREATE
```

**What to implement**:

- Confirmation endpoint (POST /confirm-account)
- Email template for invitations
- Login validation (check email_verified_at)

**P0-2: Unit Preferences (Lottery Core)**

```bash
# Tests don't exist yet - need to create
# File: tests/Feature/Controllers/FamilyPreferenceControllerTest.php
# Status: TO CREATE
```

**What to implement**:

- FamilyPreferenceController
- family_preferences table migration
- Unit selection filtered by family unit_type

**P0-3: Member Dashboard Expansion**

```bash
# Tests partially exist
./mtav test --pest tests/Feature/DashboardTest.php
```

**What to implement**:

- Expand dashboard with project stats
- Show family unit type
- Mock lottery event display

---

### 🟡 P1 - HIGH (Member Experience)

**P1-4: Profile Editing**

```bash
# Already implemented! ✅
./mtav test --pest --group=profile
# All passing
```

**P1-5: Family Member Invitation**

```bash
# Tests partially exist in MemberControllerTest
./mtav test --pest tests/Feature/Controllers/MemberControllerTest.php
# Need to add new tests for member-initiated invites
```

**What to implement**:

- Update MemberController@store for member context
- Hide family/project fields when member invites
- Auto-scope to member's own family

---

### 🟠 P2 - MEDIUM (Admin Features)

**P2-7: Unit CRUD**

```bash
# Many tests exist but skipped
./mtav test --pest tests/Feature/Controllers/UnitControllerCrudTest.php
# 17 skipped tests to enable as you implement
```

**P2-8: Family CRUD (+ unit_type_id)**

```bash
# Tests exist but need expansion
./mtav test --pest tests/Feature/Controllers/FamilyControllerCrudTest.php
```

**What to add**:

- unit_type_id requirement tests
- Empty family filtering tests

**P2-9: Admin Member Invitation**

```bash
# Tests exist but need expansion
./mtav test --pest tests/Feature/Controllers/MemberControllerCrudTest.php
```

---

### ⚪ P3 - LOW (Superadmin)

**P3-10: Project Creation with Admins**

```bash
# Tests mostly skipped
./mtav test --pest tests/Feature/Controllers/ProjectControllerTest.php --filter=superadmin
```

---

## How to Use Groups (Once Tagged)

**Run critical tests only**:

```bash
./mtav test --pest --group=p0
```

**Run member-focused MVP**:

```bash
./mtav test --pest --group=member-mvp
```

**Run lottery features**:

```bash
./mtav test --pest --group=lottery
```

**Exclude skipped tests**:

```bash
./mtav test --pest --group=p0 --exclude-group=skip
```

---

## TDD Workflow for Tomorrow

### Step-by-Step Process

**1. Ask "What's next?"**

```
You: "What's the next priority?"

Me: "Priority P0-1: Member Confirmation Flow
     Create: tests/Feature/Auth/MemberConfirmationTest.php
     Start with test: 'receives invitation email with token'"
```

**2. Create/Open Test File**

```bash
# I'll provide the exact file path
```

**3. Write Failing Test (RED)**

```php
it('receives invitation email with token', function () {
    // I'll provide test code
})->group('p0', 'member-mvp', 'registration');
```

**4. Run Test - Watch It Fail**

```bash
./mtav test --pest --filter="receives invitation email"
# Should fail - feature not implemented yet
```

**5. Implement Feature (GREEN)**

```php
// I'll guide you through implementation
// - Which controller to update
// - What methods to add
// - What migrations to create
```

**6. Run Test - Watch It Pass**

```bash
./mtav test --pest --filter="receives invitation email"
# Should pass now ✅
```

**7. Refactor If Needed**

```php
// Clean up code, extract methods, etc.
```

**8. Move to Next Test**

```
You: "What's next?"
Me: "Same feature, next test: 'confirmation page shows pre-filled form'"
```

---

## Files Already Tagged with Groups

✅ `tests/Feature/DashboardTest.php` - P0, member-mvp, dashboard
✅ `tests/Feature/Settings/ProfileUpdateTest.php` - P1, member-mvp, profile

**Tomorrow we'll tag more as we implement.**

---

## Quick Reference Commands

**See test priorities**:

```bash
cat tests/PRIORITIES.md
```

**See work queue**:

```bash
cat documentation/ai/WORK_QUEUE.md
```

**Run specific priority**:

```bash
./mtav test --pest --group=p0  # Critical
./mtav test --pest --group=p1  # High
./mtav test --pest --group=p2  # Medium
```

**Watch mode for current feature**:

```bash
./mtav test --pest --filter="MemberConfirmation"
```

---

## What I'll Provide Tomorrow

When you ask "what's next?":

1. ✅ **Priority level** (P0, P1, P2, P3)
2. ✅ **Feature description** from WORK_QUEUE.md
3. ✅ **Test file path** (create new or update existing)
4. ✅ **Exact test code** to write (if creating new)
5. ✅ **Implementation guidance** (controllers, migrations, etc.)
6. ✅ **Verification commands** (how to run the tests)
7. ✅ **Next step** once current test passes

---

## Ready for Tomorrow! 🚀

Just say:

- **"What's next?"** - I'll give you the next priority with tests
- **"Show me the test"** - I'll provide the test code
- **"How do I implement this?"** - I'll guide the implementation
- **"Run the tests"** - I'll give you the command

The test suite is now organized for systematic TDD implementation toward Nov 3rd MVP! 💪
