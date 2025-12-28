# Project Plans System

## Overview

The Project Plans system provides responsive SVG-based spatial visualization for housing cooperative projects. Displays interactive floor plans showing units, common areas, and project boundaries using a clean layered architecture that separates business logic from rendering.

**Status:** ✅ Production ready - SVG-based rendering complete, responsive scaling working, actively used in project views.

**Stage 1 Editor:** 🚧 In progress - Drag-and-drop positioning with grid snapping implemented, collision detection pending.

## Architecture

```
Plan.vue (Business Logic)
    ↓
Canvas.vue (Scaling & Rendering)
    ↓
Item.vue (Individual Items) + Boundary.vue (Background)
    ↓
Polygon.vue (SVG Primitives)
```

**Key Principle:** Each layer has a single responsibility. Rendering components contain zero business logic.

## Component Details

### Plan.vue

**Responsibility:** Accept Plan resource from API and coordinate rendering

**Props:**
- `plan` (required) - ApiResource<Plan>
- `scaleMode?` - Scaling mode (default: 'contain')
- `highlightedItemId?` - Item ID to highlight

**Emits:**
- `itemClick(id)` - Item clicked
- `itemHover(id, hovering)` - Hover state changed

**Implementation:**
- Creates boundary PolygonConfig from plan.polygon
- Passes plan.items directly to Canvas (no transformation)
- Delegates Canvas events to parent

### Canvas.vue

**Responsibility:** SVG rendering and coordinate transformation

**Props:**
- `items` (required) - PlanItem[] array
- `boundary?` - PolygonConfig for project perimeter
- `scaleMode?` - Scaling mode (default: 'contain')
- `forceRatio?` - Force a ratio for the canvas ignoring contents bounding box
- `bgColor` - Fill color (background color) for the whole canvas
- `highlightedItemId?` - Item ID to highlight

**Emits:**
- `itemClick(id)` - Forwards from Item
- `itemHover(id, hovering)` - Forwards from Item

**Implementation:**
- Calls useScaling(items, boundary, config, scaleMode) - wrapping scale() in computed() for reactivity
- Renders SVG with dynamic viewBox
- Renders Boundary (background) and Item components
- No business logic

**Reactivity:** The scale() call is wrapped in computed() to reactively update when items or boundary change (essential for editor drag-and-drop).

### Item.vue

**Responsibility:** Render individual PlanItem with styling and label

**Props:**
- `item` (required) - PlanItem resource
- `highlighted?` - Boolean highlight state

**Emits:**
- `click(id)` - Item clicked
- `hover(id, hovering)` - Hover state changed

**Implementation:**
- Calls useItem(item) for styling and properties
- Calls useTextFitting(item.polygon, label) for font sizing
- Renders Polygon component with click/hover handlers
- Renders centered text label with auto-contrasted color

### Boundary.vue

**Responsibility:** Project boundary rendering (background layer)

**Props:**
- Standard PolygonConfig properties (points, fill, stroke, etc.)

## Composables

### useScaling(items, boundary, config, mode)

**Type:** Pure function (not a Vue composable)

**Responsibility:** Calculate coordinate transformations for responsive scaling

**Input Parameters:**
- `mode: ScaleMode` - Scaling mode
- `items: PlanItem[]` - Items to transform
- `boundary: PolygonConfig | undefined` - Optional boundary
- `padding?: number` - Canvas outter padding, defaults to 1.5px
- `forceRatio?: number` - Force a ratio for the canvas, ignoring bounding box

**Returns:**
```typescript
{
  items: PlanItem[],        // Transformed coordinates
  boundary?: PolygonConfig, // Transformed boundary
  viewBox: string,          // SVG viewBox attribute
}
```

**Scaling Modes** (CSS object-fit semantics):

| Mode | Behavior |
|------|----------|
| `'contain'` | Proportional scaling, maintains aspect ratio, may letterbox |
| `'cover'` | Proportional scaling, fills viewport, may crop |
| `'fill'` | Independent X/Y scaling, fills completely, may distort |

