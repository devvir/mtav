# MTAV Lottery System - Technical Documentation

## Core Business Domain

The **lottery (sorteo)** is the primary functionality of MTAV - a fair and transparent unit assignment system for housing cooperatives. This is not an optional feature but the fundamental purpose of the entire application.

**Essential Context Documentation:**
- **Business Domain**: `documentation/ai/KNOWLEDGE_BASE.md` - Core business entities, authorization, and constraints
- **Testing Philosophy**: `documentation/ai/testing/PHILOSOPHY.md` - Universe fixture patterns and test architecture
- **Universe Structure**: `tests/_fixtures/UNIVERSE.md` - Predictable test data for development and testing
- **Project Plans**: `documentation/ai/ProjectPlans.md` - Spatial visualization system for units and families

## Business Rules & Architecture

### Purpose
- Distribute housing units fairly among families in a cooperative using mathematical optimization
- Maximize overall family satisfaction while ensuring transparency and legal compliance
- Prevent favoritism, manipulation, or disputes through algorithmic assignment

### Core Principles
- **One-time execution**: Lottery runs once per project, results are immutable
- **Preference-based**: Families rank preferred units, system optimizes global satisfaction
- **Type-segregated**: Separate algorithms per unit type (families assigned to specific types)
- **Dynamic preference resolution**: Preferences validated and filled at runtime to handle data changes
- **Audit compliance**: Complete logging for legal and regulatory requirements

### Data Architecture

**Entity Relationships:**
```
Project (1) -> Family (N) -> Member (N)
Project (1) -> UnitType (N) -> Unit (N)
Family (1) -> UnitType (1) [assignment constraint]
Family (N) <-> Unit (M) [preferences via unit_preferences pivot]
```

**Key Constraints:**
- **Family atomicity**: All members must be in same project as family
- **Unit type assignment**: Each family assigned to exactly one unit type
- **Preference validation**: Units in preferences must match family's unit type
- **Invitation-only**: No self-registration, all users created via invitation

## Dynamic Preference Management

**Architecture Decision**: Uses dynamic preference resolution rather than static storage for data integrity.

**Challenge**: Housing data is fluid - families join projects, unit inventory changes, family unit type requirements change, administrative corrections occur.

**Solution**: `LotteryService::preferences()` serves as single source of truth, dynamically building preference lists:

```php
public function preferences(Family $family): Collection
{
    // 1. Sanitize existing preferences (remove invalid unit type mismatches)
    $this->lotteryPreferencesService->sanitizeFamilyPreferences($family);

    // 2. Load relationships fresh
    $family->loadMissing(['preferences', 'unitType.units']);

    // 3. Get explicit preferences (ordered by pivot.order)
    $preferences = $family->preferences;

    // 4. Get all candidate units for this family's unit type
    $candidates = $family->unitType->units;

    // 5. Fill remaining with units not yet preferred (by ID order)
    $remainingUnits = $candidates->whereNotIn('id', $preferences->pluck('id'));

    // 6. Return: preferences first, then remaining candidates
    return $preferences->concat($remainingUnits);
}
```

**Benefits:**
- **Automatic consistency**: Preference lists always valid regardless of data changes
- **Zero maintenance**: No complex cascade operations when units/families change
- **Graceful degradation**: New families get all units as candidates automatically
- **Single source of truth**: One method provides complete, validated preference data

## Current Implementation Status

### ✅ Completed: Lottery Preferences System

**Database Layer:**
- ✅ `unit_preferences` table with family_id, unit_id, unit_type_id, order columns
- ✅ Family->preferences() relation through pivot (ordered by pivot.order)
- ✅ UnitSeeder creates units matching family count per project for realistic testing
- ✅ Comprehensive validation constraints and indexing

**Backend Services:**
- ✅ `LotteryService::preferences()` - Single source of truth for family preferences
- ✅ `LotteryPreferencesService::sanitizeFamilyPreferences()` - Data integrity validation
- ✅ `LotteryController` - Index, update lottery, update preferences endpoints
- ✅ `UpdateLotteryPreferencesRequest` - Validation with business rule enforcement
- ✅ `UpdateLotteryRequest` - Admin lottery configuration validation

