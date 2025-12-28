# Project Plan SVG Visualization Library

A clean, modular SVG-based floor plan rendering system for housing cooperative projects. Built with proper separation of concerns and single responsibility principle.

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

Each layer has a single responsibility. Rendering components contain zero business logic.

## Public API

**Components:**
- `Plan` - Full floor plan viewer with business logic
- `Item` - Generic item renderer (any type: unit, park, street, etc.)

**Types:**
- `ScaleMode` - 'contain' | 'cover' | 'fill' | 'none'
- `PolygonConfig` - SVG styling configuration

See [index.ts](./index.ts) for full exports.

## Component Details

### Plan.vue
**Responsibility**: Accept Plan resource and delegate to Canvas

**Props:**
- `plan` (required) - ApiResource<Plan> with items[] and polygon
- `scaleMode?` - Scaling mode (default: 'contain')
- `highlightedItemId?` - Item ID to highlight

**Emits:**
- `itemClick(id)` - Item clicked
- `itemHover(id, hovering)` - Hover state changed

**Implementation:**
- Creates boundary PolygonConfig from plan.polygon
- Passes plan.items directly to Canvas
- Delegates events from Canvas to parent

### Canvas.vue
**Responsibility**: SVG rendering and coordinate transformation

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
- Calls useScaling() with raw props (pure function, not composable)
- Returns transformed items and viewBox
- Renders SVG with dynamic viewBox based on content
- Renders Boundary and Item components

### Item.vue
**Responsibility**: Render individual PlanItem with styling and label

**Props:**
- `item` (required) - PlanItem resource
- `highlighted?` - Boolean highlight state

**Emits:**
- `click(id)` - Item clicked
- `hover(id, hovering)` - Hover state changed

**Implementation:**
- Calls useItem(item) to get styling and reactive properties
- Uses useTextFitting(item.polygon, label) for dynamic font sizing
- Renders Polygon component with click/hover handlers
- Renders centered text label with contrasting color

### Boundary.vue
**Responsibility**: Render project boundary polygon (background layer)

**Props:**
- `polygon`, `fill`, `stroke`, `strokeWidth`, `opacity` - Standard PolygonConfig properties

## Composables

### useScaling(items, boundary, config, mode)

**Responsibility**: Calculate coordinate transformations for responsive scaling

**Parameters:**
- `mode: ScaleMode` - Scaling mode
- `items: PlanItem[]` - Items to transform
- `boundary: PolygonConfig | undefined` - Optional boundary
- `padding?: number` - Canvas outter padding, defaults to 1.5px
- `forceRatio?: number` - Force a ratio for the canvas, ignoring bounding box

**Returns: UseScaling**
```typescript
{
  items: PlanItem[],         // Transformed coordinates
  boundary?: PolygonConfig,  // Transformed boundary
  viewBox: string,           // SVG viewBox attribute
}
```

**Scaling Modes** (CSS object-fit semantics):
- `'contain'`: Proportional scaling, maintains aspect ratio, may letterbox
- `'cover'`: Proportional scaling, fills viewport, may crop
- `'fill'`: Independent X/Y scaling, fills completely, may distort

**Implementation:**
- Pure function (no side effects, no reactivity)
- Calculates bounding box from all polygons
- Selects scale factors based on mode
- Applies linear transformation to all polygon points
- Returns plain object with transformed data

### useItem(item)

**Responsibility**: Manage item styling and text properties

**Parameter:**
- `item: PlanItem` - The item to style

**Returns:**
```typescript
{
  style: Readonly<ItemStyle>,    // fill, stroke, strokeWidth, opacity
  centroid: ComputedRef<Point>,  // [x, y] for label positioning
  textColor: ComputedRef<string>, // '#000' or '#fff'
  isHovering: Ref<boolean>       // Hover state
}
```

**Styling:**
- Default colors by item.type (unit, park, street, common, amenity)
- Metadata overrides: item.metadata.fill, .stroke, .strokeWidth, .opacity
- Text color auto-contrasts based on background luminance (WCAG formula)
- Centroid calculated as average of all polygon points

### usePositioning()

**Responsibility**: Manage pan/zoom state (for future interactive features)

**Returns:**
```typescript
{
  viewState: Ref<ViewState>,     // { panX, panY, zoom }
  resetView(): void,              // Reset to home
  pan(deltaX, deltaY): void,      // Update pan
  setZoom(level): void,           // Set zoom (clamped 0.5-5)
  zoomBy(factor): void,           // Multiply zoom
  transform: ComputedRef<string>  // CSS transform string
}
```

