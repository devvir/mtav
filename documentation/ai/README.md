# AI-Owned Documentation

**Purpose**: This folder contains artifacts fully managed by AI assistants to maintain context and guide task execution.

**Target Audience**: AI assistants (GitHub Copilot, Claude, etc.)

**Human Usage**: Review for understanding AI decision-making, but DO NOT manually edit.

---

## 📁 NEW ORGANIZED STRUCTURE (December 2024)

**Problem Solved**: 7,300-line monolithic `KNOWLEDGE_BASE.md` caused knowledge degradation over long conversations.

**Solution**: Topic-focused files matching work contexts:

```
documentation/ai/
├── core/                        # 🔴 REQUIRED - Read for 99% of work
│   ├── USER_SYSTEM.md          # User/Member/Admin/Superadmin (STI, family atomicity)
│   └── SCOPING.md              # ProjectScope global scopes (universe boundaries)
├── testing/                     # 🟡 For test development
│   └── PHILOSOPHY.md           # Universe fixture, rollback, 2-line ideal
├── features/                    # 🟢 Feature-specific (to be populated)
└── technical/                   # 🟢 Implementation details (to be populated)
```

### Quick Start for AI Assistants

**ALWAYS read first (foundational - needed 99% of time)**:
1. `core/USER_SYSTEM.md` - User types, STI pattern, family atomicity
2. `core/SCOPING.md` - ProjectScope system, authorization matrix

**If writing/running tests**:
3. `testing/PHILOSOPHY.md` - Universe fixture, test patterns, helpers

**For specific work**:
4. `features/{TOPIC}.md` or `technical/{TOPIC}.md` (when created)

**Legacy reference**:
- `KNOWLEDGE_BASE.md` - Original monolithic KB (kept during migration)

---

## Quick Start for AI Assistants (Legacy Workflow - Being Phased Out)

**When starting a new session:**

1. **Read these files in order** (essential context):
   - `ACCESSIBILITY_AND_TARGET_AUDIENCE.md` - Design constraints (WCAG, elderly users, old devices)
   - **NEW**: `core/USER_SYSTEM.md` + `core/SCOPING.md` (foundational concepts)
   - ~~`KNOWLEDGE_BASE.md`~~ - (Being replaced by organized structure)
   - `DECISIONS.md` - Rationale for key choices and deferred features
   - `TESTS_KB.md` - Testing philosophy and patterns (being migrated to `testing/`)

2. **Current development approach** (Dec 2024):
   - No formal sprints - knocking down TODOs daily as they arise
   - Priorities may shift after tutor meetings
   - Ask user for today's focus before starting work

3. **Critical rules** (never violate):
   - Always use semicolons in JavaScript/TypeScript (including Vue)
   - Always use `<script setup lang="ts">` in Vue files
   - Consult accessibility doc before ANY UI work
   - Use `./mtav` wrapper, not direct Docker commands
   - Test helpers go in `tests/Helpers/` (autoloaded by Pest)

4. **Key architectural patterns** (internalize these):
   - **STI (Single Table Inheritance)**: User → Admin/Member (see `core/USER_SYSTEM.md`)
   - **Family Atomicity**: Families are atomic units, members mirror family's project
   - **Two-Level Auth**: Global scopes (query) + Policies (action) (see `core/SCOPING.md`)
   - **Universe Fixture**: `universe.sql` loaded once, rolled back per test
   - **2-Line Test Ideal**: act + assert (see `testing/PHILOSOPHY.md`)

5. **Ask before**:
   - Modifying production code to fix tests
   - Adding new test helpers (must be reviewed)
   - Changing database schema
   - Making accessibility trade-offs

---

## File Inventory

### 🔴 CORE - Always Read (core/)

#### `core/USER_SYSTEM.md`
**What**: Complete user type system (User/Member/Admin/Superadmin)
**Priority**: 🔴 CRITICAL - Read for 99% of work
**Contains**:
- STI (Single Table Inheritance) pattern on users table
- Member: `is_admin=false`, must have family, family atomicity
- Admin: `is_admin=true`, manages projects, no family
- Superadmin: admin + config array, bypasses all policies
- Database schema, relationships, validation rules
- Capabilities matrix, workflows, constraints

#### `core/SCOPING.md`
**What**: Two-level authorization (global scopes + policies)
**Priority**: 🔴 CRITICAL - Read for 99% of work
**Contains**:
- ProjectScope trait implementation
- Query-level filtering (what "exists" for user)
- Action-level permissions (what user can DO)
- Universe definitions (Member/Admin/Superadmin boundaries)
- Authorization matrix (complete permissions table)
- Policy patterns, testing scopes

### 🟡 TESTING - Read for Test Work (testing/)

#### `testing/PHILOSOPHY.md`
**What**: Test infrastructure, universe fixture, patterns
**Priority**: 🟡 REQUIRED for test development
**Contains**:
- Universe fixture concept (`universe.sql`)
- Transaction rollback strategy
- 2-line test ideal
- Test patterns (scope, auth, controller, business logic)
- Helper functions, running tests
- Testing ProjectScope specifically

