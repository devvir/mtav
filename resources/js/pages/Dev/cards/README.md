# Entity Cards Component System - Current State

## Card Component Design Philosophy

### Scope and Purpose
The Card component system is **NOT** a generic npm-package-style component. It is specifically designed for **ShowCards and IndexCards for THIS project's entities only**.

### Design Principles

**Generic enough for the app:**
- Serves all entities (admins, members, families, projects, units, events, logs)
- Handles both ShowCard and IndexCard use cases
- Works with any content structure without requiring hacks

**Simple interface:**
- Minimal configuration props (not 20+ props)
- Sane defaults for most use cases
- Easy to use for common scenarios

**Solid architecture:**
- Core layout properties are non-negotiable (e.g. `flex flex-col` cannot be overridden)
- Non-negotiable properties separated from configurable ones in `cn()` calls
- Prevents layout-breaking customizations

**Built-in flexibility:**
- Uses project's Tailwind custom utilities and CSS custom properties
- Color, spacing, fonts configurable via CSS custom properties if needed
- Automatic flexibility from Tailwind without special implementation
- **Note:** We won't use this configurability as we want consistent global look & feel

### What ShowCards and IndexCards Are

#### ShowCard
**Purpose:** Complete UI representation of an entity with all relevant, allowed information.

**Content Guidelines:**
- Shows all information the current user is **allowed** to see (permissions-based)
- Shows all information that is **relevant** in the current context
- Example: Family's project info irrelevant for single-project users, shown only in multi-project contexts
- Sensitive information (verification states, legal IDs) shown only to authorized users

**Usage Context:**
- Displayed in isolation (modals, dedicated pages)
- Larger and more complex than IndexCards
- Full detailed view of entity

#### IndexCard
**Purpose:** Core information summary for entity identification and search result display.

**Content Guidelines:**
- Essential identifying information (name, avatar, key stats)
- Just enough context for user to identify and select the entity
- Consistent layout across all entity types for scanning

**Usage Context:**
- Lists and grids
- Search results
- Quick selection interfaces
- Compact representation

## Current Implementation

### Core Component Structure

```
resources/js/components/card/
├── Card.vue                 # Main card container
├── CardActions.vue          # Action buttons system
├── CardHeader.vue           # Card header with avatar/title
├── index.ts                 # Component exports
├── exposed.ts               # Context injection keys
├── types.ts                 # Type definitions
└── actions/                 # Individual action components
    ├── ShowAction.vue       # View/detail navigation
    ├── IndexAction.vue      # List navigation
    ├── EditAction.vue       # Edit form navigation
    ├── DeleteAction.vue     # Delete with confirmation
    ├── RestoreAction.vue    # Restore with confirmation
    └── index.ts             # Action exports
```

### Card Component API

**Basic Usage:**
```vue
<EntityCard type="index" :resource="member" />
<EntityCard type="show" :resource="family" />
```

**Props:**
- `type: 'index' | 'show'` - Card display mode
- `resource: ApiResource` - Entity data object

**Context Provided:**
- `resource` - The entity data
- `entityFromNS` - Entity type (member, family, etc.)
- `routes` - Route mappings for actions
- `type` - Card type for conditional logic

### Action System

#### CardActions Component

**Props:**
- `type: 'subtle' | 'full'` - Display mode
  - `subtle`: Chevron dropdown menu
  - `full`: Horizontal button row with subtle borders

**Features:**
- Permissions-based action filtering
- Consistent styling across both display modes
- Responsive design for mobile accessibility

#### Individual Action Components

**Navigation Actions (GET requests):**
- `ShowAction` - Navigate to detail page
- `IndexAction` - Navigate to list page
- `EditAction` - Navigate to edit form

**Mutation Actions (POST/DELETE with confirmation):**
- `DeleteAction` - Delete with typed confirmation modal
- `RestoreAction` - Restore soft-deleted items with confirmation

**Action Component Features:**
- **Route handling**: Uses injected routes context
- **Icon + text**: Size-4 icons with readable text labels
- **Accessibility**: Proper flex alignment and touch targets
- **Localization**: All user-facing text translated
- **Consistent styling**: Ghost button variant, encapsulated behavior

### Confirmation Modal System

**Component:** `ConfirmationModal.vue`

**Features:**
- **Smart text extraction**: Uses `resource.email` → `resource.name` (first 2 words) → "Confirm"
- **Typed confirmation**: User must type exact text to proceed
- **Prevents UI overflow**: Name truncation prevents layout breaks
- **Localized**: Natural language confirmations ("Are you sure you want to proceed?")
- **Accessible**: Proper focus management and ARIA handling

### Permission System Integration

**Resource Permission Structure:**
```typescript
resource.allows = {
  view: boolean,      // ShowAction visibility
  update: boolean,    // EditAction visibility
  delete: boolean,    // DeleteAction visibility
  restore: boolean    // RestoreAction visibility
}
```

**Action Filtering Logic:**
- `index`: Shows if not on index page and user can view entity type
- `show`: Shows if not on show page and resource allows view
- `edit`: Shows if resource allows update
- `delete`: Shows if resource allows delete AND not soft-deleted
- `restore`: Shows if resource allows restore AND is soft-deleted

### Styling and Theming

#### Button Layout Modes

**Subtle Mode (Dropdown):**
- Chevron trigger button
- Vertical list of actions in dropdown
- Full width items with left alignment
- Hover background effects

**Full Mode (Button Row):**
- Horizontal button group
- Subtle borders (`border-border/30`)
- Connected appearance with `-space-x-px`
- Rounded corners on first/last buttons
- Compact spacing for better mobile UX

#### Visual Design
- **Destructive actions**: Red icons (delete) while maintaining readable text
- **Consistent sizing**: Size-4 icons, text-sm labels
- **Accessible targets**: Default button size for mobile usability
- **Semantic styling**: Action components handle their own appearance

### Entity Support

**Fully Supported Entities:**
- Members
- Families
- Projects
- Units
- Admins
- Events
- Logs (view-only, no edit/delete/restore)

**Route Patterns:**
- Standard Laravel resource routes: `entities.index`, `entities.show`, `entities.edit`
- Custom restore routes: `entities.restore` (POST)
- Automatic route resolution via `entityNS()`

### Technical Notes

#### Context Injection
Uses Vue 3 provide/inject pattern for clean prop drilling avoidance:
- Resource data and metadata
- Route mappings
- Permission context
- Card type for conditional rendering

#### Localization
- All user-facing text uses `_()` translation composable
- Spanish (es_UY) translations included
- No placeholder/interpolation complexity - simple string translations

#### Error Handling
- Graceful fallbacks for missing route definitions
- Permission-based action filtering prevents unauthorized access
- Input validation in confirmation modals

This system provides a complete, production-ready card interface for all entity types in the MTAV cooperative management application.