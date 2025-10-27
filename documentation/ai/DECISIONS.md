# MTAV Project Decisions

**Purpose**: Log of key decisions made during development to guide AI assistants in future sessions.

**Format**: Decision → Rationale → Implications

**Last Updated**: 2025-10-27

---

## Architecture & Philosophy

### Documentation Philosophy (2025-10-26)

**Decision**: KNOWLEDGE_BASE.md documents the COMPLETE/DESIRED system, not current implementation reality.

**Rationale**:

- Prevents documentation debt
- Maintains clear aspirational goals
- Single source of truth for "what we're building"
- Implementation status tracked separately in WORK_QUEUE.md

**Implications**:

- Never "dumb down" KB to match implementation
- KB can describe features that don't exist yet
- Tests written for all KB features, skipped until implemented: `->skip('Not yet implemented')`
- Derived docs (Member Guide, etc.) describe complete system "as if" everything exists

### Test Strategy (2025-10-26)

**Decision**: Write all tests immediately from KB specs, skip unimplemented features.

**Pattern**:

```php
it('feature description')->skip('Not yet implemented');
```

**Rationale**:

- Tests become living TODO list
- Implementation validates against pre-written tests
- No test debt accumulation
- Clear visibility of what's pending

**Workflow**:

1. Write tests from KB specifications
2. Mark as skipped
3. Implement feature
4. Remove skip annotation
5. Verify tests green

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

- Unit Types system REQUIRED (families/units need types)
- Family Preferences system REQUIRED (lottery input)
- Lottery execution REQUIRED (admin triggers, results displayed)
- Real API integration deferred to post-MVP
- Satisfaction scores deferred to post-MVP

### Family `unit_type_id`: Required on Creation

**Decision**: Family creation form MUST include unit type selection. Field is NOT NULL in database.

**Rationale**:

- Families immediately eligible for preferences
- Prevents orphaned families without types
- Simpler workflow (no "assign type later" step)
- Type is fundamental to lottery fairness

**Implications**:

- Migration: `unit_type_id` NOT NULL on families table
- Form validation: Required field
- UnitType CRUD must exist before family creation
- Context-aware: Type selector filtered by current/selected project

### Satisfaction Scores: Not Needed for MVP

**Decision**: Remove satisfaction score tracking from Nov 3rd scope.

**Rationale**:

- Not essential to lottery execution
- Can be added post-MVP
- Simplifies DummyLotteryService algorithm
- Reduces testing surface area

**Implications**:

- DummyLotteryService returns `[{ family_id, unit_id }]` (no score)
- Lottery execution doesn't store scores
- Member dashboard shows assigned unit only (no rating)
- Can be added later with unit_assignments table

### Lottery Execution: One Time Only Per Project

**Decision**: Lottery can only be executed ONCE per project. Results are permanent.

**Rationale**:

- Enforces fairness (no re-running to get better results)
- Simpler logic (no versioning, no "which execution is active")
- Real-world behavior (cooperative lotteries are final events)

**Validation**:

- Check if ANY units in project have `family_id` set
- If yes: Block execution with error "Lottery already executed. Results are final."

**Testing Strategy**:

- Use extensive seeder to create multiple demo projects
- Users test different scenarios on different projects
- Focus on member experience (tutor/stakeholders test admin flows)

**Post-MVP**:

- Superadmin lottery invalidation (clears all assignments, allows re-run)
- Tracked as separate feature for later

### Empty Families Visibility

**Decision**:

- Members: Cannot see empty families (filter `member_count > 0`)
- Admins: Can see all families with member count indicator

**Rationale**:

- Members don't need to see empty "wrapper" families
- Admins need visibility for invitation workflows
- Empty families valid in system (persist even if all members leave)

**Implications**:

- Family index query: Scope by user role
- Dropdowns for invitations: Show empty families (admins only)
- Family show view: Indicate if empty

### Admin Removal: Detach from Project

**Decision**: Removing admin from project detaches from `project_user` pivot, keeps user record.

**Rationale**:

- Auditing (preserve user history)
- User can be admin in other projects
- Soft delete only if user leaves ALL projects

**Implications**:

- `ProjectController@update`: Remove pivot entry, not soft delete user
- Edge case: If admin detached from all projects, consider soft delete (post-MVP)

---

## Development Strategy (2025-10-27)