**Algorithm:**
1. Calculate bounding box of all polygons
2. Determine scale factors based on mode
3. Calculate center offsets for positioning
4. Apply linear transformation: `new = old * scale + offset`
5. Generate SVG viewBox with stroke padding

### useItem(itemGetter)

**Responsibility:** Manage item styling and text properties

**Input:**
- `itemGetter: () => PlanItem` - Getter function for reactive item access

**Returns:**
```typescript
{
  style: Readonly<ItemStyle>,    // fill, stroke, strokeWidth, opacity
  centroid: ComputedRef<Point>,  // [x, y] for label positioning (reactive to polygon changes)
  textColor: ComputedRef<string>, // '#000' or '#fff'
  isHovering: Ref<boolean>       // Hover state
}
```

**Implementation:**
- Default colors by item.type (unit, park, street, common, amenity)
- Metadata overrides: item.metadata.fill, .stroke, .strokeWidth, .opacity
- Text color auto-contrasts using WCAG luminance formula
- Centroid = average of all polygon points (computed reactively)

**Reactivity:** Accepts getter function to make centroid and other values reactive to polygon changes during drag operations.

### useTextFitting(polygonGetter, label)

**Responsibility:** Calculate optimal font size for text within polygon

**Input:**
- `polygonGetter: () => Point[]` - Getter function for reactive polygon access
- `label: ComputedRef<string>` - Text label to fit

**Returns:**
```typescript
{
  fontSize: ComputedRef<number>,   // Optimal font size in pixels (reactive)
  isTruncated: ComputedRef<boolean> // Whether text will be truncated
}
```

**Implementation:**
- Calculates polygon width from bounding box
- Aims for 1-2 lines with 8px padding
- Font size range: 10px - 14px
- Approximates 7px per character at 12px font

**Reactivity:** Accepts getter function to recalculate font size when polygon changes during drag operations.

### usePositioning()

**Status:** Infrastructure only, not integrated yet

**Purpose:** Pan/zoom state management for future interactive features

**Provides:**
- viewState: { panX, panY, zoom }
- Pan/zoom control methods
- CSS transform output

## TypeScript Types

**Public API** (exported from index.ts):
- `ScaleMode` - 'contain' | 'cover' | 'fill' | 'none'
- `PolygonConfig` - Point[], fill, stroke, strokeWidth, opacity

**Internal** (in useScaling.ts only):
- `Transform` - scaleX, scaleY, offsetX, offsetY
- `BoundingBox` - minX, minY, maxX, maxY, width, height
- `ScalingResult` - Return type of useScaling

**From Backend**:

PlanItem:
```typescript
interface PlanItem extends Resource {
  plan_id: number;
  type: string;              // 'unit' | 'park' | 'street' | etc.
  polygon: Point[];          // [[x1, y1], [x2, y2], ...]
  floor: number;
  unit?: ApiResource<Unit>;
  metadata?: {
    fill?: string;
    stroke?: string;
    strokeWidth?: number;
    opacity?: number;
  };
}
```

Plan:
```typescript
interface Plan extends Resource {
  polygon: Point[];           // Project boundary
  width: number;
  height: number;
  unit_system: 'meters' | 'feet';

  project: ApiResource<Project>;
  items: PlanItem[];
}
```

## Polygon Format

**Everywhere:** `Point[]` = `[[x, y], [x, y], ...]`

- **Stored in DB:** Cached as JSON column
- **Frontend Props:** Point[] arrays
- **Backend Service:** Point[] arrays
- **Canvas:** Point[] arrays

**Consistency:** Never flatten or transform format between layers.

## Styling

**Default colors by type** (from useItem):
- `unit`: #e0f2fe (light blue)
- `park`: #f0fdf4 (light green)
- `street`: #f1f5f9 (light slate)
- `common`: #fefce8 (light yellow)
- `amenity`: #fef3f2 (light red)

