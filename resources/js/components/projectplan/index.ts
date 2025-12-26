/**
 * Project Plan SVG visualization library
 *
 * Public API:
 * - Plan: Full floor plan viewer with business logic
 * - Unit: Domain-specific wrapper for unit items
 * - Item: Generic plan item renderer (any type: unit, park, street, etc.)
 */

export { default as Plan } from './Plan.vue';
export { default as Unit } from './Unit.vue';
export { default as Item } from './Item.vue';

export type { AutoScale, PolygonConfig, CanvasConfig } from './types';