**Frontend Architecture:**
```
/components/lottery/
├── admin/LotteryManagement.vue     # Admin lottery configuration & execution
├── member/PreferencesManager.vue   # Drag-and-drop preference ordering
├── shared/
│   ├── LotteryHeader.vue           # Page header with description
│   ├── LotteryContent.vue          # Role-based component loading (2fr/3fr split)
│   ├── LotteryFooter.vue           # Project plan integration
│   └── ProjectPlan.vue             # Spatial layout placeholder
├── composables/
├── types.d.ts                      # TypeScript definitions
└── index.ts                        # Public exports
```

**User Experience:**
- ✅ Role-based interface (admin management vs member preferences)
- ✅ Drag-and-drop preference ordering with keyboard accessibility
- ✅ Auto-save preference updates with error handling and loading states
- ✅ Admin lottery date configuration and execution readiness
- ✅ Complete internationalization (English/Spanish Uruguay)
- ✅ Responsive layout: mobile (vertical list) + desktop (grid)

## UI/UX Design Principles (Preferences Picker)

### Visual Hierarchy: Fixed Slots + Moving Units

**Core Concept**: Separation between static "preference positions" and dynamic "unit cards"

**Desktop Grid Architecture:**
```
┌─────────────────────────────────────┐
│  TransitionGroup (grid container)   │  ← 100% width, auto-fill columns
│  ┌──────────────────────────────┐   │
│  │ Drop Zone (full cell)        │   │  ← Handles ALL drop events
│  │  ┌────────────────────────┐  │   │
│  │  │ Visual Inset (absolute) │  │   │  ← Creates visual gap (inset-2)
│  │  │  ┌──────────────────┐   │  │   │
│  │  │  │ Numbered Slot    │   │  │   │  ← Dashed border, bg number
│  │  │  │ (background)     │   │  │   │
│  │  │  └──────────────────┘   │  │   │
│  │  │  ┌──────────────────┐   │  │   │
│  │  │  │ Unit Card        │   │  │   │  ← Semi-transparent, draggable
│  │  │  │ (foreground)     │   │  │   │  ← Rotated, inset from edges
│  │  │  └──────────────────┘   │  │   │
│  │  └────────────────────────┘  │   │
│  └──────────────────────────────┘   │
└─────────────────────────────────────┘
```

**Key Design Decisions:**

1. **Full Drop Zones (100% Cell Coverage)**
   - Outer container handles drop events for ENTIRE cell
   - No dead zones between cards (gaps are purely visual)
   - Visual gaps created with `absolute inset-2` on inner container
   - Prevents frustrating "drop between cards" failures

2. **Fixed Numbered Slots (Background Layer)**
   - Large numbers (text-4xl) in bottom-right corner
   - Dashed borders, subtle background (surface-sunken/30)
   - Numbers at 30% opacity to stay visible but not dominant
   - Positioned with `absolute inset-0`, uses `pointer-events-none`

3. **Moving Unit Cards (Foreground Layer)**
   - Semi-transparent background (bg-surface/50) with backdrop-blur
   - Inset from slot edges (w-[calc(100%-12px)] h-[calc(100%-12px)])
   - Creates "resting inside slots" visual metaphor
   - Pointer events enabled for drag interactions

4. **Subtle Random Rotations**
   - Each card rotated ±2° based on seeded pseudo-random (unit ID)
   - Uses sine-based hash for even distribution: `Math.sin(unitId * 12.9898 + 78.233)`
   - Creates "hand-placed deck of cards" aesthetic vs computer-perfect grid
   - Consistent across renders (seeded by unit ID)

5. **Smooth Animations (400ms transitions)**
   - Inspired by InfinitePaginator card entrance/exit
   - Uses `cubic-bezier(0.4, 0, 0.2, 1)` easing
   - Cards scale down + fade when dragged (opacity-40 scale-95 rotate-2)
   - Other cards smoothly cascade to new positions
   - Leave animations use `position: absolute` to prevent layout jumps

6. **Typography: Monospaced Font**
   - Unit identifiers use `font-mono` (monospaced)
   - Prevents inconsistent line breaks with systematic naming ("Unidad 1" vs "Unidad 10")
   - Ensures predictable layout across all cards
   - Professional, systematic aesthetic appropriate for identifiers