**Override via metadata:**
```typescript
item.metadata = {
  fill: '#custom-color',
  stroke: '#custom-stroke',
  strokeWidth: 2,
  opacity: 0.8
}
```

Text color auto-contrasts against background fill.

## Usage Example

```vue
<template>
  <Plan
    :plan="projectPlan"
    scaleMode="contain"
    :highlightedItemId="selectedUnitId"
    @itemClick="handleUnitClick"
    @itemHover="handleUnitHover"
  />
</template>

<script setup lang="ts">
import { Plan } from '@/components/projectplan';

const projectPlan = ref(null);
const selectedUnitId = ref<number>();

onMounted(async () => {
  const response = await fetch(`/api/plans/1`);
  projectPlan.value = await response.json();
});

function handleUnitClick(unitId: number) {
  selectedUnitId.value = unitId;
  // Navigate to unit details, open modal, etc.
}

function handleUnitHover(unitId: number, hovering: boolean) {
  console.log(`Unit ${unitId} ${hovering ? 'hovered' : 'left'}`);
}
</script>
```

## Backend Integration (PlanService)

**File:** `/app/Services/PlanService.php`

Creates default plans and positions units in grid layout.

**Key Methods:**

`addProject(Project)` - Creates default plan with boundary polygon

`addUnit(Unit)` - Creates PlanItem with auto-calculated position in grid layout
- Calculates available space from plan boundary
- Positions units in 10-per-row grid
- Returns 60x60 unit if plan has no polygon

**Polygon Format:** Always `[[x, y], [x, y], ...]` - nested Point array

## Development Notes

### File Structure
```
resources/js/components/projectplan/
├── Plan.vue              # Business logic layer
├── Item.vue              # Individual item renderer
├── core/
│   ├── Canvas.vue        # Rendering & scaling
│   ├── Polygon.vue       # SVG primitive
│   └── Boundary.vue      # Background boundary
├── editor/
│   ├── EditorCanvas.vue  # Drag-and-drop editor UI
│   └── composables/
│       └── usePlanEditor.ts  # Editor logic (coordinate conversion, translation)
├── composables/
│   ├── useScaling.ts     # Coordinate transformations (pure function)
│   ├── useItem.ts        # Item styling
│   ├── useTextFitting.ts # Font size calculation
│   └── usePositioning.ts # Pan/zoom infrastructure
├── types.ts              # Public type definitions
├── index.ts              # Public API exports
└── README.md             # Component documentation
```

### Key Design Principles

1. **Direct resource passing** - Components accept ApiResource objects, not primitives
2. **No intermediate conversions** - Plan.items go directly to Canvas
3. **Pure scaling function** - useScaling is math, not reactive
4. **Single responsibility** - Each component does one thing
5. **Events bubble up** - Canvas emits, Plan decides what to do
6. **Minimal public API** - Only 3 types exported, rest internal

### Testing

Dev pages at `/dev/plans`:
- ScalingModesGrid.vue - Tests all 3 scaling modes
- SvgCanvasTest.vue - Example units

## Performance

- **Rendering:** Native SVG, no canvas overhead
- **Responsiveness:** viewBox scaling, no DOM resize
- **Scalability:** Efficient with 100+ shapes
- **Interactions:** Direct SVG event propagation

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
- `scaleMode?` - Scaling mode: 'contain' | 'cover' | 'fill' (default: 'scale')
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
- `scaleMode?` - Scaling mode: 'contain' | 'cover' | 'fill' (default: 'scale')
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

type ScaleMode = 'contain' | 'cover' | 'fill' | 'none';

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
      scaleMode="contain"
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
  scaleMode="cover"
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

---

## Editor Components (Stage 1 - In Progress)

### EditorCanvas.vue

**Location:** `/resources/js/components/projectplan/editor/EditorCanvas.vue`

**Responsibility:** Interactive drag-and-drop editor for plan items

**Props:**
- `plan` (required) - ApiResource<Plan>

