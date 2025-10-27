# MTAV Work Queue

> **Purpose**: Temporary tracking document for tasks, ideas, and items that need attention later. Not part of the core KB - this is AI-managed workspace for helping organize your workflow.
>
> **Usage**: When you tell me something needs to be handled later, I'll add it here. Ask "what's next?" or "what should I work on now?" and I'll refer to this list.

---

## 🎯 CRITICAL MILESTONE: Nov 3rd MVP

**Deadline**: November 3, 2025 (7 days away)
**Goal**: Working app with complete lottery flow, even if simplified (mock lottery, no blueprints/plans)
**Philosophy**: Core feature (lottery) must work end-to-end. Optional features can be missing.

---

### A. Superadmin Use Cases - Nov 3rd

#### 1. Create Project + Add Admins (Single Step)

- [ ] **Project creation form includes admin assignment**
  - Fields: Project name, description, organization
  - Multi-select or repeater for admins (email, firstname, lastname)
  - Creates project + invited admins in one transaction

**Implementation needs**:

- Update `ProjectController@store` to accept admins array
- Create admin users with random passwords
- Send invitation emails with confirmation links
- Set `email_verified_at = null` until confirmation

#### 2. Add/Remove Admins for Existing Project (Single Step)

- [ ] **Project edit form includes admin management**
  - Show current admins with remove buttons
  - Add new admins (email, firstname, lastname)
  - Updates happen in one save action

**Implementation needs**:

- Update `ProjectController@update` to handle admins array
- Handle admin removal: DETACH from project (remove from `project_user` pivot, keep user record)
- Send invitation emails to newly added admins

**Notes**:

- Superadmins can do EVERYTHING admins/members can do (inheritance)
- For MVP: Only validate these superadmin-exclusive powers
- Other abilities (editing profile, etc.) already work but don't need explicit validation

---

### B. Admin Use Cases - Nov 3rd

#### 1. Configure Project: Units CRUD

- [ ] **Unit management for managed projects**
  - Create units: name/number, description (minimum fields)
  - Edit units: update name, description
  - Delete units (soft delete?)
  - NO blueprints, NO graphical plans (post-Nov 3rd)
  - NO detailed characteristics yet (square meters, bedrooms, etc. - optional for MVP)

**Implementation needs**:

- `UnitController` with basic CRUD
- `Unit` model already exists, just needs description field?
- Authorization: Admin can manage units in their projects only
- NO `unit_type_id` YET (see note below about phased approach)

#### 2. Create Families (Empty on Creation)

