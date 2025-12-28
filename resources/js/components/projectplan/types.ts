/**
 * Scaling mode for responsive rendering (matches CSS object-fit semantics)
 *
 *  - 'contain': Scale to fit, maintaining aspect ratio (letterbox/pillarbox if needed)
 *  - 'cover': Scale to fill, maintaining aspect ratio (may clip)
 *  - 'fill': Scale to fill, may distort aspect ratio
 *  - 'none': No scaling applied
 */
export type ScaleMode = 'contain' | 'cover' | 'fill' | 'none';

/**
 * Polygon styling configuration
 * Represents SVG rendering properties for any polygon shape
 */
export interface PolygonConfig {
  polygon: Point[];
  fill?: string;
  stroke?: string;
  strokeWidth?: number;
  opacity?: number;
}
