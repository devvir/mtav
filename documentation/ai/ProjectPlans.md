# Project Plans System

## Overview

The Project Plans system provides spatial visualization and management for housing cooperative projects. It enables administrators to create, view, and edit floor plans that show the spatial relationship between units, common areas, and circulation paths within a project.

## Core Concepts

### Plan Entity
Each project has exactly one **Plan** that defines:
- **Project boundary**: The overall outline/perimeter of the housing project
- **Canvas dimensions**: Width and height in real-world units (meters/feet)
- **Unit system**: Whether measurements are in meters or feet
- **Scale management**: Automatic scaling to fit display containers (no fixed scale factor)

### PlanItem Entity
Each **PlanItem** represents a discrete spatial element within a plan:
- **Units**: Residential units that belong to families
- **Common areas**: Parks, courtyards, playgrounds, community gardens
- **Circulation**: Streets, walkways, internal paths
- **Buildings**: Building outlines and structures
- **Utilities**: Parking areas, waste management, etc.

## Database Structure

### Plans Table
```sql
plans
- id, project_id (FK)
- polygon (json) -- Project boundary polygon coordinates
- width, height -- Canvas dimensions in chosen units
- unit_system (enum) -- 'meters', 'feet'
- created_at, updated_at
```

### Plan Items Table
```sql
plan_items
- id, plan_id (FK)
- type (string) -- 'unit', 'park', 'street', 'building', 'parking', etc.
- polygon (json) -- Polygon coordinates defining the shape
- floor (integer) -- 0=ground, 1=first floor, etc.
- name (varchar, nullable) -- "Central Courtyard", "Building A", etc.
- metadata (json, nullable) -- Color overrides, notes, measurements
- created_at, updated_at
```

### Unit Reference
```sql
units
- ... existing fields ...
- plan_item_id (FK) -- Links unit to its spatial representation
```

**Note**: No soft deletes on plan tables - these are admin-only features with confirmation dialogs. Auditing not required for spatial data.

## Model Relationships

### Project → Plan (1:1)
```php
public function plan(): HasOne
{
    return $this->hasOne(Plan::class);
}
```

### Plan → PlanItems (1:many)
```php
public function items(): HasMany
{
    return $this->hasMany(PlanItem::class);
}
```

### Unit → PlanItem (1:1)
```php
public function planItem(): BelongsTo
{
    return $this->belongsTo(PlanItem::class);
}
```

### Unit → Plan (through PlanItem)
```php
public function plan(): BelongsToThrough
{
    return $this->belongsToThrough(Plan::class, PlanItem::class);
}
```

## Automatic Creation (Observers)

### ProjectObserver
When a project is created:
1. Automatically creates a Plan with default boundary
2. Sets default canvas dimensions (800x600)
3. Uses meters as default unit system

### UnitObserver
When a unit is created:
1. PlanService calculates next available grid position
2. Creates PlanItem with type='unit' and default rectangular shape
3. Links unit to plan item via foreign key
4. Positions units in a simple grid layout by default

## Resource Layer

### PlanResource & PlanItemResource
```php
// app/Http/Resources/PlanResource.php
class PlanResource extends JsonResource
{
    // Extends base JsonResource with automatic:
    // - commonResourceData() for id, timestamps
    // - whenLoaded() patterns for relationships
    // - belongsTo relations always include .id
    // - hasMany relations get counts via whenCountedOrLoaded()
}

// app/Http/Resources/PlanItemResource.php
class PlanItemResource extends JsonResource
{
    // Same base class benefits
    // Includes unit relationship when loaded
}
```

Benefits of the base Resource pattern:
- **Automatic timestamp formatting**: No manual `toISOString()` calls
- **Consistent relationship handling**: `belongsTo` always provides `.id`, rest optional
- **Automatic counters**: `hasMany` relations get `{relation}_count` automatically
- **Minimal boilerplate**: Resources focus only on unique model fields

### ProjectResource Integration
```php
private function relationsData(): array
{
    return [
        // ... other relations
        'plan' => $this->whenLoaded('plan'),
    ];
}
```

Direct relationship inclusion - no special traits needed since plans follow standard resource patterns.

## Frontend Architecture

### TypeScript Types
```typescript
// resources/js/types/index.d.ts (global types)
export type PlanPolygon = number[]; // Semantic alias for polygon coordinates
export type PlanUnitSystem = 'meters' | 'feet';
export type PlanItemMetadata = Record<string, any>;

export interface Plan extends Resource {
  polygon: PlanPolygon;
  width: number;
  height: number;
  unit_system: PlanUnitSystem;

  project: { id: number } | ApiResource<Project>;
  items: ApiResource<PlanItem>[];
  items_count: number;
}

export interface PlanItem extends Resource {
  type: string;
  polygon: PlanPolygon;
  floor: number;
  name: string | null;
  metadata: PlanItemMetadata | null;

  plan: { id: number } | ApiResource<Plan>;
  unit?: ApiResource<Unit> | null;
}
```

**Key improvements:**
- **Extends base Resource**: Gets `id`, `created_at`, `updated_at` automatically
- **Global types**: Moved to `types/index.d.ts` for project-wide reusability
- **Semantic naming**: `PlanPolygon` instead of raw `number[]`
- **Consistent patterns**: Relationships follow the same patterns as all other resources