**Status:** Infrastructure only, not integrated into rendering yet

## Type Definitions

### Point
```typescript
type Point = [number, number]; // [x, y] coordinate pair
```

### ScaleMode
```typescript
type ScaleMode = 'contain' | 'cover' | 'fill';
```

### PolygonConfig
```typescript
interface PolygonConfig {
  polygon: Point[];
  fill?: string;
  stroke?: string;
  strokeWidth?: number;
  opacity?: number;
}
```

### PlanItem (from backend)
```typescript
interface PlanItem extends Resource {
  plan_id: number;
  type: string;              // 'unit' | 'park' | 'street' | 'common' | 'amenity'
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

## Usage Example

```vue
<template>
  <Plan
    :plan="planData"
    scaleMode="contain"
    :highlightedItemId="selectedUnitId"
    @itemClick="handleUnitClick"
    @itemHover="handleUnitHover"
  />
</template>

<script setup lang="ts">
import { Plan } from '@/components/projectplan';

const planData = ref(null);
const selectedUnitId = ref<number>();

onMounted(async () => {
  const response = await fetch(`/api/plans/1`);
  planData.value = await response.json();
});

function handleUnitClick(unitId: number) {
  selectedUnitId.value = unitId;
  // Navigate to unit details, open modal, etc.
}

function handleUnitHover(unitId: number, hovering: boolean) {
  console.log(`Unit ${unitId} ${hovering ? 'entered' : 'left'}`);
}
</script>
```

## Notes

- All components are auto-imported; no manual imports needed
- useScaling is a pure function, not a Vue composable
- Transform and BoundingBox types are internal to useScaling
- ScalingResult type is internal to useScaling

## Architecture

```
┌─────────────────────────────────────┐
│   Plan.vue (Business Logic)         │ ← Transforms API data to shapes
├─────────────────────────────────────┤
│   Canvas.vue (Rendering)            │ ← Applies scaling, manages interactions
├─────────────────────────────────────┤
│   Polygon.vue + Boundary.vue        │ ← Pure SVG rendering
├─────────────────────────────────────┤
│   useScaling (Composable)           │ ← Coordinate transformations
│   usePositioning (Composable)       │ ← Pan/zoom state management
└─────────────────────────────────────┘
```

## Components

### Plan.vue
**Responsibility**: Business logic and data transformation

- Receives raw plan data from API
- Passes PlanItem[] items directly to Canvas
- Defines color schemes based on item/unit types
- Handles label derivation from item properties
- Emits semantic events (itemClick, itemHover)

**Props**:
- `plan` (required): Plan data with `items[]` and `polygon` array
- `scaleMode`: Scaling mode ('contain' | 'cover' | 'fill' | 'none')
- `highlightedItemId`: ID of item to highlight

**Events**:
- `itemClick(id)`: Emitted when an item is clicked
- `itemHover(id, hovering)`: Emitted on hover

### Canvas.vue
**Responsibility**: SVG rendering and scaling coordinate transformation

- Applies scaling transformations via `useScaling()`
- Renders SVG with viewBox for responsive scaling
- Manages hover/highlight states
- Coordinates Item and Boundary rendering
- Passes PlanItems to Item component for rendering

**Props**:
- `items`: Array of PlanItem resources
- `boundary`: PolygonConfig for project perimeter
- `config`: Canvas viewport configuration
- `scaleMode`: Scaling mode
- `highlightedItemId`: Item ID to highlight

**Events**:
- `itemClick(id)`: Item click event
- `itemHover(id, hovering)`: Item hover event

### Item.vue
**Responsibility**: Individual item rendering

- Renders a single PlanItem as SVG polygon with label
- Extracts label from item.name or item.unit?.identifier
- Handles hover styling and transitions
- Applies text labels centered on polygon centroid
- Automatically contrasts text color based on background
- Uses metadata styling (fill, stroke, strokeWidth) if provided

**Props**:
- `item`: PlanItem resource
- `highlighted`: Whether item is highlighted

**Events**:
- `click(id)`: Item clicked
- `hover(id, hovering)`: Hover state changed

### Boundary.vue
**Responsibility**: Project boundary rendering

- Renders dashed boundary polygon
- Stays in background layer
- No interaction handling

**Props**:
- `boundary`: BoundaryConfig object

## Composables

### useScaling
Handles responsive scaling transformations.

**Modes**:
- `'contain'` (default): Proportional scaling, maintains aspect ratio, letterboxes if needed
- `'cover'`: Fills viewport completely, may distort, maintains aspect ratio
- `'fill'`: Independent X/Y scaling, fills completely, may distort aspect ratio

**Returns**:
```typescript
{
  shapes: PlanItem[],       // Items with transformed polygon coordinates
  boundary?: PolygonConfig,  // Transformed boundary
  viewBox: string,           // SVG viewBox value
}
```

### usePositioning
Manages pan and zoom state for future interactive features.

**API**:
```typescript
const {
  viewState,              // Current pan/zoom state
  resetView(),           // Reset to home position
  pan(deltaX, deltaY),   // Pan viewport
  setZoom(level),        // Set zoom (clamped 0.5-5)
  zoomBy(factor),        // Zoom by factor
  transform              // CSS transform string
} = usePositioning();
```



## Usage Examples

### Basic Usage
```vue
<template>
  <Plan
    :plan="projectPlan"
    scaleMode="cover"
    @itemClick="handleUnitClick"
  />
