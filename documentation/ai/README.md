# AI Development Documentation - MTAV Project

**Purpose**: Enable GitHub Copilot (Claude Sonnet 4.5) to work effectively with the MTAV cooperative housing management platform.

**Target Audience**: AI assistants (GitHub Copilot, Claude, etc.)

---

## 🎯 How to Learn the App

### Start Here (Required Reading)

**1. `KNOWLEDGE_BASE.md` - THE authoritative source**
- Contains ALL critical architectural facts needed for 99% of development work
- Docker commands, user roles, authorization, data patterns, tech stack
- Read this FIRST and ALWAYS refer back to it

### Deep Dive When Needed

**2. Topic-specific files** (read only when working on specific areas):

**Authorization Work**:
- `KNOWLEDGE_BASE.md` → Authorization Architecture section
- `core/USER_SYSTEM.md` → Detailed user system patterns and database schema
- `core/SCOPING.md` → Project scoping and authorization matrix
- `policies-reference.md` → Policy implementation patterns

**Lottery System**:
- `LOTTERY.md` → Complete lottery system reference, business rules, GLPK integration

**Project Plans**:
- `ProjectPlans.md` → Spatial visualization, canvas architecture, component hierarchy

**UI/Frontend Work**:
- `UI.md` → **START HERE** for frontend development and UI testing (architecture, FormService system, components, composables, state management)
- `ACCESSIBILITY_AND_TARGET_AUDIENCE.md` → WCAG requirements, elderly users, design constraints
- `resources-reference.md` → Resource transformation patterns
- `refactoring-preferences-manager.md` → Component refactoring patterns

**Testing Work**:
- `testing/E2E.md` → End-to-end testing setup (Playwright + Pest Browser)
- `testing/PHILOSOPHY.md` → Universe fixture, test patterns, helpers
- `testing/FORMS.md` → Form interaction test patterns
- `testing/LOTTERY_TESTS.md` → Lottery-specific testing

**Context/Decisions**:
- `DECISIONS.md` → Rationale for key architectural choices

---

## File Structure

```
documentation/ai/
├── KNOWLEDGE_BASE.md           # 🔴 PRIMARY - All critical facts
├── README.md                   # 🔴 THIS FILE - Learning guide
├── UI.md                       # 🔴 Frontend architecture & components (READ FOR UI WORK)
├── LOTTERY.md                  # 🔴 Complete lottery system reference
├── ProjectPlans.md             # 🔴 Project plan visualization system
├── core/                       # 🟡 Detailed patterns
│   ├── USER_SYSTEM.md         #     User/Member/Admin details & database schema
│   └── SCOPING.md             #     Authorization matrix details
├── testing/                    # 🟡 Testing-specific
│   ├── E2E.md                 #     Playwright + Pest Browser setup
│   ├── PHILOSOPHY.md          #     Universe fixture, patterns
│   ├── FORMS.md               #     Form test patterns
│   └── LOTTERY_TESTS.md       #     Lottery tests
├── ACCESSIBILITY_AND_TARGET_AUDIENCE.md # 🟡 UI constraints
├── DECISIONS.md               # 🟢 Architectural decisions
├── policies-reference.md      # 🟢 Policy patterns
├── refactoring-preferences-manager.md # 🟢 Component refactoring
└── resources-reference.md     # 🟢 Resource patterns
```

## 🧠 Mental Model for AI Assistants

**Always keep in mind** (from KNOWLEDGE_BASE.md):
- Docker-only: Everything through `./mtav` script
- User roles: `config('auth.superadmins')` emails + `is_admin` boolean
- Resources: Auto-transform via `ConvertsToJsonResource` trait, NEVER use `JsonResource::make()`
- Authorization: Superadmins bypass all policies via `Gate::before()`
- Scoping: Most models auto-scoped to current project context

**When working on specific areas, check**:
- Authorization issues → `core/USER_SYSTEM.md` + `core/SCOPING.md` + `policies-reference.md`
- Resource patterns → `resources-reference.md`
- UI work → `ACCESSIBILITY_AND_TARGET_AUDIENCE.md`
- Test issues → `testing/PHILOSOPHY.md`
- Architecture questions → `DECISIONS.md`

---

## Development Guidelines

**Current development approach**:
- No formal sprints - knocking down TODOs daily as they arise
- Priorities may shift after tutor meetings
- Ask user for today's focus before starting work

**Critical rules** (never violate):
- Always use semicolons in JavaScript/TypeScript (including Vue)
- Always use `<script setup lang="ts">` in Vue files
- Consult accessibility doc before ANY UI work
- Use `./mtav` wrapper, not direct Docker commands
- Test helpers go in `tests/Helpers/` (autoloaded by Pest)

