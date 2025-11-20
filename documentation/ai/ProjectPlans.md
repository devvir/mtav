# Project Plans System

## Overview

The Project Plans system provides spatial visualization for housing cooperative projects using a clean, layered architecture that separates business logic from rendering logic. The system enables viewing interactive floor plans that show spatial relationships between units, common areas, and project boundaries.

## Problem Statement

Housing cooperatives need to visualize the spatial layout of their projects, showing:
- Unit locations and assignments to families
- Common areas (parks, courtyards, amenities)
- Project boundaries and circulation paths
- Multi-floor building layouts
- Interactive highlighting and navigation

The system must handle varying polygon shapes (not just rectangles) and provide a foundation for future editing capabilities.

## Architecture

### Three-Layer Component System

```
┌─────────────────────┐
│   Business Layer    │ ← Plans/Show.vue, PlanViewer.vue
│   (Domain Logic)    │   (Assignment logic, coloring rules)
├─────────────────────┤
│   Adapter Layer     │ ← usePlanCanvasAdapter.ts
│   (Data Transform)  │   (Plan → CanvasItem conversion)
├─────────────────────┤
│   Rendering Layer   │ ← PlanCanvas.vue, usePlanCanvas.ts
│   (Pure Graphics)   │   (Konva.js polygon rendering)
└─────────────────────┘
```

**Key Principle**: The rendering layer has no business logic knowledge. It only knows how to draw colored polygons.

### Data Flow

1. **Plan data** (from Laravel API) contains business entities
2. **Adapter** transforms Plan data into abstract CanvasItems with colors/labels
3. **Canvas** renders CanvasItems as interactive Konva.js shapes

## Database Structure

### Plans Table
```sql
plans
- id, project_id (FK)
- polygon (json) -- Project boundary coordinates
- width, height -- Canvas dimensions in chosen units
- unit_system (enum) -- 'meters', 'feet'
- created_at, updated_at
```

### Plan Items Table
```sql
plan_items
- id, plan_id (FK)
- type (string) -- 'unit', 'park', 'street', 'building', etc.
- polygon (json) -- Polygon coordinates [x1,y1,x2,y2,...]
- floor (integer) -- 0=ground, 1=first floor, etc.
- name (varchar, nullable) -- Display names for non-units
- metadata (json, nullable) -- Additional properties
- created_at, updated_at
```

### Units Table (Relationship)
```sql
units
- plan_item_id (FK) -- Links unit to its spatial representation
- identifier (string) -- User-facing identifier (e.g., "A-101")
- family_id (FK, nullable) -- Assignment status
```

## Model Relationships

### Core Relationships
```php
// Project has exactly one Plan
Project::class → Plan::class (HasOne)

// Plan contains multiple spatial elements
Plan::class → PlanItem::class (HasMany)

// Units reference their spatial representation
Unit::class → PlanItem::class (BelongsTo)
```

### Data Access Patterns
```php
// Load plan with all spatial data
$plan = Plan::with(['items.unit.type', 'project'])->find($id);

// Unit assignment logic (business rule)
$isAssigned = !is_null($unit->family_id);
```

## Frontend Architecture

### Component Hierarchy

#### 1. PlanCanvas.vue (Pure Rendering)
```vue
<script setup lang="ts">
interface Props {
  plan: Plan;
  highlightUnitId?: number; // Unit database ID for highlighting
}
</script>
```

**Responsibilities:**
- Accepts Plan data and optional highlight ID
- Uses adapter to convert Plan → CanvasItems
- Delegates rendering to usePlanCanvas composable
- Contains only template (4 lines) and setup (15 lines)

#### 2. usePlanCanvasAdapter.ts (Data Transform)
```typescript
interface CanvasItem {
  polygon: number[];
  floor?: number;
  label?: string;
  color: string;
  stroke: string;
  isHighlighted?: boolean;
}

function usePlanCanvasAdapter(plan: Plan, options: {
  highlightUnitId?: number;
  colorAssignedUnits?: string;
  colorAvailableUnits?: string;
}): { items: CanvasItem[], boundary?: number[] }
```

**Responsibilities:**
- Contains all business logic (unit assignment rules, color schemes)
- Transforms PlanItems into abstract CanvasItems
- Applies highlighting based on unit ID comparison
- Determines colors based on unit.family_id presence
- Maps unit.identifier to CanvasItem.label for display

#### 3. usePlanCanvas.ts (Konva Rendering)
```typescript
function usePlanCanvas({
  items: Ref<CanvasItem[]>;
  boundary?: Ref<number[]>;
}): { containerRef: Ref<HTMLDivElement> }
```

**Responsibilities:**
- Pure Konva.js rendering logic
- Draws polygons using provided colors/labels
- Handles floor offsets for multi-story visualization
- Manages hover effects and basic interactivity
- No business domain knowledge

#### 4. PlanViewer.vue (Future Extension Point)
```vue
<!-- Currently just wraps PlanCanvas -->
<!-- Future: Add toolbar, controls, legends, etc. -->
```

### TypeScript Types

