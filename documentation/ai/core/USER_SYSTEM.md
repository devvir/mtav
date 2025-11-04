# User System & Actor Types

## Overview

MTAV uses **Single Table Inheritance (STI)** for user management with three distinct actor types:

```
Superadmin (highest privileges)
    ↓ inherits from + bypasses policies
Admin (project-scoped management)
    ↓ separate from
Member (family-bound participation)
```

**Key Principle**: Admins and Members are **mutually exclusive** - a user cannot be both.

---

## Database Schema

### users table

```sql
id (PK, auto-increment)
family_id (FK to families, NULLABLE - null for admins, required for members)
email (VARCHAR, UNIQUE, NOT NULL)
phone (VARCHAR, NULLABLE)
firstname (VARCHAR, NOT NULL - required for all users)
lastname (VARCHAR, NULLABLE - optional for all users)
legal_id (VARCHAR, NULLABLE - FUTURE FEATURE)
password (VARCHAR, NOT NULL - hashed)
avatar (VARCHAR, NULLABLE)
is_admin (BOOLEAN, DEFAULT false - PRIMARY TYPE DISCRIMINATOR)
darkmode (BOOLEAN, NULLABLE)
email_verified_at (TIMESTAMP, NULLABLE)
invitation_accepted_at (TIMESTAMP, NULLABLE)
remember_token (VARCHAR, NULLABLE)
created_at, updated_at, deleted_at (soft delete)

INDEXES:
  - PRIMARY KEY (id)
  - UNIQUE (email)
  - INDEX (family_id)
  - INDEX (is_admin)
  - INDEX (deleted_at)
```

### project_user pivot table

```sql
id (PK)
user_id (FK to users)
project_id (FK to projects)
active (BOOLEAN - TRUE = current, FALSE = historical)
created_at, updated_at

SEMANTICS:
  - Admins: Can have MULTIPLE active projects
  - Members: EXACTLY ONE active project (business rule)
  - active=false = historical membership (audit trail)
```

---

## Actor Type: Member

**Definition**: User with `is_admin = false`, MUST belong to exactly one Family

**Database Representation**:
- `is_admin = false` (PRIMARY TYPE DISCRIMINATOR)
- `family_id` REQUIRED (business rule, nullable at DB level due to shared table)
- Exactly ONE `active = true` entry in `project_user` (business rule)

**Identification**:
```php
$user->isMember() // true if is_admin = false
```

**Family Atomicity Principle**:
- Family is the atomic unit of participation, not individual member
- Member's project membership mirrors their family's project
- Member cannot join/leave projects independently
- Member switching projects requires switching families (admin-mediated)