### Component System
```
components/plans/
├── index.ts              // Single entry point
├── types.d.ts           // TypeScript definitions
├── PlanViewer.vue       // Main visualization component
└── (future components)
```

### PlanViewer Component
Built with **Konva.js** for high-performance 2D canvas rendering:
- **Polygonal shapes**: Uses `Konva.Line` with closed polygons (not rectangles)
- **Multi-floor support**: Visual displacement for floor stacking
- **Interactive features**: Hover effects, click handlers, unit highlighting
- **Unit type coloring**: Color-coded by unit type, not just assignment status
- **Responsive scaling**: Automatically fits container dimensions

## Visualization Features

### Current Implementation
- ✅ **Polygonal unit rendering** with varied shapes
- ✅ **Unit type-based coloring** from unit type definitions
- ✅ **Floor layering** with visual offset for multi-story buildings
- ✅ **Interactive hover** and click effects
- ✅ **Project boundary** display with dashed outline
- ✅ **Responsive canvas** sizing

### Floor Management
Multi-story buildings are handled by:
- **Floor integer**: 0=ground, 1=first floor, etc.
- **Visual offset**: Higher floors displaced by (floor * 5px) horizontally and (floor * -5px) vertically
- **Opacity**: Higher floors slightly transparent to show layering
- **Z-index**: Proper rendering order

## Development & Testing

### Dev Playground
- **Route**: `/dev/plans`
- **Purpose**: Test plan visualization with real project data
- **Features**:
  - Loads first project with plan and unit relationships
  - Displays project metadata and plan structure
  - Shows plan item statistics and controls
- **Access**: Available in development environment

### Sample Data
Seeded projects automatically get:
- Default plan with 800x600 canvas
- Units positioned in grid layout
- All units get 'unit' type plan items
- Basic rectangular shapes for initial layout

## Planned Features (Not Yet Implemented)

### Plan Editor Interface
- **Global editing**: Edit entire project plan in dedicated interface
- **No individual unit editing**: Maintain consistency by editing whole plan
- **Drag & drop**: Move and reshape plan items
- **Add/remove tools**:
  - Removing shapes triggers unit deletion confirmation
  - Adding shapes opens unit creation modal
- **Auto-save**: Persist changes automatically as items are modified
- **Real-time validation**: Prevent overlaps on same floor

### Unit Context Display
- **Unit ShowCard integration**: Display plan with current unit highlighted
- **Project context**: Show where unit sits within larger project
- **Navigation**: Click other units in plan to navigate between unit pages

### Enhanced Visualization
- **Zoom & pan**: Navigate large floor plans
- **Layer switching**: Toggle between floors
- **Common areas**: Parks, streets, circulation paths
- **Building outlines**: Structural boundaries and labels
- **Export/import**: CAD file integration for architectural plans

### User Experience
- **Responsive design**: Mobile and tablet friendly
- **Keyboard shortcuts**: Power user navigation
- **Undo/redo**: Multi-step action history
- **Collaboration**: Real-time multi-user editing

## Technical Considerations

### Performance
- **Canvas rendering**: Konva.js provides hardware-accelerated graphics
- **Selective updates**: Only redraw changed elements
- **Efficient queries**: Eager loading with proper relationships

### Data Consistency
- **UI-driven validation**: Collision detection and spatial constraints in frontend
- **No database constraints**: Flexibility for complex architectural layouts
- **Observer pattern**: Automatic creation maintains referential integrity

### Scalability
- **Component architecture**: Encapsulated plan system with clean interfaces
- **Type safety**: Full TypeScript coverage for plan-related code
- **Resource consistency**: Plans follow established resource patterns for predictable behavior

## Recent Architectural Improvements

### Resource Pattern Adoption
- **Eliminated HasPlan trait**: No longer needed since plans follow standard resource patterns
- **Dedicated resources**: `PlanResource` and `PlanItemResource` extend base `JsonResource`
- **Automatic features**: Timestamps, relationship loading, and counts handled by base class
- **Minimal code**: Resources focus only on unique model fields

### Type System Enhancements
- **Global type integration**: Plan types moved to `types/index.d.ts` and extend base `Resource`
- **Semantic naming**: `PlanPolygon` instead of confusing field names
- **Consistent patterns**: Same relationship typing as all other models

### Data Structure Clarification
- **Polygon semantics**: Renamed database fields from `outline_points`/`points` to `polygon`
- **Clear documentation**: Polygon coordinates as flat arrays for Konva.js compatibility
- **No conversion overhead**: Direct compatibility between database storage and graphics rendering

These changes demonstrate how good abstractions reduce code while increasing maintainability and consistency.

## Future Extensions

### Advanced Features
- **3D visualization**: CSS 3D transforms or Three.js integration
- **Measurement tools**: Distance and area calculations
- **Accessibility**: Screen reader support for spatial data
- **Analytics**: Usage patterns and space utilization metrics

### Integration Points
- **CAD software**: Import/export from architectural tools
- **Mapping services**: Geographic context and addressing
- **Facilities management**: Maintenance scheduling and asset tracking
- **Member portal**: Interactive maps for residents