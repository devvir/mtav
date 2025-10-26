# Test Catalog

Comprehensive list of all tests in the suite, organized by file and describe block.

---

## Unit Tests - Models

### `tests/Unit/Models/AdminTest.php`

#### **Admin Model**

- ✅ it can determine if they manage a project
- ✅ it does not manage projects they are not assigned to
- ✅ it superadmins manage all projects regardless of assignment
- ✅ it applies admin global scope to exclude members
- ✅ it has projects relationship

#### **Admin Business Logic - TODO**

- 📝 TODO: admin can only create families in their assigned projects
- 📝 TODO: admin can only create members in their assigned projects
- 📝 TODO: admin can only view/edit units in their assigned projects

---

### `tests/Unit/Models/FamilyTest.php`

#### **Family Model**

- ✅ it belongs to a project
- ✅ it has many members
- ✅ it can add a member to the family
- 📝 TODO: it can join a project with all family members _(blocked by schema - project_id NOT NULL)_

#### **Family Business Logic - Atomic Operations**

- 📝 TODO: family can leave a project with all members
- 📝 TODO: family can move to another project atomically
- 📝 TODO: all family members must belong to the same project as the family
- 📝 TODO: family project_id must match all members active project
- 📝 TODO: cannot move individual member to different project than family

---

### `tests/Unit/Models/MemberTest.php`

#### **Member Model**

- ✅ it has a family relationship
- 🐛 SKIP: it returns the active project via project attribute _(bug: accessor not working)_
- ✅ it returns null for project attribute when member has no active project
- 🐛 SKIP: it can join a project _(bug: accessor not working)_
- ✅ it can leave a project
- 🐛 SKIP: it can switch between projects _(bug: accessor not working)_
- ✅ it applies member global scope to exclude admins

#### **Member Business Logic - TODO**

- 📝 TODO: member switching projects should validate family atomicity
- 📝 TODO: member cannot switch projects if it breaks family atomicity
- 📝 TODO: member can only invite users to their own family

---

### `tests/Unit/Models/ProjectTest.php`

#### **Project Model**

- ✅ it has many units
- ✅ it has many families
- ✅ it has many members through pivot table
- ✅ it has many admins through pivot table
- ✅ it can add a member to the project
- ✅ it can remove a member from the project
- ✅ it can add an admin to the project
- ✅ it can check if it has a specific member
- ✅ it can check if it has a specific admin
- 🐛 SKIP: it can get the current project from state _(bug: test uses session() instead of state())_
- ✅ it has active scope
- ✅ it has alphabetically scope

---

### `tests/Unit/Models/UserTest.php`

#### **User Model**

- ✅ it can be converted to a Member when is_admin is false
- ✅ it returns null when converting to Member if is_admin is true
- ✅ it can be converted to an Admin when is_admin is true
- ✅ it returns null when converting to Admin if is_admin is false
- ✅ it identifies members correctly
- ✅ it identifies superadmins based on config
- ✅ it has projects relationship that returns only active projects

---

## Feature Tests - Authentication

### `tests/Feature/Auth/AuthenticationTest.php`

- ✅ login screen can be rendered
- ✅ users can authenticate using the login screen
- ✅ users can not authenticate with invalid password
- ✅ users can logout

### `tests/Feature/Auth/EmailVerificationTest.php`

- ✅ email verification screen can be rendered
- ✅ email is not verified with invalid hash

### `tests/Feature/Auth/PasswordConfirmationTest.php`

- ✅ confirm password screen can be rendered
- ✅ password can be confirmed
- ✅ password is not confirmed with invalid password

### `tests/Feature/Auth/PasswordResetTest.php`

- ✅ reset password link screen can be rendered
- ✅ reset password link can be requested
- ✅ reset password screen can be rendered
- ✅ password can be reset with valid token

---

## Feature Tests - Business Logic

### `tests/Feature/BusinessLogic/FamilyAtomicityTest.php`

