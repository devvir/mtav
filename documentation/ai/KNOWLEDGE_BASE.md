# MTAV - Comprehensive Knowledge Base

**MTAV**: "Mejor Tecnología de Asignación de Viviendas" (Best Technology for Housing Assignment)

**Version**: 2.0 - Complete Technical & Business Specification
**Date**: 2025-10-26
**Last Updated**: 2025-10-26

---

## Document Metadata

**Project Name**: MTAV - Mejor Tecnología de Asignación de Viviendas (Best Technology for Housing Assignment)

**Acronym**: MTAV stands for "Mejor Tecnología de Asignación de Viviendas" in Spanish, which translates to "Best Technology for Housing Assignment" in English.

**Project Mission**: Help housing cooperatives distribute units fairly while maximizing overall family satisfaction through mathematical optimization.

**Purpose**: Single source of truth for the MTAV housing cooperative management system. This document contains ALL knowledge about the system: business rules, technical architecture, development workflows, deployment procedures, testing strategies, and operational details.

**Primary Use**: AI assistants parsing this document to:

- Generate actor-specific documentation (Member guides, Admin manuals, etc.)
- Validate application code against business rules
- Create tests that enforce constraints
- Answer questions from any stakeholder
- Generate deployment procedures
- Produce troubleshooting guides

**Secondary Use**: Developers and stakeholders reading directly for comprehensive understanding.

**Scope**: Complete system knowledge - from business vision to deployment commands.

**Audiences** (for derived documentation):

- **App Actors**: Superadmins, Admins, Members
- **Technical Staff**: Developers, DevOps, System Administrators
- **Business Stakeholders**: Project Managers, College Staff
- **End Users**: Housing cooperative families

**Document Structure**:

- **Part 1**: Business Domain (rules, entities, authorization, workflows)
- **Part 2**: Technical Architecture (stack, infrastructure, deployment)
- **Part 3**: Development & Testing (TDD, roadmap, edge cases)

**Derivation Strategy**: This knowledge base can generate:

1. User guides (per actor type)
2. Technical documentation (per role)
3. API specifications
4. Test suites
5. Training materials
6. Troubleshooting guides
7. Compliance documentation
8. FAQ systems / Chatbots

**Localization Policy**:

- **App user documentation** (Members, Admins, Superadmins): Maintained in both English and Uruguayan Spanish
  - `derived/en/` - English versions
  - `derived/es_UY/` - Uruguayan Spanish versions
  - **Spanish formality level**: Use "tú" form (informal but respectful)
    - Reason: User base ranges from tech-savvy youth to elderly with limited tech knowledge, from urban to rural, modern to traditional
    - "Tú" is universally acceptable and respectful across all demographics
    - "Vos" would be too informal for some users (elderly, traditional, formal contexts)
    - "Usted" would be unnecessarily formal for a cooperative community
  - Examples: "puedes hacer", "tu perfil", "debes revisar" (not "podés", "vos", "debés")
  - **Technical language policy for Member documentation**:
    - Assume members have NO technical knowledge
    - Use extremely simple, clear language
    - Avoid technical jargon entirely
    - Use Spanish words even for terms commonly used in English by IT professionals
    - Exception: Only use English terms if they are truly common among non-technical Uruguayans
    - When in doubt, use Spanish
    - Examples: "correo electrónico" (not "email"), "contraseña" (not "password"), "iniciar sesión" (not "login")
  - **Technical language policy for Admin/Superadmin documentation**:
    - Admins can request assistance if needed, so moderate technical terms acceptable
    - Superadmins are tech-savvy, so technical terms fully acceptable
    - Still prefer Spanish when natural, but English IT terms acceptable when standard
- **Technical/internal documentation** (Developers, DevOps, READMEs, architecture docs): English only
  - Project maintainers are assumed to have reasonable English proficiency
  - Includes: Developer guides, DevOps guides, API docs, architecture specs, README files
  - Exception: If Spanish versions needed for technical staff, use "vos" form (informal, peer-to-peer)

**Derivation Metadata**: Tracks which KB sections source each derived document (maintained by AI, do not edit manually)

**AI-Managed Workflow**:

- **User Commitment**: User will NOT manually edit this Knowledge Base or derived documents
  - Reason: Maintains single source of truth, prevents contradictions
  - Process: User reviews derived docs → reports issues to AI → AI updates KB → AI regenerates affected docs → user reviews
- **AI Responsibility**: Keep KB as single source of truth
  - All changes to business rules, workflows, or system knowledge happen in KB first
  - Then regenerate all affected derived documents
  - Keep derivation metadata updated (which KB sections → which derived docs)
- **Feedback Loop**:
  1. User reviews derived documentation (human-readable, focused)
  2. User reports issues: "Section X says Y but should say Z"
  3. AI updates KB first (this document)
  4. AI regenerates all affected derived documents (both EN and ES versions)
  5. User verifies changes
  6. Commit KB + derived docs together (always synchronized)
- **Benefits**:
  - ✅ No contradictions between documents
  - ✅ Changes propagate consistently
  - ✅ All versions stay synchronized
  - ✅ Complete audit trail of changes
  - ✅ AI can train other AIs with complete context

---

## Derived Document Sources

> **Purpose**: Maps each derived document to the KB sections that sourced it. Used by AI to efficiently regenerate documents when KB sections change.
> **Maintenance**: AI-managed. Updated automatically when documents are generated or KB sections are modified.

### Member Guide (`derived/en/member-guide.md`, `derived/es_UY/member-guide.md`)

**Last Generated**: 2025-10-26
**Source Sections**:

- Document Metadata → Document info footer
- Vision & Core Purpose → "What is MTAV?" section
- Actor Type: Member → Complete actor definition
  - Actor Overview → "Understanding Your Role"
  - Capabilities Matrix → "What You Can Do" / "What You Cannot Do"
  - Family Atomicity → "Important Rules: Family Atomicity"
  - Project Scoping → "Important Rules: Project Scope"
  - Member Workflows → All workflow sections
    - Profile Management → "Managing Your Profile"
    - Preference Management → "Managing Family Preferences"
    - Event Participation → "Participating in Events"
    - Media Upload → "Uploading Media"
    - Member Invitation → "Inviting Family Members"
- UI Navigation & User Actions → "Managing Your Profile", "Accessing Settings", "Logging Out"
- Core Entity: FamilyPreference → "Managing Family Preferences" details
- Core Entity: Event → "Participating in Events" details
- Business Process: Lottery Execution → "Understanding the Lottery"
- Authorization & Constraints → Various "Important Rules" sections
  - Immutability Rules → "Immutable Fields"
  - Audit Requirements → "Audit Trail"
  - Soft Delete Policy → "Soft Delete"

**Content Coverage**:

- ✅ All member capabilities (view, update, manage, upload, RSVP, invite)
- ✅ All member restrictions (cannot do)
- ✅ Family atomicity rules (stay together)
- ✅ Project scoping (cannot access other projects)
- ✅ Preference workflows (create, update, delete, freeze)
- ✅ Event RSVP workflows
- ✅ Media upload rules
- ✅ Invitation mechanics
- ✅ Lottery process explanation (non-technical)
- ✅ Satisfaction scoring concept
- ✅ FAQ covering common member questions

**Translation Notes** (for `es_UY` version):

- "Member" → "Miembro"
- "Family" → "Familia"
- "Housing Cooperative" → "Cooperativa de Viviendas" (always plural "viviendas" in Uruguayan Spanish)
- "Lottery" → "Sorteo"
- "Preferences" → "Preferencias"
- "Unit" → "Unidad" or "Vivienda"
- "Project" → "Proyecto"
- "Satisfaction Score/Rating" → "Calificación de Satisfacción" (displayed as 1-5 stars for members, not numeric scores)
- "Admin" → "Administrador" (never leave as "admin")
- "Avatar" → "Foto de perfil" (not "avatar")
- "Profile" → "Perfil"
- "Email" → "Correo electrónico" (not "email")
- "Password" → "Contraseña" (not "password")
- "System" → "Aplicación" (when referring to the app), "Sistema" (when referring to the cooperative system/process)
- "Mathematical optimization/algorithm" → Simplify to "Cálculo justo" or "Métodos avanzados" (avoid technical jargon)
- "RSVP" → "Confirmar asistencia"
- "Login" → "Iniciar sesión"
- "Sunlight" → "Luz natural" (not "luz solar" - natural light is the common term in Uruguay)
- "Legal ID" → "CI" or "Cédula de Identidad" (in Uruguay), "DNI" (in Argentina/Spain), context-dependent

**Simplification Examples for Member Docs**:

- ❌ "Un sistema matemático usa algoritmos avanzados (como el algoritmo Húngaro)"
- ✅ "Un programa de computadora usa métodos avanzados para encontrar la mejor distribución"
- ❌ "El sistema de optimización maximiza..."
- ✅ "El sorteo maximiza..." or "La aplicación maximiza..."

---

## Vision & Core Purpose

**The Fundamental Objective**:

MTAV exists to enable the **fairest possible distribution** of collectively-built housing units among cooperative project members.

### The Cooperative Model

In a housing cooperative project:

1. **Families join a collective project** to build housing together
2. **All families contribute** equally (or proportionally) to the construction
3. **Units are built collectively** - no family "owns" a specific unit during construction
4. **After completion**, units must be distributed fairly among families
5. **The goal**: Each family gets a unit as close as possible to what they would have chosen if they could pick freely

### Why This App Exists

**Every feature in MTAV serves this single purpose**: Enable the fairest distribution by:

- **Accurately modeling reality**: Capture the true state of the cooperative project
  - Projects, families, members, units, and their characteristics
  - Ensure data integrity (family atomicity, project scope, etc.)

- **Enabling informed preferences**: Help families understand and choose their options
  - Unit characteristics (size, bedrooms, garden, location)
  - Visual blueprints showing unit positions
  - Clear categorization by unit type

- **Fair lottery execution**: Use mathematical optimization to maximize satisfaction
  - External API applies numerical methods for optimal assignment
  - Considers all family preferences simultaneously
  - Balances competing desires to achieve fairest overall outcome

- **Trust through transparency**: All families see the process is fair
  - Clear rules and constraints
  - Auditable lottery execution
  - Visible results and satisfaction metrics

### Guiding Principle for All Business Rules

**Ask**: "Does this rule/constraint help achieve the fairest distribution?"

- ✅ **Yes**: Family atomicity ensures families move together (fair)
- ✅ **Yes**: Unit type assignment prevents unfair size mismatches (fair)
- ✅ **Yes**: Project scope prevents admin overreach across projects (fair)
- ✅ **Yes**: Authorization rules protect data integrity (enables fair process)
- ✅ **Yes**: Preference collection allows families to express desires (enables fairness)

If a rule doesn't serve fairness, question whether it should exist.

---

## Table of Contents

### Part 1: Business Domain

