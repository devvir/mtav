import type { AutoScale } from '../types';

// Private interface for common properties needed for scaling calculations
interface KonvaShapeConfig {
  points: number[];
  stroke?: string;
  fill?: string;
}

interface CanvasDimensions {
  height: number;
  width: number;
}

// ============================================================================
// 1. ENTRY POINT - Main scaling function
// ============================================================================

/**
 * Scale shapes based on the specified mode
 * @param mode - Scaling mode: 'off', 'scale' (proportional), or 'stretch'
 * @param shapes - Array of shapes to scale
 * @param boundary - Polygon representing the limits
 * @param canvasWidth - Target canvas width
 * @param canvasHeight - Target canvas height
 * @returns Object containing scaled shapes and scaled boundary
 */
const scale = function (
  mode: AutoScale,
  dimensions: CanvasDimensions,
  shapes: KonvaShapeConfig[],
  boundary?: KonvaShapeConfig,
): { shapes: KonvaShapeConfig[], boundary?: KonvaShapeConfig } {
  if (mode === 'scale') {
    return scaleProportional(dimensions, shapes, boundary);
  } else if (mode === 'stretch') {
    return scaleStretch(dimensions, shapes, boundary);
  } else if (mode === 'center') {
    return scaleCenter(dimensions, shapes, boundary);
  }

  return { shapes, boundary };
};

// ============================================================================
// 2. PUBLIC SCALING FUNCTIONS
// ============================================================================

/**
 * Scale shapes proportionally (maintains aspect ratio)
 * Uses the most constraining dimension to ensure everything fits
 */
const scaleProportional = function (
  dimensions: CanvasDimensions,
  shapes: KonvaShapeConfig[],
  boundary?: KonvaShapeConfig,
): { shapes: KonvaShapeConfig[], boundary?: KonvaShapeConfig } {
  const content = boundary ? [boundary, ...shapes] : shapes;
  const boundingBox = calculateBoundingBox(content);

  // Content dimensions
  const contentWidth = boundingBox.maxX - boundingBox.minX;
  const contentHeight = boundingBox.maxY - boundingBox.minY;

  // Use the smaller scale factor for proportional scaling
  const padding = 10;
  const availableWidth = dimensions.width - padding * 2;
  const availableHeight = dimensions.height - padding * 2;
  const scaleFactorX = availableWidth / contentWidth;
  const scaleFactorY = availableHeight / contentHeight;
  const uniformScale = Math.min(scaleFactorX, scaleFactorY);

  // Calculate offsets based on the uniform scale
  const scaledContentWidth = contentWidth * uniformScale;
  const scaledContentHeight = contentHeight * uniformScale;
  const offsetX = (dimensions.width - scaledContentWidth) / 2 - boundingBox.minX * uniformScale;
  const offsetY = (dimensions.height - scaledContentHeight) / 2 - boundingBox.minY * uniformScale;

  const scaledShapes = applyTransform(shapes, uniformScale, uniformScale, offsetX, offsetY);
  const scaledBoundary = boundary ? applyTransform([boundary], uniformScale, uniformScale, offsetX, offsetY)[0] : undefined;

  return { shapes: scaledShapes, boundary: scaledBoundary };
};

/**
 * Stretch shapes to fill canvas (may distort aspect ratio)
 * Uses independent scaling for each dimension
 */
const scaleStretch = function (
  dimensions: CanvasDimensions,
  shapes: KonvaShapeConfig[],
  boundary?: KonvaShapeConfig,
): { shapes: KonvaShapeConfig[], boundary?: KonvaShapeConfig } {
  const {
    scaleFactorX,
    scaleFactorY,
    offsetX,
    offsetY
  } = calculateScaleFactors(dimensions, shapes, boundary);

  const scaledShapes = applyTransform(shapes, scaleFactorX, scaleFactorY, offsetX, offsetY);
  const scaledBoundary = boundary ? applyTransform([boundary], scaleFactorX, scaleFactorY, offsetX, offsetY)[0] : undefined;

  return { shapes: scaledShapes, boundary: scaledBoundary };
};

/**
 * Center shapes without scaling (no scaling, just centering)
 * Maintains original size but centers content in canvas
 */
