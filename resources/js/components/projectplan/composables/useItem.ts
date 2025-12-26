/**
 * useItem composable
 *
 * - Styling (defaults by type + metadata overrides)
 * - Label positioning (centroid calculation)
 * - Text color contrast calculation
 * - Hover state management
 */

interface ItemStyle {
  fill: string;
  stroke: string;
  strokeWidth: number;
  opacity?: number;
}

/**
 * Get default styling based on item type
 */
function getDefaultStyle(type: string): ItemStyle {
  const styles: Record<string, ItemStyle> = {
    unit: {
      fill: '#e0f2fe',      // Light blue
      stroke: '#cbd5e1',    // Gray
      strokeWidth: 1,
    },
    park: {
      fill: '#f0fdf4',      // Light green
      stroke: '#16a34a',    // Dark green
      strokeWidth: 2,
    },
    street: {
      fill: '#f1f5f9',      // Light slate
      stroke: '#64748b',    // Slate
      strokeWidth: 1,
    },
    common: {
      fill: '#fefce8',      // Light yellow
      stroke: '#ca8a04',    // Yellow
      strokeWidth: 1,
    },
    amenity: {
      fill: '#fef3f2',      // Light red
      stroke: '#dc2626',    // Red
      strokeWidth: 1,
    },
  };

  return styles[type] || styles.unit;
}

/**
 * Calculate centroid of polygon for label positioning
 */
function calculateCentroid(points: Point[]): Point {
  if (points.length === 0) return [0, 0];

  const sumX = points.reduce((sum: number, [x]: [number, number]) => sum + x, 0);
  const sumY = points.reduce((sum: number, [, y]: [number, number]) => sum + y, 0);

  return [sumX / points.length, sumY / points.length];
}

/**
 * Determine contrasting text color based on background luminance
 */
function getContrastColor(bgColor: string): string {
  const hex = bgColor.replace('#', '');

  if (hex.length !== 6) return '#000';

  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);

  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

  return luminance > 0.5 ? '#000' : '#fff';
}

/**
 * Compose item with styling from PlanItem data
 * Handles default styling by type and metadata overrides
 */
export const useItem = (item: PlanItem) => {
  const defaultStyle = getDefaultStyle(item.type);

  const style = {
    opacity: item.metadata?.opacity ?? 1,
    fill: item.metadata?.fill ?? defaultStyle.fill,
    stroke: item.metadata?.stroke ?? defaultStyle.stroke,
    strokeWidth: item.metadata?.strokeWidth ?? defaultStyle.strokeWidth,
  };

  const centroid: ComputedRef<Point> = computed(() => calculateCentroid(item.polygon));
  const textColor: ComputedRef<string> = computed(() => getContrastColor(style.fill));
  const isHovering: Ref<boolean> = ref(false);

  return {
    style: readonly(style) as Readonly<Required<ItemStyle>>,
    centroid,
    textColor,
    isHovering,
  };
}
