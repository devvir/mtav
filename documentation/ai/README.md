# AI-Owned Documentation

**Purpose**: This folder contains artifacts fully managed by AI assistants to maintain context and guide task execution.

**Target Audience**: AI assistants (GitHub Copilot, Claude, etc.)

**Human Usage**: Review for understanding AI decision-making, but DO NOT manually edit.

---

## File Inventory

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

**Last Updated**: 2025-11-02