**Capabilities** (within family's project):

✅ **CAN DO**:
- View all families, members, units in current project
- View and update own profile (NOT email, that's admin-only)
- Manage family preferences (any family member can update, all logged)
- Upload media to project
- RSVP to events
- Invite family members to same family
- Soft-delete self (leave system)

❌ **CANNOT DO**:
- Switch projects (family atomicity - only via family switch)
- Leave family independently (must switch families or delete self)
- Create/delete families
- Update/delete other members
- Manage units, unit types, execute lottery, create events
- Change own `family_id` directly (admin-mediated only)

**Project Scoping**:
- Universe = single active project ONLY (family's project)
- Always in single-project context (automatic, no switching)
- Current project auto-set to `family.project_id`

**Member Creation Workflows**:

1. **Admin-created**:
   - Admin creates member in any family in managed project
   - Member receives invitation email
   - Member verifies email and sets password

2. **Member-invited** (family invitation):
   - Member invites relative to join their family
   - `family_id` auto-set to inviter's family (hidden, forced)
   - `project_id` auto-set to inviter's project (hidden, forced)

**Member Family Switch** (admin-mediated):
1. Admin moves member to different family
2. If new family in different project:
   - Old project membership set `active = false`
   - New project membership created with `active = true`
3. Cannot empty source family
4. Member cannot initiate this action

---

## Actor Type: Admin

**Definition**: User with `is_admin = true`, manages one or more housing cooperative projects

**Database Representation**:
- `is_admin = true` (PRIMARY TYPE DISCRIMINATOR)
- `family_id = NULL` (CONSTRAINT: admins MUST NOT have a family)
- Can have MULTIPLE `active = true` entries in `project_user`

**Identification**:
```php
$user->isAdmin() // true if is_admin = true (includes superadmins)
```

**Project Scoping**:
- Universe = set of actively managed projects ONLY
- Can have multi-project context (manages 2+ projects) OR single-project context (manages 1 project)
- **Project scope is absolute** - cannot access unmanaged projects

**Multi-Project vs Single-Project Context**:

**Admin managing 1 project**:
- Automatically enters single-project context
- Landing page: Dashboard
- All indexes filtered to that project

**Admin managing 2+ projects**:
- Starts in multi-project context (no current project selected)
- Landing page: Projects index
- Must select project to enter single-project context
- Can switch between managed projects freely

**Capabilities** (within managed projects):

✅ **CAN DO**:
- Create other admins (can only assign to projects they manage)
- Manage families (create, update, delete)
- Manage members (create/invite, update, delete)
- Manage units, unit types, blueprints
- Manage preferences (view, update on behalf of families)
- Execute lottery
- Create events, upload media
- Switch families between managed projects
- Leave projects (set own `active = false` for project)

❌ **CANNOT DO**:
- Create projects (superadmin-only)
- Add themselves to new projects (superadmin assigns)
- Soft-delete themselves (should leave all projects instead)
- Delete other admins (superadmin-only)
- Invalidate lottery results (superadmin-only)
- Manual unit reassignment post-lottery
- Access unassigned projects

**Admin Creation Workflow**:
1. Superadmin or existing admin creates new admin user
2. Creator assigns admin to projects they manage (or all for superadmin)
3. New admin receives invitation email
4. New admin verifies email and sets password
5. New admin can immediately manage assigned projects

---

## Actor Type: Superadmin

**Definition**: Admin whose email is listed in `config('auth.superadmins')` array

**Database Representation**:
- `email` MUST be in `config('auth.superadmins')` array
- `is_admin = true` (REQUIRED - superadmins are always admins)
- `family_id = NULL` (constraint: admins MUST NOT have a family)

**Identification**:
```php
$user->isSuperadmin() // true if email in config array AND is_admin = true
```

**Authorization Behavior**:
- ALL policies bypassed via `Gate::before()`
- Returns `true` for any authorization check before policy methods run
- Superadmin authorization happens at framework level, not policy level

**Capabilities**:

**Inherits all admin capabilities PLUS**:
- ✅ Access ANY project (no scope restrictions)
- ✅ Create projects
- ✅ Assign admins to any project
- ✅ Delete admins
- ✅ Perform database corrections
- ✅ Invalidate lottery results (rare, exceptional cases)
- ✅ Override any business constraint

**Restrictions**:
- ❌ Cannot delete themselves (prevent system lockout)
- ❌ Must remain as admins (removing admin status breaks superadmin)

**Use Cases**:
- Initial system setup (create first projects, assign first admins)
- Emergency interventions (data corrections, lottery invalidation)
- Cross-project oversight
- Exceptional case handling

---

## Model Implementation

### User (Base Model)

**Pattern**: Single Table Inheritance (STI)
**Discriminator**: `is_admin` field

**Validation Rules**:
- `firstname`: REQUIRED, string, max:255
- `lastname`: OPTIONAL, nullable, max:255
- `email`: REQUIRED, unique, email format
- `password`: REQUIRED, min:8, confirmed
- `family_id`: Business rule - REQUIRED for members, NULL for admins
- `is_admin`: BOOLEAN, set at creation, cannot change after

**Relationships**:
```php
// User belongs to Family (members only)
public function family(): BelongsTo

// User belongs to many Projects (via pivot)
public function projects(): BelongsToMany

// Active projects only
public function activeProjects(): BelongsToMany
```

**Model Methods**:
```php
public function isSuperadmin(): bool
public function isAdmin(): bool
public function isMember(): bool
public function toAdmin(): ?Admin
public function toMember(): ?Member
```

**Soft Delete Behavior**:
- Uses `SoftDeletes` trait
- Setting `deleted_at` marks user as deleted
- Does NOT cascade to relationships (preserves audit trail)
- Can be restored with `restore()` method

### Admin Model

**Extends**: User
**Global Scope**: `where('is_admin', true)`

**Additional Methods**:
- Project management helpers
- Admin-specific relationships

### Member Model

**Extends**: User
**Global Scope**: `where('is_admin', false)`

**Additional Methods**:
- Family-specific helpers
- Member-specific relationships

---

## Key Constraints & Rules

### Type Immutability
- Cannot change `is_admin` after user creation
- Once admin, always admin (or deleted)
- Once member, always member (or deleted)

### Family Membership
- **Admins**: MUST have `family_id = NULL`
- **Members**: MUST have `family_id` set (business rule)
- Members cannot exist without family (orphan prevention)

### Project Membership
- **Admins**: Can manage multiple projects simultaneously
- **Members**: EXACTLY ONE active project (matches family's project)
- Historical memberships preserved with `active = false`

### Pivot Table Immutability
- Once entry created in `project_user`, ONLY `active` flag can be modified
- Never update `user_id`, `project_id`, or `created_at`
- Never delete entries (preserve history)

### Soft Delete Cascade
- User soft-delete does NOT cascade to relationships
- Preserves complete audit trail
- Relationships remain intact for historical queries

---

## Authorization Summary

**Query Level** (Global Scopes):
- Superadmin: See everything (no scope)
- Admin: See only managed projects
- Member: See only own project

**Action Level** (Policies):
- Superadmin: Bypass all policies via `Gate::before()`
- Admin: Resource-specific permissions
- Member: Limited to self and family operations

See SCOPING.md for detailed authorization matrix and global scope implementation.