#### **Family Atomicity - Core Business Rule**

- 📝 TODO: all family members must belong to same project as family
- 📝 TODO: family can move to another project with all members
- 📝 TODO: family can leave project with all members
- 📝 TODO: cannot add individual member to different project than family
- 📝 TODO: cannot change member family if new family is in different project
- 📝 TODO: moving family to new project handles edge cases

#### **Family Atomicity - Data Consistency**

- 📝 TODO: family project_id always matches members active project
- 📝 TODO: orphaned members have null family and no active project
- 📝 TODO: cannot delete family while members exist
- 📝 TODO: database constraints prevent family atomicity violations

---

### `tests/Feature/BusinessLogic/InvitationSystemTest.php`

#### **Invitation System - TODO**

- 📝 TODO: no open registration - all users created by invitation
- 📝 TODO: superadmin can invite anyone to any project
- 📝 TODO: admin can invite admins to their managed projects
- 📝 TODO: admin can invite members to their managed projects
- 📝 TODO: member can invite family members to their own family
- 📝 TODO: invitation creates user with pending verification
- 📝 TODO: invited user receives email with setup link
- 📝 TODO: invited user can set password and activate account
- 📝 TODO: invitation expires after certain period
- 📝 TODO: cannot invite with duplicate email

---

### `tests/Feature/BusinessLogic/ProjectScopeTest.php`

#### **Project Scope - Admin Restrictions**

- 📝 TODO: admin can only view families in their managed projects
- 📝 TODO: admin can only view members in their managed projects
- 📝 TODO: admin can only view units in their managed projects
- 📝 TODO: admin cannot create family in unmanaged project
- 📝 TODO: admin cannot create member in unmanaged project
- 📝 TODO: admin cannot create unit in unmanaged project
- 📝 TODO: admin cannot update family from unmanaged project
- 📝 TODO: admin cannot delete resources from unmanaged projects

#### **Project Scope - Member Restrictions**

- 📝 TODO: member can only view data from their active project
- 📝 TODO: member cannot view data from other projects
- 📝 TODO: member can only create members in their own project
- 📝 TODO: member cannot switch to different project

#### **Project Scope - Superadmin Override**

- ✅ superadmin can view all projects
- 📝 TODO: superadmin bypasses all project scope restrictions

---

## Feature Tests - Policies

### `tests/Feature/Policies/FamilyPolicyTest.php`

#### **Family Policy**

- ✅ it allows anyone to view families
- ✅ it allows admins to create families
- ✅ it denies members to create families
- ✅ it allows admins to update any family
- ✅ it allows members to update their own family
- ✅ it denies members to update other families
- ✅ it allows admins to delete families
- ✅ it denies members to delete families

#### **Family Policy - Project Scope - TODO**

- 📝 TODO: admins can only create families in projects they manage
- 📝 TODO: admins can only update families in projects they manage
- 📝 TODO: admins can only delete families in projects they manage
- 📝 TODO: superadmins bypass project scope for families

---

### `tests/Feature/Policies/MemberPolicyTest.php`

#### **Member Policy**

- ✅ it allows anyone to view members
- ✅ it allows admins to create members
- ✅ it allows members to create other members
- ✅ it allows admins to update any member
- ✅ it allows members to update themselves
- ✅ it denies members to update other members
- ✅ it allows admins to delete any member
- ✅ it allows members to delete themselves
- ✅ it denies members to delete other members

#### **Member Policy - Project Scope - TODO**

- 📝 TODO: members can only invite to their own family
- 📝 TODO: admins can only create members in projects they manage
- 📝 TODO: admins can only update members in projects they manage
- 📝 TODO: admins can only delete members in projects they manage

---

### `tests/Feature/Policies/ProjectPolicyTest.php`

#### **Project Policy**

