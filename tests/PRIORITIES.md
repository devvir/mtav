# Test Priorities for Nov 3rd MVP

**Last Updated**: October 27, 2025
**Deadline**: November 3, 2025 (7 days)
**Philosophy**: TDD approach - enable tests as features are implemented

---

## How to Use This Document

**Find next priority**:

```bash
# See this file for what to work on next
cat tests/PRIORITIES.md
```

**Run tests for specific priority**:

```bash
# Run all P0 (critical) tests
./mtav test --pest --group=p0

# Run member experience tests (primary focus)
./mtav test --pest --group=member-mvp

# Run lottery flow tests (core feature)
./mtav test --pest --group=lottery

# Run only currently passing tests in a priority
./mtav test --pest --group=p1 --exclude-group=skip
```

**See all priority groups**:

```bash
# List all test groups
./mtav test --pest --list-groups
```

---

## Priority Levels

**P0 - CRITICAL** (Must work for demo)

- Member registration/confirmation flow
- Unit preferences (lottery core)
- Basic member navigation

**P1 - HIGH** (Member experience)

- Family member invitation
- Profile editing
- Dashboard features

**P2 - MEDIUM** (Admin basics)

- Unit CRUD
- Family creation
- Member invitation

**P3 - LOW** (Superadmin)

- Project creation with admins
- Admin management

**POST-MVP** (After Nov 3rd)

- Advanced features
- Nice-to-haves
- Optimizations

---

## Feature → Test Group Mapping

### P0 - CRITICAL (Member Registration & Lottery Core)

#### 1. Member Confirmation Flow

**Priority**: P0 - CRITICAL
**Status**: ⚠️ NOT IMPLEMENTED
**Group**: `p0`, `member-mvp`, `registration`

**Tests**:

- `tests/Feature/Auth/MemberConfirmationTest.php` (TO CREATE)
  - ✗ receives invitation email with token
  - ✗ confirmation page shows pre-filled form
  - ✗ can set password and confirm account
  - ✗ email_verified_at is set after confirmation
  - ✗ cannot login before confirmation
  - ✗ can login after confirmation

**Implementation needs**:

- ConfirmMemberAccountController already exists
- Need to implement POST endpoint
- Need email template
- Need login validation

**Run these tests**:

```bash
./mtav test --pest --group=registration
```

---

#### 2. Unit Preferences (Lottery Core)

**Priority**: P0 - CRITICAL
**Status**: ⚠️ NOT IMPLEMENTED
**Group**: `p0`, `member-mvp`, `lottery`

**Tests**:

- `tests/Feature/Controllers/FamilyPreferenceControllerTest.php` (TO CREATE)
  - ✗ member can view units filtered by family unit_type
  - ✗ member can add unit to preferences
  - ✗ member can reorder preferences (drag & drop)
  - ✗ member can remove unit from preferences
  - ✗ preferences are saved to family_preferences table
  - ✗ member cannot select units of wrong type
  - ✗ member cannot modify other families' preferences

**Implementation needs**:

- FamilyPreferenceController (new)
- family_preferences migration (new)
- family_preferences model (new)

**Run these tests**:

```bash
./mtav test --pest --group=lottery
```

---

#### 3. Member Dashboard

**Priority**: P0 - CRITICAL
**Status**: ⚠️ PARTIALLY IMPLEMENTED
**Group**: `p0`, `member-mvp`, `dashboard`

**Tests**:

- `tests/Feature/Controllers/DashboardControllerTest.php` (EXISTS, needs expansion)
  - ✓ authenticated users can visit dashboard (EXISTS)
  - ✗ dashboard shows project summary
  - ✗ dashboard shows family unit type highlight
  - ✗ dashboard shows upcoming lottery event
  - ✗ dashboard shows family member count
  - ✗ dashboard shows gallery preview

**Implementation needs**:

- Expand DashboardController
- Add project stats
- Add mock lottery event display

**Run these tests**:

```bash
./mtav test --pest --group=dashboard
```

---

### P1 - HIGH (Member Experience Features)

#### 4. Member Profile Editing

**Priority**: P1 - HIGH
**Status**: ✅ IMPLEMENTED
**Group**: `p1`, `member-mvp`, `profile`

**Tests**:

- `tests/Feature/Settings/ProfileUpdateTest.php` (EXISTS)
  - ✓ profile page is displayed
  - ✓ profile information can be updated
  - ✓ email verification status unchanged when email unchanged

