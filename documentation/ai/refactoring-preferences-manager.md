# PreferencesManager Refactoring

**Date:** 2025-11-20
**Status:** Complete

## Overview

Refactored the lottery preferences manager to improve code organization and enable reusability for future features (Phase 3 project plan canvas).

## Changes Made

### 1. Created Generic Drag-and-Drop Composable

**File:** `resources/js/composables/useDragAndDrop.ts`

- **Purpose:** Extract drag-and-drop logic for reuse across multiple components
- **Key Features:**
  - Callback-based API for flexibility
  - TypeScript typed for safety
  - Framework for project plan canvas integration
  - Manages drag state and coordinates movement

**API:**
```typescript
const {
  draggedIndex,      // ref<number | null>
  handleDragStart,   // (e: DragEvent, index: number) => void
  handleDrop,        // (e: DragEvent, targetIndex: number) => void
  handleDragEnd,     // () => void
  handleDragOver,    // (e: DragEvent) => void (optional)
  handleDragEnter    // (e: DragEvent) => void (optional)
} = useDragAndDrop({
  onMove: (fromIndex, toIndex) => { /* reorder logic */ }
});
```

### 2. Refactored PreferencesManager.vue

**Before:**
- 248 lines of tightly coupled code
- Drag-and-drop logic embedded in component
- Difficult to reuse for other features

**After:**
- 232 lines with better organization
- Drag-and-drop logic extracted to composable
- Clear separation of concerns:
  - **Preference logic:** Reordering and form submission
  - **Drag-and-drop:** Using composable (reusable)
  - **Keyboard accessibility:** Arrow button handlers
  - **UI helpers:** Labels and rotations

**Key Improvements:**
- More maintainable code structure
- Reusable drag-and-drop logic
- Clear comments explaining each section
- Preserved all existing functionality

### 3. Architecture Benefits

**Reusability:**
The `useDragAndDrop` composable will be reused in:
- ✅ Member lottery preferences (current)
- 🔜 Admin project plan canvas (Phase 3)
- 🔜 Any future drag-and-drop features

**Maintainability:**
- Single source of truth for drag-and-drop behavior
- Easier to test in isolation
- Clearer component responsibilities

**Type Safety:**
- TypeScript definitions ensure correct usage
- Callback pattern prevents tight coupling

## Testing Checklist

Before deploying, verify:
- [ ] Drag-and-drop works on mobile vertical list
- [ ] Drag-and-drop works on desktop grid
- [ ] Animations still smooth (400ms transitions)
- [ ] Rotations applied correctly to cards
- [ ] Keyboard controls (arrow buttons) functional
- [ ] Form auto-saves after each reorder
- [ ] Priority badges show for top 3 choices
- [ ] Drop zones cover full cell areas
- [ ] Processing state disables interactions

## Future Work

### Phase 3: Project Plan Integration

The `useDragAndDrop` composable will enable:
1. **Drag units from canvas to preferences**
   - Visual spatial decision-making
   - Bi-directional highlighting
   - Synchronized state updates

2. **Admin visual plan editor**
   - Drag-and-drop unit placement
   - Real-time layout adjustments
   - Consistent UX with member interface

### Potential Enhancements

- Add `onDragEnter` callback for hover effects
- Support multi-select drag operations
- Add undo/redo stack
- Implement touch gestures for mobile

## Related Documentation

- [Lottery System README](../../resources/js/components/lottery/README.md)
- [KNOWLEDGE_BASE.md](./KNOWLEDGE_BASE.md)
- [TODO](../TODO.md)
