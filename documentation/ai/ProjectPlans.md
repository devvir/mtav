# Project Plans System

## Overview

The Project Plans system provides responsive SVG-based spatial visualization for housing cooperative projects. It displays interactive floor plans showing relationships between units, common areas, and project boundaries using a clean layered architecture that separates business logic from rendering.

## Current Status (Dec 26, 2025)

**✅ Production Ready** - Core visualization complete and actively used:
- SVG-based rendering (replaced Konva canvas library Dec 26)
- Three object-fit-compatible scaling modes (contain, cover, fill)
- Responsive viewBox-based scaling
- Used in `/lottery/{id}` project plan views
- Dev testing at `/dev/plans`

## Problem It Solves

Housing cooperatives need to visualize spatial layouts:
- Unit locations and family assignments
- Common areas (parks, courtyards, amenities, corridors)
- Project boundaries and scale
- Multi-floor building layouts
- Interactive unit highlighting and navigation

The system handles arbitrary polygon shapes and provides responsive scaling that adapts to different container sizes.

## Architecture

### Component Hierarchy

```
Plans/Show.vue (Page Layer)
    ↓
ProjectPlan.vue (Card Container)
    ↓
Plan.vue (Business Logic)
    ↓
Canvas.vue (Scaling & Rendering)
    ↓
Polygon.vue + Boundary.vue (SVG Primitives)
```

**Key Principle:** Each layer has a single responsibility. Rendering components contain zero business logic.

## Component Details

### Plan.vue (Business Logic)

Accepts Plan resource and passes items directly to Canvas. Handles business logic like boundary creation and event delegation.

**Location:** `/resources/js/components/projectplan/Plan.vue`

**Props:**
- `plan` (required) - ApiResource<Plan>
- `backgroundColor?` - SVG background color (default: 'transparent')
- `autoScale?` - Scaling mode: 'contain' | 'cover' | 'fill' (default: 'scale')
- `highlightedItemId?` - ID of unit to highlight

**Emits:**
- `itemClick(id)` - Item clicked
- `itemHover(id, hovering)` - Item hover state

**Responsibility:**
- Accepts raw Plan resource (no intermediate objects for consumers)
- Passes plan.items[] directly to Canvas
- Creates boundary from plan.polygon
- Applies color scheme based on item.type
- Handles item clicks (can check item type to decide behavior)

**Example - Item click handling:**
```typescript
function handleItemClick(id: number) {
  const item = props.plan?.items?.find((i: any) => i.id === id);
  if (item?.type === 'unit') {
    // Open modal, navigate, etc.
  }
}
```

### Canvas.vue (SVG Rendering & Scaling)

Pure rendering engine. Accepts PlanItem[] directly and transforms them internally to ShapeConfig for the scaling composable.

**Location:** `/resources/js/components/projectplan/core/Canvas.vue`

**Props:**
- `items` - PlanItem[]
- `boundary?` - PolygonConfig
- `backgroundColor?` - SVG background color (default: '#f8fafc')
- `autoScale?` - Scaling mode: 'contain' | 'cover' | 'fill' (default: 'scale')
- `highlightedItemId?` - Item ID to highlight

**Responsibilities:**
- Accepts PlanItem[] directly from Plan
- Applies useScaling transformations to item coordinates
- Renders SVG with dynamic viewBox based on content
- SVG adapts to container width via CSS (w-full)
- Coordinate scaling and centering
- Emits itemClick and itemHover events

**No business logic:** Just rendering and coordinate math.

### Item.vue (Individual Shape Renderer)

Renders a single PlanItem as an SVG group with polygon and label.

**Props:**
- `item` - PlanItem resource
- `config` - PolygonConfig (fill, stroke, opacity, strokeWidth)
- `highlighted?` - Boolean

**Responsibilities:**
- Extract label from `item.unit?.identifier || item.name`
- Calculate optimal label font size via useTextFitting
- Render polygon with hover/click detection
- Detect text color contrast from background fill
- Emit click and hover events with item.id