**Run these tests**:

```bash
./mtav test --pest --group=profile
```

---

#### 5. Family Member Invitation (by Members)

**Priority**: P1 - HIGH
**Status**: ⚠️ NOT IMPLEMENTED
**Group**: `p1`, `member-mvp`, `invitation`

**Tests**:

- `tests/Feature/Controllers/MemberControllerTest.php` (EXISTS, needs new tests)
  - Current: ✓ allows members to create other members
  - Need to add:
    - ✗ member can only invite to their own family (family field hidden)
    - ✗ member can only invite to their own project (project field hidden)
    - ✗ invitation sends email with confirmation link
    - ✗ invited member added to same family
    - ✗ member cannot specify different family

**Implementation needs**:

- Update MemberController@store for member-initiated invites
- Add authorization check: family must match inviter's family
- Hide family/project fields in form when member invites

**Run these tests**:

```bash
./mtav test --pest --group=invitation --filter=Member
```

---

#### 6. Theme Settings

**Priority**: P1 - HIGH
**Status**: ✅ IMPLEMENTED
**Group**: `p1`, `member-mvp`, `settings`

**Tests**:

- Feature tests not essential (frontend)
- Manual testing sufficient for MVP

---

### P2 - MEDIUM (Admin Features)

#### 7. Unit CRUD

**Priority**: P2 - MEDIUM
**Status**: ⚠️ PARTIALLY IMPLEMENTED
**Group**: `p2`, `admin-mvp`, `units`

**Tests**:

- `tests/Feature/Controllers/UnitControllerCrudTest.php` (EXISTS, many skipped)
  - ⊘ lists units for current project (SKIPPED - view not implemented)
  - ⊘ allows admin to create unit (SKIPPED - missing columns)
  - ⊘ validates required fields (SKIPPED - not implemented)
  - Many more skipped tests

**Implementation needs**:

- Complete Unit model (add description field)
- Complete UnitController CRUD
- Create Unit views
- Remove skips as features are implemented

**Run these tests**:

```bash
./mtav test --pest --group=units
```

---

#### 8. Family CRUD (Admin Creates Empty Families)

**Priority**: P2 - MEDIUM
**Status**: ✅ PARTIALLY IMPLEMENTED
**Group**: `p2`, `admin-mvp`, `families`

**Tests**:

- `tests/Feature/Controllers/FamilyControllerCrudTest.php` (EXISTS)
  - ✓ allows admins to create families
  - ✓ validates required fields
  - Need to add:
    - ✗ requires unit_type_id on creation
    - ✗ empty families visible to admins
    - ✗ empty families hidden from members in index
    - ✗ empty families available in member invitation dropdown

**Implementation needs**:

- Add unit_type_id to families table
- Update family factory
- Filter family index for members (WHERE members_count > 0)

**Run these tests**:

```bash
./mtav test --pest --group=families --filter=CRUD
```

---

#### 9. Member Invitation (by Admins)

**Priority**: P2 - MEDIUM
**Status**: ⚠️ PARTIALLY IMPLEMENTED
**Group**: `p2`, `admin-mvp`, `invitation`

**Tests**:

- `tests/Feature/Controllers/MemberControllerCrudTest.php` (EXISTS)
  - ✓ allows admins to create members
  - ✓ validates email uniqueness
  - Need to add:
    - ✗ generates confirmation token on creation
    - ✗ sends invitation email
    - ✗ sets email_verified_at to null
    - ✗ family selector shows all families (including empty)
    - ✗ family selector filtered by admin's managed projects

**Implementation needs**:

- Add confirmation_token to users table
- Update MemberController@store to generate token
- Create invitation email
- Update family selector logic

**Run these tests**:

```bash
./mtav test --pest --group=invitation --filter=Admin
```

---

### P3 - LOW (Superadmin Features)

#### 10. Project Creation with Admin Assignment

**Priority**: P3 - LOW
**Status**: ⚠️ NOT IMPLEMENTED
**Group**: `p3`, `superadmin`, `projects`

**Tests**:

- `tests/Feature/Controllers/ProjectControllerTest.php` (EXISTS, some skipped)
  - ⊘ superadmin can create project (SKIPPED)
  - Need to add:
    - ✗ project creation accepts admins array
    - ✗ creates invited admins in same transaction
    - ✗ sends invitation emails to new admins
    - ✗ validates admin emails

