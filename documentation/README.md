# MTAV Documentation

Welcome to the MTAV documentation hub. This folder contains all project documentation organized by audience and purpose.

---

## 📚 Documentation Structure

### 🧠 [Knowledge Base](knowledge-base/)

**Single source of truth** for MTAV system knowledge.

- **[KNOWLEDGE_BASE.md](knowledge-base/KNOWLEDGE_BASE.md)** - Complete system specification
  - Business domain, technical architecture, development workflows
  - **AI-managed**: Do not edit manually
  - **Purpose**: Generate all derived documentation, validate code, answer questions

- **[Derived Documentation](knowledge-base/derived/)** - Generated from KB
  - **[Member Guide](knowledge-base/derived/member-guide.md)** - For housing cooperative members
  - **[Admin Manual](knowledge-base/derived/admin-manual.md)** - For project managers _(coming soon)_
  - **[Superadmin Guide](knowledge-base/derived/superadmin-guide.md)** - For system administrators _(coming soon)_
  - **[Developer Guide](knowledge-base/derived/developer-guide.md)** - For developers _(coming soon)_

### 🔧 [Technical Documentation](technical/)

**Developer and DevOps** guides for working with the MTAV codebase.

- **[docker.md](technical/docker.md)** - Docker development setup and operations
- **[testing.md](technical/testing.md)** - Testing workflows and Git hooks
- **[builds.md](technical/builds.md)** - Production builds and deployment
- **[deployment.md](technical/deployment.md)** - Production deployment configuration
- **[scripts.md](technical/scripts.md)** - Development script documentation
- **[build-images.md](technical/build-images.md)** - Build system technical details
- **[troubleshooting.md](technical/troubleshooting.md)** - Common issues and solutions

---

## 🎯 Finding What You Need

**I'm a housing cooperative member**:
→ Read [Member Guide](knowledge-base/derived/member-guide.md)

**I'm a project manager/admin**:
→ Read [Admin Manual](knowledge-base/derived/admin-manual.md) _(coming soon)_

**I'm a system administrator**:
→ Read [Superadmin Guide](knowledge-base/derived/superadmin-guide.md) _(coming soon)_

**I'm a developer**:
→ Start with [Developer Guide](knowledge-base/derived/developer-guide.md) _(coming soon)_
→ Check [Technical Documentation](technical/) for setup and workflows

**I'm deploying to production**:
→ See [Deployment Guide](technical/deployment.md)

**I'm setting up development environment**:
→ See [Docker Setup](technical/docker.md)

**I'm an AI assistant**:
→ Parse [KNOWLEDGE_BASE.md](knowledge-base/KNOWLEDGE_BASE.md) for complete system context

---

## 📝 Documentation Maintenance

**Knowledge Base**: AI-managed, do not edit manually

- Report issues or needed changes to AI
- AI updates KB → regenerates derived docs
- Ensures consistency across all documentation

**Technical Docs**: Developer-maintained

- Update as code and infrastructure evolve
- Link to KB for business rules
- Focus on technical implementation details

---

## 🔗 External References

- **Root README**: [../README.md](../README.md) - Project overview and quick start
- **Test Documentation**: [../tests/README.md](../tests/README.md) - Test suite overview
- **Package Documentation**: Individual packages in `../packages/` may have README files
