/**
 * useTextFitting composable
 *
 * Calculates optimal font size for text within a polygon based on available width.
 * Ensures text fits within the bounding box with proper clamping.
 */

function getPolygonWidth(points: Point[]): number {
  if (points.length === 0) return 0;

  const xs = points.map(([x]) => x);

  return Math.max(...xs) - Math.min(...xs);
}

/**
 * Calculate optimal font size for text within polygon width
 * Aims to fit text on 1-2 lines with reasonable padding
 */
export const useTextFitting = (
  polygonGetter: () => Point[],
  label: ComputedRef<string>
) => {
  const minFontSize = 8;
  const maxFontSize = 12;
  const padding = 8; // pixels on each side

  const fontSize = computed(() => {
    const polygonWidth = getPolygonWidth(polygonGetter());
    const availableWidth = polygonWidth - (2 * padding);

    // Rough approximation: at 12px, average character is ~7px wide
    // Adjust based on label length and available width
    const charactersPerLine = Math.floor(availableWidth / 7);

    let size = maxFontSize;

    if (label.value.length > charactersPerLine) {
      // Text will overflow to 2 lines, scale down to fit
      size = Math.max(minFontSize, maxFontSize - 2);
    }

    return Math.round(size);
  });

  const isTruncated = computed(() => {
    const polygonWidth = getPolygonWidth(polygonGetter());
    const availableWidth = polygonWidth - (2 * padding);
    const charactersPerLine = Math.floor(availableWidth / 7);
    return label.value.length > charactersPerLine;
  });

  return {
    fontSize,
    isTruncated,
  };
}