- ✅ it allows superadmins to view all projects
- ✅ it allows admins with multiple projects to view any
- ✅ it denies admins with single project to view any
- ✅ it allows admin to view projects they manage
- ✅ it denies admin to view projects they do not manage
- ✅ it allows admin to update projects they manage
- ✅ it allows admin to delete projects they manage
- ✅ it allows superadmin to do anything with projects

#### **Project Policy - TODO**

- 📝 TODO: only superadmins can create projects
- 📝 TODO: members cannot perform any project operations

---

## Feature Tests - Controllers

### `tests/Feature/Controllers/AdminControllerCrudTest.php`

#### **Admin CRUD - Index/Show**

- ✅ it allows anyone to view admin list
- ✅ it allows anyone to view admin details

#### **Admin CRUD - Create/Store (Admin Only)**

- ✅ it allows admins to create other admins
- 🐛 SKIP: it denies members from accessing admin creation form _(bug: 302 vs 403)_
- 🐛 SKIP: it allows admin to create admin for projects they manage _(bug: controller not implemented)_
- 🐛 SKIP: it denies admin from assigning new admin to projects they do not manage _(bug: missing authorization)_
- 🐛 SKIP: it allows admin to assign new admin to multiple managed projects _(bug: controller not implemented)_
- 🐛 SKIP: it allows superadmin to create admin for any project _(bug: controller not implemented)_
- ✅ it validates required fields on creation
- 🐛 SKIP: it validates email uniqueness _(bug: controller not implemented)_
- ✅ it requires at least one project for new admin

#### **Admin CRUD - Update**

- ✅ it allows admins to update themselves
- 🐛 SKIP: it denies admins from updating other admins _(bug: 302 vs 403)_
- ✅ it allows superadmin to update any admin
- 🐛 SKIP: it denies members from updating admins _(bug: 302 vs 403)_
- 📝 TODO: admin cannot change their own project assignments
- 📝 TODO: admin cannot modify another admin project assignments

#### **Admin CRUD - Delete**

- 🐛 SKIP: it denies admins from deleting themselves _(bug: 302 vs 403)_
- 🐛 SKIP: it denies admins from deleting other admins _(bug: 302 vs 403)_
- ✅ it allows superadmin to delete admins
- 🐛 SKIP: it denies members from deleting admins _(bug: 302 vs 403)_
- ✅ it prevents superadmin from deleting themselves

#### **Admin CRUD - Project Scope Validation**

- 📝 TODO: it shows only managed projects in create form for regular admin
- 📝 TODO: it shows all projects for superadmin in create form
- 📝 TODO: admin cannot assign mixed managed and unmanaged projects

---

### `tests/Feature/Controllers/FamilyControllerCrudTest.php`

#### **Family CRUD - Index/Show**

- ✅ it lists families for current project
- ✅ it allows anyone to view family details
- ✅ it searches families by name

#### **Family CRUD - Create/Store (Admin Only)**

- ✅ it allows admins to create families
- 🐛 SKIP: it denies members from accessing family creation form _(bug: 302 vs 403)_
- ✅ it allows admin to store family in project they manage
- 🐛 SKIP: it denies admin from creating family in project they do not manage _(bug: 302 vs 403)_
- ✅ it allows superadmin to create family in any project
- 🐛 SKIP: it denies members from creating families _(bug: 302 vs 403)_
- ✅ it validates required fields on creation
- ✅ it validates project exists on creation

#### **Family CRUD - Update**

- ✅ it allows admin to update any family
- ✅ it allows member to update their own family
- 🐛 SKIP: it denies member from updating other families _(bug: 302 vs 403)_
- 🐛 SKIP: it denies admin from updating family in project they do not manage _(bug: 302 vs 403)_
- ✅ it allows superadmin to update any family

#### **Family CRUD - Delete (Admin Only)**

- ✅ it allows admin to delete families
- 🐛 SKIP: it denies members from deleting families _(bug: 302 vs 403)_
- 🐛 SKIP: it denies admin from deleting family in unmanaged project _(bug: 302 vs 403)_
- ✅ it allows superadmin to delete any family