</template>

<script setup>
import { Plan } from '@/components/projectplan';

const projectPlan = ref(null);

onMounted(async () => {
  const response = await fetch(`/api/plans/${planId}`);
  projectPlan.value = await response.json();
});

function handleUnitClick(unitId) {
  // Navigate to unit details
}
</script>
```

### Custom Coloring
```vue
<script setup>
// In Plan.vue, override getColorForItemType:

function getColorForItemType(itemType, unitType) {
  if (itemType === 'unit') {
    // Color by unit type
    switch (unitType?.slug) {
      case 'studio': return '#fef3f2';
      case 'duplex': return '#dcfce7';
      case 'standard': return '#e0f2fe';
      default: return '#f1f5f9';
    }
  }

  // Default colors for other item types
  const colorMap = {
    park: '#f0fdf4',
    street: '#f1f5f9',
    common: '#fefce8',
  };

  return colorMap[itemType] || '#e0f2fe';
}
</script>
```

### With Container Sizing
```vue
<template>
  <div class="plan-container">
    <Plan
      :plan="projectPlan"
      :config="{ width: 800, height: 600, bgColor: '#f8fafc' }"
      scaleMode="contain"
    />
  </div>
</template>

<style scoped>
.plan-container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
}
</style>
```

## Key Features

✅ **Fully responsive**: SVG scales to any container via viewBox
✅ **No dependencies**: Pure SVG rendering, no canvas libraries
✅ **Clean separation**: Business logic isolated in Plan.vue
✅ **Reusable**: Canvas and composables can be used independently
✅ **Extensible**: Easy to add new item types, colors, behaviors
✅ **Type-safe**: Full TypeScript support
✅ **Interactive**: Hover effects, click handling, highlighting

## File Structure

```
projectplan/
├── types.ts                           # Core type definitions
├── index.ts                           # Public API exports
├── Plan.vue                           # Business logic component
├── Canvas.vue                         # Rendering orchestrator
├── Polygon.vue                        # Shape rendering
├── Boundary.vue                       # Boundary rendering
└── composables/
    ├── useScaling.ts                  # Scaling transformations
    └── usePositioning.ts              # Pan/zoom state
```

## Future Enhancements

- Interactive pan/zoom controls
- Multi-floor layer management
- Drag-and-drop designer mode
- Performance optimization for large plans
- Touch gesture support
- Print/export functionality
- Accessibility improvements

## TODO - Known Issues & Improvements

### High Priority (Before Production)

- **Label Text Fitting** - Currently labels are hardcoded `text-xs` and will overflow small polygons. Need to:
  - Calculate polygon bounding box
  - Dynamically scale font size based on available space and label length
  - Implement truncation/ellipsis for labels that don't fit
  - Consider wrapping for multi-line labels
  - Add to `useItem` composable to return `fontSize` and `textOverflow` state

### Medium Priority (After Core Review)

- [ ] Pan/zoom functionality (usePositioning stub exists)
- [ ] Touch gesture support
- [ ] Performance optimization for large plans (1000+ shapes)
- [ ] Multi-floor layer management

### Low Priority (Future Phases)

- [ ] Drag-and-drop designer mode
- [ ] Print/export functionality
- [ ] Accessibility improvements (ARIA labels, keyboard navigation)
