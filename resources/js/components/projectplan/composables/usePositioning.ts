/**
 * Pan and zoom state for interactive positioning
 */
export interface ViewState {
  panX: number;
  panY: number;
  zoom: number;
}

/**
 * Positioning composable for interactive pan/zoom capabilities
 * Currently provides state management for future interactive features
 */
export function usePositioning() {
  const viewState = ref<ViewState>({ panX: 0, panY: 0, zoom: 1 });

  /**
   * Reset view to home position
   */
  function resetView() {
    viewState.value = { panX: 0, panY: 0, zoom: 1 };
  }

  /**
   * Update pan position
   */
  function pan(deltaX: number, deltaY: number) {
    viewState.value.panX += deltaX;
    viewState.value.panY += deltaY;
  }

  /**
   * Set zoom level
   */
  function setZoom(zoomLevel: number) {
    // Clamp zoom between 0.5 and 5
    viewState.value.zoom = Math.max(0.5, Math.min(5, zoomLevel));
  }

  /**
   * Zoom in/out by a factor
   */
  function zoomBy(factor: number) {
    setZoom(viewState.value.zoom * factor);
  }

  /**
   * Computed CSS transform for the viewport
   */
  const transform = computed(() => {
    return `translate(${viewState.value.panX}px, ${viewState.value.panY}px) scale(${viewState.value.zoom})`;
  });

  return {
    pan,
    zoomBy,
    setZoom,
    resetView,
    transform,
    viewState: readonly(viewState) as Readonly<ViewState>,
  };
}
