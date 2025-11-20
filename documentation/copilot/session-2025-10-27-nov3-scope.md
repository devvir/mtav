# MTAV Copilot Session - Oct 27, 2025: Nov 3rd Scope Refinement

**Date**: October 27, 2025
**Duration**: ~1 hour
**Focus**: Clarifying Nov 3rd MVP scope, member-centric implementation order, key architectural decisions

---

## Session Overview

User provided detailed clarification on Nov 3rd MVP scope, emphasizing that lottery is CORE (not optional) and must work end-to-end with mock strategy. Shifted focus to member experience as primary priority. Made 5 key decisions and defined 16-step member journey implementation order.

---

## Key Decisions Made

### 1. Lottery is Core, Not Optional

**User's Position**: "The lottery is not an optional feature; it's the actual core of the project."

**Decision**: Complete lottery flow required for Nov 3rd, even with DummyLotteryService (random assignments).

**What This Means**:

- Unit Types system MUST be implemented (families/units need types)
- Family Preferences system MUST be implemented (lottery input)
- Lottery execution endpoint MUST work (admin triggers, members see results)
- Mock/random strategy acceptable (no real API needed yet)

### 2. Family `unit_type_id` Required on Creation

**Question**: Should families be created with or without unit type?

**User's Answer**: "Yes, it should be required on creation"

**Implementation**:

- Migration: `unit_type_id` NOT NULL on families table
- Family creation form MUST include unit type selector
- Validation: Cannot create family without selecting type
- Ensures families immediately eligible for preferences

### 3. Satisfaction Scores Not Needed for MVP

**Question**: Where to store satisfaction scores?

**User's Answer**: "Satisfaction scores are not needed for this milestone"

**Implementation**:

- Remove from DummyLotteryService output
- Remove from lottery execution checklist
- Remove from member dashboard display
- Can add post-MVP if desired

### 4. Lottery Execution: One Time Only

**Question**: Can lottery be run multiple times?

**User's Answer**: "Only one time. If a user wants to try it more times, they can pick another project"

**Implementation**:

- Validate: Block execution if ANY units have family_id set
- Error message: "Lottery already executed. Results are final."
- Testing strategy: Extensive seeder creates multiple demo projects
- Users test on different projects to try multiple scenarios
- Focus: Member experience (tutor/stakeholders test admin flows)

### 5. Admin Removal: Detach from Project

**Question**: When removing admin, soft delete or detach?

**User's Answer**: "Detach from project (for auditing purposes)"

**Implementation**:

- Remove from `project_user` pivot
- Keep user record intact
- User can remain admin in other projects
- Only soft delete if leaving ALL projects (edge case)

---

## Member-Centric Implementation Order

**User's Request**: "I want to tackle development until Nov 3 with a focus on member experience, and admin and superadmin should be a secondary concern"

**Strategy**: "Let implementation loosely follow the life of a member in the project"

### The 16-Step Member Journey

