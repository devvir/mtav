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
- **Size**: 6,385+ lines
- **Lifecycle**: Permanent, continuously updated
- **Human Action**: Review derived docs (Member Guide, etc.), report issues to AI for KB updates

### `WORK_QUEUE.md`

- **Type**: Temporary task tracker
- **Owner**: AI (updated by AI as tasks progress)
- **Content**:
  - Nov 3rd MVP milestone breakdown
  - Member-centric implementation order
  - Current priorities and next steps
  - Backlog items for post-MVP
- **Lifecycle**: Active until Nov 3rd, then archive or delete
- **Human Action**: Review for status, report issues to AI for updates

### `DECISIONS.md`

- **Type**: Permanent decision log
- **Owner**: AI (created/updated based on user input)
- **Content**:
  - Key architectural decisions
  - Nov 3rd scope clarifications
  - Implementation strategy choices
  - Rationale for each decision
- **Lifecycle**: Permanent, grows over time
- **Human Action**: Reference when making new decisions, ask AI to update

---

## Folder Philosophy

**AI-Owned** (this folder):

- Complete system specification (KNOWLEDGE_BASE)
- Temporary task management (WORK_QUEUE)
- Decision logs for AI context (DECISIONS)
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
2. Read DECISIONS.md for constraints and past choices
3. Read WORK_QUEUE.md for current priorities
4. Ask user for today's focus

**When AI updates this folder**:

1. Update WORK_QUEUE with task progress
2. Add new decisions to DECISIONS if major choices made
3. Update KNOWLEDGE_BASE if system vision changes
4. Generate/update user guides (Member Guide, etc.) from KB changes

**When human needs AI context**:

1. Read WORK_QUEUE for "what's being worked on now"
2. Read DECISIONS for "why we chose this approach"
3. Read KNOWLEDGE_BASE for "what the complete system should be"
4. Ask AI to clarify or update if needed

**When human reviews work**:

1. Review user guides (Member Guide, Admin Manual, etc.)
2. Report issues: "Section X says Y but should say Z"
3. AI updates KNOWLEDGE_BASE first
4. AI regenerates affected guides
5. Human reviews again

---

**Last Updated**: 2025-10-27