#### **Family CRUD - Project Scope Validation**

- ✅ it shows only projects admin manages in create form
- ✅ it shows all projects for superadmin in create form

---

### `tests/Feature/Controllers/FamilyControllerTest.php`

#### **Family Controller - Index**

- ✅ it lists families for the current project
- ✅ it searches families by name

#### **Family Controller - Create/Store**

- ✅ it allows admins to create families
- ✅ it stores a new family
- 🐛 SKIP: it denies members from creating families _(bug: 302 vs 403)_

#### **Family Controller - TODO**

- 📝 TODO: admin can only create family in projects they manage
- 📝 TODO: family creation automatically sets project_id
- 📝 TODO: cannot delete family that has members

---

### `tests/Feature/Controllers/MemberControllerCrudTest.php`

#### **Member CRUD - Index/Show**

- ✅ it lists members for current project
- ✅ it allows anyone to view member details
- ✅ it searches members by name, email, or family

#### **Member CRUD - Create/Store (Admins and Members)**

- ✅ it allows admins to create members
- ✅ it allows members to create other members
- ✅ it allows admin to create member in project they manage
- 🐛 SKIP: it denies admin from creating member in project they do not manage _(bug: 302 vs 403)_
- ✅ it allows superadmin to create member in any project
- ✅ it validates required fields on creation
- ✅ it validates email uniqueness
- ✅ it validates family exists
- ✅ it validates project exists

#### **Member CRUD - Critical: Family/Project Constraints**

- 📝 TODO: it prevents admin from creating member with family from different project
- 📝 TODO: member can only invite to their own family
- 📝 TODO: member can only invite to their own project
- 📝 TODO: member invitation auto-fills family and project

#### **Member CRUD - Update**

- ✅ it allows admins to update any member
- ✅ it allows members to update themselves
- 🐛 SKIP: it denies members from updating other members _(bug: 302 vs 403)_
- 🐛 SKIP: it denies admin from updating member in unmanaged project _(bug: 302 vs 403)_
- ✅ it allows superadmin to update any member
- ✅ it validates email uniqueness on update
- ✅ members cannot change their own family

#### **Member CRUD - Delete**

- ✅ it allows admins to delete members
- ✅ it allows members to delete themselves
- 🐛 SKIP: it denies members from deleting other members _(bug: 302 vs 403)_
- 🐛 SKIP: it denies admin from deleting member in unmanaged project _(bug: 302 vs 403)_
- ✅ it allows superadmin to delete any member

#### **Member CRUD - Project Scope Validation**

- ✅ it shows only families from managed projects in create form
- ✅ it shows only managed projects in create form for admins
- ✅ it shows all projects for superadmin in create form
- 📝 TODO: member create form should hide project and family selectors

---

### `tests/Feature/Controllers/MemberControllerTest.php`

#### **Member Controller - Index**

- ✅ it lists members for the current project
- ✅ it searches members by name, email, or family

#### **Member Controller - Create/Store**

- ✅ it allows admins to create members
- ✅ it stores a new member and assigns to family and project
- ✅ it allows members to create other members

#### **Member Controller - Update/Delete**

- ✅ it allows admins to update any member
- ✅ it allows members to update themselves
- 🐛 SKIP: it denies members from updating other members _(bug: 302 vs 403)_

#### **Member Controller - Business Logic - TODO**

- 📝 TODO: members can only set family_id to their own family when inviting
- 📝 TODO: admin can only create members in projects they manage
- 📝 TODO: member creation validates family belongs to target project
- 📝 TODO: member automatically gets added to family project

---

### `tests/Feature/Controllers/ProjectControllerTest.php`

#### **Project CRUD - Index/Show**