7. **Responsive Grid**
   - Uses `repeat(auto-fill, minmax(150px, 1fr))`
   - Automatically fits as many columns as possible
   - Cards expand to fill available width equally
   - Minimum 150px per column ensures readability

8. **Layout Proportions**
   - Desktop: Project Plan (40%) + Preferences Picker (60%)
   - Uses `grid-cols-[2fr_3fr]` for 2:5 ratio
   - Picker gets more space as it's the primary interaction
   - Plan provides visual context without dominating

9. **Accessibility Features**
   - Keyboard controls (arrow buttons) for non-drag reordering
   - Visible on hover, positioned below unit name
   - Touch-friendly drag handles (GripVerticalIcon)
   - ARIA labels for screen readers
   - Keyboard navigation fully supported

10. **Priority Badges (Top 3)**
    - Small heart icon in top-right corner of cards 1-3
    - Subtle, non-intrusive (h-6 w-6)
    - Primary color indicates high priority
    - Shows at a glance which are top choices

### Technical Implementation Notes

**Pointer Events Hierarchy:**
```
Drop Zone Container:     pointer-events: auto (catches drops)
  └─ Visual Inset:       pointer-events: none (pass-through)
      ├─ Numbered Slot:  pointer-events: none (background only)
      └─ Unit Card:      pointer-events: auto (draggable)

**Route Structure:**
```php
// Public lottery interface
Route::get('lottery', [LotteryController::class, 'index'])->name('lottery');

// Admin lottery configuration (requires admin authorization)
Route::patch('lottery/{lottery}', [LotteryController::class, 'update'])->name('lottery.update');

// Member preference updates (requires member authorization)
Route::patch('lottery/preferences', [LotteryController::class, 'updatePreferences'])->name('lottery.preferences');
```

**Authorization Model:**
- **Admins**: Can configure lottery settings (date, description), execute lottery
- **Members**: Can view and update their family's unit preferences
- **Superadmins**: Can invalidate lottery results (planned)
- **Guests**: No lottery access

```

**Drag & Drop Logic:**
- `draggedIndex` tracks which card is being dragged
- Drop handlers on outer container (full cell coverage)
- `move(from, to)` function handles array reordering + API call
- Form auto-saves on every drop with `preserveScroll: true`

**State Management:**
- `preferences` is reactive array of units
- Order determined by array position (index + 1 = priority)
- Backend receives full ordered array on each update
- Dynamic preference resolution ensures consistency

**Why This Architecture?**
- **UI vs Logic Separation**: Visual gaps don't affect functionality
- **No Dead Zones**: Entire grid cell is droppable, frustration-free
- **Clear Affordance**: Fixed slots + movable units = obvious interaction
- **Smooth Feedback**: Animations make reordering visually clear
- **Predictable Layout**: Monospace font prevents layout surprises
- **Accessible**: Works with keyboard, mouse, and touch

### Mobile Layout (< lg breakpoint)

- Vertical list layout (stacked cards)
- Full-width cards with horizontal layout
- Drag handle + priority badge + unit name + arrow controls
- Same drag-and-drop functionality
- Optimized for scrolling through long lists

**Testing Infrastructure:**
- ✅ Universe fixture provides deterministic test data
- ✅ Feature tests for preference validation and sanitization
- ✅ UI health check coverage for lottery routes
- ✅ Transaction-based test isolation

## Pending Implementation

### Phase 1.B: Lottery Execution Engine

**Strategy Pattern Implementation:**
```php
interface LotteryStrategy {
    public function execute(Collection $familiesWithPreferences): Collection;
}

class DummyLotteryService implements LotteryStrategy // Random assignment for development
class ProductionLotteryService implements LotteryStrategy // External API integration
```

**Execution Flow:**
1. **Precondition validation**: All families have complete preferences (auto-satisfied by dynamic system)
2. **Data preparation**: Format preferences for optimization API
3. **Algorithm execution**: Call external service or fallback strategy
4. **Result processing**: Parse assignments and update units.family_id
5. **Audit logging**: Record complete execution details
6. **Notification**: Email families with assignments

**Required Components:**
- `LotteryExecutionService` - Orchestrates execution flow
- `LotteryStrategy` interface with implementations
- `LotteryExecution` model for audit trail
- Execution authorization and one-time enforcement
- Assignment result UI and notifications