1. Family creation (admin prerequisite)
2. Member invitation
3. Receive invitation email
4. Follow confirmation link
5. Set password + optional fields
6. Redirect to login (email pre-filled, password save)
7. First login → Dashboard
8. Navigate: Members, Families, Gallery, Dashboard
9. Pick unit preferences (filtered by family's unit type)
10. Quick Actions → Invite Family Member
11. Send family member invitation
12. Logout functionality
13. Update profile
14. Update theme (dark/light/system)
15. Update password (logs out, requires re-login)
16. Rich dashboard with 7 components:
    - a. Project summary
    - b. Units display (family type highlighted)
    - c. Gallery preview (3-5 mock images)
    - d. Family summary
    - e. Events block (mock lottery event)
    - f. Current preferences (with edit tools)
    - g. Project admins (with dummy contact form)

**Modal Interactions**: Members, families, admins clickable → Open show views in modals (using MaybeModal component)

---

## Superadmin Configuration (Step 0)

**User's Request**: "Use email to identify superadmins, not ids (fixes the issue with tests inadvertently turning new users into superadmins)"

### Problem Identified

- Tests with auto-increment IDs creating accidental superadmins
- Need stable identifier across environments

### Solution

1. **Email-based identification**: `config/auth.superadmins => ['superadmin@example.com']`
2. **Migration creates default superadmin**: Not seeder (exists in prod)
3. **Password strategy**: Env-based with fallback
   - `.env`: `SUPERADMIN_DEFAULT_PASSWORD=your_secure_password`
   - Migration: `Hash::make(env('SUPERADMIN_DEFAULT_PASSWORD', 'changeme123'))`
   - Big warning in `.env.example`

**User's Take on Security**: "This isn't NASA, just a community project. We won't have the worst hackers after us :-p"

**Decision**: Option A (env-based) sufficient for community project scope.

---

## Use Cases Defined (A, B, C, D)

### A. Superadmin (Nov 3rd)

1. Create project + add admins (single step)
2. Edit project + add/remove admins (single step)

### B. Admin (Nov 3rd)

1. Configure project: Units CRUD (name, description minimum)
2. Create families (empty on creation, with unit_type_id REQUIRED)
3. Invite members into families (including empty families)
4. Invite admins (project assignment)
5. List projects (only if managing multiple)
6. List families/members (context-filtered)

### C. Member (Nov 3rd - PRIMARY FOCUS)

1. Complete registration (email → confirmation → login)
2. Automatic single-project context
3. Edit profile
4. Theme settings
5. Invite family members
6. Dashboard features
7. Unit preferences (CORE)

### D. NOT Required for Nov 3rd

- Events CRUD
- Gallery uploads
- Real lottery API
- Blueprints
- Detailed unit characteristics
- Satisfaction scores
- Lottery invalidation
- Contact admin (dummy only)

---

## Technical Context Captured

### MaybeModal Pattern

- Custom wrapper for inertiaui-modal library
- Components render as modal (centered), slideover (right), OR regular page
- All show views wrapped in MaybeModal
- Clickable entities open modals

### Empty Families Behavior

- Created without members (valid state)
- Hidden from member index (`member_count > 0` filter)
- Visible to admins (with "0 members" indicator)
- Available in dropdowns for invitations

---

## Implementation Dependencies

**Phase Order** (cannot skip):

1. **Unit Types** (foundation)
   - Create unit_types table
   - Add unit_type_id to families (NOT NULL)
   - Add unit_type_id to units (NOT NULL)
   - UnitType CRUD

2. **Family Preferences** (lottery input)
   - Create family_preferences table
   - Preference CRUD for members
   - Drag-and-drop ranking UI

3. **Lottery Execution** (core feature)
   - DummyLotteryService (random algorithm)
   - Lottery controller (one-time execution)
   - Update units.family_id
   - Display results in dashboard

4. **Member Confirmation** (onboarding)
   - Add confirmation_token field
   - Invitation email templates
   - Confirmation endpoint (GET + POST)
   - Block login until verified

---

## Folder Structure Changes

**New AI-Owned Folder**: `documentation/ai/`

**Purpose**: Separate AI artifacts from human documentation

**Contents**:

- `WORK_QUEUE.md` (moved from `knowledge-base/`)
- `DECISIONS.md` (this file's content, newly created)
- `README.md` (explains folder philosophy)

**Rationale**:

- Clearer bookkeeping
- AI assistants know where to look
- Humans know what's AI-managed vs permanent docs

---

## User Communication Style

- Direct, pragmatic decisions
- Balanced security vs simplicity ("not NASA")
- Willing to defer non-essential features
- Focus on working software over perfect software
- Humor about laziness ("I feel lazy today...")
- Values clarity and specificity

---

## Outcomes

✅ **Nov 3rd scope crystal clear** - 7 days, lottery must work, member experience first

✅ **5 key decisions documented** - All open questions resolved

✅ **16-step member journey defined** - Clear implementation roadmap

✅ **Folder structure improved** - AI artifacts separated from human docs

✅ **Next AI session ready** - DECISIONS.md provides all context needed

---

## Next Steps (When User Returns)

1. **Step 0**: Superadmin email-based config (30-60 min, easy task)
2. **Step 1**: Start member journey - family creation prerequisite
3. **Or**: Jump to unit types implementation (foundation for lottery)

**Primary focus**: Member onboarding flow (steps 1-7) before anything else.

---

**End of Session**: 2025-10-27
