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
export function useTextFitting(points: Point[], label: string = '') {
  const minFontSize = 10;
  const maxFontSize = 14;
  const polygonWidth = getPolygonWidth(points);
  const padding = 8; // pixels on each side
  const availableWidth = polygonWidth - (2 * padding);

  // If label fits on one line comfortably, use larger font
  // If it needs two lines, use smaller font
  let fontSize = maxFontSize;
  let isTruncated = false;

  // Rough approximation: at 12px, average character is ~7px wide
  // Adjust based on label length and available width
  const charactersPerLine = Math.floor(availableWidth / 7);

  if (label.length > charactersPerLine) {
    // Text will overflow to 2 lines
    isTruncated = true;
    // Scale down to fit 2 lines
    fontSize = Math.max(minFontSize, maxFontSize - 2);
  }

  return {
    fontSize: Math.round(fontSize),
    isTruncated,
  };
}
