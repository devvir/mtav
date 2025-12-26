# Project Plan SVG Visualization Library

A clean, modular SVG-based floor plan rendering system for housing cooperative projects. Built with proper separation of concerns and single responsibility principle.

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
- `autoScale`: Scaling mode ('contain' | 'cover' | 'fill')
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
- `autoScale`: Scaling mode
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
  preserveAspectRatio: string // SVG aspect ratio setting
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
    autoScale="scale"
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
      autoScale="scale"
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