**Features Implemented:**
- ✅ Drag-and-drop item positioning
- ✅ Ghost preview during drag (matches visual size of item in canvas)
- ✅ Grid snapping (5-unit grid for easy alignment)
- ✅ Drop animation (subtle pulse effect)
- ✅ Real-time position updates
- ✅ Changes tracking (enables/disables reset button)

**State:**
- `items` - Reactive copy of plan.items (mutated on drop)
- `hasChanges` - Boolean flag for tracking modifications
- `draggedItemId` - Currently dragged item ID
- `isDragging` - Drag state flag
- `ghostPosition` - Cursor position for ghost preview
- `justDroppedItemId` - Recently dropped item (for animation)

**Workflow:**
1. User clicks and drags an item (`handleItemMouseDown`)
2. Ghost preview follows cursor at scaled size (`handleMouseMove`)
3. On release, coordinates are converted and snapped to grid (`handleMouseUp`)
4. Item polygon is translated to new position
5. Drop animation highlights the moved item
6. Changes flag enables reset button

**Key Implementation Details:**
```vue
// Snap dropped position to nearest multiple of 5
const gridSize = 5;
const snappedX = Math.round(canvasX / gridSize) * gridSize;
const snappedY = Math.round(canvasY / gridSize) * gridSize;
```

**Reset Functionality:**
```typescript
const resetAllChanges = () => {
  items.value = plan.items;  // Restore original
  hasChanges.value = false;
};
```

### usePlanEditor Composable

**Location:** `/resources/js/components/projectplan/editor/composables/usePlanEditor.ts`

**Responsibility:** Complex algorithms for drag-and-drop coordinate conversion and polygon manipulation

**Input:**
```typescript
{
  containerRef: Ref<HTMLDivElement | undefined>,  // Canvas container element
  draggedItem: ComputedRef<PlanItem | undefined>, // Currently dragged item
  boundary: ComputedRef<PolygonConfig>            // Plan boundary for scale calculation
}
```

**Returns:**
```typescript
{
  ghostDimensions: ComputedRef<{ width: number, height: number }>,
  screenToCanvasCoords: (screenX: number, screenY: number) => Point,
  translateItemTo: (item: PlanItem, newX: number, newY: number) => Point[]
}
```

**Algorithms:**

#### 1. Ghost Dimensions (Visual Size Matching)
```typescript
// Calculate how much canvas is scaled
const canvasScaleX = containerWidth / boundaryWidth;
const canvasScaleY = containerHeight / boundaryHeight;

// Apply same scale to item dimensions
const ghostWidth = itemWidth * canvasScaleX;
const ghostHeight = itemHeight * canvasScaleY;
```

**Purpose:** Makes ghost preview match exact visual size of item in scaled canvas

#### 2. Screen to Canvas Coordinates
```typescript
// Get mouse position relative to container
const relativeX = screenX - container.left;
const relativeY = screenY - container.top;

// Convert to canvas coordinates (inverse scale)
const canvasX = relativeX / scaleX;
const canvasY = relativeY / scaleY;
```

**Purpose:** Converts pixel coordinates (mouse position) to canvas coordinate system

#### 3. Polygon Translation
```typescript
// Calculate current centroid
const currentCenterX = (maxX + minX) / 2;
const currentCenterY = (maxY + minY) / 2;

// Calculate offset to new position
const offsetX = newCenterX - currentCenterX;
const offsetY = newCenterY - currentCenterY;

// Translate all points
return polygon.map(([x, y]) => [x + offsetX, y + offsetY]);
```

**Purpose:** Moves item polygon to new position while preserving shape

**Why Separate Composable:**
- Keeps EditorCanvas.vue focused on "what" not "how"
- Complex algorithms isolated and testable
- Easy to extend with more editor features (rotation, scaling, etc.)

### Editor UI Features

**Ghost Preview:**
- Fixed positioned div following cursor
- Contains Canvas component with single dragged item
- Scaled to match visual size in main canvas
- 70% opacity for visual feedback

**Drop Animation:**
```css
@keyframes drop-pulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.025); opacity: 0.975; }
}
```
Duration: 300ms