### Member-Centric Implementation Order

**Decision**: Focus on member experience FIRST. Admin/Superadmin features SECOND.

**Rationale**:

- Member role is highest priority for Nov 3rd testing
- Real users (cooperative members) will test member flows
- Tutor/stakeholders will test admin flows (less critical for UX)
- Better to have perfect member UX than mediocre everything

**Implementation Order**:

1. Member invitation → confirmation → login (onboarding)
2. Dashboard + navigation
3. Unit preferences (core lottery feature)
4. Invite family members
5. Account management (profile, theme, password)
6. Rich dashboard components

**Admin features**: Only implement minimum needed to unblock member testing.

### Superadmin Identification: Email-Based, Not ID-Based

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
- Works across environments (dev/staging/prod)

**Security**: Community project, env-based password acceptable. Add warning in `.env.example`.

---

## Technical Context

### MaybeModal Component Pattern

**Context**: Using custom `MaybeModal` wrapper with inertiaui-modal library.

**Behavior**:

- Components can render as modal (centered), slideover (right side), OR regular page
- All show views wrapped in MaybeModal (members, families, admins, projects)
- Clickable entities open show views in modals

**Implications**:

- Don't need separate modal/page components
- Links can specify modal type: `<ModalLink :href="..." />`
- Forms can use same pattern for create/edit

### Localization: EN + ES_UY

**Languages**: English + Uruguayan Spanish

**Spanish Formality**:

- **Member docs**: Use "tú" form (informal but respectful)
- NOT "vos" (too informal for some demographics)
- NOT "usted" (too formal for cooperative community)
- Examples: "puedes hacer", "tu perfil", "debes revisar"

**Technical Language**:

- **Member docs**: Assume NO technical knowledge, use Spanish terms
- "correo electrónico" not "email", "contraseña" not "password"
- **Admin/Superadmin docs**: Technical terms acceptable
- **Developer docs**: English only

---

## Database Decisions

### Phase Dependencies

**Order**: Unit Types → Family Preferences → Lottery Execution

**Rationale**:

- Preferences require unit types (type matching validation)
- Lottery requires preferences (input data)
- Cannot implement out of order

**Migrations**:

1. `create_unit_types_table` (project_id, name, description)
2. `add_unit_type_id_to_families` (NOT NULL)
3. `add_unit_type_id_to_units` (NOT NULL)
4. `create_family_preferences_table` (family_id, unit_id, rank)

### Confirmation Token Pattern

**Decision**: Add `confirmation_token` field to users table for invitation flow.

**Rationale**:

- Email verification via token in invitation link
- Keep token after confirmation for auditing
- Check `email_verified_at` for active status, ignore token if verified

**Flow**:

1. Admin/Member invites → Generate token
2. Email link: `/confirm-account?email={email}&token={token}`
3. User confirms → Set `email_verified_at = now()`, keep token
4. Login validation: Check `email_verified_at` not null

---

## Deferred to Post-Nov 3rd

**Explicitly out of scope for MVP**:

- Events CRUD (show mock lottery event only)
- Gallery file upload (show mock images only)
- Real lottery API integration (use DummyLotteryService only)
- Unit blueprints / graphical plans
- Detailed unit characteristics (bedrooms, bathrooms, square meters)
- Satisfaction scores
- Lottery invalidation (superadmin power)
- Member leaving family/project flow
- Family moving between projects
- Contact admin (dummy form only, no actual email)

**Rationale**: Focus limited time on core lottery flow. Polish features later.

---

## Guidelines for Future AI Sessions

**When starting new session**:

1. Read KNOWLEDGE_BASE.md for system vision
2. Read this file (DECISIONS.md) for constraints
3. Read WORK_QUEUE.md for current priorities
4. Ask user for today's focus

**When making new decisions**:

1. Document decision here with rationale
2. Update WORK_QUEUE.md if affects priorities
3. Update KNOWLEDGE_BASE.md if changes desired system

**When user reports issues**:

1. Check if decision already made (this file)
2. Check if documented in KB
3. Ask clarifying questions only if not documented

**Testing approach**:

1. Write tests from KB specifications
2. Skip if not implemented yet
3. Unskip as features built
4. 100% feature coverage goal (not line coverage)

---

**Maintenance**: AI should update this file when major decisions made. Keep concise - rationale matters more than history.
