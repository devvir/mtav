import type { PolygonConfig } from '../types';

/**
 * Calculate canvas scale factors
 * Returns how much the canvas is scaled compared to declared boundary dimensions
 */
const getCanvasScale = (containerRef: Ref<HTMLDivElement | undefined>, boundary: ComputedRef<PolygonConfig>) => {
  return computed(() => {
    if (!containerRef.value) {
      return { scaleX: 1, scaleY: 1 };
    }

    const container = containerRef.value.getBoundingClientRect();
    const boundaryPoints = boundary.value.polygon;
    const boundaryXCoords = boundaryPoints.map(([x]: Point) => x);
    const boundaryYCoords = boundaryPoints.map(([, y]: Point) => y);
    const boundaryWidth = Math.max(...boundaryXCoords) - Math.min(...boundaryXCoords);
    const boundaryHeight = Math.max(...boundaryYCoords) - Math.min(...boundaryYCoords);

    return {
      scaleX: container.width / boundaryWidth,
      scaleY: container.height / boundaryHeight,
    };
  });
}

/**
 * Calculate ghost dimensions based on canvas scaling
 *
 * Algorithm:
 * 1. Get container pixel dimensions
 * 2. Get boundary declared dimensions from polygon
 * 3. Calculate scale: containerSize / boundarySize
 * 4. Get item declared dimensions from polygon
 * 5. Apply scale: itemSize * scale = ghost pixel size
 */
export const usePlanEditor = (
  containerRef: Ref<HTMLDivElement | undefined>,
  draggedItem: ComputedRef<PlanItem | undefined>,
  boundary: ComputedRef<PolygonConfig>,
) => {
  const canvasScale = getCanvasScale(containerRef, boundary);

  const ghostDimensions = computed(() => {
    if (!draggedItem.value || !containerRef.value) {
      return { width: 200, height: 200 };
    }

    const item = draggedItem.value;
    const { scaleX, scaleY } = canvasScale.value;

    // Get the item's declared dimensions
    const itemXCoords = item.polygon.map(([x]: Point) => x);
    const itemYCoords = item.polygon.map(([, y]: Point) => y);
    const itemWidth = Math.max(...itemXCoords) - Math.min(...itemXCoords);
    const itemHeight = Math.max(...itemYCoords) - Math.min(...itemYCoords);

    // Apply scaling to get ghost dimensions
    const ghostWidth = itemWidth * scaleX;
    const ghostHeight = itemHeight * scaleY;

    return { width: ghostWidth, height: ghostHeight };
  });

  /**
   * Convert screen coordinates to canvas coordinates
   *
   * Algorithm:
   * 1. Get mouse position relative to container
   * 2. Convert pixel position to canvas coordinates using inverse scale
   * 3. Return canvas coordinates where the drop occurred
   */
  const screenToCanvasCoords = (screenX: number, screenY: number): Point => {
    if (!containerRef.value) {
      return [0, 0];
    }

    const container = containerRef.value.getBoundingClientRect();
    const { scaleX, scaleY } = canvasScale.value;

    // Get position relative to container
    const relativeX = screenX - container.left;
    const relativeY = screenY - container.top;

    // Convert to canvas coordinates (inverse scale)
    const canvasX = relativeX / scaleX;
    const canvasY = relativeY / scaleY;

    return [canvasX, canvasY];
  };

  /**
   * Translate an item's polygon to a new position with grid snapping
   *
   * Algorithm:
   * 1. Translate polygon to new center position
   * 2. Snap the centroid to grid
   * 3. Apply snap correction to all points
   *
   * This approach works for both regular (square) and irregular shapes,
   * snapping the visual center rather than an arbitrary corner.
   */
  const translateItemTo = (item: PlanItem, newCenterX: number, newCenterY: number, gridSize: number = 5): Point[] => {
    // Calculate current centroid
    const xCoords = item.polygon.map(([x]: Point) => x);
    const yCoords = item.polygon.map(([, y]: Point) => y);
    const currentCenterX = (Math.max(...xCoords) + Math.min(...xCoords)) / 2;
    const currentCenterY = (Math.max(...yCoords) + Math.min(...yCoords)) / 2;

    // Calculate offset to move to new center
    const offsetX = newCenterX - currentCenterX;
    const offsetY = newCenterY - currentCenterY;

    // Translate all points to new center
    const translatedPolygon = item.polygon.map(([x, y]: Point): Point => [x + offsetX, y + offsetY]);

    // Calculate centroid of translated polygon
    const translatedXCoords = translatedPolygon.map(([x]) => x);
    const translatedYCoords = translatedPolygon.map(([, y]) => y);
    const translatedCenterX = (Math.max(...translatedXCoords) + Math.min(...translatedXCoords)) / 2;
    const translatedCenterY = (Math.max(...translatedYCoords) + Math.min(...translatedYCoords)) / 2;

    // Snap centroid to grid
    const snappedCenterX = Math.round(translatedCenterX / gridSize) * gridSize;
    const snappedCenterY = Math.round(translatedCenterY / gridSize) * gridSize;

    // Calculate correction needed to align centroid with grid
    const snapCorrectionX = snappedCenterX - translatedCenterX;
    const snapCorrectionY = snappedCenterY - translatedCenterY;

    // Apply snap correction to all points
    return translatedPolygon.map(([x, y]: Point): Point => [
      x + snapCorrectionX,
      y + snapCorrectionY
    ]);
  };

  return {
    ghostDimensions,
    screenToCanvasCoords,
    translateItemTo,
  };
}