## Scaling System

### useScaling Composable

Provides responsive coordinate transformations with three CSS object-fit-compatible modes.

**Location:** `/resources/js/components/projectplan/composables/useScaling.ts`

**Input:**
- `shapes`, `boundary` - Collections of coordinates
- `config` - Canvas dimensions (width, height)
- `mode` - Scaling mode ('contain' | 'cover' | 'fill')
- `viewBoxPadding` - SVG stroke overflow padding (default: 1.5px)

**Output:**
```typescript
{
  shapes: PlanItem[],         // Transformed coordinates in item.polygon
  boundary?: PolygonConfig,   // Transformed boundary
  viewBox: string             // SVG viewBox attribute
}
```

**Modes:**

| Mode | Formula | Behavior |
|------|---------|----------|
| **contain** | `scale = min(scaleX, scaleY)` | Uniform scaling, maintains aspect ratio, letterboxes if needed |
| **cover** | `scale = max(scaleX, scaleY)` | Uniform scaling, maintains aspect ratio, may crop |
| **fill** | Independent X/Y scaling | Fills completely, may distort aspect ratio |

**How It Works:**
1. Calculates bounding box of all coordinates
2. Determines scale factors: `scaleX = canvasWidth / bboxWidth`, etc.
3. Selects appropriate scale per mode
4. Computes offsets to center: `offset = (canvasDim - scaledDim) / 2`
5. Transforms coordinates: `new = old * scale + offset`
6. Returns SVG viewBox with stroke padding

## TypeScript Types

### Core Types
**File:** `/resources/js/components/projectplan/types.ts`

```typescript
interface PolygonConfig {
  points: Point[];           // [[x,y], [x,y], ...]
  fill?: string;             // CSS color
  stroke?: string;           // CSS color
  strokeWidth?: number;
  opacity?: number;
}

type AutoScale = 'contain' | 'cover' | 'fill';

interface Transform {
  scaleX: number;
  scaleY: number;
  offsetX: number;
  offsetY: number;
}

interface BoundingBox {
  minX: number;
  minY: number;
  maxX: number;
  maxY: number;
  width: number;
  height: number;
}
```

### Global Types
**File:** `/resources/js/types/index.d.ts`

```typescript
interface Plan extends Resource {
  polygon: Point[];               // [[x,y], [x,y], ...] project boundary
  width: string;                  // Canvas width
  height: string;                 // Canvas height
  unit_system: 'meters' | 'feet';

  project: ApiResource<Project>;
  items: PlanItem[];              // All spatial elements
}

interface PlanItem extends Resource {
  type: string;                   // 'unit' | 'park' | 'street' | 'building' | etc.
  polygon: Point[];               // [[x,y], [x,y], ...] shape coordinates
  floor: number;
  name?: string;
  metadata?: Record<string, any>;

  plan_id: number;
  unit?: Unit;                    // Only for type='unit'
  plan: ApiResource<Plan>;
}

interface Unit extends Resource {
  identifier: string;             // Display label (e.g., 'A-101')
  family?: Family;                // Lottery assignment (nullable)
  project_id: number;
  type_id: number;

  plan_item?: PlanItem;
}

type Point = [number, number];
```

## Usage Example

### Basic Plan Display

```vue
<script setup lang="ts">
import { Plan } from '@/components/projectplan';

defineProps<{
  plan: Plan;
}>();
</script>

<template>
  <div class="h-96 w-full">
    <Plan
      :plan
      autoScale="contain"
    />
  </div>
</template>
```

### With Custom Styling

```vue
<Plan
  :plan
  :config="{
    backgroundColor: '#ffffff',
    width: 1000,
    height: 800
  }"
  autoScale="cover"
/>
```

### With Highlighting

```vue
<Plan
  :plan
  :highlightedItemId="selectedUnitId"
  @itemClick="navigateToUnit"
/>
```

## Backend - Polygon Storage (PlanService)