const scaleCenter = function (
  dimensions: CanvasDimensions,
  shapes: KonvaShapeConfig[],
  boundary?: KonvaShapeConfig,
): { shapes: KonvaShapeConfig[], boundary?: KonvaShapeConfig } {
  const content = boundary ? [boundary, ...shapes] : shapes;
  const boundingBox = calculateBoundingBox(content);

  // No scaling - scale factor is 1
  const uniformScale = 1;

  // Calculate offsets to center the content at original size
  const contentWidth = boundingBox.maxX - boundingBox.minX;
  const contentHeight = boundingBox.maxY - boundingBox.minY;
  const offsetX = (dimensions.width - contentWidth) / 2 - boundingBox.minX;
  const offsetY = (dimensions.height - contentHeight) / 2 - boundingBox.minY;

  const scaledShapes = applyTransform(shapes, uniformScale, uniformScale, offsetX, offsetY);
  const scaledBoundary = boundary ? applyTransform([boundary], uniformScale, uniformScale, offsetX, offsetY)[0] : undefined;

  return { shapes: scaledShapes, boundary: scaledBoundary };
};

// ============================================================================
// 3. AUXILIARY FUNCTIONS - Core scaling math
// ============================================================================

/**
 * Calculate bounding box of all shapes
 */
function calculateBoundingBox(shapes: KonvaShapeConfig[]) {
  if (shapes.length === 0) {
    return { minX: 0, minY: 0, maxX: 100, maxY: 100 };
  }

  let minX = Infinity;
  let minY = Infinity;
  let maxX = -Infinity;
  let maxY = -Infinity;

  shapes.forEach((shape: KonvaShapeConfig) => {
    for (let i = 0; i < shape.points.length; i += 2) {
      const x = shape.points[i];
      const y = shape.points[i + 1];
      minX = Math.min(minX, x);
      minY = Math.min(minY, y);
      maxX = Math.max(maxX, x);
      maxY = Math.max(maxY, y);
    }
  });
console.log({ minX, minY, maxX, maxY });
  return { minX, minY, maxX, maxY };
}

/**
 * Calculate maximum scale factors for both dimensions and centering offsets
 */
function calculateScaleFactors(
  dimensions: CanvasDimensions,
  shapes: KonvaShapeConfig[],
  boundary?: KonvaShapeConfig,
) {
  const content = boundary ? [boundary, ...shapes] : shapes;
  const boundingBox = calculateBoundingBox(content);
  const canvasWidth = dimensions.width;
  const canvasHeight = dimensions.height;

  // Content dimensions
  const contentWidth = boundingBox.maxX - boundingBox.minX;
  const contentHeight = boundingBox.maxY - boundingBox.minY;

  // Maximum scale factors for each dimension
  const padding = 10;
  const availableWidth = canvasWidth - padding * 2;
  const availableHeight = canvasHeight - padding * 2;
  const scaleFactorX = availableWidth / contentWidth;
  const scaleFactorY = availableHeight / contentHeight;

  // Translation offsets to center content
  const scaledMinX = boundingBox.minX * scaleFactorX;
  const scaledContentWidth = contentWidth * scaleFactorX;
  const centeringOffsetX = (canvasWidth - scaledContentWidth) / 2;
  const offsetX = centeringOffsetX - scaledMinX;

  const scaledMinY = boundingBox.minY * scaleFactorY;
  const scaledContentHeight = contentHeight * scaleFactorY;
  const centeringOffsetY = (canvasHeight - scaledContentHeight) / 2;
  const offsetY = centeringOffsetY - scaledMinY;

  return { scaleFactorX, scaleFactorY, offsetX, offsetY };
}

/**
 * Apply transformation to all shapes with given scale factors and offsets
 */
function applyTransform(
  shapes: KonvaShapeConfig[],
  scaleX: number,
  scaleY: number,
  offsetX: number,
  offsetY: number
): KonvaShapeConfig[] {
  return shapes.map((shape: KonvaShapeConfig) => ({
    ...shape,

    points: shape.points.map((coord: number, index: number) => {
      if (index % 2 === 0) {
        // x coordinate
        return coord * scaleX + offsetX;
      } else {
        // y coordinate
        return coord * scaleY + offsetY;
      }
    })
  }));
}

// ============================================================================
// 4. EXPORTS - Public API
// ============================================================================

export {
  scale,
  scaleProportional,
  scaleStretch,
  scaleCenter,
};