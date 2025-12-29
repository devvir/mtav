// Copilot - Pending review

# Plan Editor System Design

## Overview

Interactive drag-and-drop editor for floor plan layout design. Allows admins to modify unit positions and add new items to project plans.

**Key Principles:**
- Desktop-only (no touch support)
- Real-time undo/redo (50 state limit)
- Backend persistence with validation
- Grid-based snapping (5px)
- Intuitive drag-drop UX similar to modern design tools
- Clean component architecture with event-based communication

## Current Status

### ✅ Stage 1: Complete - Drag & Drop Foundation
- Drag existing items with mouse
- Visual feedback (ghost item, drop animation)
- Grid snapping (5px)
- Undo/redo history (50 states)
- Desktop-only UI with mobile notice
- EditorSidebar with action buttons (right side, 120px width)
- Event-based architecture (Edit.vue orchestrates, EditorCanvas handles visuals)

### ✅ Stage 2: Complete - Backend Persistence
- PlanController with update endpoint
- UpdatePlanRequest with validation
- PlanService with transaction handling
- Tested and working (Pest tests)
- Plan + all PlanItem polygons saved atomically

### 🚧 Stage 3: Planned - Reshaping & Boundaries
**Focus:** Advanced polygon manipulation

**Features:**
1. **Resize Items**
   - Corner/edge handles for scaling
   - Maintain aspect ratio option
   - Min size validation (30×30px)

2. **Rotate Items**
   - Rotation handle or keyboard shortcuts
   - Snap to 15° increments (configurable)

3. **Vertex Editing**
   - Add vertex: Click on edge
   - Move vertex: Drag existing point
   - Delete vertex: Right-click (min 3 vertices)
   - Max 10 vertices per item (UI restriction)
   - Self-intersection prevention

4. **Out-of-Boundary Drag Fix** ⚠️ IMPORTANT
   - Currently: Can drag items outside canvas (only centroid checked)
   - Fix: Check entire item bounding box, not just centroid
   - Behavior: Ignore mousemove when dragged item leaves canvas
   - Prevent dropping items partially/fully outside canvas

**Technical Considerations:**
- Bounding box calculation for each item
- Real-time boundary checking during drag
- Visual feedback when approaching boundary
- Grid snapping still applies within boundaries

### 🚧 Stage 4: Planned - Adding New Items
**Focus:** Create new items from templates

**Features:**
1. **Unit Items**
   - Drag from unit type palette
   - Auto-assign to next available unit
   - Default size based on unit type

2. **Non-Unit Items**
   - Parks
   - Streets
   - Gates
   - Communal spaces (pools, gyms, etc.)
   - Each with appropriate default size/shape

3. **Palette UI**
   - New sidebar section "New Items"
   - Icon + text for each item type
   - Drag-from-palette interaction
   - Drop to create on canvas

**Technical Considerations:**
- Template definitions (default polygons per type)
- Item creation logic (assign IDs, link to units if applicable)
- Validation (ensure item fits within boundaries)
- Update history on item creation

### 🚧 Stage 5: Planned - Collision Detection (Low Priority)
**Focus:** Visual helper to prevent overlaps (nice-to-have, not critical)

**Features:**
- Real-time collision detection during drag
- Visual feedback (red outlines on conflicts)
- Optional: Disable save when conflicts exist
- Frontend and backend validation

**Why Low Priority:**
- Humans editing can handle non-overlapping themselves
- Invalid plans are the editor's problem, not a system blocker
- Main value: Visual helper, not enforced constraint

**Technical Considerations:**
- Polygon overlap algorithm (SAT or library like turf.js)
- Performance: <100 items, brute force is fine
- Per-floor collision checking
- Backend re-validation on save

## Architecture

### Route & Controller Structure

**New Route:**
```
GET /plans/{plan}/edit => PlanController@edit (show edit page)
```

**New Resource Controller:**
```
app/Http/Controllers/PlanController.php
- Follows standard resource controller pattern (like FamilyController)
- edit(Plan $plan) - Show edit page with current state (thin wrapper)
- Delegates all logic to PlanService
- Authorization via PlanPolicy (viewAny/view: true, update: admin-only, create/delete/restore: false)
```

**Authorization Policy:**
```
app/Policies/PlanPolicy.php
- viewAny() → true (all authenticated users can list plans)
- view() → true (all authenticated users can view plans)
- update() → admin-only (check if user is admin on plan's project)
- create/delete/restore() → false (plans are auto-created by system)
```

