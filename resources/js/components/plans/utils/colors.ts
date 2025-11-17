/**
 * Utility to resolve CSS custom properties to actual color values
 * that can be used in JavaScript canvas contexts like Konva
 */
export function getCSSVar(varName: string): string {
  if (typeof window !== 'undefined') {
    const value = getComputedStyle(document.documentElement)
      .getPropertyValue(varName)
      .trim();
    // CSS variables are already complete color values like "hsl(0 0% 9%)"
    return value || '#3b82f6'; // fallback if empty
  }
  return '#3b82f6'; // fallback blue
}