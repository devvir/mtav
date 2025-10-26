# MTAV Work Queue

> **Purpose**: Temporary tracking document for tasks, ideas, and items that need attention later. Not part of the core KB - this is AI-managed workspace for helping organize your workflow.
>
> **Usage**: When you tell me something needs to be handled later, I'll add it here. Ask "what's next?" or "what should I work on now?" and I'll refer to this list.

---

## 🎯 CRITICAL MILESTONE: Nov 3rd MVP

**Deadline**: November 3, 2025 (8 days away)
**Goal**: Functional app ready for test users (tech & non-tech volunteers from cooperative projects)
**Purpose**: Early feedback gathering from real cooperative housing context

### Superadmin Role - MVP Scope

- [ ] **Project Management**
  - Create new projects (name, description, organization)
  - Add admins to projects (during creation + Quick Actions → New Admin)
  - View ALL projects in seeded database (Projects nav)

- [ ] **Project Context Switching**
  - Multi-project context: See all projects, all members/families across projects
  - Single-project context: Select a project → Dashboard + Gallery nav appear, Members/Families filtered to selected project
  - Deselect project: Return to multi-project context (hide Dashboard/Gallery, show all members/families)

- [ ] **Navigation Behavior**
  - Multi-project: Projects, Members (all), Families (all)
  - Single-project: Dashboard, Gallery, Projects, Members (filtered), Families (filtered)

### Admin Role - MVP Scope

- [ ] **Family & Member Management**
  - Create families
  - Edit families
  - Invite members (to families in managed projects)
  - Edit members

- [ ] **Project Management**
  - Switch between projects (if admin manages multiple)
  - Create other admins for managed projects
  - Context awareness: Multi-project if managing >1, single-project if managing 1

- [ ] **Navigation & Filtering**
  - Same context switching logic as superadmin
  - Members/Families filtered by managed projects

- [ ] **Additional Features** (TBD - "we'll see what other features we can implement")
  - Determine based on time/priority

### Member Role - MVP Scope ⭐ HIGHEST PRIORITY

**Goal**: Implement as much of Member Guide as possible

- [ ] **Profile & Settings**
  - Update profile (name, avatar, password)
  - Access settings (bottom left → Settings)
  - Change theme (light/dark/system)
  - Change language (EN/ES_UY)
  - Remember me on login
  - Logout

- [ ] **Dashboard**
  - View project info
  - See family status
  - View lottery results (if lottery run)
  - Quick actions access

- [ ] **Family Preferences**
  - Browse available units (filtered by family's unit type)
  - Add units to preferences
  - Drag to reorder preferences (rank 1-N)
  - Save preferences
  - Edit preferences anytime before lottery
  - Receive email notifications for unit changes/additions

- [ ] **Events**
  - View project events
  - RSVP: "Asistiré" / "No Asistiré" / "Tal Vez"
  - Change RSVP anytime

- [ ] **Upload Media**
  - Quick Actions → Upload Multimedia
  - Upload photos/videos
  - Add descriptions and tags
  - View in gallery

- [ ] **Invite Family Members**
  - Quick Actions → Invite Family Member
  - Send invitation email
  - New member auto-joins family in same project
  - No admin approval required

- [ ] **Browse Members/Families**
  - Members nav → See all families
  - Toggle to view individual members (Families/Members button)

- [ ] **Lottery Results**
  - View assigned unit
  - See satisfaction rating (1-5 stars)
  - Results are final

- [ ] **Leaving Family/Project** (Lower priority - can defer if time tight)
  - Family atomicity: entire family leaves together
  - Cannot rejoin without admin approval

### 🚧 Open Questions for Nov 3rd

- [ ] **Units Handling**
  - Scope unclear - needs refinement
  - REMINDER: Revisit this before Nov 3rd
  - Minimum: Families need unit types, units need to exist for preferences
  - Full CRUD? Blueprints? TBD

### 📋 Scope Refinement Needed

- Admin additional features beyond family/member/admin management
- Member Guide sections that might be deferred if time-constrained
- Units management depth

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

---

## Completed

_Items will move here once done_

---

**Last Updated**: 2025-10-26