**Implementation needs**:

- Update ProjectController@store to accept admins array
- Create admin users with tokens
- Send invitation emails

**Run these tests**:

```bash
./mtav test --pest --group=projects --group=superadmin
```

---

## Priority Order for TDD

Work through features in this order:

1. **P0-1: Member Confirmation Flow** (registration group)
   - Enable authentication for members
   - Unblocks all other member features

2. **P0-2: Unit Preferences** (lottery group)
   - Core lottery functionality
   - Shows units filtered by type
   - Saves preference rankings

3. **P0-3: Member Dashboard** (dashboard group)
   - Member landing page
   - Shows project context

4. **P1-5: Family Member Invitation** (invitation group)
   - Members can grow their families
   - Auto-scoped to member's family

5. **P2-8: Family CRUD - unit_type_id** (families group)
   - Add unit_type_id requirement
   - Filter empty families from member view

6. **P2-7: Unit CRUD** (units group)
   - Admins can manage units
   - Unblocks lottery testing

7. **P2-9: Admin Member Invitation** (invitation group)
   - Admins can invite members
   - Send confirmation emails

8. **P3-10: Superadmin Project Creation** (projects group)
   - Project + admins in one step
   - Lower priority for demo

---

## Running Tests by Status

**All MVP tests (P0-P2)**:

```bash
./mtav test --pest --group=p0,p1,p2
```

**Only implemented features**:

```bash
./mtav test --pest --exclude-group=skip
```

**Only skipped tests (to enable as you implement)**:

```bash
./mtav test --pest --group=skip
```

**Member-centric MVP**:

```bash
./mtav test --pest --group=member-mvp
```

**Admin features**:

```bash
./mtav test --pest --group=admin-mvp
```

---

## Test Creation Checklist

When implementing a new feature:

1. **Find the feature in this document**
2. **Check if tests exist** (look for "TO CREATE" or "SKIPPED")
3. **If tests don't exist**: Create them first (TDD!)
4. **If tests are skipped**: Remove the `->skip()` as you implement
5. **Add appropriate group tags**: `->group('p0', 'member-mvp', 'registration')`
6. **Run the tests**: `./mtav test --pest --group=<your-group>`
7. **Verify they fail** (Red)
8. **Implement the feature** (Green)
9. **Refactor if needed** (Refactor)
10. **Update this document** if priorities change

---

## Current Test Status Summary

**Total Tests**: 271

- ✅ **Passing**: 134
- ⊘ **Skipped**: 64 (to be enabled as features are implemented)
- 📝 **Todo**: 73 (planned features)

**By Priority**:

- **P0** (Critical): ~20 tests (many TO CREATE or SKIPPED)
- **P1** (High): ~30 tests (some passing, some TO CREATE)
- **P2** (Medium): ~50 tests (many SKIPPED, need implementation)
- **P3** (Low): ~10 tests (mostly SKIPPED)
- **Post-MVP**: ~73 TODO tests (deferred)

---

## Next Steps

**For you (developer)**:
Ask: "What's the next priority?" and I'll tell you:

1. Which feature to work on
2. Which test file to look at
3. What tests to enable/create
4. What implementation is needed

**For AI assistant**:
When asked "what's next?":

1. Check WORK_QUEUE.md for current task
2. Reference this PRIORITIES.md for test groups
3. Provide specific test file and line numbers
4. Suggest TDD approach (write test → make it pass)

**Example workflow**:

```
You: "What's the next priority?"

AI: "Priority P0-1: Member Confirmation Flow

Tests to create/enable:
- tests/Feature/Auth/MemberConfirmationTest.php (create new file)

Start with:
1. Create test file with group tags: ->group('p0', 'member-mvp', 'registration')
2. Write test: 'receives invitation email with token'
3. Run: ./mtav test --pest --group=registration
4. Watch it fail (RED)
5. Implement ConfirmMemberAccountController@store
6. Make test pass (GREEN)

Implementation files needed:
- app/Http/Controllers/Auth/ConfirmMemberAccountController.php (update)
- app/Mail/MemberInvitation.php (create)
- resources/views/emails/member-invitation.blade.php (create)
- database/migrations/add_confirmation_token_to_users.php (create)"
```
