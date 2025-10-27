# Derived Documentation Structure

This folder contains **human-facing documentation** generated from the KNOWLEDGE_BASE.md.

## 📁 Localization Structure

All documents are maintained in two languages:

```
derived/
├── en/                 # English versions
│   ├── member-guide.md
│   ├── admin-manual.md (coming soon)
│   ├── superadmin-guide.md (coming soon)
│   ├── developer-guide.md (coming soon)
│   └── devops-guide.md (coming soon)
│
└── es_UY/              # Uruguayan Spanish versions
    ├── guia-miembro.md
    ├── manual-admin.md (coming soon)
    ├── guia-superadmin.md (coming soon)
    ├── guia-desarrollador.md (coming soon)
    └── guia-devops.md (coming soon)
```

## 🔄 Maintenance Workflow

**Important**: These files are **AI-generated** and **AI-maintained**.

### User Commitment

"I commit not to move or change derived documents without your knowledge"

This allows the AI to:

- Track derivation sources in KB metadata
- Efficiently regenerate when KB changes
- Keep all versions synchronized (EN + ES)
- Maintain consistency across documents

### When You Find Issues

**✅ DO**:

1. Read the human-facing derived doc
2. Report issues: "The Member Guide says X but should be Y"
3. AI updates KB first (single source of truth)
4. AI regenerates both EN and ES versions
5. Review updated docs
6. Commit together (KB + derived docs in sync)

**❌ DON'T**:

- Edit derived docs manually
- Move/rename files without AI knowledge
- Fix issues directly in derived docs
- Create new language versions manually

## 📋 Document Status

| Document         | EN Status   | ES_UY Status | Last Updated |
| ---------------- | ----------- | ------------ | ------------ |
| Member Guide     | ✅ Complete | ✅ Complete  | 2025-10-26   |
| Admin Manual     | 📝 Pending  | 📝 Pending   | -            |
| Superadmin Guide | 📝 Pending  | 📝 Pending   | -            |
| Developer Guide  | 📝 Pending  | 📝 Pending   | -            |
| DevOps Guide     | 📝 Pending  | 📝 Pending   | -            |

## 🎯 Loading Documents in Application

**Recommended approach**:

```javascript
// Based on user's locale setting
const locale = user.locale; // 'en' or 'es_UY'
const docPath = `/documentation/knowledge-base/derived/${locale}/member-guide.md`;

// Load and display the appropriate version
```

**File naming conventions**:

- English: `kebab-case.md` (e.g., `member-guide.md`)
- Spanish: `kebab-case.md` (e.g., `guia-miembro.md`)

**Translation pairs**:

- `member-guide.md` ↔ `guia-miembro.md`
- `admin-manual.md` ↔ `manual-admin.md`
- `superadmin-guide.md` ↔ `guia-superadmin.md`
- `developer-guide.md` ↔ `guia-desarrollador.md`
- `devops-guide.md` ↔ `guia-devops.md`

## 🔗 Derivation Metadata

The KNOWLEDGE_BASE.md contains a "Derived Document Sources" section that tracks:

- Which KB sections sourced each document
- Content coverage checklist
- Translation terminology
- Last generation date

This metadata enables:

- ✅ Efficient regeneration (only affected docs)
- ✅ Consistency checking (all content covered)
- ✅ Translation accuracy (standard terminology)
- ✅ Change tracking (what changed and why)

---

**Generated**: 2025-10-26
**Maintained by**: AI (updates automatically with KB changes)
