# MTAV Project Decisions

**Purpose**: Log of key temporal decisions and rationale that complement the Knowledge Base. Focus on "why" and "when" rather than "what" (which is in KB).

**Last Updated**: 2025-11-03

---

## How This File Differs from Knowledge Base

- **KNOWLEDGE_BASE.md**: Complete system specification - describes WHAT the system is and does
- **DECISIONS.md**: Rationale and context - explains WHY choices were made and WHEN they apply

This file documents:
- Temporal scope decisions (MVP boundaries, deferred features)
- Alternatives considered and why they were rejected
- Implementation strategies and their rationale
- Test organization approaches
- Workflow guidelines for AI sessions

---

## Core Philosophy

### Documentation Philosophy (2025-10-26)

**Decision**: KNOWLEDGE_BASE.md documents the COMPLETE/DESIRED system, not current implementation reality.

**Rationale**:
- Prevents documentation debt
- Maintains clear aspirational goals
- Single source of truth for "what we're building"

**Implications**:
- Never "dumb down" KB to match implementation
- KB describes complete vision, DECISIONS notes what's deferred
- Tests written for all KB features, skipped until implemented

---

## Nov 3rd MVP Scope (2025-10-27)

### Core Feature: Lottery Must Work End-to-End

**Decision**: Lottery is CORE, not optional. Must work completely even with mock/random strategy.

**Rationale**:
- Lottery is the actual purpose of the application
- Housing cooperative unit distribution is the entire point
- Other features (events, gallery, blueprints) are nice-to-have
- Mock lottery (DummyLotteryService) is acceptable for MVP

**Implications**:
- Unit Types system REQUIRED
- Family Preferences system REQUIRED
- Lottery execution REQUIRED
- Real API integration deferred to post-MVP

### Family `unit_type_id`: Required on Creation

**Decision**: Family creation form MUST include unit type selection. Field is NOT NULL in database.

**Rationale**:
- Families immediately eligible for preferences
- Prevents orphaned families without types
- Simpler workflow (no "assign type later" step)

**Implications**:
- Migration: `unit_type_id` NOT NULL on families table
- Form validation: Required field
- UnitType CRUD must exist before family creation

---

## Test Suite Strategy (2025-10-27)

### Nov 3rd Priority Tracking

**Decision**: Use Pest's `->group()` feature for priority-based test execution.

**Pattern**: P0 (critical) → P1 (high) → P2 (medium) → P3 (low)

**Rationale**:
- Enable systematic TDD workflow focusing on Nov 3rd MVP requirements
- Allow running specific priority levels via `--group` flag
- Clear mapping of features to business priorities

**Implementation**:

- Created `tests/README.md` covering test philosophy, patterns, and organization
- Created `tests/PRIORITIES.md` mapping Nov 3rd features to test files

**Usage**:

```bash
./mtav test --pest --group=p0          # Run only critical tests
./mtav test --pest --group=p1          # Run only high priority
./mtav test --pest --group=member-mvp  # Run all member-facing MVP tests
```

**Implications**:
- TDD workflow: Focus on highest priority untested features
- Clear stopping point when time runs out (finish P0, then P1, etc.)

---

## Implementation Strategy

### Member-Centric Implementation Order (2025-10-27)

**Decision**: Focus on member experience FIRST. Admin/Superadmin features SECOND.

**Rationale**:
- Member role is highest priority for Nov 3rd testing
- Real users (cooperative members) will test member flows
- Better to have perfect member UX than mediocre everything

**Priority Order**:
1. Member invitation → confirmation → login (onboarding)
2. Dashboard + navigation
3. Unit preferences (core lottery feature)
4. Invite family members
5. Account management (profile, theme, password)

### Superadmin Identification (2025-10-27)

**Decision**: Use `config/auth.superadmins` array of emails, not IDs.

**Problem Solved**: Tests with auto-increment IDs inadvertently creating superadmins.

**Implementation**:
- Config: `'superadmins' => ['superadmin@example.com']`
- Model method: Check `in_array($this->email, config('auth.superadmins'))`
- Migration: Create default superadmin with email `superadmin@example.com`
- Password: Env-based `SUPERADMIN_DEFAULT_PASSWORD` with fallback `'changeme123'`

