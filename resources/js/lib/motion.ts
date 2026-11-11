/** Whether the person asked their device for less motion */
export const prefersStill = () =>
  typeof window.matchMedia === 'function' &&
  window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * How a scroll asked from code moves: smoothly, or at once for someone who asked for less
 * motion. `element.scrollIntoView({ block: 'center', behavior: scrollBehavior() })`
 */
export const scrollBehavior = (): ScrollBehavior => (prefersStill() ? 'auto' : 'smooth');
