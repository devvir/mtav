# MTAV - Core Knowledge Base

**Last Updated:** 2025-11-13

This document contains the essential architectural principles, patterns, and constraints needed for daily development work on MTAV.

## Critical Development Environment

**Docker-Based**: This application runs entirely in Docker containers.

**Command Interface**: Use `./mtav` script for ALL operations:
- `./mtav artisan` instead of `php artisan`
- `./mtav npm` instead of `npm`
- `./mtav up`, `./mtav down`, `./mtav build`, `./mtav shell`
- **NEVER** run raw `php`, `npm`, or `artisan` commands

**Reference**: See `docker-reference.md` for container details and setup.

---

## Core Business Domain

**MTAV** = "Mejor Tecnología de Asignación de Viviendas" (Best Technology for Housing Assignment)

**Purpose**: Housing cooperative management system that distributes units fairly through mathematical optimization.

**Key Entities**:
- **Projects** - Housing developments (buildings/complexes)
- **Families** - Groups living together (atomic units)
- **Members** - Individuals within families
- **Admins** - Project managers
- **Superadmins** - Unrestricted system access
- **Units** - Physical housing units
- **UnitTypes** - Categories of units (apartment, house, etc.)

**Critical Constraints**:
- **Families are atomic** - all members must be in same project as their family
- **Invitation-only system** - NO self-registration. All users created via invitation system
- **Family atomicity** - Members belong to exactly one family, family belongs to one project

---

## Authorization Architecture

**🚨 CRITICAL: User Role Identification (DO NOT FORGET)**:
- **Superadmins**: Identified by email in `config('auth.superadmins')` (reads SUPERADMINS env var, defaults to 'superadmin@example.com')
- **Admins**: Users with `is_admin = true` in database
- **Members**: Users with `is_admin = false` in database
- **All superadmins ARE admins**: Superadmin emails should belong to users with `is_admin = true` (business rule)

**Three User Types**:
1. **Superadmins** - Bypass ALL policies via `Gate::before()` in `AppServiceProvider`
2. **Admins** - Manage projects they're assigned to
3. **Members** - Can view most project data, can only edit their own family's data

**Key Principles**:
- **Superadmins bypass everything** - policies only handle guest/member/admin authorization
- **Admin/Member are mutually exclusive** - cannot be both
- **Project-based scoping** - most models automatically scoped to current project context

**Reference**: See `policies-reference.md` for detailed authorization patterns.

---

## Data Architecture

**Resource Transformation**: Models automatically convert to JsonResource when sent to frontend via `ConvertsToJsonResource` trait (included in base `Model` class and `User` class). **NEVER** use `JsonResource::make()`.

**Base JsonResource**: All resources extend `App\Http\Resources\JsonResource` which includes these traits:
- `ResourceSubsets` - Allows creating resources with excluded fields via `@only` and `@except` (familiar Laravel API)
- `WithResourceAbilities` - Automatically appends a `can` key with policy results (view/update/delete/restore/forceDelete) so frontend always knows when to show/hide buttons

**Global Scoping**: Most models are automatically scoped to current project context via global scopes.

**Request Validation**: Use `ProjectScopedRequest` base class for forms that need project context.

**Reference**: See `resources-reference.md` for resource patterns and `business-rules-reference.md` for detailed scoping rules.

---

## Accessibility Requirements

**Target Audience**: Elderly users, people with disabilities, users with old devices.

**Non-Negotiable Requirements**:
- **WCAG AA minimum** (4.5:1 contrast), AAA preferred (7:1)
- **16px minimum body text**, scale UP on larger screens
- **44px touch targets** on mobile
- **High contrast**, clear focus indicators (2px+ rings)
- **No heavy animations** (old device support)
- **Simple, clear interfaces**: Generous spacing, obvious affordances

**Dark Theme Colors** (non-negotiable):
- Background: `hsl(210, 20%, 2%)`
- Sidebar: `hsl(30, 30%, 6%)`