- [ ] **Family creation form**
  - Fields: Name, Project (if multi-project context), UnitType (REQUIRED)
  - Family created empty (no members yet)
  - UnitType MUST be selected on creation
  - Empty families available in dropdowns (for member invitations)
  - Empty families HIDDEN from family index (members can't see empty families)
  - Empty families VISIBLE to admins (with indicator: "0 members")

**Context-aware behavior**:

- **Multi-project context**: Show project selector + unit type selector (filtered by project)
- **Single-project context**: Hide project field (auto-set), show unit type selector

**Implementation needs**:

- Family model already exists
- Add `unit_type_id` field to families table (NOT NULL)
- Update family factory to require unit_type_id
- Filter family index queries: Members see `families.members_count > 0`, Admins see all
- UnitType selector shows types from relevant project only

#### 3. Invite Members into Families

- [ ] **Member invitation (including empty families)**
  - Fields: Email, firstname, lastname (optional), Family selector
  - Family selector shows ALL families (including empty) from relevant projects
  - Backend creates user with random password, `email_verified_at = null`
  - Generate confirmation token (new field: `confirmation_token`)
  - Send invitation email with confirmation link
  - Member's project auto-set from family's project (cascade)

**Context-aware behavior**:

- **Multi-project context**: Family selector shows families from ALL managed projects
- **Single-project context**: Family selector shows families from CURRENT project only

**Implementation needs**:

- Add `confirmation_token` field to users migration
- Update `MemberController@store` to generate token, send email
- Create invitation email template (Mailable)
- Confirmation link: `/confirm-account?email={email}&token={token}`

#### 4. Invite Admins

- [ ] **Admin invitation**
  - Similar to member invitation
  - Fields: Email, firstname, lastname (optional), Project(s) selector
  - No family (family_id = null for admins)
  - Backend creates user with `is_admin = true`, random password, `email_verified_at = null`
  - Generate confirmation token
  - Send invitation email

**Context-aware behavior**:

- **Multi-project context**: Multi-select for projects (only managed projects shown)
- **Single-project context**: Single-select disabled, current project pre-selected

**Implementation needs**:

- `AdminController@store` or reuse `UserController`?
- Project assignment via `project_user` pivot (can be multiple)
- Invitation email for admins (different copy than members)

#### 5. List Projects (If Managing Multiple)

- [ ] **Projects index**
  - Only shown if admin manages >1 project
  - Lists ONLY managed projects
  - If admin has 1 project: auto single-project context, no Projects nav link
  - Authorization: `GET /projects` allowed only if managing multiple

**Implementation needs**:

- Update Projects index controller to filter by managed projects
- Update nav logic: hide Projects link if admin.managed_projects.count == 1
- Policy: `viewAny` checks managed projects count

#### 6. List Families/Members

- [ ] **Members index with context filtering**
  - **Multi-project context**: Show families/members from ALL managed projects
  - **Single-project context**: Show families/members from CURRENT project only
  - Same toggle behavior as superadmin (Families view ↔ Members view)

**Implementation needs**:

- Already mostly implemented?
- Verify filtering logic respects admin's managed projects

**Notes**:

- Admins inherit member abilities (profile editing, etc.) but don't need explicit validation for MVP
- Focus on admin-exclusive powers listed above

---

### C. Member Use Cases - Nov 3rd

#### 1. Complete Registration (Confirmation Flow)

**1.a Receive Invitation Email**

- [ ] **Email template with proper copy**
  - What is MTAV (brief intro)
  - Who invited them (admin/member name)
  - What family they're joining
  - What project it is
  - Confirmation link with token

**Implementation needs**:

- Mailable: `MemberInvitation`
- Template: `resources/views/emails/member-invitation.blade.php`
- Subject: "You've been invited to join {Project} in MTAV"
- Copy in EN + ES_UY

**1.b Confirmation Endpoint**

- [ ] **Account confirmation page**
  - Route: `GET /confirm-account?email={email}&token={token}`
  - Controller: `ConfirmMemberAccountController@show` (already exists!)
  - View: `Auth/ConfirmMemberAccount.vue` (already exists!)
  - Shows: Email (non-editable), Name (editable, pre-filled), Lastname (optional), Avatar (optional), Password (REQUIRED)
  - Avatar default: Initials-based from API (e.g., ui-avatars.com)

**Implementation needs**:

- Update existing `ConfirmMemberAccount` controller
- Add POST endpoint: `POST /confirm-account` to process form
- Validate token matches user's `confirmation_token`
- Update user: set password, set `email_verified_at = now()`, optionally update name/avatar
- Clear or keep token (for auditing)
- Redirect to login with success message

**1.c Block Login Until Confirmed**

- [ ] **Prevent login for unconfirmed users**
  - Login validation: Check `email_verified_at` is not null
  - If null: Show error "Please confirm your account first. Check your email."
  - Password is random on creation, so users can't log in anyway
  - Extra safety: explicit check in auth

**Implementation needs**:

- Add validation to login controller
- Check `email_verified_at` before authenticating

#### 2. Automatic Single-Project Context

- [ ] **Members always in single-project context**
  - No Projects nav link (they only have 1 project)
  - Dashboard, Gallery, Members links visible
  - All resources auto-scoped to their family's project

**Implementation needs**:

- Nav logic: Members never see Projects link
- Context: Always single-project for members
- Already implemented? Verify behavior

#### 3. Edit Profile

- [ ] **Profile management**
  - Name (editable, required)
  - Lastname (editable, optional)
  - Email (editable, requires re-confirmation? Or admin-only?)
  - Avatar (editable, optional)
  - Password change (separate form)

**Implementation needs**:

- Already implemented in Settings
- Verify Member Guide alignment

#### 4. Theme Settings

- [ ] **Dark/Light/System theme**
  - Already implemented?
  - Verify toggle works

#### 5. Invite Family Members

- [ ] **Family member invitation**
  - Quick Actions → Invite Family Member
  - Fields: Email, firstname, lastname (optional)
  - Family HIDDEN (auto-set to member's family)
  - Project HIDDEN (auto-set to member's project)
  - Backend: Create user in member's family + project
  - Send invitation email (similar to admin invitation)

**Implementation needs**:

- Check if this is already implemented
- If not: Similar to admin inviting member, but family/project hidden and forced
- Authorization: Members can only invite to their own family

#### 6. Dashboard Features

- [ ] **Project summary**
  - Project name, description
  - Family count, member count, admin count
  - UnitTypes list (highlighting family's assigned type)
  - Units summary (total, by type)
  - Events preview (lottery event with mock date)
  - Gallery preview (mock images)

**Implementation needs**:

- Dashboard controller + view
- Fetch project stats
- Highlight family's unit type
- Show mock lottery event

#### 7. Unit Preferences (CORE LOTTERY FEATURE)

- [ ] **Browse + Select Preferences**
  - View available units (filtered by family's unit_type_id)
  - Add units to preferences list
  - Drag to reorder (rank 1, 2, 3, ...)
  - Save preferences to `family_preferences` table
  - Edit anytime before lottery execution

**Implementation needs**:

- `FamilyPreferenceController` with CRUD
- `family_preferences` table migration
- `FamilyPreference` model
- UI: Drag-and-drop for ranking
- Validation: unit must match family's unit type, unit must be unassigned
- Authorization: Members can manage their own family's preferences

**This is REQUIRED for Nov 3rd** - lottery needs preferences!

---

### D. NOT Required for Nov 3rd

**Explicitly out of scope** (implement after MVP):

- [ ] ❌ Events CRUD (only show mock lottery event)
- [ ] ❌ Gallery file upload (only show mock images)
- [ ] ❌ Real lottery API integration (use mock/random only)
- [ ] ❌ Unit blueprints / graphical plans
- [ ] ❌ Unit detailed characteristics (bedrooms, bathrooms, square meters)
- [ ] ❌ Satisfaction scores (1-5 stars) - requires lottery execution
- [ ] ❌ Lottery invalidation (superadmin power for post-MVP)
- [ ] ❌ Contact admin feature
- [ ] ❌ RSVP to events
- [ ] ❌ Member leaving family/project flow
- [ ] ❌ Family moving between projects
- [ ] ❌ Admin abilities beyond core powers (profile editing works but not validated)
- [ ] ❌ Superadmin abilities beyond core powers

---

### E. REQUIRED for Nov 3rd: Lottery Flow (Mock)

**Goal**: Complete lottery flow works end-to-end with random assignments

#### Core Dependencies (MUST implement)

##### 1. UnitType System

- [ ] **Create `unit_types` table migration**
  - Fields: id, project_id, name, description, timestamps
  - Unique: (project_id, name)

- [ ] **Create `UnitType` model**
  - Relationships: belongsTo(Project), hasMany(Unit), hasMany(Family)

- [ ] **Add `unit_type_id` to `families` table**
  - Nullable initially? Or required?
  - Set by admin when creating/editing family

- [ ] **Add `unit_type_id` to `units` table**
  - Required (units must have a type)
  - Set by admin when creating unit

- [ ] **UnitType CRUD for admins**
  - Create, edit, delete unit types
  - Simple form: name, description
  - Authorization: Admins can manage types in their projects

##### 2. Family Preferences System

- [ ] **Create `family_preferences` table migration**
  - Fields: id, family_id, unit_id, rank, timestamps
  - Unique: (family_id, unit_id), (family_id, rank)
  - Foreign keys with cascade delete

- [ ] **Create `FamilyPreference` model**
  - Relationships: belongsTo(Family), belongsTo(Unit)
  - Validation: unit type matches family type, rank unique per family

- [ ] **Preference CRUD for members**
  - Create, update, delete preferences
  - Reorder (update ranks)
  - Scoped to member's family only

##### 3. Lottery Execution (Mock Strategy)

- [ ] **Create `DummyLotteryService`**
  - Implements simple random assignment algorithm
  - Input: Project ID
  - Process:
    1. Get all families with preferences in project
    2. Get all unassigned units in project
    3. For each family: randomly pick from their preferred units (if available)
    4. If no preferred units available: assign any random unit of matching type
    5. Return assignments: `[{ family_id, unit_id }]`
  - No satisfaction scores needed for MVP

- [ ] **Lottery execution endpoint**
  - Route: `POST /projects/{project}/lottery/execute`
  - Controller: `LotteryController@execute`
  - Authorization: Admin or Superadmin only
  - Process:
    1. Validate lottery not already run: Check if ANY units have family_id set
    2. If already run: Return error "Lottery already executed for this project. Results are final."
    3. Call DummyLotteryService
    4. Update units.family_id with assignments
    5. Return success response
  - **ONE TIME ONLY**: Lottery cannot be re-run once executed

- [ ] **Lottery results display**
  - Members see their assigned unit in Dashboard
  - Show unit details (name, type)
  - Show "Results are final" message
  - No satisfaction scores for MVP

#### Optional for Nov 3rd (if time permits)

- [ ] **Lottery history/audit table**
  - Track when lottery was executed, by whom
  - Store results snapshot
  - Useful for auditing and debugging

---

### F. Phased Implementation Strategy

**This is the order of implementation to get lottery working**:

**Phase 0: User Confirmation (Week 1)**

1. Add `confirmation_token` field to users
2. Update member/admin creation to generate token
3. Build confirmation email templates
4. Build confirmation endpoint (GET + POST)
5. Block login until confirmed

**Phase 1: Unit Types (Week 1)**

1. Create migration + model for UnitType
2. Add unit_type_id to families table (NOT NULL - required on creation)
3. Add unit_type_id to units table (NOT NULL - required on creation)
4. Build UnitType CRUD controllers + views
5. Update family creation form to require unit type selection
6. Update unit creation form to require unit type selection

**Phase 2: Family Preferences (Week 1-2)**

1. Create migration + model for FamilyPreference
2. Build preference CRUD controllers
3. Build preference UI (browse units, add, reorder, save)
4. Add validation (type matching, uniqueness)
5. Authorization policies

**Phase 3: Mock Lottery (Week 2)**

1. Create DummyLotteryService (simple random algorithm)
2. Implement assignment logic (prefer family preferences, fallback to random)
3. Build lottery execution controller with one-time-only validation
4. Update units.family_id on execution (permanent assignment)
5. Display results in member dashboard ("Results are final")
6. Extensive seeder: Create multiple demo projects for testing

**Phase 4: Polish (Week 2)**

1. Dashboard stats and summaries
2. Mock lottery event display
3. Email notifications for invitations
4. UI polish and testing
5. Bug fixes

---

### H. Member-Centric Implementation Order 🎯

**Focus**: Member experience is PRIMARY. Admin/Superadmin features are SECONDARY.
**Strategy**: Follow the lifecycle of a member from invitation to daily usage.

**Technical Context**:

- **MaybeModal component**: Custom wrapper allowing Vue components to render as modal (centered), slideover (from right), OR regular page
- **Modal-capable**: All show views wrapped in MaybeModal (members, families, admins, projects)
- **InertiaUI Modal**: Using customized inertiaui-modal library for modal functionality
- **Clickable entities**: Members, families, admins should open show views in modals when clicked

#### Step 0: Superadmin Configuration (Quick Fix - Do First!)

**Problem**: Tests inadvertently create superadmins using auto-increment IDs. Need email-based identification instead.

- [ ] **Change superadmin identification from IDs to emails**
  - Update `config/auth.php`: Change `'superadmins' => [1]` to `'superadmins' => ['superadmin@example.com']`
  - Update `User::isSuperAdmin()` method to check email instead of ID
  - Update any policies/gates that check superadmin status

- [ ] **Create default superadmin in migration (not seeder)**
  - Create migration: `create_default_superadmin_user.php`
  - Insert user: email `superadmin@example.com`, firstname "Super", lastname "Admin", is_admin true
  - **Password strategy**: Use env variable `SUPERADMIN_DEFAULT_PASSWORD` with fallback
  - In migration: `'password' => Hash::make(env('SUPERADMIN_DEFAULT_PASSWORD', 'changeme123'))`
  - Set `email_verified_at = now()`
  - Remove from seeders (should only exist via migration)

- [ ] **Production safety for default password**
  - ✅ **Chosen: Option A (Env-based password)**
    - `.env.example`: `SUPERADMIN_DEFAULT_PASSWORD=changeme123` (with warning comment)
    - Production `.env`: Set secure password (not in repo)
    - Migration: `'password' => Hash::make(env('SUPERADMIN_DEFAULT_PASSWORD', 'changeme123'))`
    - Rationale: Balance of security and convenience for community project
    - Works out of the box in dev, secure in production with proper env config
    - Note: Add prominent comment in `.env.example`: "⚠️ CHANGE THIS IN PRODUCTION!"

- [ ] **Update tests**
  - Remove any hardcoded ID checks for superadmin
  - Use email checks: `$user->email === 'superadmin@example.com'`
  - Tests can create superadmin via: `User::factory()->create(['email' => 'superadmin@example.com', 'is_admin' => true])`

**Estimated time**: 30-60 minutes (you said it's easy, so putting it first!)

---

#### Step-by-Step Implementation (Member Journey)

**1. Family Creation (Admin prerequisite)**

- [ ] Admin can create family with name + unit_type_id in existing project
- [ ] Empty family visible to admins, hidden from members
- [ ] Family available in member invitation dropdown

**2. Member Invitation**

- [ ] Admin/Member can invite new member via form (email, firstname, lastname optional, family)
- [ ] Backend: Create user with random password, `email_verified_at = null`, `confirmation_token`
- [ ] Validation: Email unique, family exists, unit_type_id inherited from family

**3. Invitation Email**

- [ ] Member receives email with:
  - MTAV introduction
  - Who invited them (name)
  - Family they're joining
  - Project name
  - Confirmation link with token: `/confirm-account?email={email}&token={token}`
- [ ] Template in EN + ES_UY

**4. Confirmation Link**

- [ ] Route: `GET /confirm-account` (already exists)
- [ ] Shows: Email (non-editable), Name (editable, pre-filled), Lastname (optional), Avatar (optional), Password (REQUIRED)
- [ ] Avatar default: Initials-based (ui-avatars.com or similar)

**5. Submit Confirmation**

- [ ] Route: `POST /confirm-account`
- [ ] Validate token matches user's `confirmation_token`
- [ ] Update: password (hashed), name, lastname, avatar, `email_verified_at = now()`
- [ ] Clear or keep token (for auditing)

**6. Redirect to Login**

- [ ] After successful confirmation: Redirect to `/login`
- [ ] Email pre-filled in login form
- [ ] Success message: "Account confirmed! Please log in."
- [ ] Allows password manager to save credentials

**7. First Login → Dashboard**

- [ ] Member logs in
- [ ] Validation: Check `email_verified_at` is not null
- [ ] Redirect to `/dashboard` (member landing page)
- [ ] Context: Single-project (members only have 1 project)

**8. Navigation**

- [ ] Sidebar nav visible:
  - Dashboard (active)
  - Members (families + members toggle)
  - Gallery
  - Settings (bottom left)
- [ ] No Projects link (members can't switch projects)
- [ ] All resources scoped to member's family's project

**9. Units & Preferences**

- [ ] Dashboard shows units filtered by member's family unit_type_id
- [ ] Member can add units to preferences (drag-and-drop to rank)
- [ ] Save preferences to `family_preferences` table
- [ ] Edit preferences anytime before lottery
- [ ] UI: Clear indication which units match their type

**10. Quick Actions → Invite Family Member**

- [ ] Click Quick Actions button
- [ ] Shows: "Invite Family Member"
- [ ] Opens invitation form (modal or slideover)
- [ ] Fields: Email, firstname, lastname (optional)
- [ ] Family HIDDEN (auto-set to member's family)
- [ ] Project HIDDEN (auto-set to member's project)

**11. Family Member Invitation Flow**

- [ ] Member submits invitation
- [ ] Backend: Same as step 2 (create user, send email)
- [ ] New member follows steps 3-7
- [ ] New member joins same family in same project

**12. Logout**

- [ ] Logout button in sidebar (bottom left, near Settings)
- [ ] Clears session
- [ ] Redirects to login page

**13. Update Profile**

- [ ] Settings → Profile
- [ ] Edit: Name (required), Lastname (optional), Email (editable?), Avatar (upload or URL)
- [ ] Save updates user record
- [ ] Success toast notification

**14. Update Theme**

- [ ] Settings → Theme
- [ ] Options: Light, Dark, System
- [ ] Persists preference (localStorage + DB?)
- [ ] Immediate visual feedback

**15. Update Password**

- [ ] Settings → Password
- [ ] Fields: Current password, New password, Confirm new password
- [ ] Validation: Current password correct, new passwords match
- [ ] On success: Log out user, redirect to login
- [ ] Message: "Password updated. Please log in with your new password."

**16. Dashboard Components**

- [ ] **a. Project Summary**
  - Reuse logic from `projects/show`
  - Show: Name, description, organization, active status
  - Stats: Family count, member count, admin count, unit count

- [ ] **b. Units Display**
  - Show ALL units in project
  - Highlight units matching member's family unit_type_id (border, badge, color)
  - Group by unit type or list with type labels
  - Show: Unit name, type, status (available/assigned)

- [ ] **c. Gallery Preview**
  - Show 3-5 mock images from gallery
  - "View all" link to full gallery page
  - Reuse existing gallery mock data

- [ ] **d. Family Summary**
  - Reuse logic from `families/show`
  - Show: Family name, avatar, creation date
  - Members list (names + avatars)
  - Click member → Opens member show modal

- [ ] **e. Events Block**
  - Show mock Lottery event ONLY
  - Fields: Name ("Unit Distribution Lottery"), description, date (mock: 30-90 days from project creation)
  - No RSVP for MVP (just display)
  - Style: Highlighted/prominent card

- [ ] **f. Current Preferences**
  - Show member's family preferences (initially empty)
  - List: Ranked units (1st choice, 2nd choice, etc.)
  - Buttons: "Edit Preferences" (opens preference management UI), "Add Preference"
  - If empty: "No preferences yet. Add your preferred units to participate in the lottery."

- [ ] **g. Project Admins**
  - List all admins for current project
  - Show: Name, avatar, email
  - "Contact" button next to each admin
  - Click contact → Opens modal form (subject, message fields)
  - Submit contact → Dummy success message "Message sent to [Admin Name]"
  - No actual email sent for MVP (just UI/UX)

**Modal Interactions**:

- [ ] Click member anywhere → Opens `Members/Show` in modal
- [ ] Click family anywhere → Opens `Families/Show` in modal
- [ ] Click admin anywhere → Opens `Admins/Show` in modal (or Members/Show with admin flag?)
- [ ] All show views already MaybeModal-wrapped (should work out of the box)

---

### I. Admin/Superadmin Features (Secondary Priority)

**Only implement these AFTER member flow is complete**:

- [ ] Project creation with admin assignment
- [ ] Project edit with admin add/remove
- [ ] Unit CRUD for admins
- [ ] UnitType CRUD for admins
- [ ] Family creation with unit_type_id
- [ ] Admin invitation flow
- [ ] Multi-project context switching
- [ ] Lottery execution button (admin only)
- [ ] Lottery results validation

**Strategy**: Get member experience perfect first. Admin features can be rough/minimal as long as they unblock member testing.

---

### G. Decisions Made ✅

- [x] **Family `unit_type_id`: Required on creation or optional?**
  - ✅ **REQUIRED** - Family creation form must include unit type selection
  - Validation: Cannot create family without unit_type_id
  - Ensures families can immediately start selecting preferences

- [x] **Satisfaction scores: Where to store?**
  - ✅ **NOT NEEDED for Nov 3rd MVP**
  - Remove from lottery implementation checklist
  - Can be added post-MVP if desired

- [x] **Lottery execution: Can it be run multiple times?**
  - ✅ **ONE TIME ONLY per project**
  - Once lottery is executed, results are FINAL
  - Validation: Block re-execution if any units have family_id set
  - Testing strategy: Use extensive seeder to create multiple demo projects
  - Users can test on different projects if they want to try again
  - Focus: Member experience (tutor/stakeholders will test superadmin/admin flows)

- [x] **Empty families: Show in family index or not?**
  - ✅ **Members**: Hide empty families (filter `member_count > 0`)
  - ✅ **Admins**: Show all families with member count indicator
  - Empty families appear in dropdowns for invitations (admins only)

- [x] **Admin removal: Soft delete or just detach?**
  - ✅ **DETACH from project** (remove from `project_user` pivot)
  - Keep user record intact for auditing purposes
  - User can still be admin in other projects
  - Only soft delete user if they leave ALL projects (edge case, post-MVP)

---

## Current Priority Items

### � Implementation Status Tracking

**Philosophy**: KB documents the COMPLETE/DESIRED system. This section tracks what's implemented vs pending.

**Test Strategy**:

- Write tests for ALL KB features (complete system)
- Skip tests for unimplemented features using `->skip('Not yet implemented')`
- Unskip as features are built
- Tests act as living TODO list and implementation validation

#### 🔨 Implementation Status by Feature Area

**Phase 0: User Identity (legal_id)**

- Status: ⏳ PENDING - Not implemented
- KB Status: ✅ Fully documented as desired state
- Tests: ⏸️ Write tests, mark as skipped
- Implementation: Simple (1 migration, validation, policies)
- Decision: Implement when prioritized

**Phase 1: Unit Types System**

- Status: ⏳ PENDING - Not implemented
- KB Status: ✅ Fully documented as desired state
- Components Missing:
  - `unit_types` table + migration
  - `UnitType` model + relationships
  - `unit_type_id` in families table
  - `unit_type_id` in units table
  - CRUD controllers + policies
- Tests: ⏸️ Write tests, mark as skipped
- Blocks: Family Preferences, Lottery
- Decision: Defer or prioritize based on Nov 3rd scope

**Phase 2: Family Preferences**

- Status: ⏳ PENDING - Not implemented
- KB Status: ✅ Fully documented as desired state
- Components Missing:
  - `family_preferences` table + migration
  - `FamilyPreference` model
  - Preference controller (CRUD)
  - Validation (type match, uniqueness)
  - Authorization policies
- Tests: ⏸️ Write tests, mark as skipped
- Depends On: Unit Types (Phase 1)
- Blocks: Lottery (Phase 6)
- Decision: Defer or prioritize based on Nov 3rd scope

**Phase 3-5: Blueprint & Setup Features**

- Status: ⏳ PENDING - Lower priority
- KB Status: ✅ Documented as optional/future
- Decision: Defer to post-MVP

**Phase 6: Lottery Service**

- Status: ⏳ PENDING - Not implemented
- KB Status: ✅ Fully documented (DummyLotteryService + ExternalLotteryService)
- Components Missing:
  - LotteryServiceInterface
  - DummyLotteryService implementation
  - ExternalLotteryService stub
  - Configuration/service provider
- Tests: ⏸️ Write tests, mark as skipped
- Depends On: Unit Types, Family Preferences
- Decision: Defer or prioritize based on Nov 3rd scope

**Phase 7: Lottery Execution Workflow**

- Status: ⏳ PENDING - Not implemented
- KB Status: ✅ Fully documented as desired state
- Components Missing:
  - LotteryController
  - Execution validation
  - Result parsing & DB updates
  - Audit tables (`lottery_executions`, `unit_assignments`)
- Tests: ⏸️ Write tests, mark as skipped
- Depends On: Lottery Service (Phase 6)
- Decision: Defer or prioritize based on Nov 3rd scope

**Phase 8: Satisfaction Scores & Post-Lottery**

- Status: ⏳ PENDING - Not implemented
- KB Status: ✅ Fully documented (1-5 star ratings)
- Components Missing:
  - `satisfaction_score` field in assignments
  - `family_preference_rank` tracking
  - Display logic in UI
  - Assignment constraints
- Tests: ⏸️ Write tests, mark as skipped
- Depends On: Lottery Execution (Phase 7)
- Decision: Defer or prioritize based on Nov 3rd scope

#### ✅ Currently Implemented (Working)

**Core User System**:

- ✅ Users table (STI: Admin/Member)
- ✅ User model with type checking
- ✅ Global scopes for Admin/Member
- ✅ Soft deletes
- ✅ Authentication & sessions

**Project Management**:

- ✅ Projects table
- ✅ Project model
- ✅ project_user pivot (active flag)
- ✅ Basic CRUD

**Family Management**:

- ✅ Families table
- ✅ Family model
- ✅ Family-project relationship
- ✅ Basic CRUD

**Member Features (Partial)**:

- ✅ Profile management (name, avatar, password)
- ✅ Settings (theme, language)
- ✅ View families/members
- ⏳ Media upload - PENDING
- ⏳ Invitations - PENDING
- ⏳ Events/RSVP - PENDING

#### 🎯 Next Actions

**Before any new implementation**:

1. Write tests for the feature (from KB specs)
2. Mark tests as `->skip('Not yet implemented')`
3. Implement feature to make tests pass
4. Remove skip annotation
5. Verify tests are green

**Scope Discussion (Next Few Days)**:

- Review Nov 3rd checklist
- Assess remaining work complexity
- Decide which phases to include/defer
- Update Work Queue with final scope

### Documentation Review

- [ ] **Finish reviewing Member Guide - Last 2 sections**
  - Status: In progress
  - Context: There are things that need to be changed or discussed in the final sections
  - Next: Review and provide feedback on remaining content

---

## Backlog

### Testing Strategy & Coverage (Post-Nov 3rd)

- [ ] **Review Generated Backend Tests**
  - Context: Backend tests were generated but haven't been thoroughly reviewed yet
  - Priority: Important but not blocking MVP - defer until after Nov 3rd
  - Goal: 100% coverage of features and rules (not necessarily 100% line coverage)
  - Action items:
    - Review existing test suite for alignment with current KB
    - Ensure all business rules are tested
    - Verify all features have corresponding tests
    - Check for gaps in rule/feature coverage
  - Note: Request periodic reviews from AI to ensure tests stay aligned with KB as features evolve

- [ ] **Frontend Testing Implementation (Post-Nov 3rd)**
  - **Vitest**: Vue component and composable tests
  - **Cypress**: End-to-end UI tests
  - Status: Not started - postponed until after Nov 3rd MVP
  - Rationale: Focus on core functionality first, comprehensive testing comes after

### Bug Tracking & Prioritization

- [ ] **Systematic Bug Review Process**
  - Context: AI has spotted bugs during development that need tracking
  - Need: Define workflow for bug prioritization and fixing
  - Action: Review AI-spotted bugs list and prioritize by severity/impact
  - Goal: Don't neglect bugs while working on new features
  - Reminder: "Go through the bugs you spotted, so I can prioritize and fix the most important ones"

### Documentation Organization & Architecture (Future)

- [ ] **Documentation Structure Review & Reorganization**
  - Context: Documentation has grown organically - needs intentional organization
  - Considerations to define:
    - **Audience**: AI vs Devs vs Self vs Public
    - **Permanence**: Permanent docs vs temporary (like Work Queue)
    - **Translation needs**: Which docs need ES_UY versions, which stay EN-only
    - **Scope**: App functionality vs CI/CD vs Infrastructure
    - **Location**: Keep in main repo vs separate repo (git submodule option)
  - Current state: Multiple documents without clear organizational principles
  - Goal: Clear understanding and big picture of documentation ecosystem
  - Action items:
    - Define organizational principles and categories
    - Map existing documents to categories
    - Decide storage strategy (monorepo vs submodule)
    - Create documentation about documentation (meta-docs)
    - Establish guidelines for future doc creation

### Infrastructure & Deployment (Post-Nov 3rd)

- [ ] **Fix Nginx DNS Resolution for Production Deployments**
  - Issue: Nginx needs manual restart to find new php/assets containers after replacement
  - Solution: Configure Nginx to use Docker's embedded DNS with resolver directive
  - From research: "To refresh Nginx DNS when replacing an assets container in Docker Compose, you can configure Nginx to use Docker's embedded DNS by setting a resolver in your Nginx configuration. This allows Nginx to dynamically resolve the container's IP address, ensuring it always points to the correct instance."
  - Priority: Not urgent - can wait until after Nov 3rd MVP
  - Impact: Currently works but requires manual intervention (restart)
  - Goal: Zero-downtime deployments with automatic container discovery

---

## Completed

_Items will move here once done_

---

**Last Updated**: 2025-10-26