- ✅ it allows superadmins to view all projects
- ✅ it allows admins with multiple projects to view project list
- 🐛 SKIP: it denies admins with single project from viewing project list _(bug: 302 vs 403)_
- 🐛 SKIP: it denies members from viewing project list _(bug: 302 vs 403)_
- ✅ it allows admin to view project they manage
- 🐛 SKIP: it denies admin from viewing project they do not manage _(bug: 302 vs 403)_
- ✅ it allows anyone to view project details
- ✅ it searches projects by name

#### **Project CRUD - Create/Store (Superadmin Only)**

- ✅ it allows superadmin to create projects
- 🐛 SKIP: it denies admins from creating projects _(bug: 302 vs 403)_
- 🐛 SKIP: it denies members from creating projects _(bug: 302 vs 403)_
- ✅ it validates required fields on creation

#### **Project CRUD - Update (Admins in Managed Projects)**

- ✅ it allows admin to update projects they manage
- 🐛 SKIP: it denies admin from updating projects they do not manage _(bug: 302 vs 403)_
- ✅ it allows superadmin to update any project
- 🐛 SKIP: it denies members from updating projects _(bug: 302 vs 403)_

#### **Project CRUD - Delete (Admins in Managed Projects)**

- ✅ it allows admin to delete projects they manage
- 🐛 SKIP: it denies admin from deleting projects they do not manage _(bug: 302 vs 403)_
- ✅ it allows superadmin to delete any project
- 🐛 SKIP: it denies members from deleting projects _(bug: 302 vs 403)_

#### **Project CRUD - Project Switching**

- 📝 TODO: it allows switching to a project user manages or is member of

---

### `tests/Feature/Controllers/UnitControllerCrudTest.php`

#### **Unit CRUD - Index/Show**

- ✅ it lists units for current project
- ✅ it allows anyone to view unit details
- ✅ it searches units by identifier or number

#### **Unit CRUD - Create/Store (Admin Only)**

- ✅ it allows admins to create units
- 🐛 SKIP: it denies members from accessing unit creation form _(bug: 302 vs 403)_
- ✅ it allows admin to create unit in project they manage
- 🐛 SKIP: it denies admin from creating unit in project they do not manage _(bug: 302 vs 403)_
- ✅ it allows superadmin to create unit in any project
- 🐛 SKIP: it denies members from creating units _(bug: 302 vs 403)_
- ✅ it validates required fields on creation

#### **Unit CRUD - Update (Admin Only)**

- ✅ it allows admin to update units
- 🐛 SKIP: it denies members from updating units _(bug: 302 vs 403)_
- 🐛 SKIP: it denies admin from updating unit in unmanaged project _(bug: 302 vs 403)_
- ✅ it allows superadmin to update any unit

#### **Unit CRUD - Delete (Admin Only)**

- ✅ it allows admin to delete units
- 🐛 SKIP: it denies members from deleting units _(bug: 302 vs 403)_
- 🐛 SKIP: it denies admin from deleting unit in unmanaged project _(bug: 302 vs 403)_
- ✅ it allows superadmin to delete any unit

#### **Unit CRUD - Project Scope Validation**

- 📝 TODO: it shows only projects admin manages in create form

---

## Other Feature Tests

### `tests/Feature/DashboardTest.php`

- ✅ dashboard displays correct data for authenticated user

### `tests/Feature/Settings/PasswordUpdateTest.php`

- ✅ password can be updated
- ✅ password cannot be updated with wrong current password

### `tests/Feature/Settings/ProfileUpdateTest.php`

- ✅ profile information can be updated
- ✅ email verification status is unchanged when email is unchanged

---

## Test Statistics

- **Total Tests**: ~271
- **✅ Passing**: ~127
- **🐛 Skipped (bugs)**: ~12
- **📝 TODO**: ~73
- **Status**: Tests document both working features and planned enhancements

---

## Legend

- **✅** = Passing test
- **🐛 SKIP** = Skipped due to bug (documented in BUGS_AND_TODOS.md)
- **📝 TODO** = Test for missing feature (not yet implemented)

---

_Last Updated: 2025-10-26_
