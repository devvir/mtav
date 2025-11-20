export interface DragAndDropCallbacks {
  onMove: (from: number, to: number) => void;
  onDragStart?: (index: number) => void;
  onDragEnd?: () => void;
}

export function useDragAndDrop(callbacks: DragAndDropCallbacks) {
  const draggedIndex: Ref<number | null> = ref(null);

  const handleDragStart = (event: DragEvent, index: number) => {
    draggedIndex.value = index;
    callbacks.onDragStart?.(index);
  };

  const handleDrop = (event: DragEvent, targetIndex: number) => {
    if (draggedIndex.value !== null && draggedIndex.value !== targetIndex) {
      callbacks.onMove(draggedIndex.value, targetIndex);
    }
    draggedIndex.value = null;
  };

  const handleDragEnd = () => {
    draggedIndex.value = null;
    callbacks.onDragEnd?.();
  };

  const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
  };

  const handleDragEnter = (event: DragEvent) => {
    event.preventDefault();
  };

  return {
    draggedIndex,
    handleDragStart,
    handleDrop,
    handleDragEnd,
    handleDragOver,
    handleDragEnter,
  };
}
