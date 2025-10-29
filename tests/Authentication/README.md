# Authentication Tests

Tests for login/logout, password management, and session handling.

## Scope

**What belongs here:**
- Login/logout flows
- Password reset and confirmation
- Session management
- Authentication middleware

**What does NOT belong here:**
- Authorization/permissions → `Authorization/`
- User profile management → `UserManagement/`

## Current Test Files

### LoginPage.php
Login page behavior: guest access, authenticated user redirects, edge cases for deleted/inactive users and projects.

**Tests (3/16 passing, 13 skipped):**
- ✅ can be rendered by guests
- ✅ redirects admin with one project to dashboard
- ⏭️ redirects admin with two projects to projects index
- ⏭️ returns 403 for admin with no projects
- ✅ redirects admin with 2+ projects to dashboard if current project is set and admin manages it
- ⏭️ redirects admin to projects and resets context if admin does not manage current project
- ⏭️ redirects member to dashboard
- ⏭️ returns 403 for deleted member
- ⏭️ returns 403 for inactive member
- ⏭️ returns 403 for member in deleted project
- ⏭️ returns 403 for admin managing only deleted project
- ⏭️ allows member in inactive project
- ⏭️ logs out member when their only project is deleted
- ⏭️ logs out admin with only deleted project
- ⏭️ sets admin to remaining project when current project is deleted
- ⏭️ resets project context for admin with multiple projects when current project is deleted