**Page Component:**
```
resources/js/pages/Plans/Edit.vue
- Extremely thin (like other resource edit pages)
- Pass plan data to editor component
- No UI logic or component composition here
```

**Editor Component:**
```
resources/js/components/projectplan/editor/
- All editor-specific UI, logic, and composables live here
- Reusable, independent of page layer
- Not part of view-only projectplan public API
```

### Frontend State Management

**Current Implementation** (Stages 1-2):
```typescript
// In Edit.vue (orchestrator)
const { currentState, canUndo, canRedo, saveState, undo, redo, reset } = useHistory({
  items: props.plan.items,
});

// currentState.value.items = PlanItem[] with current positions
// History automatically managed (50 state limit)
```

**Component Architecture:**
- `pages/Plans/Edit.vue` - Orchestrator (history, save, layout)
- `components/projectplan/editor/EditorCanvas.vue` - Visual canvas with drag handlers
- `components/projectplan/editor/EditorSidebar.vue` - Action buttons (undo/redo/reset)
- `composables/useHistory.ts` - Undo/redo state management
- `composables/usePlanEditor.ts` - Coord transform, grid snap, drag logic

## Implementation Details (Current - Stages 1-2)

### Desktop-Only UI

**Check:**
```typescript
const isDesktop = window.innerWidth >= 1024 && window.matchMedia('(pointer:fine)').matches;
```

**Behavior:**
- Desktop: Show full editor interface
- Mobile/Tablet: Show notice "Desktop required"
- Reason: Mouse-only interaction model, no touch complexity

### Drag & Drop System

**Flow:**
1. `mouseDown` - Start drag, store offset from centroid to cursor
2. `mouseMove` - Update ghost position, follow cursor
3. `mouseUp` - Snap to grid (5px), emit `item-moved` event, save to history

**Coordinate Transform:**
```typescript
// usePlanEditor.ts
function screenToCanvasCoords(screenX: number, screenY: number): [number, number] {
  const svgRect = containerRef.value!.getBoundingClientRect();
  const pt = canvasElement.value!.createSVGPoint();
  pt.x = screenX - svgRect.left;
  pt.y = screenY - svgRect.top;
  const screenCTM = canvasElement.value!.getScreenCTM();
  const canvasPt = pt.matrixTransform(screenCTM?.inverse());
  return [canvasPt!.x, canvasPt!.y];
}
```

**Grid Snapping:**
```typescript
const GRID_SIZE = 5; // pixels
function snapToGrid([x, y]: Point): Point {
  return [
    Math.round(x / GRID_SIZE) * GRID_SIZE,
    Math.round(y / GRID_SIZE) * GRID_SIZE,
  ];
}
```

### Undo/Redo System

**Implementation (useHistory.ts):**
```typescript
const history = ref<HistoryState[]>([initialState]);
const currentIndex = ref(0);
const MAX_HISTORY = 50;

function saveState(state: HistoryState) {
  // Remove future history on new change
  history.value = history.value.slice(0, currentIndex.value + 1);
  history.value.push(deepClone(state));

  // Limit history size
  if (history.value.length > MAX_HISTORY) {
    history.value.shift();
  } else {
    currentIndex.value++;
  }
}

function undo() {
  if (currentIndex.value > 0) {
    currentIndex.value--;
  }
}

function redo() {
  if (currentIndex.value < history.value.length - 1) {
    currentIndex.value++;
  }
}
```

### Backend Persistence

**Endpoint:**
```
PATCH /plans/{plan}
```

**Request Structure:**
```json
{
  "polygon": [[0, 0], [800, 0], [800, 600], [0, 600]],
  "items": [
    {
      "id": 123,
      "polygon": [[50, 50], [150, 50], [150, 150], [50, 150]]
    }
  ]
}
```

**Validation (UpdatePlanRequest):**
- Plan polygon: min 3 vertices, numeric coordinates
- Items: must belong to this plan (withValidator check)
- Item polygons: min 3 vertices, numeric coordinates

**Service (PlanService):**
- Transaction: Update plan + all items atomically
- Rollback on failure

**Tests:**
- Feature tests: Update success, validation errors, authorization
- Unit tests: PlanService, Polygons service

## File Structure (Current Implementation)