**Rationale**:
- Stable identifier (emails don't change like IDs)
- Easy to add more superadmins (config change)
- Tests don't accidentally create superadmins

---

## Technical Implementation Patterns

### MaybeModal Component Pattern

**Context**: Using custom `MaybeModal` wrapper with inertiaui-modal library.

**Behavior**:
- Components can render as modal (centered), slideover (right side), OR regular page
- All show views wrapped in MaybeModal (members, families, admins, projects)
- Clickable entities open show views in modals

**Implications**: Don't need separate modal/page components

### Localization Formality (Spanish)

**Decision**:
- **Member docs**: Use "tú" form (informal but respectful)
- NOT "vos" (too informal for some demographics)
- NOT "usted" (too formal for cooperative community)

**Rationale**: User base ranges from tech-savvy youth to elderly with limited tech knowledge, from urban to rural. "Tú" is universally acceptable.

---

## Database Design Decisions

### Phase Dependencies

**Order**: Unit Types → Family Preferences → Lottery Execution

**Rationale**: Preferences require unit types (validation), lottery requires preferences (input data)

### Confirmation Token Pattern

**Decision**: Add `confirmation_token` field to users table for invitation flow.

**Flow**:
1. Admin/Member invites → Generate token
2. Email link: `/confirm-account?email={email}&token={token}`
3. User confirms → Set `verified_at = now()`, keep token
4. Login validation: Check `verified_at` not null

---

## Deferred to Post-Nov 3rd MVP

**Explicitly out of scope**:
- Events CRUD (show mock lottery event only)
- Gallery file upload (show mock images only)
- Real lottery API integration (use DummyLotteryService only)
- Interactive unit blueprints (wishlist, may not be implemented)
- Detailed unit characteristics (bedrooms, bathrooms, square meters)
- Satisfaction scores
- Lottery invalidation UI (manual database operation acceptable)
- Member leaving family/project flow
- Family moving between projects
- Contact admin (dummy form only, no actual email)

**Rationale**: Focus limited time on core lottery flow. Polish features later.

---

## Current Development Approach (2025-11-03)

### No Formal Sprints

**Reality**: Developer is catching up on delayed work, no sprint planning currently active.

**Workflow**:
- Knock down TODOs daily as they arise
- Priorities shift based on tutor meetings (thesis supervisor)
- Ask user for today's focus at session start

**Implication for AI**: Don't assume sprint structure or long-term roadmap. Work is reactive and adaptive.

---

## Clarifications (2025-11-03)

### Lottery Invalidation

**Implementation**: Manual database operation only, no UI planned.

**Process**:
- Developer with production database access runs SQL UPDATE
- Clears all `unit.family_id` assignments
- No app-level functionality needed

**Timeline**: No plans to build UI for this feature.

### Invitation System

**How It Works**:
- Inviter creates user record directly in database
- System generates random password (serves as invitation token)
- Email sent with link containing email + token (random password)
- Invitee must set new password to complete registration
- Setting password marks both `verified_at` and invitation as accepted

**Key Points**:
- User exists in DB from moment of invitation
- Token = random password (not separate token table)
- Email verification only happens during invitation acceptance
- Separate email verification for profile email updates

### Interactive Blueprints

**Status**: Wishlist feature, not confirmed for implementation.

**Purpose**: Visual representation of unit positions within housing project.

**Current**: Upload static blueprint image/PDF for reference.

**Wishlist**: Interactive map where units can be clicked, positions stored in DB, color-coded lottery results.

**Timeline**: None. May never be implemented.

### Lottery API Strategy Pattern

**Interface Design**:
- `load(<preferences>)` - Load preference data
- `execute(): <results>` - Execute lottery, return assignments

**Data Format**:
- Input: Lists of unit IDs, family IDs, preference mappings (simple)
- Output: Assignment results (simple)
- Exact format TBD, will be easy to decide during implementation

**Plugins**:
- Mock (current): Random assignments
- Dev (future): Fast free API for development
- Production (future): External third-party optimization API

---

## Guidelines for AI Sessions

**When starting new session**:
1. Read ACCESSIBILITY_AND_TARGET_AUDIENCE.md for design principles
2. Read KNOWLEDGE_BASE.md for complete system specification
3. Read DECISIONS.md (this file) for constraints and rationale
4. Read TESTS_KB.md for testing philosophy
5. Ask user for today's focus

**When making new decisions**:
1. Document decision here with rationale
2. Update KNOWLEDGE_BASE.md if changes desired system specification

**When user reports issues**:
1. Check if decision already made (this file)
2. Check if documented in KB
3. Ask clarifying questions only if not documented

---

**Maintenance**: Update when major decisions made. Focus on rationale and temporal context, not repeating what's in KB.
