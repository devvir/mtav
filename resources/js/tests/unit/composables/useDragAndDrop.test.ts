// Copilot - Pending review
import { describe, it, expect, vi } from 'vitest';

vi.unmock('@/composables/useDragAndDrop');

import { useDragAndDrop } from '@/composables/useDragAndDrop';

// Mock drag event object since DragEvent is not available in test environment
const createMockDragEvent = (type: string) => ({
  type,
  preventDefault: vi.fn(),
  defaultPrevented: false,
  dataTransfer: { dropEffect: 'move' },
});

describe('useDragAndDrop composable', () => {
  describe('drag and drop state', () => {
    it('initializes draggedIndex as null', () => {
      const { draggedIndex } = useDragAndDrop({
        onMove: () => {},
      });

      expect(draggedIndex.value).toBe(null);
    });

    it('sets draggedIndex on drag start', () => {
      const { draggedIndex, handleDragStart } = useDragAndDrop({
        onMove: () => {},
      });

      const event = createMockDragEvent('dragstart');
      handleDragStart(event as any, 2);

      expect(draggedIndex.value).toBe(2);
    });

    it('clears draggedIndex on drag end', () => {
      const { draggedIndex, handleDragStart, handleDragEnd } = useDragAndDrop({
        onMove: () => {},
      });

      const event = createMockDragEvent('dragstart');
      handleDragStart(event as any, 2);
      expect(draggedIndex.value).toBe(2);

      handleDragEnd();
      expect(draggedIndex.value).toBe(null);
    });

    it('clears draggedIndex after drop', () => {
      const { draggedIndex, handleDragStart, handleDrop } = useDragAndDrop({
        onMove: () => {},
      });

      const startEvent = createMockDragEvent('dragstart');
      handleDragStart(startEvent as any, 1);
      expect(draggedIndex.value).toBe(1);

      const dropEvent = createMockDragEvent('drop');
      handleDrop(dropEvent as any, 3);
      expect(draggedIndex.value).toBe(null);
    });
  });

  describe('drag start callback', () => {
    it('calls onDragStart callback with correct index', () => {
      const onDragStart = vi.fn();
      const { handleDragStart } = useDragAndDrop({
        onMove: () => {},
        onDragStart,
      });

      const event = createMockDragEvent('dragstart');
      handleDragStart(event as any, 5);

      expect(onDragStart).toHaveBeenCalledWith(5);
    });

    it('does not fail if onDragStart is not provided', () => {
      const { handleDragStart } = useDragAndDrop({
        onMove: () => {},
      });

      const event = createMockDragEvent('dragstart');
      expect(() => {
        handleDragStart(event as any, 1);
      }).not.toThrow();
    });
  });

  describe('drag end callback', () => {
    it('calls onDragEnd callback', () => {
      const onDragEnd = vi.fn();
      const { handleDragEnd } = useDragAndDrop({
        onMove: () => {},
        onDragEnd,
      });

      handleDragEnd();

      expect(onDragEnd).toHaveBeenCalled();
    });

    it('does not fail if onDragEnd is not provided', () => {
      const { handleDragEnd } = useDragAndDrop({
        onMove: () => {},
      });

      expect(() => {
        handleDragEnd();
      }).not.toThrow();
    });
  });

  describe('drop and move callback', () => {
    it('calls onMove when dropping to different index', () => {
      const onMove = vi.fn();
      const { handleDragStart, handleDrop } = useDragAndDrop({
        onMove,
      });

      const startEvent = createMockDragEvent('dragstart');
      handleDragStart(startEvent as any, 2);

      const dropEvent = createMockDragEvent('drop');
      handleDrop(dropEvent as any, 5);

      expect(onMove).toHaveBeenCalledWith(2, 5);
    });

    it('does not call onMove when dropping on same index', () => {
      const onMove = vi.fn();
      const { handleDragStart, handleDrop } = useDragAndDrop({
        onMove,
      });

      const startEvent = createMockDragEvent('dragstart');
      handleDragStart(startEvent as any, 3);

      const dropEvent = createMockDragEvent('drop');
      handleDrop(dropEvent as any, 3);

      expect(onMove).not.toHaveBeenCalled();
    });

    it('does not call onMove if draggedIndex is null', () => {
      const onMove = vi.fn();
      const { handleDrop } = useDragAndDrop({
        onMove,
      });

      const dropEvent = createMockDragEvent('drop');
      handleDrop(dropEvent as any, 2);

      expect(onMove).not.toHaveBeenCalled();
    });

    it('calls onMove multiple times for multiple drags', () => {
      const onMove = vi.fn();
      const { handleDragStart, handleDrop, handleDragEnd } = useDragAndDrop({
        onMove,
      });

      // First drag
      const startEvent1 = createMockDragEvent('dragstart');
      handleDragStart(startEvent1 as any, 0);

      const dropEvent1 = createMockDragEvent('drop');
      handleDrop(dropEvent1 as any, 2);
      expect(onMove).toHaveBeenCalledWith(0, 2);

      // Reset state
      handleDragEnd();

      // Second drag
      const startEvent2 = createMockDragEvent('dragstart');
      handleDragStart(startEvent2 as any, 1);

      const dropEvent2 = createMockDragEvent('drop');
      handleDrop(dropEvent2 as any, 4);
      expect(onMove).toHaveBeenCalledWith(1, 4);

      expect(onMove).toHaveBeenCalledTimes(2);
    });
  });

  describe('drag over and enter', () => {
    it('prevents default on dragOver', () => {
      const { handleDragOver } = useDragAndDrop({
        onMove: () => {},
      });

      const event = createMockDragEvent('dragover');
      handleDragOver(event as any);

      expect(event.preventDefault).toHaveBeenCalled();
    });

    it('prevents default on dragEnter', () => {
      const { handleDragEnter } = useDragAndDrop({
        onMove: () => {},
      });

      const event = createMockDragEvent('dragenter');
      handleDragEnter(event as any);

      expect(event.preventDefault).toHaveBeenCalled();
    });
  });

  describe('complete drag workflow', () => {
    it('handles complete drag operation from start to end', () => {
      const onDragStart = vi.fn();
      const onMove = vi.fn();
      const onDragEnd = vi.fn();

      const { draggedIndex, handleDragStart, handleDragOver, handleDrop, handleDragEnd } = useDragAndDrop({
        onMove,
        onDragStart,
        onDragEnd,
      });

      // Start drag
      const startEvent = createMockDragEvent('dragstart');
      handleDragStart(startEvent as any, 1);
      expect(draggedIndex.value).toBe(1);
      expect(onDragStart).toHaveBeenCalledWith(1);

      // Drag over target
      const dragOverEvent = createMockDragEvent('dragover');
      handleDragOver(dragOverEvent as any);
      expect(dragOverEvent.preventDefault).toHaveBeenCalled();

      // Drop on target
      const dropEvent = createMockDragEvent('drop');
      handleDrop(dropEvent as any, 4);
      expect(onMove).toHaveBeenCalledWith(1, 4);
      expect(draggedIndex.value).toBe(null);

      // End drag
      handleDragEnd();
      expect(onDragEnd).toHaveBeenCalled();
    });
  });
});