```
# Backend
app/Http/Controllers/Resources/
└── PlanController.php              # ✅ edit(), update()

app/Http/Requests/
└── UpdatePlanRequest.php           # ✅ Validation rules

app/Services/
├── PlanService.php                 # ✅ updatePlan()
└── Plan/
    └── Polygons.php                # ✅ Transaction handling

app/Policies/
└── PlanPolicy.php                  # ✅ Authorization

tests/Feature/Plan/
├── UpdatePlanTest.php              # ✅ HTTP tests
└── PlanServiceTest.php             # ✅ Service tests

tests/Unit/Plan/
└── PolygonsServiceTest.php         # ✅ Unit tests

# Frontend
resources/js/pages/Plans/
└── Edit.vue                        # ✅ Orchestrator

resources/js/components/projectplan/editor/
├── EditorCanvas.vue                # ✅ Drag & drop canvas
├── EditorSidebar.vue               # ✅ Action buttons
└── composables/
    ├── useHistory.ts               # ✅ Undo/redo (50 states)
    └── usePlanEditor.ts            # ✅ Coord transform, grid snap

# Routes & Translations
routes/web.php                      # ✅ GET /plans/{id}/edit, PATCH /plans/{id}
lang/es_UY.json                     # ✅ Spanish translations
```

## Next Steps

### Stage 3: Reshaping & Boundaries (Next)

**Priority Features:**
1. **Out-of-Boundary Drag Fix** 🔥 HIGH PRIORITY
   - Problem: Currently can drag items outside canvas (only centroid checked)
   - Solution: Check entire item bounding box during drag
   - Behavior: Stop drag movement when item would leave canvas
   - Implementation: Calculate bbox on each mousemove, constrain position

2. **Resize Items**
   - Corner/edge handles for scaling
   - Maintain aspect ratio (optional toggle)
   - Min size: 30×30px

3. **Rotate Items**
   - Rotation handle or keyboard shortcuts
   - Snap to 15° increments

4. **Vertex Editing**
   - Add: Click edge to insert new vertex
   - Move: Drag existing vertex
   - Delete: Right-click vertex (min 3 vertices)
   - Max: 10 vertices per item
   - Validate: Prevent self-intersecting edges

### Stage 4: Adding New Items

**Item Types:**
- Units (from unit type palette)
- Parks
- Streets
- Gates
- Communal spaces

**Palette UI:**
- New EditorSidebar section "New Items"
- Drag-from-palette interaction
- Default templates for each type

### Stage 5: Collision Detection (Low Priority)

**Why Low Priority:**
- Humans can handle non-overlapping themselves
- Invalid plans are editor's problem, not system blocker
- Main value: Visual helper for convenience

**When Implemented:**
- Real-time detection during drag
- Visual feedback (red outlines)
- Optional: Block save on conflicts
- Backend re-validation

---

## Missing from Updated Plan?

You mentioned:
- ✅ Reshaping (Stage 3)
- ✅ Out-of-boundary drag fix (Stage 3)
- ✅ Adding items - units and other types (Stage 4)
- ✅ Collision detection (Stage 5)
- ❓ **Vertical space handling (floors)** - Not explicitly in any stage

### Floor/Vertical Space Handling

**Should add to Stage 3 or 4:**

**Option A: Stage 3 (with reshaping)**
- Simpler: Just change floor assignment for existing items
- UI: Dropdown in item properties panel
- No collision between different floors

**Option B: Stage 4 (with adding items)**
- When creating new items, specify floor
- More complex: Multi-floor view/toggle

**Recommendation: Stage 3**
- Floor is a property like position/size
- Simple dropdown: "Floor: 0, 1, 2, etc."
- Collision detection (Stage 5) would check floor matching
- Fits naturally with other item property editing

### Updated Stage Plan

**Stage 3: Reshaping, Boundaries & Floors**
1. Out-of-boundary drag fix 🔥
2. Resize items
3. Rotate items
4. Vertex editing (add/move/delete)
5. Floor assignment dropdown

**Stage 4: Adding New Items**
1. Unit items from palette
2. Non-unit items (park, street, gate, communal)
3. Palette UI in sidebar
4. Drag-to-create interaction

**Stage 5: Collision Detection**
1. Polygon overlap algorithm
2. Real-time visual feedback
3. Optional save blocking
4. Backend validation