**Grid Snapping:**
- Grid size: 5 units (configurable)
- Applied on drop, not during drag
- Snap formula: `Math.round(coord / gridSize) * gridSize`
- Example: 23 → 25, 22 → 20

**Visual Feedback:**
- Yellow border (`#fbbf24`) on highlighted/hovered items
- Increased stroke width on highlighted items
- Smooth 300ms transitions on all position changes
- Cursor changes to grab/grabbing during drag

### Reactivity Architecture

**Problem Solved:**
When dragging items, polygon coordinates change but labels weren't updating because composables captured initial values at setup time.

**Solution:**
Getter functions instead of direct values:
```typescript
// OLD (non-reactive)
const { centroid } = useItem(item);

// NEW (reactive to prop changes)
const { centroid } = useItem(() => item);
const { fontSize } = useTextFitting(() => item.polygon, label);
```

The composables internally create computed refs from getters, making all derived values reactive to prop changes.

**Key Insight:** Vue 3.5 getter pattern provides reactivity without excessive ref wrapping.

### Stage 1 Status: ✅ COMPLETE

**Completed:**
- ✅ Drag-and-drop positioning
- ✅ Ghost preview with accurate visual sizing
- ✅ Screen-to-canvas coordinate conversion
- ✅ Grid snapping for alignment
- ✅ Drop animation
- ✅ Changes tracking and reset
- ✅ Reactive updates (labels, text, polygons)
- ✅ Backend persistence (Controller, FormRequest, Service, Tests)
- ✅ Save button and API integration
- ✅ Route: `GET /plans/{id}/edit` and `PATCH /plans/{id}`

**Backend Architecture:**
- `PlanController@edit` - Loads plan with items for editing
- `PlanController@update` - Persists polygon changes
- `UpdatePlanRequest` - Validates plan boundary and item polygons
- `PlanService::updatePlan()` - Delegates to Polygons service
- `Polygons::update()` - Atomic transaction for all updates
- **Authorization:** Admin-only (via PlanPolicy - pending)

**API Endpoint:**
```http
PATCH /plans/{id}
Content-Type: application/json

{
  "polygon": [[0, 0], [800, 0], [800, 600], [0, 600]],
  "items": [
    { "id": 1, "polygon": [[10, 10], [110, 10], [110, 110], [10, 110]] },
    { "id": 2, "polygon": [[120, 10], [220, 10], [220, 110], [120, 110]] }
  ]
}
```

**Validation Rules:**
- Plan polygon: min 3 points, each point must be [x, y] numeric pair
- Items: array (may be empty), each item must have `id` and `polygon`
- Item polygons: same structure as plan polygon
- Items must belong to the plan being updated

**Test Coverage:**
- Unit tests: Service atomicity, transaction rollback, partial updates
- Feature tests: Happy path, all validation rules, authorization, edge cases
- All tests use universe.sql fixture (20-30x faster than factories)

**Stage 2:** Item properties panel - Edit labels, colors, and metadata for individual items

**Stage 3:** Undo/redo history - Track changes and allow reverting to previous states

**Stage 4:** Shape transformation - Rotation, scaling, and reshaping (add/remove edges, move vertices) to modify unit geometry beyond simple positioning

**Stage 5:** Collision avoidance - Detect and prevent overlapping items with visual feedback

**Stage 6:** Multi-select and bulk operations - Select and manipulate multiple items simultaneously

### Testing the Editor

**Access:** `/plans/{id}/edit` (desktop only with mouse)

**Manual Testing:**
1. Navigate to `/plans/{id}/edit`
2. Drag items around (ghost preview follows cursor)
3. Drop items (snaps to 5-unit grid with pulse animation)
4. Click "Save Changes" to persist
5. Refresh page to verify positions persisted
6. Make changes and click "Reset All Changes" to revert

**Known Limitations:**
- No collision prevention yet (Stage 5)
- Desktop only (no touch support)
- No undo/redo history yet (Stage 3)
- Admin-only access (authorization pending PlanPolicy implementation)