**Reference**: See `ACCESSIBILITY_AND_TARGET_AUDIENCE.md` for complete guidelines.

---

## Lottery System

**Core Concept**: The lottery is a ONE-TIME, ATOMIC assignment event that permanently assigns ALL units to ALL families simultaneously.

**Critical Facts**:
- **Before Execution**: Families can set/modify preferences for any unit of their type
- **After Execution**: NO further preference changes allowed - the assignment is final
- **No "Available" Units**: Units are never "available" or "unavailable" individually
- **Binary State**: The entire lottery is either "pending" (not executed) or "completed" (executed)

**Preferences vs Assignment**:
- **Preferences**: Family's ordered list of desired units (can be modified until execution)
- **Assignment**: The final result after lottery execution (immutable)

**UI Implications**:
- Show preference management ONLY if lottery not executed
- Show assignment results ONLY if lottery executed
- Never use terms like "available units" - all units of family's type are preference candidates

**Database State**:
- Preferences stored in `unit_preferences` pivot table
- Assignments stored as `family_id` on `units` table
- Lottery execution tracked via `Event` model with `EventType::LOTTERY`

---

## Technology Stack
- **Backend**: Laravel 12, PHP 8.4, MariaDB 12
- **Frontend**: Vue 3, TypeScript, Inertia.js
- **Custom Packages**: `devvir/laravel-instant-api`, `devvir/laravel-resource-tools`

---

## Development Patterns

**Vue Components**: Always use `<script setup lang="ts">` with TypeScript
**JavaScript**: Always end lines with semicolons
**Testing**: TDD approach - write tests first
**Commands**: Everything through `./mtav` script

**Key Constraints**:
- **NEVER bypass global scopes** in business logic (use `withoutGlobalScope()` only in maintenance tools)
- **NEVER update existing pivot entries** - always create new entries to preserve history
- **NEVER commit `.env` files** - they're git-ignored
- **NEVER convert models to resources manually** - automatic conversion happens when sending to frontend
- **NEVER pass authorization data from controllers** - use frontend composables like `iAmAdmin`, `iAmMember`
- **PREFER expressive Eloquent syntax** - use `project()->events()` over `Event::where('project_id', $project->id)`

**Controller Best Practices**:
- Only pass specific data needed by the view
- Let frontend handle authorization logic via composables
- Use expressive relationship methods instead of raw queries
- Models auto-convert to JsonResource when sent to frontend

---

## Localization

**Spanish Formality**: Use "tú" form (informal but respectful) for all user-facing content
- Examples: "puedes hacer", "tu perfil", "debes revisar"
- **NOT** "vos" or "usted" forms

**Frontend Localization**:
- English strings as keys in code
- Spanish translations in `lang/es_UY.json` (gets loaded by frontend JavaScript)
- Keep `lang/en.json` empty (`{}`) - English keys work automatically

**Backend Localization**:
- Uses Laravel's grouped translation system with codes as keys
- English translations: `lang/en/<group-id>.php`
- Spanish translations: `lang/es_UY/<group-id>.php`
- Usage: `__('validation.something')` where `validation` is the group file and `something` is the key
- **Rationale**: Backend translations stay server-side, avoiding unnecessary payload to frontend

---

## Quick Reference

**Start Development**: `./mtav up`
**Run Backend Tests**: `./mtav artisan test --pest`
**Run Frontend Tests**: `./mtav artisan test --vitest --once` (the --once turns off watch mode)
**Frontend Dev**: `./mtav npm run dev`
**Enter Container**: `./mtav shell`
**Stop Everything**: `./mtav down`

**Check Authorization**: `app/Policies/{Model}Policy.php`
**Check Resources**: `app/Http/Resources/{Model}Resource.php`
**Check Business Rules**: `app/Http/Requests/` and `business-rules-reference.md`
**Check Docker Setup**: `docker-reference.md`
**Check Accessibility**: `ACCESSIBILITY_AND_TARGET_AUDIENCE.md`