1. [Vision & Core Purpose](#vision--core-purpose)
2. [Domain Overview](#domain-overview)
3. [Actor Definitions](#actor-definitions)
4. [Core Entities & Relationships](#core-entities--relationships)
5. [State Management](#state-management)
6. [Authorization Matrix](#authorization-matrix)
7. [Business Rules by Entity](#business-rules-by-entity)
8. [Family Atomicity Rules](#family-atomicity-rules)
9. [Project Scope & Context](#project-scope--context)
10. [Invitation & User Management](#invitation--user-management)
11. [Unit Types & Distribution System](#unit-types--distribution-system)
12. [Unit Distribution Lottery (Sorteo)](#unit-distribution-lottery-sorteo)
13. [UI/UX Principles](#uiux-principles)

### Part 2: Technical Architecture

14. [Technology Stack](#technology-stack)
15. [Application Architecture](#application-architecture)
16. [Development Environment](#development-environment)
17. [Docker Infrastructure](#docker-infrastructure)
18. [Development Workflows](#development-workflows)
19. [Testing Infrastructure](#testing-infrastructure)
20. [Quality Assurance & Git Hooks](#quality-assurance--git-hooks)
21. [Build System & Production Images](#build-system--production-images)
22. [Deployment Architecture](#deployment-architecture)
23. [Configuration Management](#configuration-management)
24. [Troubleshooting & Common Issues](#troubleshooting--common-issues)

### Part 3: Development & Testing

25. [Development & Testing Strategy](#development--testing-strategy)
26. [TDD Roadmap for Remaining Features](#tdd-roadmap-for-remaining-features)
27. [Edge Cases & Constraints](#edge-cases--constraints)
28. [Open Questions](#open-questions)

---

## Domain Overview

MTAV is a housing cooperative management system where:

- **Projects** are housing developments (buildings/complexes)
- **Families** are groups of people living together in a unit
- **Members** are individuals within families
- **Admins** manage one or more projects
- **Superadmins** have unrestricted access to everything
- **Units** are physical housing units (apartments/houses) within projects

**Key Business Concept**: Families are **atomic units**. They belong to exactly one project, and all members of a family should be in the same project as their family.

---

## Actor Definitions

**System Actors**: The MTAV system has three types of authenticated users, each with distinct roles, permissions, and capabilities.

**Actor Hierarchy**:

```
Superadmin (highest privileges)
    ↓ inherits from + bypasses policies
Admin (project-scoped management)
    ↓ separate from
Member (family-bound participation)
```

**Isolation**: Admins and Members are mutually exclusive - a user cannot be both.

---

### Actor Type: Superadmin

**Actor Category**: System Administrator
**Database Type**: Admin (with special configuration)
**Primary Role**: Unrestricted system oversight and exceptional interventions
**Typical Users**: College staff, system administrators

#### Definition

- User whose emails are listed in `config('auth.superadmins')` array
- Typically user email = 'superadmin@example.com', but configurable
- NOT a separate user type - just a regular User/Admin with special email
- **Superadmins are ALWAYS admins** (`is_admin = true` required)

#### Identification (Code)

```php
$user->isSuperadmin() // true if $user->email in config array AND is_admin = true
```

#### Authorization Behavior

**Policy Bypass**:

- ALL policies are bypassed via `Gate::before()`
- Returns `true` for any authorization check before individual policy methods run
- No need to implement superadmin logic in individual policies
- Superadmin authorization happens at framework level, not policy level

#### Database Representation

```sql
users table:
  - email (must be in config('auth.superadmins') array)
  - is_admin = true (REQUIRED - superadmins are always admins)
  - family_id = NULL (constraint: admins MUST NOT have a family)

project_user pivot:
  - Same as regular admins
  - Can manage ALL projects (no scope restrictions)
```

#### Capabilities Summary

**Inherits**: All admin capabilities (see Admin section)

**Additional Capabilities**:

- ✅ Access ANY project (no scope restrictions)
- ✅ Create projects
- ✅ Assign admins to any project
- ✅ Perform database corrections (manual SQL when needed)
- ✅ Invalidate lottery results (rare, exceptional cases)
- ✅ Override any business constraint (emergency interventions)
- ✅ Delete admins (regular admins cannot)

**Restrictions**:

- ❌ Cannot delete themselves (prevent system lockout)
- ❌ Must remain as admins (removing from admin breaks superadmin status)

**Use Cases**:

- Initial system setup (create first projects, assign first admins)
- Emergency interventions (data corrections, lottery invalidation)
- Cross-project oversight
- Exceptional case handling (state rollbacks, constraint overrides)

**Accountability**: Superadmins answer to institutional oversight (college, board). All actions must be auditable.

---

### Actor Type: Admin

**Actor Category**: Project Manager
**Database Type**: User with `is_admin = true`
**Primary Role**: Manage assigned housing cooperative projects
**Typical Users**: Project coordinators, housing cooperative managers

#### Definition

- User with `is_admin = true`
- Assigned to one or more projects via `project_user` pivot table
- Manages assigned projects but NOT other projects
- **Project scope is absolute** - universe limited to assigned projects only

#### Database Representation

```sql
users table:
  - is_admin = true  (PRIMARY TYPE DISCRIMINATOR)
  - family_id = NULL (CONSTRAINT: admins MUST NOT have a family)
  - (other standard user fields)

project_user pivot:
  - user_id (FK to users)
  - project_id (FK to projects)
  - active (boolean - TRUE = currently managing, FALSE = historical)
  - created_at, updated_at

SEMANTICS for Admins:
  - Can have MULTIPLE projects with active = true (manage several simultaneously)
  - active = false means historical management (audit trail)
  - Admin can "leave" project (sets active = false for that project)
```

#### Scoping & Universe

**Project Scope Rules**:

- Admin can ONLY access resources in projects where they have `active = true`
- Cannot see projects they're not assigned to
- Cannot create resources in unmanaged projects
- **Universe = set of actively managed projects ONLY**

**Multi-Project Context**:

- Admin managing 2+ projects starts in multi-project context
- Must select specific project to enter single-project context
- Can switch between managed projects freely

**Single-Project Context**:

- Admin managing 1 project automatically enters that project's context
- All indexes filtered to current project only

#### Capabilities Matrix

**✅ CAN DO** (within managed projects):

| Capability                       | Scope                    | Notes                                   |
| -------------------------------- | ------------------------ | --------------------------------------- |
| Create other admins              | Managed projects only    | Can only assign to projects they manage |
| Create projects                  | ❌ NO                    | Only superadmins create projects        |
| Manage families                  | Managed projects         | Create, update, delete families         |
| Manage members                   | Managed projects         | Create (invite), update, delete members |
| Manage units                     | Managed projects         | Create, update, delete units            |
| Manage unit types                | Managed projects         | Create, update, delete types            |
| Manage blueprints                | Managed projects         | Upload, update project blueprints       |
| Manage preferences               | Managed projects         | View, update family preferences         |
| Execute lottery                  | Managed projects         | Run lottery for project                 |
| Create events                    | Managed projects         | Community and lottery events            |
| Upload media                     | Managed projects         | Project-related images/videos           |
| Switch families between projects | Between managed projects | Move families within their universe     |
| Leave projects                   | Self only                | Set own `active = false` for project    |

**❌ CANNOT DO**:

| Restriction                           | Reason                                     |
| ------------------------------------- | ------------------------------------------ |
| Add themselves to new projects        | Only superadmins assign admins to projects |
| Manage unassigned projects            | Project scope enforcement                  |
| Create projects                       | Superadmin-only capability                 |
| Soft-delete themselves                | Should leave all projects instead          |
| Delete other admins                   | Only superadmins delete admins             |
| Invalidate lottery results            | Superadmin-only (fairness protection)      |
| Manual unit reassignment post-lottery | Lottery results are immutable              |
| Access other projects                 | Absolute project scope boundary            |

#### Workflows

**Admin Creation Workflow**:

1. Superadmin or existing admin creates new admin user
2. Creator assigns admin to projects they manage (or all projects for superadmin)
3. New admin receives invitation email
4. New admin verifies email and sets password
5. New admin can immediately manage assigned projects

**Admin Self-Management**:

- Can update own profile (name, email, avatar, password)
- Can leave projects (sets `active = false`)
- Cannot delete themselves (prevents accidental lockout)
- Cannot add themselves to new projects (requires superadmin)

**Accountability**: Admins answer to members and superadmins. All actions logged for audit and fairness verification.

---

### Actor Type: Member

**Actor Category**: Cooperative Participant
**Database Type**: User with `is_admin = false`
**Primary Role**: Participate in housing cooperative as family representative
**Typical Users**: Housing cooperative family members

#### Definition

- User with `is_admin = false` (PRIMARY TYPE DISCRIMINATOR)
- **MUST belong to exactly one Family** (required - `family_id` not null)
- Has exactly ONE active project at a time via `project_user` pivot
- May have inactive project associations from historical family moves
- **Cannot exist without family** - family membership is mandatory
- Represents individual person within collective family unit

#### Database Representation

```sql
users table:
  - is_admin = false (PRIMARY TYPE DISCRIMINATOR)
  - family_id (FK to families - MUST BE PRESENT, business rule enforced)
  - (other standard user fields)

Note: Database cannot enforce NOT NULL on family_id because admins share this table
Business rule enforcement: Members MUST have family_id, validated at application level

project_user pivot:
  - user_id (FK to users)
  - project_id (FK to projects)
  - active (boolean - TRUE = current project, FALSE = historical)
  - created_at, updated_at

SEMANTICS for Members:
  - Can have EXACTLY ONE project with active = true (business rule enforced)
  - active = false means historical membership from:
    a) Full family project switch (family moved, cascades to all members)
    b) Member switched to different family in different project (admin-mediated)
  - Member cannot leave project independently (family atomicity)
  - Member cannot make autonomous project decisions (family is decision unit)
```

#### Family Atomicity Principle

**Core Concept**: Family is the atomic unit of participation, not individual member.

**Implications**:

- Member's project membership mirrors their family's project
- Member cannot join/leave projects independently
- Member switching projects requires switching families (admin-mediated)
- All family members MUST share same active project
- Member actions are individual (profile, RSVP, media upload)
- Family actions are collective (preferences, project membership)

**Enforcement**:

- Database: `family_id` required (business rule)
- Application: Validation prevents orphan members
- Cascade: Family deletion soft-deletes all members
- Coordination: Family project changes cascade to member pivots

#### Scoping & Universe

**Project Scope Rules**:

- Member can ONLY access resources in their active project (family's project)
- Cannot see other projects
- Cannot switch projects directly (must switch families via admin)
- **Universe = single active project ONLY**

**Single-Project Context**:

- Members ALWAYS in single-project context (family's project)
- Current project automatically set to `family.project_id`
- No project selection UI for members (automatic)
- All indexes filtered to current project

#### Capabilities Matrix

**✅ CAN DO** (within family's project):

| Capability                | Scope           | Notes                                             |
| ------------------------- | --------------- | ------------------------------------------------- |
| View families             | Current project | All families visible                              |
| View members              | Current project | All members visible                               |
| View units                | Current project | All units visible                                 |
| View own family           | Current project | Full access to family data                        |
| View own profile          | Self            | Personal data visibility                          |
| Update own profile        | Self            | Name, avatar, password (NOT email)                |
| Manage family preferences | Own family      | Any family member can update (all logged)         |
| Upload media              | Current project | Project-related images/videos                     |
| RSVP to events            | Current project | Community and lottery events                      |
| Invite family members     | Own family      | Create new member in same family (admin approval) |
| Soft-delete self          | Self            | Leave system entirely (family may become empty)   |

**❌ CANNOT DO**:

| Restriction                   | Reason                                                 |
| ----------------------------- | ------------------------------------------------------ |
| Switch projects               | Family atomicity - only via family switch              |
| Leave family independently    | Family atomicity - must switch families or delete self |
| Create families               | Admin-only operation                                   |
| Delete families               | Admin-only operation                                   |
| Update other members          | Self-management only                                   |
| Delete other members          | Self-management only                                   |
| Manage units                  | Admin-only operation                                   |
| Manage unit types             | Admin-only operation                                   |
| Execute lottery               | Admin-only operation                                   |
| Create events                 | Admin-only operation                                   |
| Create other admins           | Admin-only operation                                   |
| Access other projects         | Absolute project scope boundary                        |
| Change own family_id directly | Admin-mediated family switch only                      |

#### Workflows

**Member Creation Workflow**:

1. **Admin-created**:
   - Admin creates member in any family in managed project
   - Member receives invitation email
   - Member verifies email and sets password
   - Member immediately active in family's project

2. **Member-invited** (family invitation):
   - Member invites relative to join their family
   - `family_id` auto-set to inviter's family (hidden, forced)
   - `project_id` auto-set to inviter's project (hidden, forced)
   - Invitee receives email and sets password
   - Invitee joins family in same project

**Member Self-Management**:

- Can update profile (name, avatar, password)
- Can soft-delete themselves (leave system)
- Cannot change email (requires admin intervention)

**Member Family Switch** (admin-mediated):

1. Admin moves member to different family
2. If new family in different project:
   - Old project membership set `active = false`
   - New project membership created with `active = true`
3. Cannot empty source family (must have at least one member)
4. Member cannot initiate this action (admin discretion)

**Member Project Switch** (via family):

1. Admin moves entire family to different project
2. All family members' project memberships updated:
   - Old project set `active = false` for all members
   - New project set `active = true` for all members
3. Atomic operation (all members move together)
4. Preserves historical membership records

#### Events System

**Member Event Capabilities**:

**Community Events**:

- View events in current project
- RSVP to events (attending, not attending, maybe)
- Add event to personal calendar
- Receive event reminders

**Lottery Events**:

- View lottery event details
- RSVP (required for participation)
- View lottery results after execution
- View assigned unit (if applicable)

**Event Details**:

- Events have: title, description, date, location (or "online")
- Admins create events
- Members RSVP
- Events scoped to project (members only see their project's events)

#### Media Upload

**Member Media Capabilities**:

- Upload images related to project (progress photos, community photos)
- Upload videos (if enabled)
- Tag media with project/family context
- View project media gallery
- Cannot delete others' media (admin moderation)

#### Preference Management

**Member Preference Capabilities**:

**Before Lottery**:

- View all available units in family's unit type
- Create ranked preferences (1st choice, 2nd choice, etc.)
- Update preferences freely until lottery execution
- Any family member can manage preferences (all actions logged)
- See family's current preference list

**During/After Lottery**:

- Preferences frozen (read-only)
- View submitted preferences (audit record)
- View assigned unit (if applicable)
- View satisfaction score

**Automatic Updates**:

- Unit deleted → Auto-removed from preferences + email notification
- Unit type changed → Auto-removed if incompatible + email notification
- Unit added → Email notification (opt-in to add to preferences)
- Unit metadata changed → Email notification (review preferences)

#### Accountability

**Member Accountability**:

- Members answer to their families (collective decision-making)
- Members answer to admins (rule enforcement)
- All actions logged for audit trail
- Preference changes logged (who, when, what changed)
- Cannot manipulate lottery results (fairness protection)

**Transparency**:

- Members can view all families, units, preferences (within project)
- Lottery process is transparent and auditable
- Results visible to all (satisfaction scores, assignments)
- Clear explanation of rules and constraints

---

## Core Entities & Relationships

**Entity Model**: MTAV uses Laravel Eloquent ORM to model the housing cooperative domain.

**Relationship Strategy**:

- Standard Laravel relationships (belongsTo, hasMany, belongsToMany)
- Soft deletes throughout (preserves audit trail)
- Pivot tables for many-to-many (project_user, family_preferences)
- STI (Single Table Inheritance) for User → Admin/Member

**Data Integrity**:

- Foreign keys with constraints
- Business rules enforced at application level (family atomicity, etc.)
- Validation in Form Requests
- Authorization in Policies

---

### Entity: User (Base Model)

**Purpose**: Base model for all authenticated users (Admins and Members)
**Pattern**: Single Table Inheritance (STI) - one table, multiple types
**Discriminator**: `is_admin` field (true = Admin, false = Member)

#### Database Schema

**Table**: `users`

```sql
id (PK, auto-increment)
family_id (FK to families, NULLABLE - null for admins, required for members)
email (VARCHAR, UNIQUE, NOT NULL)
phone (VARCHAR, NULLABLE)
firstname (VARCHAR, NOT NULL - required for all users)
lastname (VARCHAR, NULLABLE - optional for all users)
legal_id (VARCHAR, NULLABLE - FUTURE FEATURE: Legal ID document for members. Called CI in Uruguay, DNI in Argentina/Spain, etc. Policy-based requirement, hidden from other members, immutable by members once set)
password (VARCHAR, NOT NULL - hashed)
avatar (VARCHAR, NULLABLE - file path or URL)
is_admin (BOOLEAN, DEFAULT false - TYPE DISCRIMINATOR)
darkmode (BOOLEAN, NULLABLE - UI preference)
email_verified_at (TIMESTAMP, NULLABLE - email verification status)
remember_token (VARCHAR, NULLABLE - Laravel auth)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
deleted_at (TIMESTAMP, NULLABLE - soft delete)

INDEXES:
  - PRIMARY KEY (id)
  - UNIQUE (email)
  - INDEX (family_id)
  - INDEX (is_admin)
  - INDEX (deleted_at) -- for soft delete queries
```

#### Validation Rules

| Field     | Validation                                           | Notes                                                                                                                                                                                                 |
| --------- | ---------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| firstname | REQUIRED, string, max:255                            | All users must have first name                                                                                                                                                                        |
| lastname  | OPTIONAL, nullable, max:255                          | Last name not required                                                                                                                                                                                |
| email     | REQUIRED, unique, email format                       | Used for authentication                                                                                                                                                                               |
| phone     | OPTIONAL, nullable, string                           | Contact number                                                                                                                                                                                        |
| legal_id  | FUTURE FEATURE: NULLABLE, string, unique             | Legal ID document (CI in Uruguay, DNI in Argentina/Spain, etc.). Policy-based requirement (each project decides). Hidden from other members. Members cannot edit once set, admin can correct mistakes |
| password  | REQUIRED, min:8, confirmed                           | Laravel password rules                                                                                                                                                                                |
| avatar    | NULLABLE, file, image types                          | Profile picture                                                                                                                                                                                       |
| family_id | Business rule: REQUIRED for members, NULL for admins | Enforced at application level                                                                                                                                                                         |
| is_admin  | BOOLEAN, set at creation                             | Cannot change type after creation                                                                                                                                                                     |

#### Relationships

```php
// User belongs to Family (members only)
public function family(): BelongsTo
{
    return $this->belongsTo(Family::class);
}

// User belongs to many Projects (via pivot)
public function projects(): BelongsToMany
{
    return $this->belongsToMany(Project::class, 'project_user')
        ->withPivot('active')
        ->withTimestamps();
}

// Active projects only (default scope for most queries)
public function activeProjects(): BelongsToMany
{
    return $this->projects()->wherePivot('active', true);
}
```

#### Model Scopes

```php
// Global scope on Admin model
protected static function booted()
{
    static::addGlobalScope('admin', function (Builder $query) {
        $query->where('is_admin', true);
    });
}

// Global scope on Member model
protected static function booted()
{
    static::addGlobalScope('member', function (Builder $query) {
        $query->where('is_admin', false);
    });
}
```

#### Model Methods

```php
// Type checking
public function isSuperadmin(): bool
{
    // Checks config array AND is_admin = true
    return $this->is_admin && in_array($this->email, config('auth.superadmins'));
}

public function isAdmin(): bool
{
    return $this->is_admin === true;
}

public function isMember(): bool
{
    return $this->is_admin === false;
}

// Type casting
public function toAdmin(): ?Admin
{
    return $this->isAdmin() ? Admin::find($this->id) : null;
}

public function toMember(): ?Member
{
    return $this->isMember() ? Member::find($this->id) : null;
}
```

#### Soft Delete Behavior

**Uses**: `SoftDeletes` trait from Laravel

**Behavior**:

- Setting `deleted_at` timestamp marks user as deleted
- User becomes invisible to normal queries (unless `withTrashed()`)
- **Does NOT cascade to relationships** - preserves audit trail
- Relationships remain intact (projects, family, etc.)

**Purpose**: Auditability and accountability

**Visibility**:

- Soft-deleted users still appear in:
  - Historical logs and audit trails
  - Relationship queries if explicitly included (`withTrashed()`)
  - Project membership history
  - Family records (if member was deleted)

**Restoration**: Can be un-deleted with `restore()` method

#### Mutators & Accessors

```php
// Password hashing (automatic)
protected function password(): Attribute
{
    return Attribute::make(
        set: fn ($value) => bcrypt($value),
    );
}

// Avatar URL (computed)
protected function avatarUrl(): Attribute
{
    return Attribute::make(
        get: fn () => $this->avatar
            ? Storage::url($this->avatar)
            : asset('images/default-avatar.png'),
    );
}
```

#### Factory (Testing)

```php
// UserFactory generates test data
User::factory()->create(); // Random user
User::factory()->admin()->create(); // Admin user
User::factory()->member()->create(); // Member user
User::factory()->count(10)->create(); // 10 users
```

---

### Entity: Project

**Purpose**: Represents a housing cooperative development (building/complex)
**Scope**: Container for families, members, units, and lottery execution
**Lifecycle**: Created by superadmin, managed by assigned admins

#### Database Schema

**Table**: `projects`

````sql

**Fields**:

```sql
id (PK)
family_id (FK to families, nullable - null for admins, required for members)
email (unique, NOT NULL)
phone (nullable)
firstname (NOT NULL - required for all users)
lastname (nullable - optional for all users)
dni (nullable - immutable ID document, for members)
password (NOT NULL)
avatar (nullable)
is_admin (boolean, default false)
darkmode (boolean, nullable)
email_verified_at (timestamp, nullable)
remember_token
created_at, updated_at
deleted_at (soft delete timestamp, nullable)
````

**Validation Rules**:

- `firstname`: **REQUIRED** (NOT NULL)
- `lastname`: **OPTIONAL** (nullable)
- `email`: REQUIRED, unique
- `dni`: nullable, **immutable once set** (cannot be modified via UI)
- `family_id`: nullable at database level, but **REQUIRED for members** (business rule)
- `is_admin`: boolean, defines user type (Admin vs Member)

**Relationships**:

- `belongsTo(Family)` - for members only (null for admins)
- `belongsToMany(Project)` via `project_user` - active and historical projects

**Scopes**:

- Global scope on Admin model: `where('is_admin', true)`
- Global scope on Member model: `where('is_admin', false)`

**Methods**:

- `isSuperadmin(): bool` - checks config array (superadmins are ALWAYS admins, filtered here)
- `isAdmin(): bool` - checks `is_admin = true` (includes superadmins)
- `isMember(): bool` - checks `is_admin = false` (superadmins are NOT members)
- `toAdmin(): ?Admin` - cast to Admin if is_admin
- `toMember(): ?Member` - cast to Member if !is_admin

**Soft Delete Behavior**:

- Uses `SoftDeletes` trait
- `deleted_at` marks user as deleted
- **ONLY affects the model itself** - does NOT cascade to relationships
- Relationships remain intact for audit trail
- Soft-deleted users still appear in:
  - Historical logs
  - Relationship queries (if soft-deletes not explicitly scoped)
  - Project membership history
- Purpose: **auditability and accountability** - preserve historical records

**Clarifications**:

- `is_admin = true/false` is the **PRIMARY** user type distinction
- Other constraints (family_id, etc.) are **CONSEQUENCES** of user type
- Admins and Members are **ISOLATED SETS** - a user cannot be both
- Superadmins are ALWAYS admins (`is_admin = true`), filtered in `isSuperadmin()` via config

---

### Project

**Table**: `projects`

**Fields**:

```sql
id (PK)
name (NOT NULL)
active (boolean, default true)
status (enum: 'planned', 'under_construction', 'completed', default 'planned')
created_at, updated_at
deleted_at (soft delete, for canceled/mistake projects)
```

**Relationships**:

- `hasMany(Unit)` - housing units in project
- `hasMany(UnitType)` - unit type categories defined for this project
- `hasOne(Blueprint)` - OPTIONAL, visual representation of project layout
- `hasMany(Family)` - families in project
- `belongsToMany(User)->wherePivot('active', true)` - active members
- `belongsToMany(Admin)->wherePivot('active', true)` - assigned admins

**Methods**:

- `static current(): ?Project` - returns `state('project')` (NOT session)
- `addMember(Member): self` - add member to project (active = true)
- `removeMember(Member): self` - set member inactive (active = false)
- `addAdmin(Admin): self` - assign admin to project
- `hasMember(Member): bool` - check if member exists in project
- `hasAdmin(Admin): bool` - check if admin assigned to project

**Scopes**:

- `active()` - where active = true
- `alphabetically()` - order by name

**State Management**:

- One project is "current" per session via `state('project')`
- Members ALWAYS have current project = their family's project
- Admins can switch between managed projects
- Superadmins can switch to any project

**Project Active Flag**:

- `active = true`: Project is ongoing/active
- `active = false`: Project is **completed or archived** (construction finished, units assigned)
- **NOT for mistakes/canceled projects** - use soft-delete for those cases
- Inactive projects may be hidden from listings but remain accessible for historical reference
- Projects can be reactivated if needed (e.g., re-opening for modifications)

**Project States** (field: `status`):

- **Planned** (default): Initial state when project is created
- **Under Construction**: Physical building work in progress (includes common spaces, roads, infrastructure)
- **Completed**: Construction finished, ready for occupancy

**Note**: Project status is independent of unit status:

- Project encompasses entire development (units + common areas + infrastructure)
- Units track their individual construction progress
- Project can be "under construction" while some units are "completed"
- Project can be "completed" while individual units vary in state

**State Transitions** (forward-only, no rollback):

```
Planned → Under Construction → Completed
```

**1. Planned → Under Construction**:

- **Constraint**: None (can transition immediately)
- **Triggered by**: Admin/superadmin manual action OR automatic (see below)
- **Automation**: When **any unit** transitions to "under construction", project **automatically** transitions to "under construction" (if still "planned")
- **Effect on units**: None (unit states unchanged)
- **Rationale**: Physical work starting on any unit means construction has begun

**2. Under Construction → Completed**:

- **Constraint**: ❌ **BLOCKED if ANY unit is not "completed"**
  - ALL units must be in "completed" state
  - **UI Behavior**:
    - Transition button **disabled** (not hidden)
    - Show clear explanation: "Cannot complete project - {count} units still under construction"
    - List incomplete units (optionally, via tooltip or expandable section)
  - **Rationale**: Users should understand WHY action is blocked, not guess
- **Triggered by**: Admin/superadmin manual action only
- **Automation**: None (units transitioning to "completed" does NOT auto-complete project)
- **Effect on units**: None (unit states unchanged)
- **Rationale**: Project completion means entire development ready, requires all units finished

**Error Correction**:

- ❌ **No rollback via UI** (transitions are forward-only)
- If mistake made: **Superadmin corrects directly in database**
- Manual database UPDATE to revert `status` field (exceptional cases only)

**State Effects on Operations**:

- **Planned**: All operations allowed (create/modify units, types, families, etc.)
- **Under Construction**: All operations allowed (construction ongoing, changes expected)
- **Completed**: All operations allowed (may need post-completion adjustments)
- **Note**: State is informational, does NOT freeze operations (use `active = false` to freeze project)

**Soft Delete Behavior**:

- Projects can be soft-deleted (for mistakes or canceled projects)
- Soft-delete affects **only the project model** (preserves relationships)
- Families and members in soft-deleted projects remain intact (audit trail)
- Use `active = false` for **completed** projects
- Use soft-delete for **canceled/mistake** projects

**Member Pivot `active = false`**:

- Indicates **historical membership** created in two scenarios:
  1. **Full family project switch**: Entire family moves to different project (cascades to all members)
  2. **Member family switch**: Member switches to different family in different project (admin-mediated, cannot empty source family)
- Member cannot manually leave project (family atomicity)
- Preserves membership history across project changes and family switches
- Default relationships fetch only `active = true` (explicit scope for historical data)

**Project Deletion Rules**:

- Soft-delete allowed (canceled/mistake projects)
- Does **NOT cascade** to families or members
- Families/members remain in database with intact project_id references
- Enables full audit trail and accountability
- Hard delete only through exceptional manual database action

---

### Family

**Table**: `families`

**Fields**:

```sql
id (PK)
project_id (FK to projects, NOT NULL - families MUST belong to project)
unit_type_id (FK to unit_types, NULLABLE - assigned by admin for lottery eligibility)
name (NOT NULL)
avatar (nullable)
created_at, updated_at

UNIQUE(project_id, name) - family names unique within project
```

**Relationships**:

- `belongsTo(Project)` - family's project
- `belongsTo(UnitType)` - NULLABLE, the type of unit this family is eligible for
- `hasMany(Member)` - all members in family
- `hasMany(FamilyPreference)` - family's unit preferences for lottery
- `hasOne(Unit)` - NULLABLE via units.family_id, assigned after lottery

**Methods**:

- `addMember(Member): self` - add member to family
- `join(Project): self` - move family + all members to project
  - **QUESTION**: Does this work currently or is it TODO?
  - Sets family.project_id
  - Calls project.addMember() for each member
  - What about old project memberships (set inactive?)

**NOT YET IMPLEMENTED** (TODO):

- `leave(Project): self` - remove family from project
- `moveToProject(Project): self` - atomically move to different project

**Scopes**:

- `alphabetically()` - order by name
- `search(string $q, bool $searchMembers)` - search by name or members

**Business Rules** (see detailed section below):

- Family MUST belong to exactly one project (NOT NULL constraint)
- All family members MUST be in same project as family (family atomicity enforced)
- Cannot create family without project_id
- Moving family moves all members atomically (batch operation)

**Family Creation & Empty Families**:

- Families **CAN exist temporarily without members** during creation
- Creation workflow: create family first, then add members
- Empty families should be **filtered from listings** (appear invalid to users)
- In database: empty families are valid (allows creation workflow)
- In UI: empty families hidden or marked as incomplete/invalid
- `join(Project)` works - sets family.project_id and migrates all members

**Family Deletion & Cascading**:

- Families can be **soft-deleted** (for audit trail)
- Soft-delete **cascades to ALL members** (all members soft-deleted simultaneously)
- **NO orphan members allowed** - members must always have family
- Preserves entire family unit in database for historical reference
- Cannot delete family if you want to keep members - violates family atomicity

**Family Project Changes**:

- `family.project_id` can be updated directly via admin actions or update form
- Changes should trigger member pivot updates (set all members to new project)
- `join(Project)` method handles atomic family + member migration:
  - Sets `family.project_id` to new project
  - Calls `project.addMember()` for each family member
  - Sets old project memberships to `active = false` (historical)
  - Creates new project memberships with `active = true`
- `moveToProject(Project)` method is similar, handles complete migration

---

### Member (extends User)

**Table**: `users` (with `is_admin = false`)

**Relationships**:

- `belongsTo(Family)` - member's family (REQUIRED, never null)
- `belongsToMany(Project)` - projects (exactly one active, others historical)

**Accessor**:

- `project: ?Project` - returns active project via `$this->projects->where('pivot.active', true)->first()`

**Methods**:

- `joinProject(Project): self` - add member to project (admin-only operation)
- `leaveProject(Project): self` - set project inactive (admin-only, family context)
- `switchProject(Project): self` - leave old, join new (admin batch operation)

**Family Atomicity Constraints**:

- Members **CANNOT join projects independently** - only via family
- `joinProject()` / `leaveProject()` / `switchProject()` should be **admin operations** or internal methods
- Member-initiated individual project changes violate family atomicity principle
- These methods exist for admin batch operations when moving families
- Individual members should NOT call these directly via UI
- Methods should validate family atomicity or be protected/private

**"Leaving a Project" for Members**:

- Members cannot "leave" their family's project independently
- Member is ALWAYS in family's project - no contradiction possible
- If member wants to leave project, two options:
  1. **Switch to new family** (admin-mediated, cannot empty source family)
  2. **Soft-delete themselves** (leave entire system, not just project)
- "Leave project" operation only makes sense in admin context:
  - Admin moves entire family to new project
  - Sets old project memberships to `active = false` for all family members
  - Creates new active memberships for all family members
- Member switching to different project than their family is **PREVENTED**
- Family atomicity enforced at application level (policies, validation)

---

### Admin (extends User)

**Table**: `users` (with `is_admin = true`)

**Relationships**:

- `belongsToMany(Project)` - assigned projects

**Methods**:

- `manages(Project): bool` - true if assigned to project OR is superadmin

**Admin-Project Assignment**:

**Pivot Active Flag Semantics**:

- When admin is **assigned** to project: pivot has `active = true`
- When admin **stops managing** project: pivot switches to `active = false` (historical record)
- Admins with `active = true` are currently managing the project
- Admins with `active = false` managed the project in the past (audit trail)

**Inactive Project Constraints**:

- If `project.active = false` (completed/archived project):
  - **NO admins can be assigned or unassigned** (pivot active flag frozen)
  - Inactive projects are **frozen** - no changes allowed except:
    - Read operations (viewing historical data)
    - Potentially "unarchiving" (`project.active` back to true)
  - All non-read actions are **forbidden** on inactive projects

**Assignment Process** (4 ways to assign admins to projects):

1. **Project creation** (`project.create` form) - can include initial admins
2. **Project edit** (`project.edit`) - update list of assigned admins
3. **Admin creation** (`admin.create`) - new admin given list of managed projects
4. **Admin edit** (`admin.edit`) - modify admin's managed projects list

**Assignment Authorization**:

- Admins can be assigned by:
  - **Superadmins** (can assign to any project)
  - **Other admins** (can only assign to projects they manage)
- Admins **CANNOT assign themselves** to new projects:
  - Admins don't see projects they don't manage
  - Impossible to assign yourself to unseen project
- Admins **CAN deassign themselves** from projects:
  - Sets pivot `active = false`
  - After deassignment, they no longer see that project
  - **CANNOT reassign themselves** (project now invisible to them)

---

### Unit

**Table**: `units`

**Fields**:

```sql
id (PK)
project_id (FK to projects, NOT NULL)
unit_type_id (FK to unit_types, NOT NULL)
family_id (FK to families, NULLABLE) -- NULL until lottery/sorteo assigns it
number (string, NOT NULL) -- e.g., "House 1", "Apt 2B"
identifier (string, NULLABLE) -- Additional identifier if needed
square_meters (decimal, NOT NULL) -- Dimensions
bedrooms (integer, NOT NULL)
bathrooms (integer, NOT NULL)
has_garden (boolean, default false)
has_balcony (boolean, default false)
-- TODO: More characteristics to be modeled
created_at, updated_at

UNIQUE(project_id, number) -- Unit numbers unique within project
```

**Relationships**:

- `belongsTo(Project)` - the project this unit is part of
- `belongsTo(UnitType)` - the type/category of this unit
- `belongsTo(Family)` - NULLABLE, assigned after lottery/sorteo

**Characteristics**:

- Each unit has physical dimensions (square meters)
- Each unit has features: bedrooms, bathrooms, garden, balcony, etc.
- **TODO**: More characteristics need to be modeled (parking, floor level, orientation, etc.)

**Lifecycle**:

1. **Creation**: Admin creates unit, assigns to UnitType, sets characteristics
2. **Pre-Assignment**: `family_id` is NULL, unit available for lottery
3. **Lottery/Sorteo**: Unit assigned to family via external API
4. **Post-Assignment**: `family_id` set, unit belongs to family

**Unit States** (field: `status`):

```sql
status (enum: 'planned', 'under_construction', 'completed', default 'planned')
```

- **Planned** (default): Initial state when unit is created
- **Under Construction**: Physical building work in progress
- **Completed**: Construction finished, ready for lottery assignment

**Unit Creation Constraint**:

- ✅ **Can create units while project is not completed**
- ❌ **BLOCKED if project status = 'completed'**
  - Cannot add new units to completed projects
  - **UI Behavior**:
    - "Create Unit" button **disabled** (not hidden)
    - Show tooltip/message: "Cannot create unit - project is completed"
    - Button remains visible so users understand the limitation
  - **Rationale**: Users should see the option exists but understand why it's unavailable

**State Transitions** (forward-only, no rollback):

```
Planned → Under Construction → Completed
```

**1. Planned → Under Construction**:

- **Constraint**: None (can transition immediately)
- **Triggered by**: Admin/superadmin manual action (individual unit basis)
- **Automation**: ✅ **Project auto-transitions to "under construction"** (if still "planned")
  - When **first unit** moves to "under construction", project follows
  - Subsequent unit transitions have no effect (project already "under construction")
- **Effect**: Marks this specific unit's construction as started
- **Rationale**: Tracks individual unit construction progress

**2. Under Construction → Completed**:

- **Constraint**: None (can transition immediately)
- **Triggered by**: Admin/superadmin manual action (individual unit basis)
- **Automation**: None (does NOT auto-complete project)
  - Project completion requires ALL units completed (manual check)
  - Unit completion is independent event
- **Effect**: Marks this specific unit as construction-finished
- **Rationale**: Units finish at different times, project waits for all

**Error Correction**:

- ❌ **No rollback via UI** (transitions are forward-only)
- If mistake made: **Superadmin corrects directly in database**
- Manual database UPDATE to revert `status` field (exceptional cases only)

**State Effects on Operations**:

- **Planned**: All operations allowed (modify characteristics, assign type, etc.)
- **Under Construction**: All operations allowed (changes during construction common)
- **Completed**: All operations allowed (may need corrections/updates)
- **Note**: State is informational, does NOT freeze modifications

**Business Rules**:

- Unit numbers must be unique within a project
- Units can only be assigned to families that are eligible (family's `unit_type_id` matches unit's `unit_type_id`)
- Once assigned via lottery, unit **CANNOT be manually reassigned** (no overrides, no admin reassignment)
- Unassigned units (family_id = NULL) are available for lottery

**Unit Deletion**:

✅ **Can delete units while project is not completed**:

- **Constraint**: ❌ **BLOCKED if project status = 'completed'**
  - Once project is completed, unit inventory is frozen
  - Cannot add or remove units after project completion
  - **UI Behavior**:
    - Delete action/button **disabled** (not hidden)
    - Show tooltip/message: "Cannot delete unit - project is completed"
    - Action remains visible so users understand the limitation
  - **Rationale**: Users should see the option but understand why it's unavailable

- **Allowed states**: Project status = 'planned' OR 'under_construction'
  - Admins/superadmins can delete units during planning/construction
  - Real-world construction changes are expected during these phases

**Deletion Side Effects**:

1. **Before any preferences exist**: Clean deletion (no side effects)
2. **After preferences submitted**:
   - ✅ **Automatically removes unit from ALL family preferences** (cascade delete)
   - ✅ **Sends email notification to affected families**
   - Audit log records deletion and affected families
3. **After lottery assignment**: ❌ **Should not delete** - would orphan assigned family
   - Extremely rare, requires superadmin intervention
   - Must unassign family first (manual process)
   - Only via lottery invalidation (clears all assignments)

**Unit Modification**:

✅ **Unit Metadata** (bedrooms, bathrooms, square_meters, etc.) - **Can modify at any time**:

- **No state restrictions** - freely modifiable at any project/unit state
- **Before preferences**: Freely modifiable by superadmins and admins
- **During preferences**: Allowed - triggers email notification to families who preferred this unit
  - Families can review changes and decide to keep/remove from preferences
- **After lottery**: Allowed (real-world construction changes happen)
  - Assignment based on snapshot at lottery execution time
  - Post-lottery changes don't affect fairness (already assigned)

✅ **Unit Type** (`unit_type_id`) - **Can modify only if unit is not completed**:

- **Constraint**: ❌ **BLOCKED if unit status = 'completed'**
  - Once unit is completed, its type is frozen
  - Cannot change type after unit construction finished
  - **UI Behavior**:
    - Type dropdown/selector **disabled** (not hidden)
    - Show tooltip/message: "Cannot change type - unit is completed"
    - Field remains visible so users understand the limitation
  - **Rationale**: Users should see the field but understand why it's read-only

- **Allowed states**: Unit status = 'planned' OR 'under_construction'
  - Admins/superadmins can change type during planning/construction
  - Common scenario: Unit specs change during construction

**Type Change Side Effects**:

1. **Before any preferences exist**: Clean change (no side effects)
2. **After preferences submitted**:
   - ✅ **Automatically removes unit from incompatible family preferences**
   - Families with different `unit_type_id` lose this preference (cascade delete)
   - ✅ **Sends email notification to affected families**
   - Families with matching type keep their preferences (no notification needed)
3. **After lottery**: Type changes should not occur (lottery already assigned based on type)

**Unit Addition**:

✅ **Can add units while project is not completed**:

- **Constraint**: ❌ **BLOCKED if project status = 'completed'**
  - Cannot add units after project completion
  - **UI Behavior**:
    - "Add Unit" button **disabled** (not hidden)
    - Show tooltip/message: "Cannot add unit - project is completed"
    - Button remains visible so users understand the limitation
  - **Rationale**: Users should see the option but understand why it's unavailable

- **Allowed states**: Project status = 'planned' OR 'under_construction'
  - Admins/superadmins can add units during planning/construction

**Addition Side Effects**:

- ✅ **Email notification sent to ALL families of matching type**
- Families can choose to add new unit to their preferences

**Rationale**: Real-world projects require flexibility during construction. State tracking enforces critical constraints (no changes to completed units/projects) while allowing necessary changes during planning/construction. Email notifications keep families informed and allow them to adjust preferences accordingly.

**Unit Reassignment (Lottery Results)**:

- Unit assignment is **decided exclusively by lottery algorithm**
- **NO manual reassignment allowed** (legal fairness requirements)
- **NO administrative overrides** permitted
- Only way to change results: **Lottery Invalidation Process**:
  1. **Superadmin invalidates lottery** (special ability, rare cases only)
  2. Invalidation clears all `unit.family_id` assignments
  3. Preferences "unfreeze" and can be edited again
  4. Admins can execute new lottery
  5. New results are final (unless invalidated again)
- Prevents lottery abuse and maintains legal fairness standards

**Additional Unit Characteristics** (TODO):

- Parking spaces (count or boolean)
- Floor level (integer, for apartments)
- Orientation (enum: north, south, east, west)
- Accessibility features (wheelchair accessible, etc.)
- Views/special features

---

### UnitType

**Table**: `unit_types`

**Fields**:

```sql
id (PK)
project_id (FK to projects, NOT NULL)
name (VARCHAR, NOT NULL) -- e.g., "Large Family Home", "Couple Apartment", "Single Unit"
description (TEXT, NULLABLE) -- Detailed description of this unit type
created_at, updated_at

UNIQUE(project_id, name) -- Type names unique within project
```

**Relationships**:

- `belongsTo(Project)` - the project this unit type belongs to
- `hasMany(Unit)` - all units of this type
- `hasMany(Family)` - families eligible for this unit type

**Purpose**:

- Categorizes units into types based on size/purpose (e.g., large family vs couple)
- Families are assigned to ONE unit type, making them eligible only for units of that type
- Enables fair distribution during lottery (families compete only within their category)

**Business Rules**:

- **MUST be defined before units can be created** - admins must set up unit type structure first
- Type names must be unique within a project
- **NO default/generic unit type** - each project must explicitly define types
- Families assigned to types by admins only

**UnitType Modification**:

✅ **Can modify unit types** (rename, change description):

- **Before units/families assigned**: Freely modifiable
- **After units/families assigned**: Allowed, with workflow considerations (see below)

**UnitType Deletion**:

⚠️ **Deletion should be approached carefully**:

- **Before preferences submitted**: Can delete freely
  - Reassign units/families to different types first, or cascade delete
- **During/after preferences**: May orphan preferences
  - Consider impact on families who selected units of this type
  - Clean up orphaned preferences (cascade delete)
- **After lottery**: Should prevent deletion if families assigned
  - Type is "frozen" by lottery results
  - Only allow via lottery invalidation process

**UnitType Coordination with State Transitions**:

**State Awareness**:

- Unit types can be modified regardless of project/unit states
- State transitions (planned → under construction → completed) are independent of type structure
- **However**: Consider workflow implications when modifying types

**Workflow Considerations**:

**1. Before Preferences Submitted**:

- ✅ Freely modify types (rename, change description, add/delete)
- Units and families can be reassigned to different types
- No fairness concerns (families haven't made choices yet)

**2. During/After Preferences Submitted**:

- ⚠️ **Type modifications may orphan preferences**
  - Deleting type → orphans families assigned to that type
  - Deleting type → orphans units of that type
  - Orphaned preferences should be cleaned up (cascade delete)
  - Consider notifying affected families (off-platform)
- Type renames/description changes are safe (don't break relationships)

**3. After Lottery Execution**:

- ✅ Types are "frozen" by lottery results (informally)
- Modifying types doesn't affect assignments (already made)
- Deleting type with assigned families/units should be **blocked** or **warned**
- Only superadmin lottery invalidation allows restructuring

**State Coordination**:

- Unit/project states (planned, under construction, completed) do NOT restrict type operations
- States are informational, help admins coordinate timing
- Type modifications allowed at any state (real-world flexibility needed)

**Family Unit Type Changes**:

- **Before lottery**: ✅ **Admins can change** family's assigned unit type
  - Common scenarios:
    - Original assignment was a mistake
    - Family composition changed (members left, now smaller family)
- **After lottery**: ❌ **Cannot change** - results are final
  - Changing type after lottery would invalidate fairness
  - Only option: lottery invalidation by superadmin (clears all assignments)

**UnitType Coordination with State Transitions**:

**State Awareness**:

- Unit types can be modified regardless of project/unit states
- State transitions (planned → under construction → completed) are independent of type structure
- **However**: Consider workflow implications when modifying types

**Workflow Considerations**:

**1. Before Preferences Submitted**:

- ✅ Freely modify types (rename, change description, add/delete)
- Units and families can be reassigned to different types
- No fairness concerns (families haven't made choices yet)

**2. During/After Preferences Submitted**:

- ⚠️ **Type modifications may orphan preferences**
  - Deleting type → orphans families assigned to that type
  - Deleting type → orphans units of that type
  - Orphaned preferences should be cleaned up (cascade delete)
  - Consider notifying affected families (off-platform)
- Type renames/description changes are safe (don't break relationships)

**3. After Lottery Execution**:

- ✅ Types are "frozen" by lottery results (informally)
- Modifying types doesn't affect assignments (already made)
- Deleting type with assigned families/units should be **blocked** or **warned**
- Only superadmin lottery invalidation allows restructuring

**State Coordination**:

- Unit/project states (planned, under construction, completed) do NOT restrict type operations
- States are informational, help admins coordinate timing
- Type modifications allowed at any state (real-world flexibility needed)

---

### Blueprint

**Table**: `blueprints`

**Fields**:

```sql
id (PK)
project_id (FK to projects, NOT NULL, UNIQUE) -- One blueprint per project
name (VARCHAR, NULLABLE) -- Optional name/title
description (TEXT, NULLABLE) -- Description of the layout
svg_data (TEXT, NULLABLE) -- SVG representation of the map
json_data (JSON, NULLABLE) -- Structured data for D3/diagram rendering
file_path (VARCHAR, NULLABLE) -- Path to uploaded blueprint file
created_at, updated_at
```

**Relationships**:

- `belongsTo(Project)` - One-to-One (optional), the project this blueprint represents

**Purpose**:

- Visual representation of the project's physical layout
- Shows location of all units on a map/diagram
- Helps families visualize their preferences during lottery selection
- Can be rendered as SVG or D3 diagram in the UI

**Business Rules**:

- **Optional** - Projects can exist without blueprints
- **One-to-One** - Each project has at most one blueprint
- Blueprints can store data in multiple formats:
  - SVG data (inline)
  - JSON data (for D3/custom rendering)
  - File path (uploaded image/PDF)

**Blueprint Format & Management**:

**1. Supported Formats**:

- ✅ **Support multiple formats** (flexible approach):
  - **Image files** (PNG/JPG) - Simple uploaded images
  - **PDF** - Detailed architectural plans
  - **SVG** (inline) - Editable vector graphics (future enhancement)
  - **JSON** (structured) - For D3/custom rendering (future enhancement)
- **Current MVP scope**: Focus on **file uploads** (images/PDFs)
- Store file path in `file_path` column
- Future: Populate `svg_data` and `json_data` for interactive blueprints

**2. Editing Blueprints**:

- ✅ **Blueprints CAN be edited** after creation
- Admins can **replace the uploaded file**
- ❌ **Version history NOT in MVP scope** (future enhancement)
- Current approach: Overwrite existing file (simple update)

**3. Authorization**:

- ✅ **Project admins** can create/modify blueprints for their projects
- ✅ **Superadmins** can create/modify blueprints for any project
- ❌ **Members** cannot create/modify blueprints (read-only access)

**4. Unit Positioning**:

- ⏳ **Interactive blueprint is FUTURE ENHANCEMENT** (not MVP)
- Current scope: Blueprint is **display-only** (visual reference)
- Future scope:
  - Store unit coordinates in `units` table (x, y positions)
  - Link unit IDs to blueprint positions
  - Interactive blueprint UI (click unit to see details, status, assigned family)
  - Visual lottery results (color-coded units)

**5. MVP Scope**:

- ✅ **Blueprints ARE needed for MVP** (basic implementation):
  - Upload blueprint file (image/PDF)
  - Display blueprint on project page
  - Simple CRUD operations (create, read, update, delete)
- Future enhancements:
  - Interactive positioning
  - SVG/JSON formats
  - Version history

---

### FamilyPreference

**Table**: `family_preferences`

**Fields**:

```sql
id (PK)
family_id (FK to families, NOT NULL)
unit_id (FK to units, NOT NULL)
rank (INTEGER, NOT NULL) -- 1 = first choice, 2 = second, etc.
created_at, updated_at

UNIQUE(family_id, unit_id) -- Can't prefer same unit twice
UNIQUE(family_id, rank) -- Each rank used once per family
```

**Relationships**:

- `belongsTo(Family)` - the family making the preference
- `belongsTo(Unit)` - the preferred unit

**Purpose**:

- Stores family preferences for units during lottery process
- Each family ranks their preferred units in order (1st choice, 2nd choice, etc.)
- Used by lottery API to determine optimal assignments

**Business Rules**:

- Each family can have a limited number of preferences (e.g., max 4-5)
- Families can only prefer units of their assigned UnitType
- Preferences must be for unassigned units (family_id = NULL)
- Rank must be unique per family (can't have two #1 choices)
- Unit must be unique per family (can't prefer same unit twice)

**Lifecycle**:

1. **Creation**: Family or admin creates preferences
2. **Modification**: Can change until lottery execution
3. **Lottery**: API reads preferences to compute assignments
4. **Post-Lottery**: Preferences **frozen forever** as auditing artifact

**Preference Modification Rules**:

✅ **Before Lottery Execution**:

- Preferences can be freely created, modified, deleted
- **Any family member** can update preferences (all activity logged for audit)
- **Admins** can also update on behalf of families
- **No restrictions based on project/unit states**:
  - Members can pick preferences even if project is "planned"
  - Members can pick preferences while units are "under construction"
  - Members can pick preferences at any time until lottery executes
- **Rationale**: Families should be able to think ahead and plan, regardless of construction progress

❌ **After Lottery Execution**:

- Preferences **frozen** - cannot be modified
- Remain in database as historical/audit record
- Show which units families preferred vs which they received

✅ **After Lottery Invalidation** (rare, superadmin-only):

- If superadmin invalidates lottery results
- Preferences **unfreeze** and become editable again
- Families can update preferences before new lottery execution
- New lottery execution freezes them again

**Automatic Preference Updates**:

**When Unit Changes Affect Preferences**:

1. **Unit Deleted**:
   - ✅ **Automatically remove from ALL family preferences**
   - Cascade delete: Delete all `family_preferences` records pointing to deleted unit
   - ✅ **Email notification sent to affected families**:
     - "Unit X has been removed from the project"
     - "We've removed it from your preferences - please review and update"
   - Families should review remaining preferences and potentially add new ones

2. **Unit Type Changed**:
   - ✅ **Automatically remove from incompatible family preferences**
   - If unit's `unit_type_id` changes, families with different `unit_type_id` can no longer prefer it
   - Delete preferences where `family.unit_type_id ≠ unit.unit_type_id`
   - ✅ **Email notification sent to affected families**:
     - "Unit X changed from Type A to Type B"
     - "We've removed it from your preferences (incompatible type) - please review"

3. **Unit Added**:
   - ⚠️ **No automatic preference creation** (families must opt-in)
   - ✅ **Email notification sent to ALL families** (of matching type):
     - "New Unit X added to the project (Type A)"
     - "You may want to review your preferences and consider this new option"
   - Families can choose to add it to their preferences or ignore it

4. **Unit Metadata Changed** (bedrooms, bathrooms, etc.):
   - ❌ **No automatic preference removal** (unit is still same type)
   - ✅ **Email notification sent to families who preferred this unit**:
     - "Unit X characteristics updated: [changes]"
     - "Please review your preferences to ensure they still match your needs"
   - Families decide whether to keep or remove from preferences

**Lottery Execution Constraint**:

⚠️ **Lottery is BLOCKED until ALL families submit preferences**:

- Lottery cannot be executed if any family has no preferences
- This is an external deadline/forcing function
- If family doesn't submit, admins/other members contact them in real life
- Not something app enforces beyond blocking lottery button
- Ensures fairness - all families must participate

**Rationale**:

- Legal fairness requirements demand complete preference records
- Frozen preferences prove what each family wanted
- Audit trail prevents disputes about lottery fairness
- Lottery invalidation is only escape hatch (superadmin oversight)

---

## State Management

### Session State - Current Project

**Implementation**: `state('project')` (NOT standard session)

**Automatic Project Selection on Login**:

1. **Members** (always have exactly one project):
   - Current project **automatically set to family's project**
   - No manual switching possible
   - Universe = single project only

2. **Admins with single managed project**:
   - Current project **automatically set to that project**
   - Landing page: Dashboard (single-project context)
   - Universe = that single project

3. **Admins with multiple managed projects**:
   - Start in **multi-project context** (no current project selected)
   - Landing page: **Projects index** (not Dashboard)
   - Nav items visible: **Members, Projects** (Dashboard, Gallery hidden - require single-project context)
   - Must select a project to enter single-project context

4. **Superadmins**:
   - Same behavior as admins with multiple projects
   - Start in multi-project context
   - Can select any project (not limited to managed projects)

**Multi-Project Context** (admins/superadmins only):

- **Projects index**: List of all managed projects (or all projects for superadmins)
- **Members index**: Shows ALL members across ALL accessible projects
  - Grouped by Family (UI toggle available)
  - Or standalone Members list (UI toggle available)
- **Families visible**: ALL families across ALL accessible projects
- No current project set (`state('project')` is null)

**Single-Project Context** (all users):

- **Current project selected**: `state('project')` has value
- **All indexes filtered** by current project:
  - `families.index`: Only families in current project
  - `members.index`: Only members in current project
  - `units.index`: Only units in current project
  - etc.
- **Dashboard, Gallery** available (require single-project context)

**Universe Boundaries** (NEVER exceeded):

- **Members**: Can ONLY see their single active project (automatically set)
- **Admins**: Can ONLY see their managed projects (multi-project or single-project context)
- **Superadmins**: Can see ALL projects

**Project Switching**:

- **Members**: ❌ Cannot switch (automatic, based on family)
- **Admins**: ✅ Can switch between managed projects (via project selection UI)
- **Superadmins**: ✅ Can switch to any project

---

### User State

**Email Verification** (`email_verified_at`):

- Users created via invitation have NULL initially
- Must verify email before full access
- **TODO**: Define restrictions for unverified users
  - Can they log in?
  - What resources can they access?
  - Blocked actions?

**User Active State**:

- Uses **soft delete** (`deleted_at`) not explicit active flag
- Soft-deleted users marked as "deleted" but preserved for audit
- See User entity section for soft-delete behavior details

---

### Project State

See Project entity section for:

- `active` field (true = ongoing, false = completed/archived)
- `status` field (planned, under_construction, completed)
- Soft delete for canceled/mistake projects

---

### Member-Project State

**Pivot Active** (`project_user.active`):

- Exactly ONE project should be active per member (business rule enforced)
- Others are historical (from family moves or member family switches)

**States**:

1. **Active Member**: Has `active = true` for exactly one project
2. **Inactive Membership**: Has `active = false` (historical - from family project switch or member family switch)
3. **No Membership**: Not in `project_user` at all (invalid state - should never occur)

**Family Project Switch Process**:

1. **Old project**: Set all family members to `active = false` (historical)
2. **New project**: Create new pivot entries with `active = true` for all family members
3. **Never update existing pivot** - always create new entries
4. Preserves complete history of family migrations

**Member Creation**:

- Member is **created WITH project assignment** (via family)
- No "creation without project" state
- Invalid if member has no active project
- Member always has exactly one `active = true` entry from creation

**Family Deletion** (cascades to members):

- If family soft-deleted, all members soft-deleted
- Pivot entries remain unchanged (audit trail)
- Members still have `active = true` for their project (historical record)

**Pivot Table Immutability**:

⚠️ **CRITICAL RULE**: Once entry created in `project_user`, **ONLY the `active` flag can be modified**

- Never update `user_id`, `project_id`, `created_at`
- Never delete entries (preserve history)
- Only toggle `active` flag when family switches or admin leaves project

---

## Authorization Matrix

### General Principles

1. **Superadmins**: Bypass ALL policies via `Gate::before()` - always allowed
2. **Project Scope**: Admins can only access resources in projects they manage
3. **Family Scope**: Members can only access their own family's resources
4. **Self-Management**: Users can view/update themselves (with restrictions)

---

### Project Operations

| Action                           | Superadmin | Admin (manages project)    | Admin (doesn't manage) | Member                           |
| -------------------------------- | ---------- | -------------------------- | ---------------------- | -------------------------------- |
| **viewAny** (list all projects)  | ✅ Always  | ✅ If manages 2+ projects  | ❌ 403                 | ❌ 403                           |
| **view** (view specific project) | ✅ Always  | ✅ If manages this project | ❌ 403                 | ✅ Can view their active project |
| **create**                       | ✅ Always  | ❌ 403                     | ❌ 403                 | ❌ 403                           |
| **update**                       | ✅ Always  | ✅ If manages this project | ❌ 403                 | ❌ 403                           |
| **delete**                       | ✅ Always  | ✅ If manages this project | ❌ 403                 | ❌ 403                           |

**Notes**:

- Members can view their own project details but NOT the project list
- Admins with only 1 project should not see project selector/list
- **QUESTION**: Can admins with 1 project still access the index route, or is it hidden in UI only?

---

### Family Operations

| Action      | Superadmin | Admin (in family's project)          | Admin (other project)                | Member (own family) | Member (other family) |
| ----------- | ---------- | ------------------------------------ | ------------------------------------ | ------------------- | --------------------- |
| **viewAny** | ✅ Always  | ✅ Sees families in managed projects | ✅ Sees families in managed projects | ✅ Can view list    | ✅ Can view list      |
| **view**    | ✅ Always  | ✅ Any family                        | ✅ Any family                        | ✅ Any family       | ✅ Any family         |
| **create**  | ✅ Always  | ✅ In managed project only           | ❌ 403 Cannot create in unmanaged    | ❌ 403              | ❌ 403                |
| **update**  | ✅ Always  | ✅ In managed project only           | ❌ 403                               | ✅ Own family only  | ❌ 403                |
| **delete**  | ✅ Always  | ✅ In managed project only           | ❌ 403                               | ❌ 403              | ❌ 403                |

**Notes**:

- Everyone can VIEW families (viewAny, view)
- Only admins can CREATE families (in projects they manage)
- Members can UPDATE their own family info (name, avatar)
- Only admins can DELETE families

**Family Listing Behavior** (clarified):

**Multi-Project Context** (admins/superadmins with no current project selected):

- `families.index`: Shows **ALL families** across **ALL accessible projects**
  - Admins: families in ALL managed projects
  - Superadmins: families in ALL projects
- No current project filter applied

**Single-Project Context** (all users with current project selected):

- `families.index`: Shows **ONLY families in current project**
  - Members: ONLY their project's families (automatically filtered)
  - Admins: ONLY current project's families (manually selected)
  - Superadmins: ONLY current project's families (manually selected)

**Universe Boundaries** (NEVER exceeded):

- Members: Can ONLY see families in their single active project
- Admins: Can ONLY see families in their managed projects
- Superadmins: Can see families in ALL projects

---

### Member Operations

| Action      | Superadmin  | Admin (member's project)   | Admin (other project)  | Member (self)              | Member (same family)       | Member (other)                      |
| ----------- | ----------- | -------------------------- | ---------------------- | -------------------------- | -------------------------- | ----------------------------------- |
| **viewAny** | ✅ Always   | ✅ In managed projects     | ✅ In managed projects | ✅ List                    | ✅ List                    | ✅ List                             |
| **view**    | ✅ Always   | ✅ Any                     | ✅ Any                 | ✅ Any                     | ✅ Any                     | ✅ Any                              |
| **create**  | ✅ Anywhere | ✅ In managed project only | ❌ 403                 | ✅ Can invite (restricted) | ✅ Can invite (restricted) | ❌ 403 Cannot invite outside family |
| **update**  | ✅ Always   | ✅ In managed project only | ❌ 403                 | ✅ Self only               | ❌ 403                     | ❌ 403                              |
| **delete**  | ✅ Always   | ✅ In managed project only | ❌ 403                 | ✅ Self only               | ❌ 403                     | ❌ 403                              |

**Special Member Creation Rules**:

**Admin creating member**:

- Can create in any project they manage
- Can assign to any family in that project
- Can set any project_id they manage
- Must ensure family.project_id matches target project_id

**Member creating member ("invitation")**:

- Can only create members in their own family
- `family_id` is FORCED to member's own family_id
- `project_id` is FORCED to member's active project
- Cannot choose family or project (auto-filled, hidden in form)
- **QUESTION**: Any other restrictions?
  - Email domain restrictions?
  - Approval process?

**Member Intra-Family Operations**:

1. **Can members update other members in their family?** ❌ **No**
   - Members can only update themselves
   - If member needs another family member updated, contact admin off-platform (in real life)
   - Admins can update any member in projects they manage

2. **Can members delete other members in their family?** ❌ **No**
   - Members can only delete themselves
   - If member needs another family member removed:
     - Contact admin off-platform (in real life) to request deletion
     - Admin performs deletion (auditable action)
     - If admin abuses this power, it's auditable - not app's concern to prevent

3. **Member creating another member (family invitation)**:
   - ✅ **Invitation process** (same as admin inviting member)
   - Creates invitation record with unique token
   - Sends invitation email: "Member X invited you to join their Family Y in Project Z in MTAV"
   - Email is different from admin invitation, but process is identical
   - Invitee sets password and accepts invitation
   - User created with email_verified_at set (auto-verified)

4. **Can members delete themselves?** ✅ **Yes** (soft-delete)
   - Member can soft-delete themselves at any time
   - **If they're the last member**:
     - Family remains in database (not deleted)
     - Family is now **empty** (zero members)
     - This is VALID - family is a "wrapper" that persists
     - Admins can still invite new members into empty family
     - Empty families filtered from member-facing listings
     - Empty families visible to admins (with indicator/warning)

---

### Admin Operations

| Action      | Superadmin               | Admin (self)              | Admin (other, same project) | Admin (other, different project) | Member  |
| ----------- | ------------------------ | ------------------------- | --------------------------- | -------------------------------- | ------- |
| **viewAny** | ✅ Always                | ✅ List                   | ✅ List                     | ✅ List                          | ✅ List |
| **view**    | ✅ Always                | ✅ Any                    | ✅ Any                      | ✅ Any                           | ✅ Any  |
| **create**  | ✅ Anywhere              | **QUESTION**              | **QUESTION**                | **QUESTION**                     | ❌ 403  |
| **update**  | ✅ Always                | ✅ Self only              | ❌ 403                      | ❌ 403                           | ❌ 403  |
| **delete**  | ✅ Always (except self?) | ❌ 403 Cannot delete self | ❌ 403                      | ❌ 403                           | ❌ 403  |

**Open Questions on Admin Creation**:

1. Can regular admins create other admins?
2. If yes, can they only assign to projects they manage?
3. Can admins modify their own project assignments?
4. Can admins assign other admins to projects?

**Notes**:

- Admins can only update themselves (name, email, etc.)
- Admins CANNOT update other admins
- Admins CANNOT delete themselves
- Admins CANNOT delete other admins
- Only superadmins can delete admins
- Superadmins can delete others but maybe not themselves?

---

### Unit Operations

| Action      | Superadmin  | Admin (unit's project)     | Admin (other project)  | Member               |
| ----------- | ----------- | -------------------------- | ---------------------- | -------------------- |
| **viewAny** | ✅ Always   | ✅ In managed projects     | ✅ In managed projects | ✅ In active project |
| **view**    | ✅ Always   | ✅ Any                     | ✅ Any                 | ✅ Any               |
| **create**  | ✅ Anywhere | ✅ In managed project only | ❌ 403                 | ❌ 403               |
| **update**  | ✅ Always   | ✅ In managed project only | ❌ 403                 | ❌ 403               |
| **delete**  | ✅ Always   | ✅ In managed project only | ❌ 403                 | ❌ 403               |

**Notes**:

- Everyone can VIEW units
- Only admins can CREATE/UPDATE/DELETE units
- Units are scoped to projects (admins can only manage units in their projects)

---

## Business Rules by Entity

### User/Member/Admin Creation

**Admin Creation**:

```
Input: firstname, lastname, email, project (singular, required)
Process:
  1. Validate email unique
  2. Validate project exists
  3. [QUESTION] Validate admin manages project? (if not superadmin)
  4. Create user with is_admin = true, family_id = null
  5. [MISSING] Attach to project via project_user pivot
  6. [MISSING] Set active = ? in pivot
  7. [QUESTION] Send invitation email?
```

**Current Implementation Status**:

- ❌ Step 5-6 missing in controller
- ❌ Validation for project management missing
- ⚠️ Request validates single `project` but admins can have multiple projects

**Member Creation by Admin**:

```
Input: firstname, lastname, email, family_id, project_id
Process:
  1. Validate email unique
  2. Validate family exists
  3. Validate project exists
  4. [CRITICAL] Validate family.project_id == project_id (prevent mismatch)
  5. [QUESTION] Validate admin manages project?
  6. Create user with is_admin = false, family_id
  7. Add to project via project_user with active = true
  8. [QUESTION] Send invitation email?
```

**Current Implementation Status**:

- ❌ Step 4 NOT enforced (critical bug)
- ❌ Step 5 NOT enforced (authorization bug)

**Member Creation by Member ("Invitation")**:

```
Input from form: firstname, lastname, email (family & project hidden/disabled)
Process:
  1. Validate email unique
  2. OVERRIDE family_id = auth()->user()->family_id
  3. OVERRIDE project_id = auth()->user()->project->id
  4. Create user with is_admin = false, family_id
  5. Add to project via project_user with active = true
  6. [QUESTION] Send invitation email?
```

**Current Implementation Status**:

- ❌ Override logic NOT implemented
- ❌ Family/project fields shown in form (should be hidden for members)

---

### Family Creation

```
Input: name, project_id
Process:
  1. Validate name required
  2. Validate project exists
  3. [QUESTION] Validate admin manages project? (if not superadmin)
  4. Validate name unique within project (DB constraint)
  5. Create family with project_id
```

**Current Implementation Status**:

- ✅ Basic validation works
- ❌ Project management validation missing for admins

---

### Family Updates

```
Input: name, project_id (optional - for family project switch)
Process:
  1. Authorize user can update family
  2. Validate name unique within project
  3. If project_id changed:
     a. Validate admin manages BOTH source and target projects (or is superadmin)
     b. Set family.project_id to new project
     c. For ALL family members (batch operation):
        - Set old project pivot to active = false
        - Create new project pivot with active = true
     d. Atomic operation - all members move together (family atomicity)
  4. Update family name/other fields
```

**Family Project Switch**:

- ✅ **Can change `family.project_id` via edit form** (admin operation)
- ✅ **All members automatically moved** (batch cascade)
- Family atomicity enforced - impossible to move family without members
- Admin must manage both projects (source and target) unless superadmin
- Creates historical pivot entries (`active = false`) for old project
- Creates new active pivot entries for new project

**Alternative Methods**:

- `family.join(Project)` method
- `family.moveToProject(Project)` method
- Both achieve same result as updating `project_id` in edit form

---

### Family Deletion

```
Process:
  1. Authorize user can delete family (admin in managed project, or superadmin)
  2. Soft-delete family (sets deleted_at timestamp)
  3. CASCADE soft-delete to ALL family members (batch operation)
     - All members get deleted_at set simultaneously
     - Family atomicity preserved even in deletion
  4. Pivot entries remain unchanged (audit trail)
     - Members still have active = true for their project
     - Historical record preserved
```

**Family Deletion Behavior**:

- ✅ **Soft-delete cascades to ALL members** (no orphans allowed)
- ✅ **Does NOT prevent deletion if has members** (cascade handles it)
- ❌ **NO orphan members** - members must always have family
- Preserves entire family unit in database for historical reference
- Pivot relationships untouched - complete audit trail maintained

**Rationale**:

- Family is atomic unit - cannot partially delete
- If family goes, all members go (soft-delete preserves records)
- Audit trail shows: family existed, had these members, in this project
- No inconsistent state possible (orphaned members)

---

## Family Atomicity Rules**Core Principle**: Families are atomic units. All members of a family must be in the same project as the family.

### Rules

1. **Family MUST belong to exactly one project**
   - `families.project_id` is NOT NULL (DB constraint)
   - Cannot create family without project_id
   - **IMPLEMENTED**: ✅ Database constraint

2. **All family members SHOULD be in family's project**
   - When member.family_id = X, member should have active membership in families[X].project
   - **NOT ENFORCED**: ❌ No validation currently

3. **Creating member with family from different project is INVALID**
   - If creating member with family_id = F and project_id = P
   - Must verify families[F].project_id == P
   - **NOT ENFORCED**: ❌ Missing validation (critical)

4. **Updating member's family checks project**
   - If changing member.family_id from F1 to F2
   - Must verify families[F2].project_id == member's active project
   - **NOT ENFORCED**: ❌ Missing validation

5. **Individual members cannot switch projects independently**
   - Member can only switch if whole family switches
   - `member.switchProject()` should validate or be restricted
   - **NOT ENFORCED**: ❌ Model allows it, should be controller restriction

6. **Family moving projects moves all members atomically**
   - `family.join(project)` or `family.moveToProject(project)` should:
     - Set family.project_id = project.id
     - For each member: Set old project_user.active = false
     - For each member: Create/update project_user with new project, active = true
   - **PARTIALLY IMPLEMENTED**: ⚠️ `join()` exists but may not handle inactive correctly

7. **Members leaving project should be prevented if breaks atomicity**
   - If member tries to leave their family's project, deny
   - Member must leave family first, or whole family must leave
   - **NOT ENFORCED**: ❌ No validation

### Implementation Checklist

**Database Level**:

- ✅ `families.project_id` NOT NULL constraint
- ❌ No constraint linking `users.family_id` → `families.project_id` → `project_user.project_id`
  - This would require triggers or computed columns

**Application Level - REQUIRED**:

- ❌ Validation in `CreateMemberRequest`: Check `family.project_id == request.project_id`
- ❌ Validation in `UpdateMemberRequest`: Check new family's project matches member's project
- ❌ Controller restriction: Members cannot call `switchProject()` directly
- ❌ Observer/Event: When family.project_id changes, update all members

**Application Level - NICE TO HAVE**:

- ❌ Observer: When member.family_id changes, verify projects match
- ❌ Scheduled job: Check for atomicity violations and report/fix
- ❌ Admin interface: Show warnings if atomicity broken

---

## Project Scope & Context

### Current Project Selection

**Purpose**: Determines what data is shown in indexes/lists

**For Members**:

- Current project is ALWAYS their family's project
- Cannot switch to different project
- Automatically set on login
- **QUESTION**: What if member's family has no project? (orphaned)

**For Admins**:

- Can switch between projects they manage
- UI shows project selector if manages 2+ projects
- Default to... first project? Last used?
- **QUESTION**: What if admin manages 0 projects?

**For Superadmins**:

- Can switch to any project
- UI shows all projects in selector
- Default to...?

### Data Filtering by Current Project

**When listing resources**:

**Families**:

- Admins see families in current project (filtered by current project)
- Members see families in their (only) project
- **QUESTION**: Or do admins see families in ALL managed projects?

**Members**:

- Admins see members in current project
- Members see members in their project
- **QUESTION**: Filtered by current project or by active membership?

**Units**:

- Filtered by current project

**Admins**:

- Admins see... all admins? Or filtered by current project?
- **GUESS**: All admins, not filtered by project

**Projects**:

- Admins see projects they manage (or all if superadmin)
- Not filtered by "current" since you're viewing the project list

### Index Filtering Logic

**Family Index**:

```php
// Admin
$pool = Project::current()?->families() ?? Family::query();

// Member
$pool = Project::current()->families(); // Always has current project
```

**Member Index**:

```php
// Admin
$pool = Project::current()?->members() ?? Member::query();

// Member
$pool = Project::current()->members();
```

**Null Current Project Handling**:

1. **If `Project::current()` is null**:

   **For Admins/Superadmins**:
   - This defines **Multi-Project Context** (normal/expected state)
   - Default context for admins managing multiple projects
   - Land on Projects index page
   - Stay in multi-project context until they select a project
   - Can view ALL resources across ALL managed projects:
     - `families.index`: ALL families from ALL managed projects
     - `members.index`: ALL members from ALL managed projects
     - Form selects show ALL resources from ALL managed projects
     - Can still invite members, create families, etc. (see all available options)

   **For Members**:
   - ❌ **INVALID STATE - BUG**
   - Member does NOT have an active project (should never happen)
   - **Do NOT allow member to proceed into app**
   - Show error message: "You do not have an active project. Please contact an administrator."
   - Error is generic - app doesn't decide who to contact (that's real-world decision)

2. **Multi-Project Context Resource Viewing**:
   - ✅ Admins can view indexes WITHOUT current project selected
   - Multi-project context shows aggregated view:
     - Projects index: list of managed projects
     - Members index: all members from all managed projects
     - Families index: all families from all managed projects
   - All form dropdowns/selects show resources from ALL managed projects
   - Actions (create, update, delete) work across all managed projects
   - Filtering happens at authorization level (managed projects only), not current project level

---

## Invitation & User Management

### User Registration

**No Open Registration**:

- Users cannot self-register
- All users created via invitation
- **NOT IMPLEMENTED**: ❌ Registration routes may still exist

### Invitation Flow (Planned)

1. **Inviter** (admin or member) creates invitation
   - Specifies email, name, role (admin/member)
   - For members: family and project (auto-filled if inviter is member)
   - For admins: project(s) to assign

2. **System** creates invitation record
   - Generates unique token
   - Sets expiration (e.g., 7 days)
   - Stores inviter_id, invitee_email, role, etc.

3. **System** sends invitation email
   - Link with token: `/invitation/{token}`
   - Invitee clicks link

4. **Invitee** sets password
   - Views invitation details (read-only: name, role, project/family)
   - Sets password
   - Accepts invitation

5. **System** creates user account
   - Sets email_verified_at = now
   - Sets password
   - Creates project/family associations
   - Marks invitation as accepted

6. **Invitee** can now log in

**NOT IMPLEMENTED**: ❌ Entire invitation system is TODO

### Email Verification

**Current State**:

- Laravel Breeze includes email verification
- New users have `email_verified_at = null`
- **QUESTION**: Are unverified users allowed to log in?
- **QUESTION**: What actions are restricted for unverified users?

**With Invitation System**:

- Invited users are auto-verified when they set password
- No separate email verification step needed
- **QUESTION**: Can admins create users without invitation (for testing)?

---

## Unit Types & Distribution System

### Overview

The unit distribution system manages how housing units are allocated to families in a project. This is a multi-phase process:

1. **Project Setup**: Admins define unit types
2. **Unit Creation**: Admins create units and assign to types
3. **Family Assignment**: Admins assign families to unit types (eligibility)
4. **Preference Collection**: Families select their preferred units
5. **Lottery/Sorteo**: External API distributes units based on preferences
6. **Assignment**: Families receive their allocated units

---

### Phase 1: Unit Type Structure Definition

**Requirement**: **MUST** be completed before units can be created

**Process**:

1. Admin creates UnitType records for the project
2. Defines categories like:
   - "Large Family Home" (4+ bedrooms)
   - "Medium Family Home" (2-3 bedrooms)
   - "Couple Apartment" (1-2 bedrooms)
   - "Single Unit" (studio/1 bedroom)
3. Provides descriptions for each type

**Business Rules**:

- Admins managing the project can create unit types
- Unit type names must be unique within the project
- **QUESTION**: Minimum number of unit types required? Or can project have just one type?
- **QUESTION**: Can unit types be created after units already exist? Or must be defined first?

**Authorization**:

| Action      | Superadmin  | Admin (manages project)    | Admin (other project)  | Member               |
| ----------- | ----------- | -------------------------- | ---------------------- | -------------------- |
| **viewAny** | ✅ Always   | ✅ In managed projects     | ✅ In managed projects | ✅ In active project |
| **view**    | ✅ Always   | ✅ Any                     | ✅ Any                 | ✅ Any               |
| **create**  | ✅ Anywhere | ✅ In managed project only | ❌ 403                 | ❌ 403               |
| **update**  | ✅ Always   | ✅ In managed project only | ❌ 403                 | ❌ 403               |
| **delete**  | ✅ Always   | ✅ In managed project only | ❌ 403                 | ❌ 403               |

---

### Phase 2: Unit Creation & Categorization

**Process**:

1. Admin creates Unit records
2. Assigns each unit to a UnitType
3. Sets unit characteristics:
   - Number/identifier
   - Square meters
   - Bedrooms
   - Bathrooms
   - Garden (yes/no)
   - Balcony (yes/no)
   - **TODO**: Additional characteristics
4. Unit created with `family_id = NULL` (unassigned)

**Business Rules**:

- Unit MUST have a valid unit_type_id
- Unit MUST belong to a project
- Unit characteristics MUST be specified (square meters, bedrooms, bathrooms)
- Unit number MUST be unique within project
- Unit starts unassigned (family_id = NULL)

**Validation**:

- Validate unit_type_id exists and belongs to same project
- Validate unit number not duplicate in project
- Validate numeric fields (square_meters > 0, bedrooms >= 0, bathrooms >= 0)

**Authorization**: Same as UnitType (admins can manage units in their projects)

---

### Phase 3: Family-to-UnitType Assignment

**Purpose**: Determine which families are eligible for which units

**Database Addition**:

```sql
ADD COLUMN unit_type_id (FK to unit_types, NULLABLE?) to families table
```

**Process**:

1. Admin assigns each family to a UnitType
2. Family becomes eligible ONLY for units of that type
3. Assignment typically based on family size/needs

**Business Rules**:

- Each family is assigned to **exactly one** UnitType
- Family can only receive units matching their assigned UnitType
- **QUESTION**: Can families be created without UnitType assignment?
  - During initial project setup?
  - Or required from creation?
- **QUESTION**: Can families change their assigned UnitType?
  - Before lottery?
  - After lottery?
  - Never?

**Validation**:

- When assigning family to UnitType:
  - Validate UnitType belongs to family's project
  - Ensure family.project_id == unit_type.project_id

**Edge Cases**:

- **More families than units of a type**: Some families won't get units
  - **QUESTION**: How to handle? Waitlist? Reassign to different type?
- **More units than families of a type**: Some units remain unassigned
  - Units stay empty or assigned to other families?
- **No units of a family's type**: Family has no options
  - **QUESTION**: Block family assignment to type with no units?

---

### Phase 4: Family Preference Collection

**Purpose**: Families select which units they prefer

**New Entity**: `FamilyPreference` (or pivot with ordering)

**Table**: `family_preferences`

```sql
id (PK)
family_id (FK to families, NOT NULL)
unit_id (FK to units, NOT NULL)
rank (integer, NOT NULL) -- 1 = first choice, 2 = second, etc.
created_at, updated_at

UNIQUE(family_id, unit_id) -- Can't prefer same unit twice
UNIQUE(family_id, rank) -- Each rank used once per family
```

**Process**:

1. System determines maximum number of preferences (configurable)
   - Example: Each family can pick 4 units
   - **QUESTION**: Configurable per project? Or system-wide?
2. Family views available units of their type
   - Filtered by: `units.unit_type_id = family.unit_type_id AND units.family_id IS NULL`
3. Family selects preferred units in order:
   - "I want Unit 2, or Unit 3, or Unit 4, or Unit 8"
   - Ranks: 1, 2, 3, 4
4. Preferences saved to database

**Business Rules**:

- Families can only prefer units of their assigned UnitType
- Number of preferences limited (e.g., max 4-5 choices)
- Families can change preferences until lottery deadline
- **QUESTION**: Are preferences required? Or can family not submit any?
  - If no preferences, random assignment?
  - Or family not eligible for lottery?

**Validation**:

- Validate unit.unit_type_id == family.unit_type_id
- Validate unit is unassigned (family_id = NULL)
- Validate rank is within allowed range (1 to max_preferences)
- Validate no duplicate ranks
- Validate no duplicate units

**Authorization**:

- **Create/Update Preferences**:
  - Admins managing the project: ✅ Can set any family's preferences
  - Members of the family: ✅ Can set their own family's preferences
  - Other members: ❌ Cannot set other families' preferences
- **View Preferences**:
  - Admins: ✅ Can view all preferences
  - Members: ❌ Can only view their own family's preferences (to prevent gaming)
  - **QUESTION**: Or can everyone see everyone's preferences?

---

### Phase 5: Lottery Configuration

**Before running lottery**:

1. All families assigned to UnitTypes
2. All families submitted preferences (or deadline passed)
3. Admin reviews configuration

**Configuration Options**:

- **QUESTION**: Are there lottery parameters?
  - Random seed?
  - Weighting factors?
  - Priority rules (seniority, special needs)?
- **QUESTION**: Can lottery be run multiple times?
  - Test run vs final run?
  - Preview results before committing?

---

## Unit Distribution Lottery (Sorteo)

### Overview

The lottery/sorteo is the process of fairly distributing units to families based on their preferences. This uses an **external API** with **numerical methods** to find an optimal solution that maximizes overall satisfaction.

---

### External API Integration

**Purpose**: Call external service to compute unit assignments

**Input Data** (sent to API):

```json
{
  "project_id": 123,
  "unit_types": [
    {
      "id": 1,
      "name": "Large Family Home",
      "units": [
        {"id": 10, "number": "House 2", "characteristics": {...}},
        {"id": 11, "number": "House 3", "characteristics": {...}},
        {"id": 12, "number": "House 4", "characteristics": {...}}
      ],
      "families": [
        {
          "id": 50,
          "name": "García Family",
          "preferences": [10, 12, 11] // Unit IDs in preference order
        },
        {
          "id": 51,
          "name": "Rodriguez Family",
          "preferences": [11, 10, 12]
        }
      ]
    }
  ]
}
```

**Output Data** (received from API):

```json
{
  "success": true,
  "assignments": [
    { "family_id": 50, "unit_id": 10, "satisfaction_score": 1.0 },
    { "family_id": 51, "unit_id": 11, "satisfaction_score": 1.0 }
  ],
  "unassigned_families": [52, 53], // More families than units
  "unassigned_units": [12], // More units than families
  "overall_satisfaction": 0.95, // 0-1 scale
  "metadata": {
    "algorithm": "hungarian_method",
    "execution_time_ms": 234
  }
}
```

**Algorithm** (External API - Black Box):

- **Mathematical Model**: External API handles all optimization mathematics
- **MTAV's Responsibility**:
  - Format data correctly for API
  - Send one unit type at a time
  - Parse and apply results
  - Handle errors gracefully
- **API's Responsibility**:
  - Apply numerical methods for optimal assignment
  - Maximize overall satisfaction across all families
  - Handle preference weighting and conflict resolution
  - Return fair, mathematically sound assignments
- **We don't need to know**:
  - Specific algorithm used (Hungarian, genetic, simulated annealing, etc.)
  - Mathematical formulas
  - Optimization techniques
  - Only requirement: API follows the input/output contract

**Development Note**:

- **Dummy lottery for testing** - Random assignment, same format
- **Production API** - Mathematical optimization, same format
- **Seamless swap** - Change config, no code changes needed

**API Contract Requirements** (PENDING SPECS):

1. ✅ Input format defined (see above)
2. ✅ Output format defined (see above)
3. ❌ Actual API URL/endpoint - TBD
4. ❌ Authentication method - TBD
5. ❌ Rate limits / timeouts - TBD
6. ❌ Error response format - TBD

**Lottery Implementation Strategy**:

**1. Strategy Pattern Architecture**:

- **Design**: Use Strategy Pattern for pluggable lottery implementations
- **Configuration**: Easily configurable via env vars / Laravel config
- **Implementations**:
  - **Mock Strategy** (for testing): Fully random lottery, ignores preferences
  - **Development Strategy** (future): Free/fast lottery for dev environments
  - **Production Strategy** (future): Real external API (third-party, not built by us)
- **Adapter Pattern**: May need adapter for third-party APIs, but no need to build strategies themselves

**Current Scope - Mock Strategy Only**:

- ✅ **Build mock lottery strategy**: Fully random assignment
- ❌ **Ignores family preferences** (random distribution)
- ✅ **Always returns valid result** (no ties, deterministic output)
- ✅ **Used in all tests** (predictable, fast)
- Future: Real API will be plugged in via strategy pattern (external, not our concern now)

**2. Tie Breaking**:

- ❌ **No ties occur** - lottery strategies ALWAYS return well-defined result
- Each family gets exactly one unit (or explicitly marked as unassigned)
- Each unit gets exactly one family (or explicitly marked as unassigned)
- Strategy handles tie-breaking internally (not app's concern)

**3. API Failure Handling**:

- Lottery is **NOT flagged as successful** if strategy fails/errors
- **Admin who executed lottery is notified** of failure
- Admin can:
  - **Retry execution** (attempt lottery again)
  - **Seek developer help** if issue persists
- No automatic fallback to manual assignment (would violate fairness)
- System remains in "preferences submitted, lottery pending" state

**4. Running Lottery Multiple Times**:

- ❌ **Cannot run multiple times normally** (results are final)
- ✅ **Only if lottery is invalidated** (superadmin-only action):
  1. Superadmin invalidates lottery results (rare, requires good reason)
  2. Clears all unit.family_id assignments
  3. Preferences unfreeze (families can edit again)
  4. Admin can execute new lottery
  5. New results are final (unless invalidated again)
- No "preview vs final" modes - one execution, final results

**5. Lottery Execution Authorization**:

- ✅ **Admins who manage the project** can trigger lottery
- ❌ **Superadmins can also trigger** (but typically delegate to project admins)
- ❌ **Members CANNOT trigger** lottery
- ⚠️ **Lottery BLOCKED until**:
  - ALL families have submitted preferences
  - All unit types have units
  - All families have assigned unit types
- Action is **repeatable only after invalidation** (see above)

---

### Lottery Execution Process

**Step-by-Step**:

1. **Admin initiates lottery** (button/command)
2. **System validates preconditions**:
   - All families have UnitType assignments
   - Families have submitted preferences (or deadline passed)
   - No units currently assigned (or confirmation to reset)
3. **System prepares API request**:
   - Gather all units (unassigned or all?)
   - Gather all families with their UnitType and preferences
   - Format as API payload
4. **System calls external API**:
   - POST request with data
   - Wait for response (with timeout)
5. **System receives assignments**:
   - Parse response
   - Validate assignments (unit/family IDs exist, types match)
6. **System previews results** (optional):
   - Show admin what assignments will be made
   - Display satisfaction metrics
   - Admin can approve or cancel
7. **System commits assignments** (if approved):
   - For each assignment: `UPDATE units SET family_id = X WHERE id = Y`
   - Log assignment details (when, by whom, satisfaction scores)
   - **QUESTION**: Make assignments immutable? Or allow reset?
8. **System notifies families**:
   - Email/notification with assigned unit
   - Show in UI

**Lottery Execution Process**:

**1. No Preview Step**:

- ❌ No "preview" mode for lottery results
- Lottery execution is **immediate and final**
- Results are committed automatically when lottery strategy succeeds
- Admin triggers lottery → Strategy executes → Results saved → Done
- **Rationale**: Preview implies ability to reject/retry, which would allow gaming the system

**2. Assignment Reversion**:

- ❌ **Assignments CANNOT be reverted** after lottery execution
- Results are **permanent and final**
- **Only exception**: Superadmin invalidates entire lottery
  - Clears ALL assignments (not selective reversion)
  - Allows fresh lottery execution
  - Rare action requiring strong justification
- No admin/superadmin manual reassignment
- No family-to-family unit trades through app
- Real-world changes outside app scope

**3. Logging & Audit Trail**:

- ✅ **EVERYTHING must be logged** (comprehensive audit)
- **What to log**:
  - Lottery execution timestamp
  - Admin who triggered lottery
  - Strategy used (mock, dev, production API)
  - All family-unit assignments (results)
  - Preferences snapshot at execution time
  - Any errors/failures during execution
  - Invalidation events (if lottery invalidated later)
  - Who invalidated and why (reason field)
- **Purpose**: Legal compliance, dispute resolution, accountability

**4. Notification System**:

- ⚠️ **Real-time notifications are OUT OF SCOPE** for current phase
- Future enhancement: Real-time notifications for:
  - Lottery execution completion
  - Unit assignment received
  - Lottery invalidation
- Current scope: Email notifications only (on execution failure)

---

### Post-Lottery State

**Database Changes**:

- Units: `family_id` set from NULL to assigned family
- Families: Now have a `unit` (via units.family_id)

**Lottery Metadata Storage**:

- ✅ **ALL meaningful metadata MUST be stored**
- **Required fields** (may have legal requirements):
  - `executed_at` (timestamp)
  - `executed_by_user_id` (admin who triggered)
  - `strategy_used` (enum: mock, dev, production, etc.)
  - `status` (enum: pending, success, failed, invalidated)
  - `results_json` (complete assignment mapping)
  - `preferences_snapshot_json` (all preferences at execution time)
  - `error_message` (if failed)
  - `invalidated_at` (nullable, if invalidated)
  - `invalidated_by_user_id` (nullable, superadmin who invalidated)
  - `invalidation_reason` (nullable, text explanation)
- **Optional/Future**:
  - Satisfaction scores (if strategy provides them)
  - Algorithm parameters (if applicable)
  - Execution duration (performance metrics)

**Lottery Executions Table** (REQUIRED):

```sql
id (PK)
project_id (FK to projects, NOT NULL)
executed_by (FK to users, NOT NULL) -- Admin who ran it
executed_at (TIMESTAMP, NOT NULL)
algorithm (VARCHAR) -- From API metadata
overall_satisfaction (DECIMAL)
api_response (JSON) -- Full API response for audit
created_at, updated_at
```

**New Table?** `unit_assignments`:

```sql
id (PK)
lottery_execution_id (FK, NOT NULL)
family_id (FK to families, NOT NULL)
unit_id (FK to units, NOT NULL)
satisfaction_score (DECIMAL) -- How happy (1.0 = first choice, 0.0 = random)
family_preference_rank (INTEGER, NULLABLE) -- Which preference was matched (1, 2, 3, etc.)
created_at, updated_at
```

**Purpose of History Tables**:

- Audit trail
- Can see when lottery was run
- Can see satisfaction metrics
- Can analyze if re-running would be better

**Lottery Audit & History Requirements**:

**Comprehensive Audit Trail Required**:

- ✅ **Lottery history/execution table IS REQUIRED** (not optional for MVP)
- ❌ **NOT sufficient to just set unit.family_id and move on**
- **Everything that reflects state changes MUST be logged**:
  - Who did it (user_id)
  - When it happened (timestamp)
  - What changed (before/after state)
  - Why it changed (action context)
- Applies to ALL operations:
  - Creating resources (families, units, preferences, etc.)
  - Modifying resources (family project switch, unit edits, etc.)
  - Deleting resources (soft-deletes, invalidations, etc.)
  - Lottery executions (triggers, results, invalidations)

**Preserve Audit When Lottery Re-run**:

- ✅ **YES - preserve complete audit trail**
- When lottery invalidated and re-run:
  - Original lottery execution record kept (status = 'invalidated')
  - Original assignments preserved in `results_json`
  - Original preferences preserved in `preferences_snapshot_json`
  - New lottery execution creates NEW record
  - Both records maintained forever (historical comparison)
- **Purpose**: Legal compliance, dispute resolution, system transparency

---

### Business Rules Summary

**Unit Assignment Rules**:

1. Units can only be assigned to families of matching UnitType
   - `unit.unit_type_id MUST EQUAL family.unit_type_id`
2. Each family gets at most one unit
   - `units.family_id` should be unique (one family = one unit)
   - ❌ **Families CANNOT get multiple units** (no exceptions for large families)
3. Each unit assigned to at most one family
   - `units.family_id` is a foreign key, not many-to-many
4. Assignments are based on lottery algorithm + family preferences
5. **Assignments are PERMANENT** (as far as app is concerned)
   - ❌ **NO family-to-family unit trades** via app
   - ❌ **NO admin/superadmin manual reassignment** via app
   - ❌ **NO family arrangements/swaps** via app
   - Real-world changes outside app scope (people can arrange in real life, app doesn't track)
   - **Only way to change**: Superadmin invalidates lottery (clears ALL, not selective)

**Family-Unit Matching**:

**One-to-One Rule**: One family ↔ one unit (strict)

**Unmatched Scenarios** (units ≠ families):

**Case 1: More units than families**:

- Create **mock families** for excess units
- Lottery strategy receives equal counts (families = units)
- Result: Units assigned to mock families = **units remain unassigned**
- Mock families are placeholders, not real database records

**Case 2: More families than units**:

- Create **mock units** for excess families
- Lottery strategy receives equal counts (families = units)
- Result: Families assigned to mock units = **families receive no unit**
- Mock units are placeholders, not real database records

**Handling Unmatched**:

- Rare cases requiring real-world administrative decisions
- Superadmins can invalidate lottery if better solution found
- App tracks which families/units are unassigned after lottery

**Edge Cases**:

- **Unassigned Families**: More families than units of their type
  - Families remain without units
  - **QUESTION**: Waitlist? Compensated? Reassigned to different type?
- **Unassigned Units**: More units than families of that type
  - Units remain empty (family_id = NULL)
  - **QUESTION**: Can be assigned to families of different type? Manual override?
- **Family with no preferences**:
  - Get random assignment?
  - Or not eligible for lottery (skip)?
- **Unit deleted after preferences submitted**:
  - Families preferring it need adjustment
  - Validation before lottery?

---

### Authorization for Lottery Operations

**Run Lottery**:

- Superadmins: ✅ Always
- Admins managing project: ✅ Can run for their project
- Admins other projects: ❌ 403
- Members: ❌ 403

**View Lottery Results**:

- Superadmins: ✅ All results, all metrics
- Admins managing project: ✅ Results for their project
- Members: ✅ Can see their own assignment only (not other families)
  - **QUESTION**: Or can members see all assignments after lottery?

**Reset/Rerun Lottery**:

- Superadmins: ✅ Always
- Admins: ❌ 403 (too dangerous?) or ✅ with confirmation?
- Members: ❌ 403

---

### UI/UX Considerations (Not for tests, but for context)

**Family Preference Selection**:

- Interactive map/list of available units
- Drag-and-drop to rank preferences
- Visual indicators of unit characteristics
- Blueprint integration (click unit on map to select)

**Admin Lottery Dashboard**:

- Summary of families/units by type
- Validation checklist (all families assigned types, all preferences in, etc.)
- "Run Lottery" button with confirmation
- Results preview with satisfaction metrics
- Commit/Cancel options

**Post-Lottery View**:

- Families can see their assigned unit
- Unit details, characteristics, location on blueprint
- Satisfaction indicator ("You got your #1 choice!")

---

## UI/UX Principles

### Transparency Over Hiding

**Core Principle**: When actions are disabled or blocked, the UI should **explain WHY**, not hide options.

**Anti-Pattern** ❌:

- Hiding buttons when conditions aren't met
- Removing menu items when actions are unavailable
- Changing UI structure based on state without explanation
- Users left guessing why options disappeared

**Best Practice** ✅:

- **Keep UI elements visible** (buttons, fields, menu items)
- **Disable** instead of hide (grayed out, not clickable)
- **Show clear explanations** via:
  - Tooltips on hover ("Cannot delete - project is completed")
  - Inline messages near disabled elements
  - Helper text explaining the constraint
  - Links to documentation if explanation is complex
- **Provide context** about when action will be available
  - "Available when project status is 'planned' or 'under construction'"
  - "Available after lottery is invalidated"
  - "Contact admin to request this action"

**Examples Throughout App**:

1. **Project Completion Button**:
   - ❌ Don't: Hide button when units incomplete
   - ✅ Do: Show disabled button with message "Cannot complete - 3 units still under construction"

2. **Unit Type Selector**:
   - ❌ Don't: Remove dropdown when unit is completed
   - ✅ Do: Show disabled dropdown with tooltip "Type is frozen - unit is completed"

3. **Add Unit Button**:
   - ❌ Don't: Hide button when project is completed
   - ✅ Do: Show disabled button with message "Cannot add units - project is completed"

4. **Preference Editing**:
   - ❌ Don't: Remove edit button after lottery
   - ✅ Do: Show disabled button with message "Preferences are frozen after lottery execution"

5. **Family Type Assignment**:
   - ❌ Don't: Hide type selector after lottery
   - ✅ Do: Show disabled selector with tooltip "Type assignment is final after lottery"

**Benefits**:

- **Reduces user confusion** - clear why options aren't available
- **Educates users** about system rules and constraints
- **Maintains consistency** - UI structure doesn't change unexpectedly
- **Builds trust** - transparency shows system is fair and rule-based
- **Reduces support requests** - users self-serve answers

**Implementation Guidelines**:

- Use consistent patterns across all disabled actions
- Keep explanations concise (1-2 sentences max)
- Reference the relevant entity state ("project is completed", "lottery executed")
- Provide constructive guidance when possible ("Contact superadmin to invalidate lottery")

---

# Part 2: Technical Architecture

## Technology Stack

### Backend Framework & Language

- **PHP 8.4** - Modern PHP with performance and type safety improvements
- **Laravel 12.0** - Comprehensive MVC framework
  - Eloquent ORM for database operations
  - Blade templating (server-side only, not used for main app)
  - Artisan CLI for code generation and maintenance
  - Built-in authentication (Laravel Sanctum)
  - Queue system for background jobs
  - Event broadcasting (Laravel Reverb)

### Frontend Framework & Libraries

- **Vue.js 3.5.20** - Progressive JavaScript framework
  - Composition API (primary approach)
  - Script setup with TypeScript
  - Reactive state management with Pinia 3.0
- **Inertia.js 2.1.3** - Modern monolith SPA framework
  - Server-driven routing
  - No API needed for client-server communication
  - Shares data via props from controllers
  - @inertiaui/modal-vue 0.21 for modal management
- **TypeScript 5.9** - Type-safe JavaScript
  - Strict mode enabled
  - Auto-imports configured (unplugin-auto-import)
  - Full type coverage for Vue components

### UI & Styling

- **Tailwind CSS 4.1.12** - Utility-first CSS framework
  - @tailwindcss/vite 4.1.12 for Vite integration
  - Custom design system via CVA (class-variance-authority)
  - Responsive design utilities
- **Reka UI 2.5.0** - Headless component library
  - Accessible components (modals, dropdowns, etc.)
  - Unstyled, customizable with Tailwind
- **Lucide Vue Next 0.542** - Icon library
  - Tree-shakeable SVG icons
  - Vue 3 components
- **VueUse 13.8** - Composition utilities
  - Reactive browser APIs
  - State management helpers
  - Animation utilities (@formkit/auto-animate 0.9)

### Build Tools & Development

- **Vite 7.1.3** - Next-generation frontend tooling
  - Lightning-fast HMR (Hot Module Replacement)
  - Optimized production builds
  - Built-in TypeScript support
  - Laravel Vite Plugin 2.0 for integration
- **Vitest 3.2.4** - Vite-native testing framework
  - Unit tests for Vue components
  - Compatible with Jest API
  - Fast execution with Vite's transform pipeline
- **Cypress 14.5.4** - E2E testing framework (planned)
  - Browser automation
  - Integration tests

### Database & Caching

- **MariaDB 12** - Production database
  - MySQL-compatible
  - Enhanced performance over MySQL
  - Full ACID compliance
- **Redis** (optional, production) - Caching and sessions
  - Session storage
  - Cache backend
  - Queue backend
  - Real-time broadcasting

### Development Tools & Quality

- **Pest 3.8** - PHP testing framework
  - Elegant test syntax
  - Laravel plugin 3.2
  - Fast test execution
- **PHPInsights 2.13** - Code quality analysis
  - Complexity analysis
  - Architecture checks
  - Code style enforcement
- **Laravel Pint 1.18** - PHP code formatter
  - Opinionated formatting
  - Based on PHP-CS-Fixer
  - Laravel conventions
- **ESLint 9.34** - JavaScript/TypeScript linting
  - Vue plugin 10.4
  - TypeScript ESLint 8.41
  - Prettier integration (eslint-config-prettier)
- **Prettier 3.6.2** - Code formatting
  - Tailwind plugin (class ordering)
  - Vue plugin
  - Organize imports plugin
- **Husky 9.1.7** - Git hooks
  - Pre-commit: tests + linting
  - Pre-push: code quality analysis

### Routing & API Utilities

- **Ziggy 2.5.3** - Laravel route helper for JavaScript
  - Type-safe route() function in Vue
  - Full Laravel routing available in frontend
  - No manual API endpoint management

### Real-time & Communication

- **Laravel Echo 2.2** - WebSocket client
  - Real-time event broadcasting
  - Pusher JS 8.4 backend
- **Laravel Reverb 1.5** - WebSocket server
  - Native Laravel broadcasting server
  - Alternative to Pusher/Ably for production
- **MailHog** - Email testing (development only)
  - SMTP server mock
  - Web UI for viewing sent emails
  - No emails sent to real addresses in dev

### Custom Packages (Local Development)

- **devvir/laravel-instant-api** - Automatic REST API generation
  - Location: `packages/laravel-instant-api/`
  - Repository type: path (local package)
- **devvir/laravel-resource-tools** - Laravel resource utilities
  - Location: `packages/laravel-resource-tools/`
  - Repository type: path (local package)

### Container & Deployment Stack

- **Docker 24.0+** - Containerization platform
- **Docker Compose 2.0+** - Multi-container orchestration
- **PHP-FPM 8.4-FPM-Alpine** - PHP runtime container
- **Node.js 22-Alpine** - Frontend build container
- **Nginx 1.26-Alpine** - Web server and reverse proxy
- **GitHub Container Registry (ghcr.io)** - Production image storage

### Operating System & Shell

- **Alpine Linux** - Lightweight container OS
  - Minimal attack surface
  - Small image sizes
  - Efficient resource usage
- **Zsh** - Default development shell
  - Enhanced scripting capabilities
  - Better user experience

### Code Style Conventions

- **Vue Files**: Script setup with TypeScript
  - `<script setup lang="ts">` syntax
  - Always use semicolons in JavaScript/TypeScript
  - Composition API over Options API
- **PHP Files**: Laravel conventions
  - PSR-12 code style
  - Type hints and return types
  - Strict types declaration
- **Git Conventions**: Conventional commits encouraged
  - Descriptive commit messages
  - Atomic commits preferred

---

## Application Architecture

### MVC Pattern with Inertia

MTAV follows a **server-driven SPA architecture** using Laravel + Inertia + Vue:

**Request Flow**:

1. User navigates → Laravel route
2. Route → Controller action
3. Controller → Inertia::render('Component', ['data' => $data])
4. Inertia serializes data → JSON
5. Frontend receives JSON → Vue component renders
6. User interaction → Inertia request (XHR)
7. Cycle repeats

**Key Architectural Principles**:

- **No REST API** - Inertia handles client-server communication
- **Server-driven routing** - All routes defined in Laravel
- **Props-based data** - Controllers pass data via Inertia props
- **SPA experience** - No full page reloads after initial load
- **SEO-friendly** - Server-side initial render possible

### Directory Structure

```
mtav/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Inertia controllers (render Vue pages)
│   │   ├── Middleware/        # Request filtering & auth
│   │   ├── Requests/          # Form validation
│   │   └── Resources/         # API resources (JSON transformation)
│   ├── Models/                # Eloquent models (database entities)
│   │   └── Concerns/          # Reusable model traits
│   └── Policies/              # Authorization logic (per model)
├── resources/
│   ├── js/
│   │   ├── Components/        # Vue components
│   │   ├── Layouts/           # Page layouts
│   │   ├── Pages/             # Inertia pages (routes)
│   │   └── app.ts             # Frontend entry point
│   ├── css/                   # Global styles
│   └── views/                 # Blade templates (app shell only)
├── routes/
│   ├── web.php                # Main routes (Inertia pages)
│   ├── api.php                # API routes (unused for main app)
│   └── channels.php           # Broadcasting channels
├── database/
│   ├── migrations/            # Database schema
│   ├── seeders/               # Sample data
│   └── factories/             # Test data generators
├── tests/
│   ├── Feature/               # Integration tests (HTTP, database)
│   ├── Unit/                  # Unit tests (isolated logic)
│   └── KNOWLEDGE_BASE.md      # This file
├── config/                    # Laravel configuration files
├── storage/                   # User uploads, logs, cache
├── public/                    # Publicly accessible files
├── .docker/                   # Docker development environment
├── documentation/             # Additional documentation
└── packages/                  # Local custom packages
```

### Single-Table Inheritance (STI)

**User Model Hierarchy**:

```
User (base model, users table)
├── Admin (User where is_admin = true)
└── Member (User where is_admin = false)
```

**Implementation**:

- Single `users` table stores all user types
- `is_admin` boolean discriminates type
- Admin and Member models extend User
- Global scopes filter by `is_admin`
- Shared fields: email, firstname, lastname, password, etc.
- Type-specific: `family_id` (members only), project assignments (different semantics)

**Benefits**:

- Simplified relationships (polymorphic not needed)
- Shared authentication logic
- Easy type checking (`$user->isMember()`, `$user->isAdmin()`)
- Single source of truth for user data

### Pivot Tables & Relationships

**project_user Pivot** (polymorphic behavior based on user type):

```sql
project_user
├── user_id (FK to users)
├── project_id (FK to projects)
├── active (boolean)
└── timestamps

Semantics:
- For Admins: can have MULTIPLE active projects
- For Members: can have EXACTLY ONE active project
```

**family_preferences** (lottery preference tracking):

```sql
family_preferences
├── family_id (FK to families)
├── unit_id (FK to units)
├── rank (integer, 1 = first choice)
└── timestamps

Constraints:
- UNIQUE(family_id, unit_id) - can't prefer same unit twice
- UNIQUE(family_id, rank) - each rank used once
```

### Authentication & Authorization

**Authentication**:

- Laravel Sanctum 4.0 for API tokens (if needed)
- Session-based auth for web (default)
- Email verification (`email_verified_at` field)

**Authorization**:

- Laravel Policies per model (AdminPolicy, FamilyPolicy, etc.)
- Gate::before() for superadmin bypass
- Authorization matrix (see [Authorization Matrix](#authorization-matrix))
- Row-level security via policy methods

### State Management

**Backend State**:

- `state('project')` - Current project context (NOT session)
  - Persists across requests
  - Scopes all queries to current project
  - Members: automatically set to family's project
  - Admins: manually selected, can switch

**Frontend State**:

- Pinia stores for complex state
- Inertia shared data for global state
- Component props for local state
- VueUse composables for reactive utilities

### UI Navigation & User Actions

**Profile & Settings Access**:

- Location: Bottom left corner of the application (sidebar)
- Trigger: Click on user's avatar/name
- Options available:
  - "Settings" / "Configuración" → Opens settings pages (Profile, Password, Appearance)
  - "Log out" / "Cerrar Sesión" → Ends user session

**Settings Pages** (accessible via Settings menu):

- Profile Settings (`/settings/profile`):
  - Update first name, last name
  - Update profile photo
  - Update email (admins only - members must request admin help)
  - View CI (Cédula de Identidad - non-editable)
- Password Settings (`/settings/password`):
  - Change password
  - Requires current password confirmation
- Appearance Settings (`/settings/appearance`):
  - Theme selection (light/dark mode)
  - Language selection (EN/ES_UY)

**Login Session Persistence**:

- "Remember me" / "Recordarme" checkbox on login page
- When checked: Session persists beyond timeout period
- When unchecked: Session ends after configured timeout (default: 120 minutes)
- Implementation: Laravel's session-based authentication with "remember" token
- Users should select "Remember me" to avoid re-logging in frequently

---

## Development Environment

### Architecture Overview

MTAV uses a **three-layer development architecture**:

**Layer 1: Docker Containers** (bare-bones, full control)

- Raw `docker compose` commands
- Direct container management
- For debugging and advanced operations

**Layer 2: Convenience Scripts** (`.docker/scripts/`)

- Wrappers around Docker commands
- Service-specific operations
- Detailed in [Development Workflows](#development-workflows)

**Layer 3: MTAV Command** (`./mtav`)

- High-level development commands
- Everyday workflow automation
- Recommended for daily use

### Docker Services

**Five containers** work together in development:

1. **PHP-FPM** (`dev-php-1`)
   - Container: `php:8.4-fpm-alpine`
   - Purpose: Laravel application runtime
   - Runs: PHP-FPM process for dynamic content
   - User mapping: Uses host PUID/PGID for correct file permissions
   - Health check: Laravel Artisan command

2. **Assets** (`dev-assets-1`)
   - Container: `node:22-alpine`
   - Purpose: Frontend development server
   - Runs: Vite dev server with HMR
   - Port: 5173 (auto-reload on code changes)
   - User mapping: Same as host for file permissions

3. **Nginx** (`dev-nginx-1`)
   - Container: `nginx:1.26-alpine`
   - Purpose: Web server and reverse proxy
   - Port: 8000 (main application)
   - Routing: Static files + proxy to PHP-FPM
   - Configuration: `.docker/nginx/default.conf`

4. **MariaDB** (`dev-mysql-1`)
   - Container: `mariadb:12`
   - Purpose: Database
   - Port: 3307 (exposed to host)
   - Credentials: user=mtav, password=secret
   - Data: Persistent volume (`mtav_mysql`)

5. **MailHog** (`dev-mailhog-1`)
   - Container: `mailhog/mailhog`
   - Purpose: Email testing and debugging
   - SMTP Port: 1025 (Laravel sends here)
   - Web UI: 8025 (view captured emails)
   - No real emails sent in development

### Quick Start (New Developers)

**Single command setup**:

```bash
./mtav up
```

This automatically:

1. Creates `.env` file (from template)
2. Builds all Docker containers
3. Installs Composer dependencies
4. Installs NPM dependencies
5. Generates Laravel app key
6. Runs database migrations
7. Seeds database with sample data
8. Starts all services

**Access points**:

- Main App: http://localhost:8000
- Vite HMR: http://localhost:5173
- MailHog: http://localhost:8025
- Database: localhost:3307

### Daily Development Commands

```bash
# Start development day
./mtav up              # Start all services (or resume if already created)

# Update dependencies and schema
./mtav update          # Pull latest, install deps, run migrations

# Check status
./mtav status          # Show running containers

# View logs
./mtav logs            # All services
./mtav logs php        # Specific service
./mtav logs -f nginx   # Follow mode

# Stop for break
./mtav stop            # Stop containers (keep state)

# End of day
./mtav down            # Stop and remove containers

# Nuclear option (issues)
./mtav fresh           # Rebuild everything from scratch

# Run tests
./mtav test            # All tests in watch mode
./mtav test --once     # Run once and exit

# Laravel commands
./mtav artisan migrate              # Run migrations
./mtav artisan tinker               # REPL
./mtav artisan make:model Post      # Generate code

# Package management
./mtav composer require package/name    # Add PHP package
./mtav npm add vue-package              # Add JS package

# Container access
./mtav shell php       # Open shell in PHP container
./mtav shell nginx     # Open shell in Nginx container
```

### User ID Mapping & Permissions

**Automatic mapping** prevents permission issues:

- `PUID` and `PGID` environment variables
- Set from host user's ID
- Containers run as your user
- Files created in containers have correct ownership
- No `sudo` needed for generated files

**If permission issues occur**:

```bash
# Fix storage and cache
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Or rebuild (recommended)
./mtav fresh
```

### Environment Configuration

**Environment files**:

- `.env` - Active environment (git-ignored)
- `.env.template` - Template with defaults
- Generated automatically by `./mtav up`

**Key variables**:

```bash
# App
APP_NAME=MTAV
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=mtav
DB_USERNAME=mtav
DB_PASSWORD=secret

# Mail (MailHog)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

# Docker Ports (customizable to avoid conflicts)
DOCKER_NGINX_PORT=8000
DOCKER_VITE_PORT=5173
DOCKER_MYSQL_PORT=3307
DOCKER_MAILHOG_WEB_PORT=8025
DOCKER_MAILHOG_SMTP_PORT=1025

# User mapping (usually auto-detected)
PUID=1000
PGID=1000
```

### File Structure (Development)

```
.docker/
├── compose.yml               # Docker Compose configuration
├── nginx/
│   └── default.conf          # Nginx development config
├── php/
│   ├── Dockerfile            # PHP-FPM development image
│   └── php.ini               # PHP configuration
├── assets/
│   └── Dockerfile            # Node.js/Vite image
└── scripts/                  # Convenience scripts (Layer 2)
    ├── artisan.sh            # Laravel commands
    ├── composer.sh           # PHP dependencies
    ├── npm.sh                # JS dependencies
    ├── migrate.sh            # Quick migration
    ├── db.sh                 # Database access
    ├── shell.sh              # Container shell
    └── compose.sh            # Docker Compose wrapper
```

---

## Docker Infrastructure

### Layered Architecture Rationale

**Why three layers?**

- **Layer 1** (Docker) - Maximum control for complex debugging
- **Layer 2** (Scripts) - Moderate control for specific tasks
- **Layer 3** (MTAV) - Simplicity for everyday development

**Principle**: Use the highest layer (MTAV) for daily work, drop to lower layers only when needed.

### Direct Docker Commands (Layer 1)

**When to use**: Debugging, custom operations, CI/CD, learning internals

**Manual startup**:

```bash
# Generate environment
.docker/scripts/generate-env.sh dev .env

# Start services
docker compose -f .docker/compose.yml --env-file .env up --build -d

# Check status
docker compose -f .docker/compose.yml --env-file .env ps

# View logs
docker compose -f .docker/compose.yml --env-file .env logs -f

# Stop services
docker compose -f .docker/compose.yml --env-file .env down
```

**Shortcut**: Use `compose.sh` to avoid repeating flags:

```bash
.docker/scripts/compose.sh up -d
.docker/scripts/compose.sh ps
.docker/scripts/compose.sh logs -f php
```

### Individual Service Management

```bash
# Start specific services
docker compose -f .docker/compose.yml up php mysql -d

# Restart a service
docker compose -f .docker/compose.yml restart nginx

# View logs for specific service
docker compose -f .docker/compose.yml logs -f php

# Execute command in container
docker compose -f .docker/compose.yml exec php php artisan migrate
```

### Troubleshooting Commands

```bash
# Rebuild from scratch (no cache)
docker compose -f .docker/compose.yml build --no-cache

# Clean start
docker compose -f .docker/compose.yml down
docker compose -f .docker/compose.yml up --build -d

# Check container health
docker compose -f .docker/compose.yml ps
docker compose -f .docker/compose.yml top

# Inspect container
docker inspect dev-php-1
docker logs dev-php-1
```

### Network & Storage

**Docker Network**: Automatic bridge network

- Containers communicate via service names
- `mysql` resolves to MariaDB container
- `mailhog` resolves to MailHog container
- Internal DNS provided by Docker

**Persistent Volumes**:

```bash
# Named volumes (managed by Docker)
mtav_mysql         # Database data
mtav_storage       # Laravel storage (uploads, logs)

# List volumes
docker volume ls

# Inspect volume
docker volume inspect mtav_mysql

# Remove volume (⚠️ destroys data)
docker volume rm mtav_mysql
```

### Container Details

**PHP Container**:

- Base: `php:8.4-fpm-alpine`
- Extensions: pdo_mysql, zip, exif, pcntl, bcmath, gd
- Composer: Pre-installed globally
- Working directory: `/var/www`
- User: Mapped to host UID/GID

**Assets Container**:

- Base: `node:22-alpine`
- NPM: Pre-installed
- Working directory: `/var/www`
- Purpose: Vite dev server only (not for builds)

**Nginx Container**:

- Base: `nginx:1.26-alpine`
- Configuration: Custom Laravel-optimized
- Proxy: Forwards PHP requests to PHP-FPM
- Static: Serves compiled assets directly

**MariaDB Container**:

- Base: `mariadb:12`
- Encoding: utf8mb4 (full Unicode support)
- Storage engine: InnoDB
- Configuration: Production-tuned in production builds

**MailHog Container**:

- Base: `mailhog/mailhog`
- SMTP: Captures all outbound emails
- Web UI: Browse sent emails
- No external delivery

---

## Development Workflows

### Layer 2: Convenience Scripts

Located in `.docker/scripts/`, these provide service-specific operations:

**artisan.sh** - Laravel Artisan commands:

```bash
# Database
.docker/scripts/artisan.sh migrate
.docker/scripts/artisan.sh migrate:fresh --seed
.docker/scripts/artisan.sh migrate:rollback

# Code generation
.docker/scripts/artisan.sh make:controller UserController
.docker/scripts/artisan.sh make:model Post -m
.docker/scripts/artisan.sh make:migration create_posts_table

# Maintenance
.docker/scripts/artisan.sh cache:clear
.docker/scripts/artisan.sh config:clear
.docker/scripts/artisan.sh route:list

# Interactive
.docker/scripts/artisan.sh tinker
.docker/scripts/artisan.sh queue:work
```

**composer.sh** - PHP package manager:

```bash
# Install dependencies
.docker/scripts/composer.sh install
.docker/scripts/composer.sh install --no-dev

# Add packages
.docker/scripts/composer.sh require laravel/sanctum
.docker/scripts/composer.sh require --dev phpunit/phpunit

# Update packages
.docker/scripts/composer.sh update
.docker/scripts/composer.sh update laravel/framework

# Utilities
.docker/scripts/composer.sh dump-autoload
.docker/scripts/composer.sh show
.docker/scripts/composer.sh outdated
```

**npm.sh** - Node.js package manager:

```bash
# Install dependencies
.docker/scripts/npm.sh install
.docker/scripts/npm.sh ci

# Add packages
.docker/scripts/npm.sh add vue@latest
.docker/scripts/npm.sh add --save-dev @types/node

# Development tasks
.docker/scripts/npm.sh run dev
.docker/scripts/npm.sh run build
.docker/scripts/npm.sh run test

# Utilities
.docker/scripts/npm.sh update
.docker/scripts/npm.sh list
.docker/scripts/npm.sh outdated
```

**Other scripts**:

```bash
# Quick migration
.docker/scripts/migrate.sh

# Database access (password: secret)
.docker/scripts/db.sh

# Container shell access
.docker/scripts/shell.sh
.docker/scripts/shell.sh php
.docker/scripts/shell.sh nginx

# Docker Compose wrapper
.docker/scripts/compose.sh ps
.docker/scripts/compose.sh logs -f
```

### Common Development Tasks

**Setting up a new feature**:

```bash
# Create migration and model
./mtav artisan make:model Product -m

# Edit migration, then run
./mtav artisan migrate

# Create controller
./mtav artisan make:controller ProductController

# Create Vue page
# (Manually create in resources/js/Pages/)

# Add route
# (Edit routes/web.php)
```

**Adding dependencies**:

```bash
# PHP package
./mtav composer require vendor/package

# JavaScript package
./mtav npm add package-name

# Update autoloader
./mtav composer dump-autoload
```

**Database operations**:

```bash
# Fresh database
./mtav artisan migrate:fresh --seed

# Backup database (from host)
docker exec $(docker compose -f .docker/compose.yml ps -q mysql) \
  mysqldump -u mtav -psecret mtav > backup.sql

# Restore database
docker exec -i $(docker compose -f .docker/compose.yml ps -q mysql) \
  mysql -u mtav -psecret mtav < backup.sql
```

**Debugging workflow**:

```bash
# Check logs
./mtav logs php
./mtav logs nginx

# Access container
./mtav shell php

# Inside container - check Laravel logs
tail -f storage/logs/laravel.log

# Database debugging
./mtav shell php
# php artisan tinker
# DB::connection()->getPdo();

# Or direct SQL
.docker/scripts/db.sh
# SHOW PROCESSLIST;
# EXPLAIN SELECT ...;
```

### Hot Module Replacement (HMR)

**Vite HMR** provides instant feedback during development:

**How it works**:

1. Vite dev server runs on port 5173
2. Watches `resources/js/` and `resources/css/`
3. On file change: Transforms → Sends to browser
4. Browser: Swaps module without reload
5. State preserved (mostly)

**What triggers HMR**:

- Vue component changes
- TypeScript/JavaScript changes
- CSS/Tailwind changes
- Asset imports (images, etc.)

**What requires full reload**:

- `.env` changes
- `vite.config.ts` changes
- New dependencies installed
- Laravel route changes
- Laravel controller changes

**Troubleshooting HMR**:

```bash
# HMR not working
./mtav down && ./mtav up

# Vite port conflict
# Edit .env: DOCKER_VITE_PORT=3000
./mtav down && ./mtav up

# Check Vite is running
./mtav logs assets

# Manual Vite restart
./mtav npm run dev
```

---

## Testing Infrastructure

### Testing Stack

**Backend (PHP/Laravel)**:

- **Pest 3.8** - Modern PHP testing framework
  - Elegant syntax: `it('does something')`
  - Laravel plugin: Database, HTTP testing
  - Parallel execution support
  - Code coverage reports
- **Mockery 1.6** - Mocking library
  - Mock dependencies
  - Spy on method calls
- **Faker 1.23** - Fake data generation
  - Used in factories for test data

**Frontend (Vue/TypeScript)**:

- **Vitest 3.2.4** - Vite-native testing
  - Compatible with Jest API
  - Fast execution (Vite transforms)
  - Component testing support
- **Vue Test Utils 2.4.6** - Vue testing utilities
  - Mount components
  - Interact with DOM
  - Assert component behavior
- **JSDOM 27.0** - DOM implementation for Node
  - Browser environment simulation
  - No real browser needed

**E2E (Planned)**:

- **Cypress 14.5.4** - Browser automation
  - Real browser testing
  - Visual debugging
  - Network stubbing

### Running Tests

**All tests (recommended)**:

```bash
# Watch mode (auto-rerun on changes)
./mtav test

# Run once and exit
./mtav test --once
```

**Individual test suites**:

```bash
# Backend only (Pest)
./mtav artisan test
.docker/scripts/artisan.sh test

# Frontend only (Vitest)
./mtav npm test
.docker/scripts/npm.sh test

# With coverage
./mtav artisan test --coverage
./mtav npm run test -- --coverage

# Specific test file
./mtav artisan test tests/Feature/AuthTest.php
./mtav npm test -- resources/js/Components/Button.test.ts
```

**E2E tests (when implemented)**:

```bash
./mtav npm run test:e2e
```

### Test Organization

**Backend tests** (`tests/`):

```
tests/
├── Pest.php                 # Pest configuration
├── TestCase.php             # Base test class
├── Feature/                 # Integration tests
│   ├── AuthTest.php         # HTTP, database
│   ├── ProjectTest.php
│   └── FamilyTest.php
└── Unit/                    # Unit tests
    ├── Models/              # Model logic
    └── Services/            # Service classes
```

**Frontend tests** (co-located with components):

```
resources/js/
├── Components/
│   ├── Button.vue
│   └── Button.test.ts       # Component test
├── Pages/
│   ├── Dashboard.vue
│   └── Dashboard.test.ts    # Page test
└── Stores/
    ├── user.ts
    └── user.test.ts         # Store test
```

### Testing Conventions

**Pest syntax** (backend):

```php
<?php

use App\Models\User;
use App\Models\Project;

// Describe test group
describe('Project Management', function () {
    // Individual test
    it('allows admin to create project', function () {
        $admin = User::factory()->admin()->create();

        actingAs($admin)
            ->post('/projects', ['name' => 'Test Project'])
            ->assertRedirect()
            ->assertSessionHas('success');

        expect(Project::count())->toBe(1);
    });

    it('prevents member from creating project', function () {
        $member = User::factory()->member()->create();

        actingAs($member)
            ->post('/projects', ['name' => 'Test Project'])
            ->assertForbidden();
    });
});
```

**Vitest syntax** (frontend):

```typescript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import Button from './Button.vue';

describe('Button', () => {
  it('renders slot content', () => {
    const wrapper = mount(Button, {
      slots: { default: 'Click me' },
    });

    expect(wrapper.text()).toBe('Click me');
  });

  it('emits click event', async () => {
    const wrapper = mount(Button);
    await wrapper.trigger('click');

    expect(wrapper.emitted('click')).toBeTruthy();
  });
});
```

### Test Database

**Automatic setup**: Pest Laravel plugin handles database:

- Uses SQLite in-memory by default (fast)
- Refreshes database before each test
- Runs migrations automatically
- No manual cleanup needed

**Custom configuration** (`phpunit.xml`):

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

**Factory usage**:

```php
// Create test data
$user = User::factory()->create();
$admin = User::factory()->admin()->create();
$project = Project::factory()->create();
$family = Family::factory()
    ->for($project)
    ->has(User::factory()->count(3), 'members')
    ->create();
```

---

## Quality Assurance & Git Hooks

### Automated Quality Checks

**Git Hooks** (via Husky 9.1.7):

MTAV enforces quality standards automatically via Git hooks. **These cannot be bypassed without `--no-verify` flag.**

**Pre-commit Hook**:

Runs before every commit:

1. **Frontend tests** (Vitest)
   - All unit tests
   - Component tests
   - Fast execution (seconds)
2. **Backend tests** (Pest)
   - Feature tests
   - Unit tests
   - Database tests
3. **ESLint** (frontend linting)
   - TypeScript type checking
   - Vue component linting
   - Code style enforcement

**Blocks commit if**:

- Any test fails
- Linting errors detected
- Type errors present

**Pre-push Hook**:

Runs before every push:

1. **PHP Insights** (code quality analysis)
   - Complexity analysis
   - Architecture checks
   - Code style verification
   - Security patterns

**Blocks push if**:

- Code quality score below threshold
- Architecture violations detected
- Security issues found

### Bypassing Hooks (⚠️ Not Recommended)

**Emergency situations only**:

```bash
# Skip pre-commit (tests + linting)
git commit --no-verify -m "Emergency fix"

# Skip pre-push (code quality)
git push --no-verify
```

**When to bypass**:

- Critical production bug
- Infrastructure failure
- Time-sensitive deployment
- **Always create follow-up task** to fix properly

### Manual Quality Checks

**Run checks manually**:

```bash
# Frontend linting
./mtav npm run lint

# Frontend formatting
./mtav npm run format        # Fix
./mtav npm run format:check  # Check only

# Backend code quality
./mtav artisan insights

# Backend formatting
./mtav artisan pint
```

### Code Quality Standards

**PHP (Laravel/Backend)**:

- **PSR-12** code style
- **Complexity**: Max 10 per method (configurable)
- **Architecture**: Respect Laravel conventions
- **Type hints**: Required for parameters and returns
- **Strict types**: `declare(strict_types=1);` in all files

**TypeScript/Vue (Frontend)**:

- **ESLint**: No errors allowed
- **Prettier**: Consistent formatting
- **Type safety**: Strict mode enabled
- **Vue conventions**: Composition API, script setup
- **Always use semicolons** (custom rule)

### InertiaUI Package Synchronization

**Critical**: Keep backend and frontend InertiaUI versions synchronized.

**Why**: InertiaUI modal has linked backend and frontend packages that must match.

**Update process**:

```bash
# Check current versions
./mtav composer show inertiaui/modal
./mtav npm list @inertiaui/modal-vue

# Update both to same version
./mtav composer update inertiaui/modal
./mtav npm update @inertiaui/modal-vue

# Verify sync
./mtav composer show inertiaui/modal
./mtav npm list @inertiaui/modal-vue
```

**Symptoms of version mismatch**:

- Modals not opening
- Props not passed correctly
- Runtime errors in console
- Type errors in TypeScript

---

## Build System & Production Images

### Build Architecture

MTAV uses **multi-stage Docker builds** to create optimized production images:

**Strategy**:

1. **Multi-stage builds** - Separate build and runtime stages
2. **Minimal final images** - Only production dependencies
3. **Registry storage** - GitHub Container Registry (ghcr.io)
4. **Versioned tags** - Semantic versioning (1.0.0, 1.2.0, etc.)
5. **Separate services** - PHP, Assets, Nginx, MySQL built independently

### Production Image Services

**1. PHP Container** (`mtav-php`):

**Multi-stage build**:

- **Stage 1: Frontend Compilation**
  - Base: `node:22-alpine`
  - Install: NPM dependencies
  - Build: Vite production build (`npm run build`)
  - Output: Compiled assets in `public/build/`

- **Stage 2: Backend Production**
  - Base: `php:8.4-fpm-alpine`
  - Install: Composer dependencies (`--no-dev --optimize-autoloader`)
  - Copy: Compiled assets from Stage 1
  - Configure: Production PHP settings (OPcache, memory limits)
  - User: Non-root for security

**Result**: Single container with PHP-FPM + compiled frontend assets.

**2. Assets Container** (`mtav-assets`):

- Base: `nginx:1.26-alpine`
- Purpose: Serve static files only (CSS, JS, images)
- Content: Compiled assets from PHP build
- Configuration: Optimized for static file serving

**3. Nginx Container** (`mtav-nginx`):

- Base: `nginx:1.26-alpine`
- Purpose: Reverse proxy and routing
- Routes:
  - `/` → PHP container (dynamic content)
  - `/build/*` → Assets container (static files)
- Configuration: Production-optimized
  - Gzip compression
  - Security headers
  - Asset caching

**4. MySQL Container** (`mtav-mysql`):

- Base: `mariadb:12`
- Purpose: Production database
- Configuration: Production-tuned
  - Optimized buffer sizes
  - InnoDB settings
  - Connection limits

### Building Images

**Version requirement**: All builds require semantic version tag.

**Build commands**:

```bash
# PHP (app + compiled assets)
./mtav build php 1.2.0
# Creates: ghcr.io/devvir/mtav-php:1.2.0

# Assets (static file server)
./mtav build assets 1.2.0
# Creates: ghcr.io/devvir/mtav-assets:1.2.0

# Nginx (reverse proxy)
./mtav build nginx 1.0.1
# Creates: ghcr.io/devvir/mtav-nginx:1.0.1

# MySQL (database)
./mtav build mysql 1.0.0
# Creates: ghcr.io/devvir/mtav-mysql:1.0.0
```

**Build process**:

1. Executes `.docker/scripts/build.sh`
2. Runs Docker build with proper context
3. Tags image with version
4. Optionally pushes to registry (asks for confirmation)

**Build script location**: `.docker/scripts/build.sh`

### GitHub Container Registry

**Registry**: `ghcr.io` (GitHub Container Registry)

**Benefits**:

- **Free**: Unlimited storage and bandwidth for public repos
- **Integrated**: Native GitHub access control
- **Reliable**: Enterprise-grade infrastructure
- **Naming**: `ghcr.io/devvir/mtav-{service}:{version}`

**Authentication**:

```bash
# Login (one-time setup)
echo $GITHUB_TOKEN | docker login ghcr.io -u USERNAME --password-stdin

# Build will automatically push if authenticated
./mtav build php 1.2.0
```

**Registry organization**:

```
ghcr.io/devvir/
├── mtav-php:1.0.0, 1.1.0, 1.2.0
├── mtav-assets:1.0.0, 1.1.0, 1.2.0
├── mtav-nginx:1.0.0, 1.0.1
└── mtav-mysql:1.0.0
```

### Production vs Development Images

**Key differences**:

| Aspect              | Development                 | Production                   |
| ------------------- | --------------------------- | ---------------------------- |
| **Base**            | Alpine Linux                | Alpine Linux                 |
| **Dependencies**    | Dev + Production            | Production only              |
| **Assets**          | Live compilation (Vite HMR) | Pre-compiled, optimized      |
| **PHP**             | Xdebug enabled              | OPcache enabled              |
| **Error reporting** | Detailed errors             | Minimal errors               |
| **Code**            | Volume-mounted (live edits) | Baked into image (immutable) |
| **Size**            | Larger (dev tools)          | Minimal (no dev tools)       |
| **User**            | Root or host-mapped         | Non-root (security)          |

### Configuration Differences

**PHP Configuration** (production):

```ini
; OPcache enabled
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000

; Error handling
display_errors=Off
log_errors=On

; Security
expose_php=Off
```

**Nginx Configuration** (production):

```nginx
# Gzip compression
gzip on;
gzip_types text/css application/javascript application/json;

# Security headers
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";

# Asset caching
location /build/ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### Build File Structure

```
.docker/build/
├── README.md                # Build system docs
├── compose.yml              # Production orchestration
├── app/
│   ├── Dockerfile           # Multi-stage PHP + assets build
│   └── php.ini              # Production PHP config
├── mysql/
│   ├── Dockerfile           # MySQL production image
│   └── my.cnf               # Production MySQL config
└── nginx/
    ├── Dockerfile           # Nginx production image
    └── nginx.conf           # Production nginx config
```

---

## Deployment Architecture

### Deployment Repository

**Separation of concerns**: Code repo (mtav) vs deployment repo (mtav-deploy)

**mtav repository** (this repo):

- Application code
- Development environment
- Builds production images
- Pushes to registry

**mtav-deploy repository** (separate):

- Deployment scripts
- Environment configurations
- Pulls images from registry
- Deploys to servers
- **No application code access needed**

**Benefits**:

- Operations team doesn't need codebase access
- Deployment configs separate from code
- Simplified deployment process
- Clear separation of duties

### Environment Naming Conventions

**Project names** identify environment:

```bash
# Production
docker compose --project-name prod up -d
# Containers: prod-php-1, prod-nginx-1, prod-mysql-1

# Testing/Staging
docker compose --project-name test up -d
# Containers: test-php-1, test-nginx-1, test-mysql-1

# Development (local)
docker compose --project-name dev up -d
# Containers: dev-php-1, dev-nginx-1, dev-mysql-1
```

**Benefits**:

- Multiple environments on same host
- No container name conflicts
- Clear environment identification
- Easy to script

### Deployment Process

**From mtav-deploy repository**:

```bash
# Clone deployment repo
git clone <mtav-deploy-repo-url>
cd mtav-deploy

# Configure environment
cp .env.template .env
# Edit .env with production values

# Deploy specific version
./deploy.sh --tag=v1.2.0

# Or prepare + deploy
./deploy.sh --prepare --tag=v1.2.0
```

**What happens**:

1. Pulls images from `ghcr.io/devvir/mtav-*:VERSION`
2. Creates containers with `--project-name prod`
3. Sets up volumes for persistence
4. Starts services in order (MySQL → PHP → Assets → Nginx)
5. Runs health checks
6. Reports deployment status

### Service Deployment Details

**Start order** (critical):

1. **MySQL** (must be ready first)
2. **PHP** (depends on MySQL)
3. **Assets** (static files, no dependencies)
4. **Nginx** (reverse proxy, started last)

**Example manual deployment**:

```bash
# Create network
docker network create prod

# Start MySQL
docker run -d \
  --name prod-mysql-1 \
  --restart unless-stopped \
  -v mtav_mysql:/var/lib/mysql \
  -e MYSQL_ROOT_PASSWORD=rootsecret \
  -e MYSQL_DATABASE=mtav \
  -e MYSQL_USER=mtav \
  -e MYSQL_PASSWORD=secret \
  --network prod \
  ghcr.io/devvir/mtav-mysql:1.0.0

# Start PHP
docker run -d \
  --name prod-php-1 \
  --restart unless-stopped \
  -v mtav_storage:/var/www/storage \
  --network prod \
  ghcr.io/devvir/mtav-php:1.2.0

# Start Assets
docker run -d \
  --name prod-assets-1 \
  --restart unless-stopped \
  --network prod \
  ghcr.io/devvir/mtav-assets:1.2.0

# Start Nginx
docker run -d \
  --name prod-nginx-1 \
  --restart unless-stopped \
  -p 80:80 -p 443:443 \
  --network prod \
  ghcr.io/devvir/mtav-nginx:1.0.1
```

**Critical volume**: `mtav_storage`

- **Must persist** across container restarts
- Contains: User uploads, logs, cache
- **Never run PHP without this volume** - data loss guaranteed

### Production Environment Variables

**Environment configuration** (`.env` in mtav-deploy):

```bash
# App
APP_NAME=MTAV
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=mtav
DB_USERNAME=mtav
DB_PASSWORD=<STRONG_PASSWORD>

# Mail (production SMTP)
MAIL_MAILER=smtp
MAIL_HOST=<SMTP_HOST>
MAIL_PORT=587
MAIL_USERNAME=<SMTP_USER>
MAIL_PASSWORD=<SMTP_PASSWORD>
MAIL_ENCRYPTION=tls

# Redis (if using)
REDIS_HOST=redis
REDIS_PASSWORD=<REDIS_PASSWORD>
REDIS_PORT=6379

# Security
SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Health Checks

**Application health endpoint**: `https://yourdomain.com/health`

**What it checks**:

- Database connectivity
- File system writability
- Queue workers status
- Cache availability

**Verify deployment**:

```bash
# Check health endpoint
curl https://yourdomain.com/health

# Expected response:
# {"status": "healthy", "database": "ok", "cache": "ok"}

# Check container status
docker ps --filter name=prod-

# Check logs
docker logs prod-php-1
docker logs prod-nginx-1
```

### Rollback Process

**If deployment fails**:

```bash
# Redeploy previous version
./deploy.sh --tag=v1.1.0

# Or manually
docker compose --project-name prod down
docker compose --project-name prod \
  --env-file .env.v1.1.0 \
  up -d
```

**Data safety**: MySQL volume persists, safe to rollback containers.

### Scaling Considerations

**Current architecture**: Single-server deployment

**Cloud-native scaling** (future):

- **PHP containers**: Can scale horizontally (load balancer)
- **Assets containers**: Can scale (CDN or load balancer)
- **Nginx**: Load balancer tier (multiple instances)
- **MySQL**: Vertical scaling or managed database service
- **Redis**: Managed service or cluster

### Production Requirements

**Minimum specs**:

- **CPU**: 2 cores
- **RAM**: 2GB (4GB recommended)
- **Storage**: 10GB (20GB+ for growth)
- **Docker**: 24.0+
- **Docker Compose**: 2.0+

**Recommended specs**:

- **CPU**: 4 cores
- **RAM**: 8GB
- **Storage**: 50GB+ (SSD)
- **Docker**: Latest stable
- **Backup**: Automated MySQL backups

---

## Configuration Management

### Environment Files

**Development** (`.env`):

- Auto-generated by `./mtav up`
- Based on `.env.template`
- Git-ignored (never commit)
- Contains: Database passwords, app keys, local ports

**Production** (deployed separately):

- Created manually in mtav-deploy repo
- Contains: Real credentials, production URLs
- Encrypted at rest (server-level)
- Never in version control

### Configuration Caching

**Development**: Config caching disabled

- Live changes reflected immediately
- Easier debugging
- Slight performance cost (acceptable)

**Production**: Config caching enabled

```bash
# Cache config (production optimization)
php artisan config:cache

# Clear cache (after config changes)
php artisan config:clear
```

### Port Customization

**Avoiding conflicts** (development):

Edit `.env` to change ports:

```bash
# Default ports
DOCKER_NGINX_PORT=8000
DOCKER_VITE_PORT=5173
DOCKER_MYSQL_PORT=3307
DOCKER_MAILHOG_WEB_PORT=8025
DOCKER_MAILHOG_SMTP_PORT=1025

# Custom ports (if defaults conflict)
DOCKER_NGINX_PORT=9000
DOCKER_VITE_PORT=3000
DOCKER_MYSQL_PORT=3308
DOCKER_MAILHOG_WEB_PORT=9025
DOCKER_MAILHOG_SMTP_PORT=2025
```

**After changing ports**:

```bash
./mtav down
./mtav up
```

### User/Group Mapping

**Automatic detection** (usually works):

```bash
# .env automatically sets
PUID=1000   # Your user ID
PGID=1000   # Your group ID
```

**Manual override** (if needed):

```bash
# Find your IDs
id -u  # User ID
id -g  # Group ID

# Set in .env
PUID=1001
PGID=1001
```

**Apply changes**:

```bash
./mtav fresh
```

---

## Troubleshooting & Common Issues

### Quick Fixes

**First resort** (solves 80% of issues):

```bash
./mtav fresh    # Nuclear option: rebuild everything
./mtav logs     # Check all logs
./mtav logs php # Check specific service
```

### Port Conflicts

**Symptoms**:

- "Address already in use" errors
- Containers fail to start
- Cannot access application

**Solution**:

```bash
# Check what's using the port
lsof -i :8000
sudo netstat -tulpn | grep :8000

# Option 1: Stop conflicting service
sudo systemctl stop <service>

# Option 2: Change MTAV ports (recommended)
# Edit .env
DOCKER_NGINX_PORT=9000
DOCKER_VITE_PORT=3000
DOCKER_MYSQL_PORT=3308

# Restart
./mtav down && ./mtav up
```

### File Permission Issues

**Symptoms**:

- "Permission denied" when writing files
- Storage errors
- Cache errors

**Solution**:

```bash
# Quick fix (from host)
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Proper fix (rebuild with correct mapping)
./mtav fresh

# Or update PUID/PGID in .env
PUID=$(id -u)
PGID=$(id -g)
./mtav fresh
```

### HMR Not Working

**Symptoms**:

- Changes not reflected in browser
- Manual refresh required
- Vite errors in console

**Solutions**:

```bash
# Restart assets container
./mtav down && ./mtav up

# Check Vite is running
./mtav logs assets

# Port conflict
# Edit .env: DOCKER_VITE_PORT=3000
./mtav down && ./mtav up

# Clear browser cache
# Hard reload: Ctrl+Shift+R (Linux/Windows) or Cmd+Shift+R (Mac)
```

### Database Connection Errors

**Symptoms**:

- "SQLSTATE[HY000] [2002] Connection refused"
- Migrations fail
- Cannot access data

**Solutions**:

```bash
# Check MySQL is running
./mtav status
docker ps | grep mysql

# Check MySQL logs
./mtav logs mysql

# Restart MySQL
./mtav down && ./mtav up

# Fresh database
./mtav artisan migrate:fresh --seed

# Check credentials in .env
DB_CONNECTION=mysql
DB_HOST=mysql     # Must be "mysql" (service name)
DB_PORT=3306      # Inside container (not 3307)
DB_DATABASE=mtav
DB_USERNAME=mtav
DB_PASSWORD=secret
```

### Composer/NPM Issues

**Symptoms**:

- "Class not found"
- "Module not found"
- Dependencies missing

**Solutions**:

```bash
# Reinstall PHP dependencies
./mtav composer install

# Reinstall JS dependencies
./mtav npm install

# Clear autoloader
./mtav composer dump-autoload

# Nuclear option
rm -rf vendor node_modules
./mtav composer install
./mtav npm install
```

### Container Won't Start

**Symptoms**:

- Container exits immediately
- Status shows "Exited (1)"

**Diagnosis**:

```bash
# Check logs
./mtav logs <service>

# Inspect container
docker inspect dev-<service>-1

# Check for port conflicts
./mtav status
```

**Solutions**:

```bash
# Rebuild specific service
docker compose -f .docker/compose.yml build --no-cache <service>
./mtav up

# Or rebuild everything
./mtav fresh
```

### Slow Performance

**Symptoms**:

- Slow page loads
- Slow asset compilation
- Slow database queries

**Solutions**:

```bash
# Clear Laravel caches
./mtav artisan cache:clear
./mtav artisan config:clear
./mtav artisan view:clear
./mtav artisan route:clear

# Optimize for development
./mtav artisan optimize:clear

# Check Docker resources
# Docker Desktop → Settings → Resources
# Increase CPU/RAM allocation

# Check disk space
df -h
docker system df
```

### Git Hooks Failing

**Symptoms**:

- Cannot commit
- Tests fail during commit
- Linting errors block commit

**Solutions**:

```bash
# Run tests manually
./mtav test --once

# Fix linting
./mtav npm run lint

# Emergency bypass (not recommended)
git commit --no-verify -m "Emergency fix"

# Disable hooks temporarily
rm -rf .husky
# (Reinstall: ./mtav npm run prepare)
```

### InertiaUI Modal Issues

**Symptoms**:

- Modals not opening
- Props not passing
- TypeScript errors

**Solution** (version mismatch):

```bash
# Check versions
./mtav composer show inertiaui/modal
./mtav npm list @inertiaui/modal-vue

# Sync versions
./mtav composer update inertiaui/modal
./mtav npm update @inertiaui/modal-vue

# Verify sync
./mtav composer show inertiaui/modal
./mtav npm list @inertiaui/modal-vue
```

### Complete Reset

**When all else fails**:

```bash
# Nuclear option: destroy everything
./mtav down
docker system prune -a --volumes

# Warning: Destroys ALL Docker data (not just MTAV)
# Better: Target specific volumes
docker volume rm mtav_mysql
docker volume rm mtav_storage

# Rebuild from scratch
./mtav up
```

---

# Part 3: Development & Testing

## Development & Testing Strategy

### Lottery Implementation Approach

**Production**: External API with Mathematical Optimization

- **Purpose**: Use numerical methods (Hungarian algorithm, optimization) to maximize fairness
- **Status**: API specifications pending
- **Input**: Project data (families, units, preferences by unit type)
- **Output**: Optimal assignments with satisfaction scores
- **When**: Production deployment only

**Development**: Dummy Lottery Service

- **Purpose**: Enable TDD and development without external API dependency
- **Implementation**: Simple random assignment within constraints
- **Same Interface**: Accepts exact same input format as production API
- **Same Output**: Returns exact same output format as production API
- **Constraints Enforced**:
  - Only assigns units to families of matching UnitType
  - Respects one unit per family
  - Returns unassigned families/units if mismatch
  - Generates dummy satisfaction scores

**Example Dummy Lottery Logic**:

```php
class DummyLotteryService
{
    public function assignUnits(array $lotteryData): array
    {
        $assignments = [];
        $unassignedFamilies = [];
        $unassignedUnits = [];

        foreach ($lotteryData['unit_types'] as $unitType) {
            $units = $unitType['units'];
            $families = $unitType['families'];

            // Shuffle for randomness
            shuffle($units);
            shuffle($families);

            // Assign one-to-one
            for ($i = 0; $i < min(count($units), count($families)); $i++) {
                $assignments[] = [
                    'family_id' => $families[$i]['id'],
                    'unit_id' => $units[$i]['id'],
                    'satisfaction_score' => rand(50, 100) / 100, // Random 0.5-1.0
                ];
            }

            // Track unassigned
            if (count($families) > count($units)) {
                $unassignedFamilies = array_merge(
                    $unassignedFamilies,
                    array_slice($families, count($units))
                );
            }
            if (count($units) > count($families)) {
                $unassignedUnits = array_merge(
                    $unassignedUnits,
                    array_slice($units, count($families))
                );
            }
        }

        return [
            'success' => true,
            'assignments' => $assignments,
            'unassigned_families' => array_column($unassignedFamilies, 'id'),
            'unassigned_units' => array_column($unassignedUnits, 'id'),
            'overall_satisfaction' => 0.75, // Dummy average
            'metadata' => [
                'algorithm' => 'random_dummy',
                'execution_time_ms' => 1,
            ],
        ];
    }
}
```

**Configuration**:

```php
// config/lottery.php
return [
    'driver' => env('LOTTERY_DRIVER', 'dummy'), // 'dummy' or 'api'

    'drivers' => [
        'dummy' => [
            'class' => \App\Services\Lottery\DummyLotteryService::class,
        ],
        'api' => [
            'class' => \App\Services\Lottery\ExternalLotteryService::class,
            'url' => env('LOTTERY_API_URL'),
            'key' => env('LOTTERY_API_KEY'),
            'timeout' => 30,
        ],
    ],
];
```

### Testing Strategy

**Unit Tests**:

- ✅ Test each entity model (validation, relationships, scopes)
- ✅ Test service classes in isolation
- ✅ Test DummyLotteryService output format
- ✅ Mock external API for ExternalLotteryService tests

**Feature Tests**:

- ✅ Test authorization policies for all operations
- ✅ Test controller CRUD operations with dummy lottery
- ✅ Test lottery execution flow end-to-end with dummy
- ✅ Test preference submission and validation
- ✅ Test unit assignment business rules

**Integration Tests**:

- ✅ Test complete workflow: Project setup → UnitTypes → Units → Families → Preferences → Lottery → Assignments
- ✅ Use dummy lottery to verify entire flow
- ✅ Test edge cases (unassigned families/units)

**Test Data Factories**:

- ✅ UnitTypeFactory
- ✅ UnitFactory (with characteristics)
- ✅ FamilyPreferenceFactory
- ✅ BlueprintFactory (if needed)

**TDD Approach for Remaining Features**:

1. **Write test first** - Define expected behavior
2. **Use dummy lottery** - Don't wait for external API
3. **Implement minimal code** - Make test pass
4. **Refactor** - Clean up implementation
5. **Integration** - Swap dummy for real API later (same interface)

**Benefits**:

- ✅ Can develop and test without external dependencies
- ✅ Fast test execution (no API calls)
- ✅ Predictable test scenarios (random seed control)
- ✅ Easy to swap for production API (same contract)

### API Integration Contract

**When API specs are received, ensure**:

1. **Input Format Matches**:
   - DummyLotteryService accepts same structure
   - Validation rules match API expectations
   - All required fields present

2. **Output Format Matches**:
   - DummyLotteryService returns same structure
   - All fields application depends on are present
   - Error responses follow same format

3. **Tests Cover API Contract**:
   - Test that app sends correct format
   - Test that app handles successful responses
   - Test that app handles error responses
   - Test timeout scenarios
   - Test validation errors from API

4. **Graceful Fallback**:
   - If API unavailable, clear error message
   - Don't lose lottery data if API fails
   - Retry logic for transient failures
   - Admin can re-trigger if needed

### Feature Flags (Optional)

```php
// Feature flag for lottery
if (Feature::active('unit-lottery')) {
    // Show lottery UI, allow execution
} else {
    // Hide lottery features, show "coming soon"
}
```

**Use Cases**:

- Gradual rollout to projects
- Testing in production with subset of users
- Emergency disable if issues found

---

### TDD Roadmap for Remaining Features

This section provides a roadmap for implementing the remaining features using Test-Driven Development. Each feature should have tests written FIRST, then implementation follows.

#### Phase 0: User Identity & Legal Compliance (Foundation)

**Models & Migrations**:

- [ ] Add `legal_id` column to `users` table (VARCHAR, NULLABLE, UNIQUE)
- [ ] Add validation rules for `legal_id` format (optional, country/region-specific)
- [ ] Update `User` model to handle `legal_id` field

**Business Rules**:

- Legal ID document: CI (Cédula de Identidad) in Uruguay, DNI in Argentina/Spain, varies by country
- **Policy-based requirement**: Each project decides if members must provide it (not app-enforced)
- **Privacy**: Hidden from other members (only viewable by self and admins)
- **Immutability**: Members cannot edit once set (prevents fraud)
- **Admin correction**: Admins can update to fix mistakes during data entry
- **Uniqueness**: One CI per person (database constraint)

**Tests to Write** (`tests/Unit/Models/UserTest.php`):

```php
it('allows nullable legal_id')
it('enforces unique legal_id')
it('legal_id is hidden from other members in JSON') // AppendAttribute control
it('member can view own legal_id')
it('admin can view member legal_id')
```

**Tests to Write** (`tests/Feature/Policies/UserPolicyTest.php`):

```php
it('allows member to update own profile but not legal_id')
it('allows admin to update member legal_id in managed project')
it('prevents admin from updating member legal_id in unmanaged project')
it('prevents member from viewing other member legal_id')
```

**Tests to Write** (`tests/Feature/Controllers/ProfileControllerTest.php`):

```php
it('member can set legal_id if null')
it('member cannot change legal_id once set')
it('admin can update member legal_id')
it('legal_id does not appear in member list for other members')
```

---

#### Phase 1: Unit Type Management (Foundation)

**Models & Migrations**:

- [ ] Create `unit_types` table migration
- [ ] Create `UnitType` model with relationships
- [ ] Add `unit_type_id` to `families` table
- [ ] Add `unit_type_id` to `units` table

**Tests to Write** (`tests/Unit/Models/UnitTypeTest.php`):

```php
it('belongs to a project')
it('has many units')
it('has many families')
it('requires a project_id')
it('requires a name')
it('name must be unique within project')
it('allows same name in different projects')
it('can be soft deleted') // or prevent deletion?
```

**Tests to Write** (`tests/Feature/Controllers/UnitTypeControllerTest.php`):

```php
it('lists unit types for current project') // viewAny
it('shows unit type details') // view
it('creates unit type in managed project') // store
it('prevents creating unit type in unmanaged project')
it('updates unit type in managed project')
it('prevents updating unit type in unmanaged project')
it('deletes unit type if no dependencies') // or prevents?
```

**Authorization Tests** (`tests/Feature/Policies/UnitTypePolicyTest.php`):

```php
it('allows superadmin all actions')
it('allows admin to manage unit types in their projects')
it('prevents admin from managing unit types in other projects')
it('allows members to view unit types in their project')
it('prevents members from creating/updating/deleting unit types')
```

#### Phase 2: Enhanced Unit Management

**Migrations**:

- [ ] Add `unit_type_id`, `square_meters`, `bedrooms`, `bathrooms`, `has_garden`, `has_balcony` to `units` table
- [ ] Add `family_id` (nullable) to `units` table

**Tests to Update** (`tests/Unit/Models/UnitTest.php`):

```php
it('belongs to a unit type')
it('requires unit_type_id')
it('requires square_meters')
it('requires bedrooms and bathrooms counts')
it('has garden and balcony flags')
it('can belong to a family after lottery') // nullable family_id
it('number is unique within project')
it('validates unit_type belongs to same project')
```

**Business Rule Tests** (`tests/Feature/BusinessLogic/UnitAssignmentTest.php`):

```php
it('can only be assigned to family of matching unit type')
it('prevents assigning unit to family of different unit type')
it('allows unit without family_id (pre-lottery)')
it('prevents duplicate family assignments') // one family = one unit
```

#### Phase 3: Family-UnitType Assignment

**Tests to Add** (`tests/Unit/Models/FamilyTest.php`):

```php
it('can be assigned to a unit type')
it('unit type must be in same project as family')
it('can have many preferences for units')
it('can have one assigned unit after lottery')
```

**Controller Tests** (`tests/Feature/Controllers/FamilyControllerTest.php`):

```php
it('allows admin to assign family to unit type')
it('prevents assigning family to unit type from different project')
it('allows family to update own name/avatar but not unit_type_id')
```

#### Phase 4: Family Preferences System

**Models & Migrations**:

- [ ] Create `family_preferences` table migration
- [ ] Create `FamilyPreference` model

**Tests to Write** (`tests/Unit/Models/FamilyPreferenceTest.php`):

```php
it('belongs to a family')
it('belongs to a unit')
it('requires a rank')
it('rank must be unique per family')
it('unit must be unique per family')
it('validates unit is of family unit type')
it('validates unit is unassigned')
it('validates rank is within allowed range') // e.g., 1-5
```

**Controller Tests** (`tests/Feature/Controllers/FamilyPreferenceControllerTest.php`):

```php
it('allows family members to create preferences for own family')
it('prevents members from creating preferences for other families')
it('allows admin to create preferences for any family')
it('prevents preferring units of wrong unit type')
it('prevents duplicate ranks')
it('prevents duplicate units')
it('allows updating preferences before lottery')
it('allows deleting preferences before lottery')
```

**Authorization Tests** (`tests/Feature/Policies/FamilyPreferencePolicyTest.php`):

```php
it('allows members to manage their own family preferences')
it('prevents members from managing other family preferences')
it('allows admins to manage preferences in their projects')
it('members can only view their own preferences') // prevent gaming
```

#### Phase 5: Blueprint Management (Optional for MVP)

**Models & Migrations**:

- [ ] Create `blueprints` table migration
- [ ] Create `Blueprint` model (1-to-1 with Project)

**Tests to Write** (`tests/Unit/Models/BlueprintTest.php`):

```php
it('belongs to exactly one project')
it('can store svg_data')
it('can store json_data')
it('can store file_path')
it('allows any one format or combination')
```

**Controller Tests** (`tests/Feature/Controllers/BlueprintControllerTest.php`):

```php
it('allows admin to create blueprint for their project')
it('allows admin to update blueprint')
it('allows members to view blueprint of their project')
it('prevents creating multiple blueprints per project') // 1-to-1
```

#### Phase 6: Lottery Service Implementation

**Service Classes**:

- [ ] `App\Services\Lottery\LotteryServiceInterface`
- [ ] `App\Services\Lottery\DummyLotteryService`
- [ ] `App\Services\Lottery\ExternalLotteryService` (stub for now)

**Tests to Write** (`tests/Unit/Services/DummyLotteryServiceTest.php`):

```php
it('accepts correct input format')
it('returns correct output format')
it('assigns units to families of matching type')
it('respects one unit per family constraint')
it('returns unassigned families if more families than units')
it('returns unassigned units if more units than families')
it('generates satisfaction scores')
it('includes metadata in response')
it('handles empty unit types')
it('handles families with no preferences') // random assignment
```

**Integration Tests** (`tests/Feature/Services/LotteryServiceIntegrationTest.php`):

```php
it('processes complete project lottery data')
it('handles multiple unit types in one call')
it('validates input before processing')
it('returns error for invalid data')
```

#### Phase 7: Lottery Execution Workflow

**Controller/Action Tests** (`tests/Feature/Controllers/LotteryControllerTest.php`):

```php
it('allows admin to initiate lottery for their project')
it('prevents lottery if unit types not defined')
it('prevents lottery if families not assigned to unit types')
it('validates all families have submitted preferences') // or allows?
it('calls lottery service with correct data format')
it('parses lottery service response')
it('commits assignments to database')
it('sets unit.family_id for each assignment')
it('logs lottery execution') // if history tables implemented
it('notifies families of assignments') // if notifications implemented
it('prevents non-admins from running lottery')
it('allows re-running lottery with confirmation')
```

**Business Logic Tests** (`tests/Feature/BusinessLogic/LotteryExecutionTest.php`):

```php
it('processes lottery results one unit type at a time')
it('validates assignments match unit types')
it('prevents assigning unit to wrong unit type family')
it('handles partial assignments gracefully')
it('maintains data integrity if service fails')
it('allows admin to preview results before committing')
it('allows admin to cancel after preview')
```

#### Phase 8: Post-Lottery Features

**Tests to Write**:

```php
// tests/Feature/Views/UnitAssignmentViewTest.php
it('shows family their assigned unit')
it('shows unit details and characteristics')
it('shows satisfaction score if available')
it('shows "not assigned" message for unassigned families')

// tests/Feature/Controllers/UnitAssignmentControllerTest.php
it('prevents manual unit assignment before lottery')
it('allows admin manual override after lottery') // if allowed
it('prevents changing assignments without permission')

// tests/Feature/BusinessLogic/PostLotteryConstraintsTest.php
it('prevents deleting units that are assigned')
it('prevents deleting families that have units')
it('prevents changing unit type of assigned unit')
it('prevents changing family unit type after assignment')
```

#### Test Organization Summary

**Unit Tests** (Fast, isolated):

- `tests/Unit/Models/UnitTypeTest.php`
- `tests/Unit/Models/UnitTest.php` (enhanced)
- `tests/Unit/Models/FamilyTest.php` (enhanced)
- `tests/Unit/Models/FamilyPreferenceTest.php`
- `tests/Unit/Models/BlueprintTest.php`
- `tests/Unit/Services/DummyLotteryServiceTest.php`

**Feature Tests** (Integration, database):

- `tests/Feature/Controllers/UnitTypeControllerTest.php`
- `tests/Feature/Controllers/UnitControllerTest.php` (enhanced)
- `tests/Feature/Controllers/FamilyPreferenceControllerTest.php`
- `tests/Feature/Controllers/BlueprintControllerTest.php`
- `tests/Feature/Controllers/LotteryControllerTest.php`
- `tests/Feature/Policies/UnitTypePolicyTest.php`
- `tests/Feature/Policies/FamilyPreferencePolicyTest.php`
- `tests/Feature/BusinessLogic/UnitAssignmentTest.php`
- `tests/Feature/BusinessLogic/LotteryExecutionTest.php`
- `tests/Feature/BusinessLogic/PostLotteryConstraintsTest.php`

**End-to-End Tests** (Full workflow):

- `tests/Feature/Workflows/CompleteLotteryWorkflowTest.php`
  ```php
  it('completes entire lottery workflow from setup to assignment', function() {
      // 1. Create project
      // 2. Create unit types
      // 3. Create units
      // 4. Create families
      // 5. Assign families to unit types
      // 6. Families submit preferences
      // 7. Admin runs lottery
      // 8. Verify assignments
      // 9. Families see their units
  });
  ```

#### Implementation Order

**Sprint 0**: User Identity & Legal Compliance (Optional - can be done anytime)

1. Add `legal_id` migration to users table
2. Update User model with `legal_id` handling
3. Update ProfileController to handle `legal_id` (set once, admin can correct)
4. Add authorization rules (members can't edit, admins can)
5. Hide `legal_id` from other members in API responses
6. **Tests**: All User/Legal ID tests passing

**Sprint 1**: Foundation

1. UnitType model, migration, factory
2. Enhanced Unit model with characteristics
3. Family.unit_type_id relationship
4. Basic CRUD controllers for UnitType
5. Authorization policies
6. **Tests**: All Unit/Model tests passing

**Sprint 2**: Preferences

1. FamilyPreference model, migration, factory
2. Preference controller (CRUD)
3. Validation (unit type match, uniqueness)
4. Authorization (family members vs admins)
5. **Tests**: All Preference tests passing

**Sprint 3**: Lottery Service

1. LotteryServiceInterface
2. DummyLotteryService implementation
3. Config/service provider setup
4. Input/output validation
5. **Tests**: All Service tests passing

**Sprint 4**: Lottery Execution

1. LotteryController (execute action)
2. Precondition validation
3. Service integration
4. Result parsing and DB updates
5. **Tests**: All Execution tests passing

**Sprint 5**: UI & Polish

1. Blueprint model (if time)
2. UI for preferences
3. UI for lottery dashboard
4. UI for results
5. **Tests**: All E2E tests passing

**Sprint 6**: Production Ready

1. ExternalLotteryService stub
2. API contract tests
3. Error handling & retries
4. Logging & audit trail
5. **Tests**: All integration tests passing

#### Success Metrics

After each sprint, verify:

- ✅ All new tests are GREEN
- ✅ No existing tests broken (regression)
- ✅ Test coverage > 80% for new code
- ✅ All TODO tests have clear implementation path
- ✅ Documentation updated

#### Benefits of This TDD Approach

1. **Clear path forward** - Each test defines what needs to be built
2. **Prevents scope creep** - Tests enforce boundaries
3. **Living documentation** - Tests show how features work
4. **Confidence** - Every feature proven by tests
5. **Refactoring safety** - Can improve code without breaking features
6. **Future-proof** - Easy to swap dummy lottery for real API

---

## Edge Cases & Constraints

### Orphaned Members

**Definition**: Member with `family_id = null`

**How Created**:

- Family deleted while member exists?
- Member removed from family?
- Member created without family?

**Constraints**:

- **QUESTION**: Is `users.family_id` nullable?
- **QUESTION**: Can orphaned members exist?
- If yes, what project are they in?
  - No family → no family.project_id → which project?
  - Use their active project_user entry?

**Behavior**:

- Can orphaned members log in?
- What do they see?
- Can they be assigned to a new family?
- Should orphans be prevented?

### Members Without Active Project

**How Created**:

- Member created but not added to any project?
- All project memberships set to inactive?
- Family has no project (contradicts NOT NULL constraint)?

**Constraints**:

- Every member should have exactly one active project
- Derived from their family's project

**QUESTION**: Can this state exist legitimately?

- During creation process?
- Temporarily during family move?

### Admins Without Projects

**How Created**:

- Admin created but not assigned to any project
- Admin removed from all projects

**Behavior**:

- Can they log in?
- What do they see (no projects to manage)?
- Are they useless?
- Should this be prevented?

**QUESTION**: Should admins be required to manage at least one project?

### Projects Without Admins

**How Created**:

- Project created by superadmin
- All admins removed from project

**Behavior**:

- Project still exists but no admins to manage it
- Only superadmins can manage it
- Families/members can still exist in it

**QUESTION**: Is this allowed? Desirable?

### Families Without Members

**How Created**:

- Family created but no members added
- All members deleted/removed

**Behavior**:

- Empty family exists
- Can it be deleted?
- Should family auto-delete when last member leaves?

**QUESTION**: Are empty families allowed?

### Circular Assignments

**Scenario**: Admin assigned to Project A, manages Family in Project A, member of that Family

**QUESTION**: Can an admin be a member simultaneously?

- `is_admin = true` but also has `family_id`?
- Seems contradictory (admins have `family_id = null`)
- Should be prevented?

### Family Name Uniqueness

**Constraint**: `UNIQUE(project_id, name)`

**Implications**:

- Family names must be unique within a project
- Same name OK in different projects
- **QUESTION**: Case-sensitive or insensitive?

### Project Name Uniqueness

**QUESTION**: Are project names unique globally?

- Or can multiple projects have same name?
- **GUESS**: Should be unique globally

---

## Open Questions

✅ **ALL QUESTIONS ANSWERED** - This section is now resolved.

All critical questions, implementation details, and business logic clarifications have been integrated throughout the document. See updated sections for comprehensive answers covering:

- Member/family atomicity and orphan prevention
- Admin creation and project assignment processes
- Multi-project context behavior for admins
- Family project switching and cascade behavior
- Authorization patterns (403 vs 302)
- Deletion and soft-delete cascades
- Index filtering in multi-project contexts
- Unit-family relationships and lottery system
- Project/unit states and lifecycle
- Member operations and intra-family restrictions
- Lottery execution process and audit requirements
- Blueprint management and authorization

### Remaining TODOs

**Pending Clarifications** (marked with ⚠️ TODO throughout document):

1. **Email verification workflow** - Define requirements for verification
2. **Unit/Project state transitions** - Comprehensive state machine rules
3. **Events entity** - Community events with RSVP functionality
4. **Media uploads** - Profile images, family photos, project gallery
5. **Lottery strategy implementations** - Dev and Production strategies (currently only Mock)
6. **Real-time notifications** - Push notifications for lottery results, updates

---

## Next Steps

### For Review

✅ **Document is now comprehensive and ready for:**

1. ✅ **Alignment with test suite** - Use as single source of truth for test development
2. ✅ **Implementation planning** - Follow TDD roadmap section
3. ✅ **Authorization matrix validation** - Implement policies based on documented rules
4. ✅ **Lottery system development** - Follow strategy pattern architecture
5. ✅ **Audit/logging implementation** - Ensure comprehensive tracking per requirements
6. ✅ **Add any missing rules** not covered here

### After Review

Once this document is finalized, I will:

1. Update all tests to match the specifications
2. Mark tests as TODO for unimplemented features
3. Mark tests as SKIP for bugs in code
4. Add new tests for any missing coverage
5. Ensure authorization matrix is fully tested
6. Validate family atomicity rules are tested
7. Create comprehensive test scenarios for edge cases

---

## Appendix: Database Schema Reference

### users

```sql
CREATE TABLE users (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  family_id BIGINT NULLABLE?, -- FK to families
  email VARCHAR(255) UNIQUE NOT NULL,
  phone VARCHAR(255) NULLABLE,
  firstname VARCHAR(255) NOT NULL?,
  lastname VARCHAR(255) NOT NULL?,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULLABLE,
  is_admin BOOLEAN DEFAULT false,
  darkmode BOOLEAN NULLABLE,
  email_verified_at TIMESTAMP NULLABLE,
  remember_token VARCHAR(100) NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE ?
);

CREATE INDEX idx_users_lastname_firstname ON users(lastname, firstname);
```

### projects

```sql
CREATE TABLE projects (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL, -- UNIQUE?
  active BOOLEAN DEFAULT true?,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### families

```sql
CREATE TABLE families (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  project_id BIGINT NOT NULL, -- FK to projects
  name VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  UNIQUE(project_id, name),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

### project_user (pivot)

```sql
CREATE TABLE project_user (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  project_id BIGINT NOT NULL,
  active BOOLEAN DEFAULT true,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

### units

```sql
CREATE TABLE units (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  project_id BIGINT NOT NULL,
  unit_type_id BIGINT NOT NULL,
  family_id BIGINT NULLABLE, -- NULL until lottery assigns
  number VARCHAR(255) NOT NULL,
  identifier VARCHAR(255) NULLABLE,
  square_meters DECIMAL(10,2) NOT NULL,
  bedrooms INTEGER NOT NULL,
  bathrooms INTEGER NOT NULL,
  has_garden BOOLEAN DEFAULT false,
  has_balcony BOOLEAN DEFAULT false,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  UNIQUE(project_id, number),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (unit_type_id) REFERENCES unit_types(id) ON DELETE RESTRICT,
  FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE SET NULL
);
```

### unit_types

```sql
CREATE TABLE unit_types (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  project_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  UNIQUE(project_id, name),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

### families (updated with unit_type_id)

```sql
CREATE TABLE families (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  project_id BIGINT NOT NULL,
  unit_type_id BIGINT NULLABLE, -- Assigned by admin, determines eligibility
  name VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  UNIQUE(project_id, name),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (unit_type_id) REFERENCES unit_types(id) ON DELETE SET NULL
);
```

### blueprints

```sql
CREATE TABLE blueprints (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  project_id BIGINT NOT NULL UNIQUE, -- One blueprint per project
  name VARCHAR(255) NULLABLE,
  description TEXT NULLABLE,
  svg_data TEXT NULLABLE,
  json_data JSON NULLABLE,
  file_path VARCHAR(255) NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

### family_preferences

```sql
CREATE TABLE family_preferences (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  family_id BIGINT NOT NULL,
  unit_id BIGINT NOT NULL,
  rank INTEGER NOT NULL, -- 1 = first choice, 2 = second, etc.
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  UNIQUE(family_id, unit_id), -- Can't prefer same unit twice
  UNIQUE(family_id, rank), -- Each rank used once
  FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
);
```

### lottery_executions (Optional - for audit/history)

```sql
CREATE TABLE lottery_executions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  project_id BIGINT NOT NULL,
  executed_by BIGINT NOT NULL, -- Admin user who ran lottery
  executed_at TIMESTAMP NOT NULL,
  algorithm VARCHAR(255) NULLABLE,
  overall_satisfaction DECIMAL(5,4) NULLABLE, -- 0.0000 to 1.0000
  api_response JSON NULLABLE, -- Full API response for audit
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (executed_by) REFERENCES users(id) ON DELETE RESTRICT
);
```

### unit_assignments (Optional - for audit/history)

```sql
CREATE TABLE unit_assignments (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  lottery_execution_id BIGINT NOT NULL,
  family_id BIGINT NOT NULL,
  unit_id BIGINT NOT NULL,
  satisfaction_score DECIMAL(5,4) NULLABLE, -- How well matched to preferences
  family_preference_rank INTEGER NULLABLE, -- 1, 2, 3, etc. or NULL if random
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  FOREIGN KEY (lottery_execution_id) REFERENCES lottery_executions(id) ON DELETE CASCADE,
  FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
);
```

---

**End of Document**

_This is a living document. Please review, correct, and enhance with your domain knowledge._
