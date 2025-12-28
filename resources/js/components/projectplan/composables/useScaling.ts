import type { ScaleMode, PolygonConfig } from '../types';

/**
 * Public API of the module
 */
export interface UseScaling {
  scale: (items: PlanItem[], boundary?: PolygonConfig, forceRatio?: number) => ScaleResult;
}

/**
 * Result of scaling operation
 */
export interface ScaleResult {
  items: PlanItem[];
  boundary?: PolygonConfig;
  scaleX: number;
  scaleY: number;
  viewBox: string;
  bbox: BoundingBox;
}

/**
 * Transform state for positioning/scaling (internal)
 */
interface Transform {
  scaleX: number;
  scaleY: number;
  offsetX: number;
  offsetY: number;
}

/**
 * Bounding box for coordinate calculations (internal)
 */
interface BoundingBox {
  minX: number;
  minY: number;
  maxX: number;
  maxY: number;
  width: number;
  height: number;
}

interface PolygonItem {
  polygon: Point[];
}

/**
 * Apply transform to polygon coordinates
 */
function transformPolygonItem<T extends PolygonItem>(item: T, transform: Transform): T {
  const polygon: Point[] = item.polygon.map(([x, y]) => [
    x * transform.scaleX + transform.offsetX,
    y * transform.scaleY + transform.offsetY,
  ]);

  return { ...item, polygon };
}

/**
 * Calculate bounding box for a set of items and boundary
 */
function calculateBoundingBox(items: PlanItem[], boundary?: PolygonConfig): BoundingBox {
  const allPoints: Point[] = [];

  // Collect all points from items
  items.forEach((item) => allPoints.push(...item.polygon));

  // Collect boundary points
  if (boundary?.polygon) allPoints.push(...boundary.polygon);

  if (allPoints.length === 0) {
    return { minX: 0, minY: 0, maxX: 100, maxY: 100, width: 100, height: 100 };
  }

  const xCoords = allPoints.map(([x]) => x);
  const yCoords = allPoints.map(([, y]) => y);

  const minX = Math.min(...xCoords);
  const minY = Math.min(...yCoords);
  const maxX = Math.max(...xCoords);
  const maxY = Math.max(...yCoords);

  return {
    minX, minY, maxX, maxY,
    width: maxX - minX,
    height: maxY - minY,
  };
}

function getScaleTransform(bbox: BoundingBox, forceRtio: number, scaleFn: (w: number, h: number) => number): Transform {
  const scale = scaleFn(forceRtio, 1);

  const centerOffsetX = bbox.width * (forceRtio - scale) / 2;
  const centerOffsetY = bbox.height * (1 - scale) / 2;

  return {
    scaleX: scale,
    scaleY: scale,
    offsetX: centerOffsetX - bbox.minX * scale,
    offsetY: centerOffsetY - bbox.minY * scale,
  };
}

function scalingSpec(mode: ScaleMode, items: PlanItem[], boundary: PolygonConfig | undefined, forceRatio?: number) {
  const bbox = calculateBoundingBox(items, boundary);
  const padding = boundary ? (boundary.strokeWidth || 1) / 2 : 0;
  const stretch = (mode === 'none' || ! forceRatio) ? 1 : forceRatio * bbox.width / bbox.height;

  let transform: Transform = { scaleX: 1, scaleY: 1, offsetX: 0, offsetY: 0 };

  switch (mode) {
    case 'contain':
      transform = getScaleTransform(bbox, forceRatio || 1, Math.min);
      break;
    case 'cover':
      transform = getScaleTransform(bbox, forceRatio || 1, Math.max);
      break;
    case 'fill':
      transform = { scaleX: stretch, scaleY: 1, offsetX: -bbox.minX * stretch, offsetY: -bbox.minY };
      break;
  }

  return { bbox, stretch, padding, ...transform };
}

/**
 * Scaling composable for responsive floor plan visualization
 * Handles multiple scaling modes and coordinate transformations
 */
export const useScaling = (mode: ScaleMode): UseScaling => {
  function scale(items: PlanItem[], boundary: PolygonConfig | undefined, forceRatio?: number): ScaleResult {
    const { bbox, stretch, padding, ...transform } = scalingSpec(mode, items, boundary, forceRatio);

    return {
      items: items.map((item: PlanItem) => transformPolygonItem(item, transform)),
      boundary: boundary ? transformPolygonItem(boundary, transform) : undefined,
      viewBox: `${-padding} ${-padding} ${stretch * bbox.width + padding * 2} ${bbox.height + padding * 2}`,
      scaleX: transform.scaleX,
      scaleY: transform.scaleY,
      bbox,
    };
  }

  return {
    scale,
  };
}
