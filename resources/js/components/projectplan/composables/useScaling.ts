import type { AutoScale, CanvasConfig, PolygonConfig } from '../types';

/**
 * Public API of the module
 */
export interface UseScaling {
  boundary?: PolygonConfig;
  items: PlanItem[];
  viewBox: string; // SVG viewBox attribute
  preserveAspectRatio: 'xMidYMid meet' | 'xMidYMid slice' | 'none';
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

/**
 * Apply transform to item coordinates
 */
function transformItem(item: PlanItem, transform: Transform): PlanItem {
  const polygon: Point[] = item.polygon.map(([x, y]) => [
    x * transform.scaleX + transform.offsetX,
    y * transform.scaleY + transform.offsetY,
  ]);

  return { ...item, polygon };
}

/**
 * Apply transform to boundary coordinates
 */
function transformBoundary(boundary: PolygonConfig, transform: Transform): PolygonConfig {
  const points = boundary.points.map(([x, y]: Point) => [
    x * transform.scaleX + transform.offsetX,
    y * transform.scaleY + transform.offsetY,
  ] as Point);

  return { ...boundary, points };
}

/**
 * Calculate bounding box for a set of items and boundary
 */
function calculateBoundingBox(items: PlanItem[], boundary?: PolygonConfig): BoundingBox {
  const allPoints: Point[] = [];

  // Collect all points from items
  items.forEach((item) => allPoints.push(...item.polygon));

  // Collect boundary points
  if (boundary?.points) {
    allPoints.push(...boundary.points);
  }

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

/**
 * Calculate scale factors and offsets for 'contain' mode
 * Maintains aspect ratio, fits content within canvas bounds with uniform scaling
 */
function containScale(bbox: BoundingBox, canvasWidth: number, canvasHeight: number): Transform {
  const scaleFactorX = canvasWidth / bbox.width;
  const scaleFactorY = canvasHeight / bbox.height;

  // Use smaller scale to maintain aspect ratio
  const scale = Math.min(scaleFactorX, scaleFactorY);

  // Calculate centered position
  const scaledWidth = bbox.width * scale;
  const scaledHeight = bbox.height * scale;

  const centerOffsetX = (canvasWidth - scaledWidth) / 2;
  const centerOffsetY = (canvasHeight - scaledHeight) / 2;

  return {
    scaleX: scale,
    scaleY: scale,
    offsetX: centerOffsetX - bbox.minX * scale,
    offsetY: centerOffsetY - bbox.minY * scale,
  };
}

/**
 * Calculate scale factors and offsets for 'cover' mode
 * Fills viewport completely while maintaining aspect ratio, may crop content
 */
function coverScale(bbox: BoundingBox, canvasWidth: number, canvasHeight: number): Transform {
  const scaleFactorX = canvasWidth / bbox.width;
  const scaleFactorY = canvasHeight / bbox.height;

  // Use larger scale to maintain aspect ratio while filling
  const scale = Math.max(scaleFactorX, scaleFactorY);

  // Calculate centered position
  const scaledWidth = bbox.width * scale;
  const scaledHeight = bbox.height * scale;

  const centerOffsetX = (canvasWidth - scaledWidth) / 2;
  const centerOffsetY = (canvasHeight - scaledHeight) / 2;

  return {
    scaleX: scale,
    scaleY: scale,
    offsetX: centerOffsetX - bbox.minX * scale,
    offsetY: centerOffsetY - bbox.minY * scale,
  };
}

/**
 * Calculate scale factors and offsets for 'fill' mode
 * Fills viewport completely, may distort aspect ratio
 */
function fillScale(bbox: BoundingBox, canvasWidth: number, canvasHeight: number): Transform {
  const scaleX = canvasWidth / bbox.width;
  const scaleY = canvasHeight / bbox.height;

  return {
    scaleX,
    scaleY,
    offsetX: -bbox.minX * scaleX,
    offsetY: -bbox.minY * scaleY,
  };
}

/**
 * Scaling composable for responsive floor plan visualization
 * Handles multiple scaling modes and coordinate transformations
 */
export const useScaling = (
  items: PlanItem[],
  boundary: PolygonConfig | undefined,
  config: CanvasConfig,
  mode: AutoScale,
  padding: number = 1.5,
): UseScaling => {
  const canvasWidth = config.width || 800;
  const canvasHeight = config.height || 600;
  const bbox = calculateBoundingBox(items, boundary);

  // Determine transform based on mode
  let transform: Transform;

  switch (mode) {
    case 'contain':
      transform = containScale(bbox, canvasWidth, canvasHeight);
      break;
    case 'cover':
      transform = coverScale(bbox, canvasWidth, canvasHeight);
      break;
    case 'fill':
      transform = fillScale(bbox, canvasWidth, canvasHeight);
      break;
    default:
      transform = { scaleX: 1, scaleY: 1, offsetX: 0, offsetY: 0 };
  }

  return {
    items: items.map((item: PlanItem) => transformItem(item, transform)),
    boundary: boundary ? transformBoundary(boundary, transform) : undefined,
    viewBox: `${-padding} ${-padding} ${canvasWidth + padding * 2} ${canvasHeight + padding * 2}`,
    preserveAspectRatio: 'xMidYMid meet',
  };
}