**In Scope:**
- ✅ Drag existing items around canvas
- ✅ Visual drag feedback (cursor, outline, ghost)
- ✅ Non-overlapping validation per floor (real-time)
- ✅ Real-time conflict visualization (red outlines)
- ✅ Undo on cancel or invalid position (revert to original)
- ✅ Desktop-only UI (hide on mobile, show notice)
- ✅ Grid-based snapping (configurable, ~5px)
- ✅ Selection/highlight on click
- ✅ Undo/redo history (50 state limit)
- ✅ EditorSidebar with action buttons (right side)

**Out of Scope (Stage 1):**
- ❌ Save to backend (stage 2)
- ❌ Resizing (stage 3)
- ❌ Floor changes (stage 3)
- ❌ Reshaping/vertices (stage 3)
- ❌ Adding new items (stage 4)
- ❌ Deleting items (users delete from unit ShowCard)
- ❌ Touch support

### Desktop-Only UI

**Responsive Behavior:**
```vue
<template>
  <div>
    <!-- Desktop: Editor -->
    <div v-if="isDesktop" class="flex h-screen">
      <EditorCanvas />
    </div>

    <!-- Mobile/Tablet: Notice -->
    <div v-else class="flex items-center justify-center h-screen bg-gray-100">
      <div class="text-center">
        <p class="text-lg font-semibold">Desktop required</p>
        <p class="text-gray-600">Plan editing is only available on desktop with a mouse</p>
        <Link href="/plans" class="mt-4 inline-block btn">Back to Plans</Link>
      </div>
    </div>
  </div>
</template>

<script setup>
const isDesktop = computed(() => {
  // Check viewport width >= 1024px AND pointer: fine (mouse)
  return window.innerWidth >= 1024 && window.matchMedia('(pointer:fine)').matches;
});
</script>
```

**Benefits:**
- Simplifies interaction model (mouse only, no touch)
- No responsive layout complexity
- Reasonable restriction (admins have desk access)
- Still works on iPad with mouse (matchMedia respects pointer type)

### Implementation Details

#### 1. Drag Detection & State Management

**Dragging States:**
- `idle` - Not dragging
- `dragging` - Item being moved (following mouse)
- `validating` - Released, checking collisions
- `valid` - Confirmed position, no conflicts
- `invalid` - Conflicts detected, visual feedback shown

**Event Flow:**
```
mouseDown on item
  → Set draggedItemId, store initial offset
  → Render ghost/outline
  → Listen to mousemove

mousemove
  → Calculate new position
  → Update item.polygon (translate)
  → Check collisions in real-time
  → Render red outlines for conflicts

mouseUp
  → Validate final position
  → If valid: Keep new position, clear conflicts
  → If invalid: Revert to original position
  → Update selectedItemId, clear draggedItemId
```

**State Structure:**
```typescript
const dragState = ref<{
  itemId: number;
  offset: [number, number]; // [offsetX, offsetY] from centroid to cursor
  originalPolygon: Point[];   // Backup for rollback on invalid drop
} | null>(null);

const selectedItemId = ref<number | null>(null);
const conflictingItemIds = ref<Set<number>>(new Set());
```

#### 2. Collision Detection Algorithm

**Polygon Intersection:**

**Research & Implementation Path:**
1. Check npm for existing collision detection libraries:
   - `@turf/boolean-polygon-in-polygon`
   - `point-in-polygon`
   - `flatland` (polygon utilities)
   - Generic 2D geometry libraries

