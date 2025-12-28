// Copilot - Pending review

# Plan Editor System Design

## Overview

Interactive drag-and-drop editor for floor plan layout design. Allows admins to modify unit positions with real-time collision detection. **Stage 1 focuses on drag-and-drop with validation; no backend persistence until stage 2.**

**Key Principles:**
- Desktop-only (no touch support)
- Non-destructive frontend: Changes held in state until explicit save (stage 2)
- Real-time collision detection per floor
- Intuitive drag-drop UX similar to modern design tools
- Thin controllers, logic in PlanService and composables

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

**Edit Mode State** (in useEditMode composable):
```typescript
const planState = ref({
  items: PlanItem[],         // All plan items (with current positions/states)
  selectedItemId: number | null,
  draggedItemId: number | null,
});

const originalPlan = ref(Plan); // Snapshot for reset
const validationErrors = ref<ValidationError[]>([]);
```

**Note on Stage 1:**
- No persistence to backend (stage 2)
- No undo/redo (keep it simple, use browser back button if needed)
- State is lost on page reload (expected for stage 1)
- Focus on making drag/validation UX solid

**Undo/Redo** (future, stage 2+):
```typescript
const history = ref<PlanState[]>([]);
const historyIndex = ref(0);

function undo() { historyIndex.value--; }
function redo() { historyIndex.value++; }
function saveToHistory() { history.splice(historyIndex.value + 1); history.push(deepClone(planState)); }
```

## Stage 1: Move Items with Non-Overlapping Validation

### Scope - Stage 1 Focus

**In Scope:**
- ✅ Drag existing items around canvas
- ✅ Visual drag feedback (cursor, outline, ghost)
- ✅ Non-overlapping validation per floor (real-time)
- ✅ Real-time conflict visualization (red outlines)
- ✅ Undo on cancel or invalid position (revert to original)
- ✅ Desktop-only UI (hide on mobile, show notice)
- ✅ Grid-based snapping (configurable, ~5px)
- ✅ Selection/highlight on click

**Out of Scope (Stage 1):**
- ❌ Save to backend (stage 2)
- ❌ Resizing (stage 3)
- ❌ Floor changes (stage 3)
- ❌ Reshaping/vertices (stage 3)
- ❌ Adding new items (stage 4)
- ❌ Deleting items (users delete from unit ShowCard)
- ❌ Touch support
- ❌ Undo/redo stack (browser back suffices for now)

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
- [ ] Create `PlanController` resource controller (standard pattern)
- [ ] `edit(Plan $plan)` - Pass plan to Edit.vue view
- [ ] Create `PlanPolicy` with authorization rules
  - [ ] `viewAny()` → true
  - [ ] `view()` → true
  - [ ] `update()` → admin-only
  - [ ] `create/delete/restore()` → false
- [ ] Route: `GET /plans/{id}/edit` → PlanController@edit
- [ ] No backend persistence logic (stage 2)

**Frontend - Core Editor Logic:**
- [ ] Create `useCollisionDetection` composable (polygon overlap)
  - [ ] Research & use library if suitable (geometry is hard)
  - [ ] Fallback: Implement SAT if needed
  - [ ] Function: `getConflictingItems(item, floor)`
- [ ] Create `useDragDetection` composable
  - [ ] mouseDown → store item, offset
  - [ ] mouseMove → update position, validate collisions
  - [ ] mouseUp → snap to grid, check validity, revert if invalid
- [ ] Create `useCoordinateTransform` utility
  - [ ] Convert viewport → canvas coordinates
  - [ ] Handle SVG transform matrix

**Frontend - Components:**
- [ ] Create `resources/js/pages/Plans/Edit.vue` (thin wrapper)
  - [ ] Check isDesktop, show notice if not
  - [ ] Pass plan to EditorCanvas component
- [ ] Create `resources/js/components/projectplan/editor/EditorCanvas.vue`
  - [ ] Render Canvas with drag handlers
  - [ ] Show/hide red outlines on conflicts
  - [ ] Show ghost item during drag
  - [ ] Show selected item highlight
- [ ] Create `resources/js/components/projectplan/editor/DraggableItem.vue`
  - [ ] Extend Item component with drag capabilities
  - [ ] Show different styling for dragging/selected/conflicted
- [ ] Implement keyboard shortcuts
  - [ ] Delete (unsaved items only)
  - [ ] Escape (deselect & cancel drag)

**Frontend - UI Polish:**
- [ ] Grid snapping (~5px)
- [ ] Visual feedback: Ghost item, red/green outlines
- [ ] Selected item highlight
- [ ] Conflict preview (red overlay on conflicting items)
- [ ] Status bar showing selected item info
- [ ] "Reset" button to revert all changes
- [ ] Desktop-only responsive check

**File Structure:**
```
app/Http/Controllers/
└── PlanController.php          # Thin resource controller

app/Policies/
└── PlanPolicy.php              # Authorization

resources/js/pages/Plans/
└── Edit.vue                    # Thin page wrapper

resources/js/components/projectplan/editor/
├── EditorCanvas.vue            # Main editor UI
├── DraggableItem.vue           # Draggable item wrapper
├── useCollisionDetection.ts    # Polygon overlap
├── useDragDetection.ts         # Drag state & handlers
└── useCoordinateTransform.ts   # Viewport ↔ canvas

routes/web.php                 # Add /plans/{id}/edit route
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
- [ ] Undo/redo history (stage 2+)
- [ ] Delete button for persistent items (ShowCard only)
- [ ] Item resizing, reshaping, floor changes (stage 3)
- [ ] Add new items template (stage 4)

---

## Stage 2: Persist Changes to Backend

**Scope:**
- Backend validation (collision check before save)
- Transaction handling for atomic save
- Undo/redo history in frontend
- Confirmation dialog before save
- API endpoint to save plan items

**Considerations:**
- Concurrent edits: Implement optimistic locking (prevent stale writes)
- Large plans: Batch update or full replace (decide based on testing)
- Validations: Backend re-checks collisions (never trust frontend)
- Error handling: Show validation errors, allow user to fix and retry

**Deliverables:**
- `PlanController@update(Request $request, Plan $plan)`
  - Delegates to `PlanService@updateItems()`
  - Validates non-overlapping per floor
  - Saves atomically or rolls back
- `POST /plans/{id}` endpoint with item list
- Frontend undo/redo stack (50 state limit)
- Save confirmation dialog with summary of changes
- Optimistic locking check (version field)

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