### Phase 2: Production Readiness

**Advanced Validation:**
- Comprehensive precondition checking
- Assignment immutability protection
- Duplicate execution prevention
- Strategy configuration management

**Enhanced User Experience:**
- Real-time lottery progress updates
- Satisfaction scoring display
- Preference deadline management
- Advanced result analytics

**Audit & Compliance:**
- Digital signatures for results
- Result verification system
- Lottery invalidation (superadmin only)
- Regulatory compliance features

### Phase 3: Interactive Project Plan Integration

**Goal**: Transform abstract preference ordering into spatial decision-making

**Admin: Visual Plan Editor**
- Graphical canvas for drawing/arranging unit layouts
- Drag-and-drop unit placement matching actual building layout
- Save spatial positions and relationships to database
- WYSIWYG representation of physical project

**Member: Integrated Experience**
- **Bi-directional highlighting**:
  - Click unit in grid → highlights in plan canvas
  - Click unit in plan → highlights in preference grid
- **Drag from plan to picker**:
  - Grab unit from visual layout
  - Drop into preference slot in grid
  - Reorder visually based on spatial understanding
- **Visual context**:
  - See exactly where "Unidad 8" is in the building
  - Understand floor, orientation, neighbors
  - Make informed decisions based on actual position
- **Better decision-making**:
  - Choose based on location, not just identifier
  - See relationships between units
  - Understand spatial trade-offs

**Technical Requirements:**
- Plan component with click/drag event handlers
- Shared state between ProjectPlan and PreferencesManager
- Unit coordinates/positions stored in database (plan_x, plan_y)
- SVG or Canvas-based rendering for scalable graphics
- Responsive scaling for different screen sizes
- Touch-friendly for mobile/tablet interaction

**Benefits:**
- Transforms lottery from abstract to concrete
- Users understand WHAT they're choosing, not just order
- Reduces confusion and post-lottery disputes
- Better informed preference decisions
- More satisfying user experience

### Phase 4: Enterprise Features

**External API Integration:**
- Real optimization service client
- Timeout and retry logic
- Fallback strategy handling
- API health monitoring

**Advanced Analytics:**
- Lottery execution metrics
- Performance monitoring
- Family satisfaction analysis
- Historical trend tracking

## Technical Architecture

### Data Flow
```
Input:  Family preferences as Collection<Unit> (ordered)
Processing: External API or local strategy
Output: Assignments as array [family_id => unit_id]
Persistence: Update units.family_id with assignments
```

### Security Constraints
- **One-time execution**: Immutable results per project
- **Authorization checks**: Admin for execution, member for preferences
- **Data validation**: Unit type matching, preference ordering
- **Audit trail**: Complete logging for legal compliance

### Scalability Design
- **External API**: Handles optimization complexity
- **Strategy pattern**: Algorithm swapping without code changes
- **Dynamic preferences**: No cascade maintenance overhead
- **Result caching**: Persistent assignment storage

## Knowledge Requirements

**Essential Reading for AI Context:**
1. **`documentation/ai/KNOWLEDGE_BASE.md`** - Business domain, authorization patterns, development constraints
2. **`documentation/ai/testing/PHILOSOPHY.md`** - Universe fixture philosophy, test patterns
3. **`tests/_fixtures/UNIVERSE.md`** - Test data structure and relationships
4. **`documentation/ai/ProjectPlans.md`** - Spatial visualization system architecture
5. **`.github/copilot-instructions.md`** - Code style, translation patterns, development rules

**Core Models Understanding:**
- **Project**: Housing developments with families and units
- **Family**: Atomic groups living together, assigned to unit types
- **UnitType**: Categories of housing units (apartment, house, etc.)
- **Unit**: Physical housing units that can be assigned to families
- **Event**: Lottery stored as event type for date/description management

**Authorization Hierarchy:**
- **Superadmins**: Bypass all policies (email-based identification)
- **Admins**: Manage assigned projects (is_admin = true)
- **Members**: View project data, edit family data (is_admin = false)

The lottery system represents the culmination of MTAV's purpose: ensuring fair, transparent, and mathematically optimal distribution of housing units among cooperative families.