Stores polygons in Point[] format: `[[x,y], [x,y], ...]`

**File:** `/app/Services/PlanService.php`

```php
public function addProject(Project $project): Plan
{
    return Plan::create([
        'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
        'width' => 800,
        'height' => 600,
    ]);
}

private function getNextAvailablePosition(Plan $plan): array
{
    // Calculate from boundary
    $xs = array_column($plan->polygon, 0);
    $ys = array_column($plan->polygon, 1);

    // Return as Point[] with corners
    return [
        [$x, $y],                              // Top-left
        [$x + $unitWidth, $y],                 // Top-right
        [$x + $unitWidth, $y + $unitHeight],   // Bottom-right
        [$x, $y + $unitHeight],                // Bottom-left
    ];
}
```

**Key:** Polygons are stored as Point[] arrays, not flat number[] arrays.

## Common Customizations

### Change Colors by Type

Edit Plan.vue `getColorForItemType()`:
```typescript
function getColorForItemType(itemType: string, _unitType?: any): string {
  const colorMap: Record<string, string> = {
    unit: '#e0f2fe',      // Light blue
    park: '#f0fdf4',      // Light green
    street: '#f1f5f9',    // Light slate
    common: '#fefce8',    // Light yellow
    amenity: '#fef3f2',   // Light red
  };
  return colorMap[itemType] || '#e0f2fe';
}
```

### Custom Styling

## Styling

Item styling comes from two sources, with metadata overriding defaults:

**Default styling** is based on `item.type`:
- `unit`: Light blue (#e0f2fe)
- `park`: Light green (#f0fdf4)
- `street`: Light slate (#f1f5f9)
- `common`: Light yellow (#fefce8)
- `amenity`: Light red (#fef3f2)

**Override styling** via `item.metadata`:
```typescript
item.metadata = {
  fill: '#custom-color',
  stroke: '#custom-stroke',
  strokeWidth: 3,
}
```

## Performance

- **Rendering:** Native SVG, no canvas overhead
- **Responsiveness:** viewBox scaling, no DOM resizing
- **Scalability:** Works with 100+ shapes efficiently
- **Interactions:** Direct SVG event propagation

## Development & Testing

### Dev Test Page

**URL:** `/dev/plans`

Shows:
- ScalingModesGrid.vue - Tests all 3 modes across container ratios
- SvgCanvasTest.vue - Example with test units
- SingleUnit.vue - Individual unit rendering

### File Structure

```
resources/js/components/projectplan/
├── Canvas.vue              # SVG rendering & viewBox
├── Plan.vue                # Business logic transformation
├── Polygon.vue             # Individual polygon shape
├── Boundary.vue            # Project boundary
├── types.ts                # TypeScript definitions
├── index.ts                # Public API exports
├── composables/
│   └── useScaling.ts       # Scaling calculations
└── README.md               # Library documentation
```

## Future Development

### Planned Features
- **Drag-drop Designer:** Move shapes, edit properties
- **Multi-layer:** Toggle floors, filter by type
- **Legend:** Color coding explanation UI
- **Export:** SVG/PNG image generation
- **Pan/Zoom:** Mouse wheel or pinch controls
- **Touch:** Mobile gesture support

### Architecture Ready For
- Adding pan/zoom (usePositioning composable stub ready)
- Drag-drop shape editing (event handlers in place)
- Real-time collaborative editing
- Large plan virtualization

## Key Design Principles

1. **Resources are first-class** - Components accept ApiResource objects directly, not destructured properties
2. **No intermediate structures** - Canvas accepts PlanItem[], Plan accepts Plan resource
3. **One responsibility per component** - Plan handles business logic, Canvas handles rendering, Item handles a single shape
4. **Polygon format consistency** - Point[] format [[x,y], [x,y], ...] everywhere (stored, transmitted, used)
5. **Canvas is dumb** - No business logic, just rendering and coordinate math
6. **Events bubble up** - Canvas emits itemClick/itemHover, Plan decides what to do based on item type
