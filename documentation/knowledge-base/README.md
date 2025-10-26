# MTAV Knowledge Base

This directory contains the **single source of truth** for all MTAV system knowledge and derived documentation.

---

## 📖 Core Document

### [KNOWLEDGE_BASE.md](KNOWLEDGE_BASE.md)

**Complete system specification** - the authoritative source for:

- Business domain and rules
- Actor definitions and capabilities
- Entity models and relationships
- Technical architecture
- Development workflows
- Deployment procedures
- Testing strategies

**Status**: AI-managed (do not edit manually)

**Purpose**:

- Generate all derived documentation
- Validate application code
- Answer stakeholder questions
- Train AI assistants and chatbots
- Ensure consistency across all docs

**Structure**:

- Part 1: Business Domain
- Part 2: Technical Architecture
- Part 3: Development & Testing

---

## 📚 Derived Documentation

All documents in the `derived/` folder are **generated from KNOWLEDGE_BASE.md** and tailored for specific audiences.

**Localization**: All human-facing documents are maintained in both languages:

- `derived/en/` - English versions
- `derived/es_UY/` - Uruguayan Spanish versions

### For App Users

- **Member Guide** - Housing cooperative member handbook
  - [English](derived/en/member-guide.md) | [Español (UY)](derived/es_UY/guia-miembro.md)
  - How to use the MTAV application
  - What members can and cannot do
  - Workflows: preferences, RSVP, invitations, profile
  - Understanding the lottery process

- **Admin Manual** - Project manager handbook _(coming soon)_
  - [English](derived/en/admin-manual.md) | [Español (UY)](derived/es_UY/manual-admin.md)
  - Managing housing cooperative projects
  - Creating families, members, units
  - Running the lottery
  - Event management

- **Superadmin Guide** - System administrator guide _(coming soon)_
  - [English](derived/en/superadmin-guide.md) | [Español (UY)](derived/es_UY/guia-superadmin.md)
  - System oversight and exceptional interventions
  - Creating projects and assigning admins
  - Database corrections and lottery invalidation
  - Emergency procedures

### For Technical Staff

- **Developer Guide** - Software developer handbook _(coming soon)_
  - [English](derived/en/developer-guide.md) | [Español (UY)](derived/es_UY/guia-desarrollador.md)
  - Understanding the codebase
  - Implementing new features
  - Testing requirements
  - Architecture patterns

- **DevOps Guide** - Operations handbook _(coming soon)_
  - [English](derived/en/devops-guide.md) | [Español (UY)](derived/es_UY/guia-devops.md)
  - Deployment procedures
  - Infrastructure management
  - Monitoring and maintenance
  - Troubleshooting

---

## 🔄 Update Workflow

**Important**: Do not edit files in this directory manually.

**Correct workflow**:

1. **Identify issue** in derived doc or application code
2. **Report to AI** - "The Member Guide says X but it should be Y"
3. **AI updates KB** first (KNOWLEDGE_BASE.md)
4. **AI regenerates** affected derived documents
5. **Review** updated derived docs
6. **Commit** changes together (KB + derived docs in sync)

This ensures:

- ✅ Single source of truth maintained
- ✅ No contradictions between documents
- ✅ All docs stay synchronized
- ✅ Changes traceable to KB updates

---

## 🤖 AI Integration

The knowledge base is designed for AI consumption:

**Current capabilities**:

- Generate actor-specific documentation
- Validate code against business rules
- Answer questions from any stakeholder
- Create test specifications
- Produce deployment guides

**Future capabilities**:

- In-app chatbot for contextual help
- Automated documentation updates
- Code generation from specifications
- Compliance verification
- Training material generation

---

## 📊 Document Status

| Document            | Status       | Last Updated |
| ------------------- | ------------ | ------------ |
| KNOWLEDGE_BASE.md   | ✅ Active    | 2025-10-26   |
| member-guide.md     | 🚀 Generated | 2025-10-26   |
| admin-manual.md     | 📋 Planned   | -            |
| superadmin-guide.md | 📋 Planned   | -            |
| developer-guide.md  | 📋 Planned   | -            |
| devops-guide.md     | 📋 Planned   | -            |

---

## 🔗 Related Documentation

- **Technical Docs**: [../technical/](../technical/) - Developer and DevOps guides
- **Test Docs**: [../../tests/README.md](../../tests/README.md) - Test suite overview
- **Main README**: [../../README.md](../../README.md) - Project overview