#### Global Types (types/index.d.ts)
```typescript
interface Plan extends Resource {
  polygon: number[]; // Project boundary coordinates
  width: number;
  height: number;
  unit_system: 'meters' | 'feet';

  project: ApiResource<Project> | { id: number };
  items: PlanItem[];
}

interface PlanItem extends Resource {
  type: string; // 'unit', 'park', 'street', etc.
  polygon: number[]; // Shape coordinates
  floor: number;
  name: string | null;
  metadata: Record<string, any> | null;

  plan: ApiResource<Plan> | { id: number };
  unit?: ApiResource<Unit> | null; // Only present for type='unit'
}

interface Unit extends Resource {
  identifier: string | null; // User-facing identifier (display only)

  project: ApiResource<Project> | { id: number };
  type: ApiResource<UnitType> | { id: number };
  family: ApiResource<Family> | { id: number } | null; // Assignment
}
```

**Key Type Decisions:**
- **Unit.id** (number): Programming identifier for relationships/highlighting
- **Unit.identifier** (string): User-facing display label, may contain spaces/special chars
- **Polygon coordinates**: Flat array format [x1,y1,x2,y2,...] for direct Konva compatibility

## Resource Layer (API)

### UnitResource (Source of Truth)
```php
class UnitResource extends JsonResource {
    public function toArray(Request $_): array {
        return [
            ...$this->commonResourceData(), // id, timestamps
            'identifier' => $this->identifier,
            ...$this->relationsData(), // type, project, family
        ];
    }
}
```

**Critical Business Rule**: Unit assignment determined by presence of `family` relationship, not a separate `assigned` boolean field.

### PlanResource & PlanItemResource
```php
// Follow standard JsonResource patterns
// Automatic relationship loading with whenLoaded()
// Consistent ID-only fallbacks for unloaded relations
```

## Current Features

### Visualization Capabilities
- ✅ **Multi-floor support**: Visual stacking with opacity and offset
- ✅ **Unit assignment colors**: Green (assigned) vs Blue (available)
- ✅ **Interactive highlighting**: Hover effects and unit ID-based highlighting
- ✅ **Project boundaries**: Dashed outline showing project perimeter
- ✅ **Responsive canvas**: Auto-sizing to container dimensions
- ✅ **Polygon rendering**: Arbitrary shapes, not limited to rectangles

### Pages & Routes
- ✅ **Plans/Show.vue**: Full plan display with project context and statistics
- ✅ **Dev/Plans.vue**: Development testing interface
- ✅ **Resource routes**: RESTful PlanController with standard operations

### Color Scheme
```typescript
const colors = {
  assignedUnits: '#dcfce7',    // Light green
  availableUnits: '#e0f2fe',   // Light blue
  highlighted: '#fbbf24',      // Amber border
  commonAreas: {
    park: '#f0fdf4',
    street: '#f1f5f9',
    common: '#fefce8',
    amenity: '#fef3f2'
  }
};
```

## Development Environment

### Testing Route
- **URL**: `/dev/plans`
- **Purpose**: Live testing with real project data
- **Features**: Project selection, plan statistics, interactive canvas

### Sample Data Generation
```php
// ProjectObserver: Auto-creates Plan on project creation
// UnitObserver: Auto-creates PlanItem on unit creation
// Grid layout positioning for initial unit placement
```

## Architecture Benefits

### Clean Separation of Concerns
1. **Business logic** isolated in adapter layer
2. **Rendering logic** pure and reusable across contexts
3. **Component props** minimal and focused

### Maintainability
- Canvas can render any polygonal data, not just plans
- Business rules centralized in adapter
- TypeScript ensures type safety across layers
- Standard Laravel resource patterns

### Extensibility
- New canvas use cases: Add different adapters
- New business rules: Modify adapter logic
- New rendering features: Extend canvas composable
- Additional UI: Compose around PlanCanvas

## Future Development

### Immediate Opportunities
- **Unit click handling**: Navigate to unit details
- **Legend display**: Color coding explanation
- **Export functionality**: Image/PDF generation
- **Performance optimization**: Large plan rendering

### Advanced Features
- **Plan editing**: Drag/drop interface for spatial modification
- **Zoom/pan controls**: Navigation for large plans
- **Layer management**: Toggle floors, filter by type
- **CAD integration**: Import/export architectural drawings
- **Real-time updates**: Multi-user collaborative editing

### Technical Debt
- ❌ **No error handling**: Canvas rendering failures not gracefully handled
- ❌ **No loading states**: Plan data fetching shows no progress
- ❌ **No accessibility**: Screen reader support for spatial data
- ❌ **Limited mobile**: Touch interactions not optimized

## Implementation Guidelines

### Adding New Canvas Use Cases
1. Create adapter composable for your domain data
2. Transform domain objects → CanvasItems
3. Reuse existing usePlanCanvas for rendering
4. No modifications to canvas layer needed

### Modifying Business Logic
1. Update usePlanCanvasAdapter with new rules
2. Canvas automatically reflects changes
3. Keep rendering layer unchanged

### Extension Points
- **PlanViewer.vue**: Add controls, legends, toolbars
- **Plans/Show.vue**: Add plan-specific UI elements
- **Adapter options**: New color schemes, highlighting rules
- **Canvas events**: Click handlers, selection logic

This architecture demonstrates clean abstraction: the canvas draws colored shapes without knowing what a "unit" or "assignment" means, while business components focus on domain logic without graphics concerns.