**Key architectural patterns** (internalize these):
- **STI (Single Table Inheritance)**: User → Admin/Member (see `core/USER_SYSTEM.md`)
- **Family Atomicity**: Families are atomic units, members mirror family's project
- **Two-Level Auth**: Global scopes (query) + Policies (action) (see `core/SCOPING.md`)
- **Universe Fixture**: `universe.sql` loaded once, rolled back per test
- **2-Line Test Ideal**: act + assert (see `testing/PHILOSOPHY.md`)
- **Reusable Composables**: Extract shared logic (e.g., `useDragAndDrop` for lottery + project plan)

**Ask before**:
- Modifying production code to fix tests
- Adding new test helpers (must be reviewed)
- Changing database schema
- Making accessibility trade-offs

---

## Navigation Guide

### When You Need...

**General technical answers** → `KNOWLEDGE_BASE.md`
**Architecture rationale** → `DECISIONS.md`
**User/accessibility info** → `ACCESSIBILITY_AND_TARGET_AUDIENCE.md`
**Lottery system details** → `LOTTERY.md`
**Project plans system** → `ProjectPlans.md`
**Authentication patterns** → `core/USER_SYSTEM.md`
**Scoping patterns** → `core/SCOPING.md`
**Testing approach** → `testing/PHILOSOPHY.md`
**Form test patterns** → `testing/FORMS.md`
**Lottery test details** → `testing/LOTTERY_TESTS.md`
**Policy patterns** → `policies-reference.md`
**Component refactoring** → `refactoring-preferences-manager.md`
**Resource patterns** → `resources-reference.md`

---

## Key Reference Files

### Primary Technical Documentation

**`KNOWLEDGE_BASE.md`** - Complete system specification (single source of truth)
- Docker commands, tech stack, development patterns
- User roles, authorization architecture
- Entity models and relationships
- Business domain and rules
- Development workflows, testing strategies

**`DECISIONS.md`** - Architectural decisions and rationale
- Key architectural choices with justification
- Temporal scope (MVP boundaries, deferred features)
- Implementation strategies
- Alternatives considered/rejected

**`ACCESSIBILITY_AND_TARGET_AUDIENCE.md`** - Design principles
- Target audience (elderly, disabilities, old devices)
- WCAG AA/AAA requirements
- Typography, color, contrast standards
- Touch targets, focus indicators
- Performance considerations

### Domain-Specific Systems

**`LOTTERY.md`** - Complete lottery system reference
- Business rules, one-time execution principle
- Data architecture, entity relationships
- Dynamic preference management
- Multi-phase execution strategy
- GLPK integration details
- Implementation status and roadmap

**`ProjectPlans.md`** - Project plan visualization
- Canvas architecture (3-layer component system)
- Database structure for spatial data
- Component hierarchy (PlanCanvas, adapter, rendering)
- Data flow and transformation patterns

### Core System Patterns

**`core/USER_SYSTEM.md`** - Complete user type system
- STI (Single Table Inheritance) pattern
- Member/Admin/Superadmin capabilities
- Database schema, relationships
- Validation rules, constraints

**`core/SCOPING.md`** - Two-level authorization
- ProjectScope trait implementation
- Query-level filtering vs action-level permissions
- Authorization matrix
- Policy patterns, testing scopes

### Testing Documentation

**`testing/PHILOSOPHY.md`** - Test infrastructure
- Universe fixture concept (`universe.sql`)
- Transaction rollback strategy
- 2-line test ideal
- Test patterns (scope, auth, controller)

**`testing/FORMS.md`** - Form interaction patterns
- Form testing approaches
- Validation testing

**`testing/LOTTERY_TESTS.md`** - Lottery-specific testing
- Lottery domain test patterns
- Execution testing strategies

### Additional References

**`policies-reference.md`** - Policy implementation patterns
**`refactoring-preferences-manager.md`** - Component refactoring patterns (useDragAndDrop composable)
**`resources-reference.md`** - Resource transformation patterns
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

---

## Context Gathering Strategy

**When starting new work:**
1. Start with KNOWLEDGE_BASE.md - scan relevant sections
2. Check DECISIONS.md if architecture affects approach
3. Dive into core/ or testing/ for domain knowledge
4. Cross-reference when concepts span files

**When documenting new features:**
- Technical details → Update KNOWLEDGE_BASE.md
- Architectural choices → DECISIONS.md (if significant)
- New major component → Create core/*.md
- New test patterns → Add to testing/*.md

---

## Maintenance

**When to update:**
- Architectural patterns change
- New major features added
- Testing approaches evolve
- Policies/authorization rules change

**How to update:**
1. Identify right file using navigation guide
2. Update in place - no "Updated on..." notes
3. Remove outdated info - don't mark deprecated
4. Keep concise - link to code vs duplicating

---

## Quality Standards

**Good documentation:**
- ✅ Actionable - what to do, not just what exists
- ✅ Current - reflects actual codebase
- ✅ Findable - organized logically
- ✅ Concise - gets to the point
- ✅ Cross-referenced - links related concepts

**Avoid:**
- ❌ Listing every file/class
- ❌ Documenting obvious things
- ❌ Preserving irrelevant history
- ❌ Duplicating across files