### 🟢 FEATURES - Read as Needed (features/) - TO BE POPULATED

**Planned files**:
- `LOTTERY.md` - Lottery system, preferences, unit assignment
- `UNITS.md` - Unit types, characteristics, blueprints
- `FAMILIES.md` - Family management, project switching
- `EVENTS.md` - Community events, RSVP
- `MEDIA.md` - Profile images, gallery, uploads

### 🟢 TECHNICAL - Read as Needed (technical/) - TO BE POPULATED

**Planned files**:
- `DOCKER.md` - Docker setup, wrapper, build process
- `INERTIA.md` - Inertia.js patterns, modal system
- `VALIDATION.md` - Form requests, custom rules
- `DEPLOYMENT.md` - Build images, registry, deployment

---

## Legacy Files (Being Phased Out)

### `KNOWLEDGE_BASE.md`

- **Type**: Permanent system specification (single source of truth)
- **Owner**: AI (generated and maintained by AI based on user requirements)
- **Content**:
  - Complete system vision (DESIRED state, not current implementation)
  - Business domain and rules
  - Actor definitions and capabilities
  - Entity models and relationships
  - Technical architecture
  - Development workflows
  - Testing strategies
- **Size**: ~7,300 lines
- **Lifecycle**: Permanent, continuously updated
- **Human Action**: Review derived docs (Member Guide, etc.), report issues to AI for KB updates

### `ACCESSIBILITY_AND_TARGET_AUDIENCE.md`

- **Type**: Permanent design principles reference
- **Owner**: AI (maintained by AI)
- **Content**:
  - Target audience characteristics (elderly, disabilities, old devices)
  - WCAG AA/AAA requirements
  - Typography, color, contrast standards
  - Touch targets, focus indicators
  - Performance considerations
  - Testing checklist
- **Size**: ~120 lines
- **Lifecycle**: Permanent, updated as design system evolves
- **Human Action**: Critical reference - consulted before ANY UI implementation

### `DECISIONS.md`

- **Type**: Permanent decision log
- **Owner**: AI (created/updated based on user input)
- **Content**:
  - Key architectural decisions with rationale
  - Temporal scope decisions (MVP boundaries, deferred features)
  - Implementation strategy choices
  - Alternatives considered and rejected
  - Test organization approaches
- **Size**: ~200 lines
- **Lifecycle**: Permanent, grows over time
- **Human Action**: Reference when making new decisions, ask AI to update

### `TESTS_KB.md`

- **Type**: Permanent testing philosophy guide
- **Owner**: AI (maintained by AI)
- **Content**:
  - "Tests are an application" principle
  - Toolbox metaphor and patterns
  - Fixture usage and conventions
  - Anti-patterns to avoid
- **Size**: ~1,150 lines
- **Lifecycle**: Permanent, updated as testing patterns evolve
- **Human Action**: Reference when writing tests, ask AI to update

---

## Folder Philosophy

**AI-Owned** (this folder):

- Complete system specification (KNOWLEDGE_BASE)
- Design principles (ACCESSIBILITY_AND_TARGET_AUDIENCE)
- Decision logs for AI context (DECISIONS)
- Testing philosophy (TESTS_KB)
- AI operational guidelines
- NOT for thesis/academic documentation

**Human-Owned** (`documentation/guides/`):

- Member Guide (EN + ES_UY) - For cooperative members
- Admin Manual (future) - For project administrators
- User-facing documentation generated from KNOWLEDGE_BASE

**Technical/Development** (`documentation/`):

- Build, deployment, docker, testing docs
- Scripts and troubleshooting guides
- Developer-focused README files

**Academic** (`documentation/copilot/`):

- Session transcripts for thesis appendix
- Process documentation for tribunal
- AI collaboration evidence
- Historical record of methodology

---

## Workflow

**When AI starts new session**:

1. Read KNOWLEDGE_BASE.md for complete system vision
2. Read ACCESSIBILITY_AND_TARGET_AUDIENCE.md for design principles
3. Read DECISIONS.md for constraints and past choices
4. Read TESTS_KB.md if working on testing
5. Ask user for today's focus

**When AI updates this folder**:

1. Update KNOWLEDGE_BASE if system vision changes
2. Add new decisions to DECISIONS if major choices made
3. Update ACCESSIBILITY if design principles evolve
4. Update TESTS_KB if testing patterns change
5. Generate/update user guides (Member Guide, etc.) from KB changes

**When human needs AI context**:

1. Read DECISIONS for "why we chose this approach"
2. Read KNOWLEDGE_BASE for "what the complete system should be"
3. Read ACCESSIBILITY for "how to design for our users"
4. Ask AI to clarify or update if needed

**When human reviews work**:

1. Review user guides (Member Guide, Admin Manual, etc.)
2. Report issues: "Section X says Y but should say Z"
3. AI updates KNOWLEDGE_BASE first
4. AI regenerates affected guides
5. Human reviews again

---

**Last Updated**: 2025-11-03