2. If library found that's light-weight and well-maintained:
   - Use it (don't reinvent)
   - Wrap in utility function for consistency

3. If no suitable library or extending it is hard:
   - Implement Separating Axis Theorem (SAT) manually
   - Keep it focused: polygon-to-polygon only
   - Test thoroughly (geometry is error-prone)

**Algorithm (if implementing SAT):**
```typescript
function polygonsOverlap(poly1: Point[], poly2: Point[]): boolean {
  const edges = [...getEdges(poly1), ...getEdges(poly2)];

  for (const edge of edges) {
    const normal = perpendicular(edge);
    const proj1 = projectPolygon(poly1, normal);
    const proj2 = projectPolygon(poly2, normal);

    if (!overlaps(proj1, proj2)) {
      return false; // Found separating axis - no overlap
    }
  }

  return true; // No separating axis found - overlapping
}

function getConflictingItems(item: PlanItem, allItems: PlanItem[]): PlanItem[] {
  return allItems.filter(other =>
    other.id !== item.id &&
    other.floor === item.floor && // Same floor only
    polygonsOverlap(item.polygon, other.polygon)
  );
}
```

**Performance:**
- For <100 items: Brute-force (check all pairs) is fine
- No spatial partitioning needed (premature optimization)
- Real-time collision check on mousemove is acceptable

#### 3. Visual Feedback

**Ghost Item During Drag:**
```vue
<g v-if="dragState" opacity="0.5" fill="rgba(100,200,255,0.3)" stroke="rgba(100,150,255,0.8)" stroke-width="2">
  <!-- Render polygon at cursor-following position -->
  <polygon :points="ghostPolygonPoints" />
</g>
```

**Conflict Indicators:**
```vue
<!-- Dragged item (always outline) -->
<Item
  :item="draggedItem"
  :class="{ 'stroke-yellow-400 stroke-4': isDragging }"
/>

<!-- Conflicting items (red outlines) -->
<Item
  v-for="conflictId in conflictingItemIds"
  :key="conflictId"
  :item="getItem(conflictId)"
  :class="{ 'stroke-red-500 stroke-2': true }"
/>

<!-- Valid (green outline after release) -->
<Item
  v-if="lastDropWasValid"
  :item="draggedItem"
  :class="{ 'stroke-green-500 stroke-2': true }"
/>
```

**Cursor & Interactivity:**
```css
.draggable-item {
  cursor: grab;

  &:active {
    cursor: grabbing;
  }
}

.conflicting-item {
  pointer-events: none; /* Don't interfere with drag */
  opacity: 0.7;
}

.selected-item {
  filter: drop-shadow(0 0 4px rgba(0,100,255,0.5));
}
```

#### 4. Coordinate Transformation & Drag Offset

**Challenge:**
1. Mouse coordinates in viewport space
2. Polygons in canvas space (after useScaling transformation)
3. Item centroid may not be under cursor (need offset)

**Solution:**
```typescript
const canvasElement = ref<SVGElement>();

function getCanvasCoordinates(event: MouseEvent): Point {
  // Get SVG element's position relative to viewport
  const svgRect = canvasElement.value!.getBoundingClientRect();
  const clientX = event.clientX - svgRect.left;
  const clientY = event.clientY - svgRect.top;

  // SVG has own coordinate system, transform to it
  const pt = canvasElement.value!.createSVGPoint();
  pt.x = clientX;
  pt.y = clientY;

  // Get transform matrix and inverse it
  const screenCTM = canvasElement.value!.getScreenCTM();
  const canvasPt = pt.matrixTransform(screenCTM?.inverse());

  return [canvasPt!.x, canvasPt!.y];
}

// During drag start: store offset from centroid to drag point
function startDrag(itemId: number, event: MouseEvent) {
  const item = getItem(itemId);
  const centroid = calculateCentroid(item.polygon);
  const dragPos = getCanvasCoordinates(event);

  dragState.value = {
    itemId,
    offset: [dragPos[0] - centroid[0], dragPos[1] - centroid[1]],
    originalPolygon: item.polygon,
  };
}

// During drag move: update item position based on mouse + offset
function updateDragPosition(event: MouseEvent) {
  const item = getItem(dragState.value!.itemId);
  const centroid = calculateCentroid(item.polygon);
  const dragPos = getCanvasCoordinates(event);

  // New centroid = cursor position - offset
  const newCentroid = [
    dragPos[0] - dragState.value!.offset[0],
    dragPos[1] - dragState.value!.offset[1],
  ];

  // Translate polygon
  const delta = [newCentroid[0] - centroid[0], newCentroid[1] - centroid[1]];
  item.polygon = item.polygon.map(([x, y]) => [x + delta[0], y + delta[1]]);
}
```

#### 5. Grid Snapping & Release Validation

**Snap to Grid:**
```typescript
const GRID_SIZE = 5; // pixels

function snapToGrid([x, y]: Point): Point {
  return [
    Math.round(x / GRID_SIZE) * GRID_SIZE,
    Math.round(y / GRID_SIZE) * GRID_SIZE,
  ];
}

function snapItemToGrid(item: PlanItem) {
  item.polygon = item.polygon.map(snapToGrid);
}

function handleMouseUp(event: MouseEvent) {
  if (!dragState.value) return;

  const item = getItem(dragState.value.itemId);

  // Snap to grid
  snapItemToGrid(item);

  // Check collisions at final position
  const conflicts = getConflictingItems(item, planState.value.items);

  if (conflicts.length > 0) {
    // Invalid: Revert
    item.polygon = dragState.value.originalPolygon;
    conflictingItemIds.value = new Set();
  } else {
    // Valid: Keep new position
    conflictingItemIds.value.clear();
  }

  dragState.value = null;
}
```

**Snap to Grid:**
- Improves precision, prevents sub-pixel positions
- Grid size: ~5px (smaller than 10px for better control)
- `snappedCoord = round(coord / 5) * 5`
- Apply only on release (mouseUp), not during drag
- Helps align items visually

**Keyboard Shortcuts (Stage 1):**
- `Delete` - Remove selected item (only if unsaved/dirty)
  - Unsaved items: Created in this session, not in database
  - Persistent items: Cannot delete (must do from unit ShowCard)
- `Escape` - Deselect item & cancel current drag
- `Ctrl+Z`, `Ctrl+Y` - NOT implemented (save to browser history instead)

**Reuse Existing Drag-Drop:**
- Research if current drag-drop module (used elsewhere) can be extended
- Scope: Check if it handles polygon/arbitrary shape dragging
- If it fits: Extend it; if it forces awkward patterns: Build independent drag system

#### 6. Selection & Highlighting

**Click to Select:**
```typescript
function handleItemClick(itemId: number, event: MouseEvent) {
  // Only toggle selection if not dragging
  if (!dragState.value) {
    selectedItemId.value = selectedItemId.value === itemId ? null : itemId;
    event.stopPropagation();
  }
}

// Deselect on canvas background click
function handleCanvasClick(event: MouseEvent) {
  if (event.target === svgElement.value) {
    selectedItemId.value = null;
  }
}
```

**Info Panel (Right Sidebar):**
```vue
<aside v-if="selectedItem" class="w-64 bg-white border-l p-4 overflow-y-auto">
  <h3 class="font-bold mb-4">{{ selectedItem.identifier }}</h3>
  <div class="space-y-4">
    <div>
      <label class="text-sm font-semibold">Type</label>
      <p>{{ selectedItem.type }}</p>
    </div>
    <div>
      <label class="text-sm font-semibold">Floor</label>
      <p>{{ selectedItem.floor }}</p>
    </div>
    <div>
      <label class="text-sm font-semibold">Position</label>
      <p class="text-xs text-gray-600">{{ centroid }}</p>
    </div>
    <div v-if="hasConflicts" class="bg-red-50 border-l-4 border-red-500 p-3">
      <p class="text-sm font-semibold text-red-700">Conflicts with:</p>
      <ul class="text-xs text-red-600 mt-2">
        <li v-for="c in conflicts" :key="c.id">{{ c.identifier }}</li>
      </ul>
    </div>
  </div>
</aside>
```

#### 7. Edge Cases & Error Handling

**Item Outside Canvas Bounds:**
```typescript
function isWithinBounds(polygon: Point[], boundary: Point[]): boolean {
  // Check if all vertices are within or slightly outside boundary
  // For now: Allow partial outside, just validate collision
  return true; // No boundary enforcement in stage 1
}
```

**Undo/Reset on Invalid:**
```typescript
function resetPlanToOriginal() {
  planState.value.items = JSON.parse(JSON.stringify(originalPlan.value.items));
  selectedItemId.value = null;
  dragState.value = null;
  conflictingItemIds.value.clear();
}

// Button: "Reset All Changes"
<button @click="resetPlanToOriginal" class="btn btn-secondary">
  Reset All Changes
</button>
```

#### 8. Details, Constraints & Gotchas

**Constraints & Edge Cases:**
- **Vertices limit:** Max 10 vertices per item (visual aid only, not architectural precision)
- **Item count:** Realistically <100 items per plan (no spatial partitioning needed)
- **Item outside canvas:** Allow dragging partially outside bounds (validate full polygon)
- **Very small items:** If dropped too small, warn user (show min size tooltip)
- **Same position drag:** If item dropped at same location, no-op (no validation error)
- **Fast drags:** Collision check happens on every move; no debouncing needed for <100 items
- **Snap to grid conflicts:** Item still valid even if snapped into conflict (user sees red outline)

**What We're NOT Doing in Stage 1:**
- Undo/redo history (page reload loses state)
- Backend persistence (stage 2)
- Resizing/reshaping (stage 3)
- Item deletion (use ShowCard)
- Touch support (desktop only)
- Drag from template palette (stage 4)
- Zoom/pan (add if needed later)
- Concurrent editor locking (stage 2)

---

## Stage 1 Checklist

**Backend (Thin Layer):**
- [x] Create `PlanController` resource controller (standard pattern)
- [x] `edit(Plan $plan)` - Pass plan to Edit.vue view
- [x] Create `PlanPolicy` with authorization rules
  - [x] `viewAny()` → true
  - [x] `view()` → true
  - [x] `update()` → admin-only
  - [x] `create/delete/restore()` → false
- [x] Route: `GET /plans/{id}/edit` → PlanController@edit
- [x] No backend persistence logic (stage 2)

**Frontend - Core Editor Logic:**
- [x] Create `useHistory` composable (undo/redo with 50 state limit)
- [ ] Create `useCollisionDetection` composable (polygon overlap) - DEFERRED
  - Collision detection deferred to later stage
  - Currently items can overlap (visual only, no validation)
- [x] Create drag detection logic in EditorCanvas
  - [x] mouseDown → store item, offset
  - [x] mouseMove → update position
  - [x] mouseUp → snap to grid, emit change
- [x] Create `usePlanEditor` composable
  - [x] Convert viewport → canvas coordinates
  - [x] Handle SVG transform matrix
  - [x] Grid snapping (5px)

**Frontend - Components:**
- [x] Create `resources/js/pages/Plans/Edit.vue` (orchestrator)
  - [x] Check isDesktop, show notice if not
  - [x] Manage history state with useHistory
  - [x] Coordinate EditorCanvas and EditorSidebar
  - [x] Handle save to backend (calls update endpoint)
- [x] Create `resources/js/components/projectplan/editor/EditorCanvas.vue`
  - [x] Render Canvas with drag handlers
  - [x] Show ghost item during drag
  - [x] Show drop animation
  - [x] Emit item-moved events to parent
- [x] Create `resources/js/components/projectplan/editor/EditorSidebar.vue`
  - [x] Action buttons: Undo, Redo, Reset
  - [x] Icon + text layout (120px width)
  - [x] Positioned on right side
  - [x] Extensible for future sections (New Items, Legend)
- [ ] Implement keyboard shortcuts - DEFERRED
  - [ ] Ctrl+Z / Ctrl+Y for undo/redo
  - [ ] Escape (deselect & cancel drag)

**Frontend - UI Polish:**
- [x] Grid snapping (5px)
- [x] Visual feedback: Ghost item during drag
- [x] Drop animation (highlight)
- [ ] Selected item highlight - DEFERRED
- [ ] Conflict preview (red overlay on conflicting items) - DEFERRED
- [ ] Status bar showing selected item info - DEFERRED
- [x] Undo/Redo/Reset buttons in sidebar
- [x] Desktop-only responsive check
- [x] Spanish translations (Actions, Undo, Redo, Reset)

**File Structure:**
```
app/Http/Controllers/
└── PlanController.php          # ✅ Thin resource controller

app/Policies/
└── PlanPolicy.php              # ✅ Authorization

resources/js/pages/Plans/
└── Edit.vue                    # ✅ Orchestrator (history, save, layout)

resources/js/components/projectplan/editor/
├── EditorCanvas.vue            # ✅ Main canvas with drag handlers
├── EditorSidebar.vue           # ✅ Action buttons (undo/redo/reset)
└── composables/
    ├── useHistory.ts           # ✅ Undo/redo state management
    └── usePlanEditor.ts        # ✅ Coord transform, grid snap, drag logic

routes/web.php                 # ✅ /plans/{id}/edit route
lang/es_UY.json                # ✅ Spanish translations
```

**Tests (Minimal for Stage 1):**
- [ ] Unit: Collision detection (polygon overlap)
- [ ] Unit: Coordinate transformation (viewport → canvas)
- [ ] Feature: Can drag item without overlap
- [ ] Feature: Item reverts on invalid drop
- [ ] Feature: Keyboard shortcuts work (Delete, Escape)
- [ ] UI: Desktop-only check works

**NOT in Stage 1:**
- [ ] Backend persistence (stage 2)
- [ ] Collision detection validation (deferred)
- [ ] Selected item info panel (deferred)
- [ ] Keyboard shortcuts (deferred)
- [ ] Item resizing, reshaping, floor changes (stage 3)
- [ ] Add new items template (stage 4)

---

## Stage 2: Persist Changes to Backend + Collision Detection

**Status:** Ready to start (undo/redo already completed in Stage 1)

**Scope:**
1. **Backend Persistence:**
   - `PlanController@update()` - Save item positions
   - Form Request validation
   - Transaction handling for atomic save
   - Return updated plan state

2. **Collision Detection:**
   - Frontend: `useCollisionDetection` composable
   - Real-time validation during drag
   - Visual feedback (red outlines on conflicts)
   - Backend: Re-validate on save (never trust client)

3. **UI Enhancements:**
   - Save button with loading state (already in Edit.vue)
   - Show validation errors from backend
   - Prevent saving if conflicts exist
   - Toast notifications on success/error

**Implementation Order:**
1. ✅ Save functionality (already implemented in Edit.vue)
2. Backend endpoint (`PlanController@update`, Form Request, Service)
3. Collision detection composable (polygon overlap algorithm)
4. Integrate collision detection into drag flow
5. Backend validation (re-check collisions before save)
6. Error handling and user feedback

**Considerations:**
- **Collision Algorithm:** Use existing library if available (turf.js, SAT.js), or implement SAT
- **Performance:** <100 items = brute force is fine, no spatial partitioning needed
- **Validation:** Backend must re-check collisions (security: never trust frontend)
- **Transactions:** Wrap in DB transaction, rollback on validation failure
- **Optimistic Locking:** Add `version` field to Plan table (optional, for concurrent edit detection)

**Deliverables:**
- [ ] `app/Http/Controllers/PlanController@update`
- [ ] `app/Http/Requests/UpdatePlanRequest` (validation rules)
- [ ] `app/Services/PlanService@updateItems()` (business logic)
- [ ] `resources/js/components/projectplan/composables/useCollisionDetection.ts`
- [ ] Integrate collision detection in EditorCanvas
- [ ] Backend validation tests (Pest)
- [ ] Frontend collision detection tests (Vitest)

---

## Stage 3: Advanced Editing (Resize, Floor, Reshape)

**Scope:**
- Resize items (corner/edge handles)
- Change floor (dropdown in info panel)
- Reshape (move vertices, max 10 per item)
- Combined with stage 1 collision validation

**Reshape UI Pattern:**
- Click-to-add vertex on edge (shows insertion point on hover)
- Drag existing vertex to move
- Right-click vertex to delete (min 3 vertices to prevent degenerate shapes)
- Visual feedback: Vertex indicators (circles), edges (lines)
- Similar to Figma node editing

**Considerations:**
- **Max 10 vertices per item** (visual aid, not architectural)
- Polygon validity: No self-intersecting edges (add validation)
- Minimum size: 30×30px (or configurable)
- Snap vertices to grid (~5px)
- Rectangle detection: Quick rotate/flip for rectangular items

---

## Stage 4: Add New Items (Drag-Drop Creation)

**Scope:**
- Palette of item templates outside canvas (Unit for stage 1)
- Drag template onto canvas to create new item
- Modal form for required properties
- Transactional: Saved only when backend accepts

**Item Creation Flow:**
1. User sees "Unit" template card outside canvas
2. Drags template onto canvas
3. Ghost follows cursor (shows preview)
4. On release:
   - If over canvas: Open form modal for item properties
   - If outside canvas: Return template to palette
5. Form modal:
   - Identifier (required, auto-suggest)
   - Unit Type (dropdown)
   - Floor (number)
   - Position locked to drag position
6. On confirm: Add to plan items, mark dirty
7. On cancel: Discard, template returns to palette

**Template Palette UI:**
```vue
<aside class="w-48 bg-white border-r p-4">
  <h3 class="font-bold mb-4">Add Items</h3>
  <div
    draggable
    class="p-3 bg-blue-50 border-2 border-blue-300 rounded cursor-move"
    @dragstart="startTemplateDrag"
  >
    <p class="text-sm font-semibold">Unit</p>
    <p class="text-xs text-gray-600">Drag to canvas</p>
  </div>
</aside>
```

**Form Modal:**
- Auto-focus on identifier input
- Show position (locked, informational)
- Save to plan only after validation
- Prevent duplicate identifiers
