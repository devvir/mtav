import { _ } from '@/lib/translations';

/** The kinds of shape a plan holds; a shape's kind never changes */
export type ShapeType =
  | 'unit'
  | 'building'
  | 'street'
  | 'green'
  | 'common'
  | 'parking'
  | 'playground'
  | 'entrance'
  | 'other';

/** The colour each kind starts in */
export const DEFAULT_COLORS: Record<ShapeType, string> = {
  unit: '#93c5fd',
  building: '#d6d3d1',
  street: '#a8a29e',
  green: '#86efac',
  common: '#fcd34d',
  parking: '#57534e',
  playground: '#fb923c',
  entrance: '#a5b4fc',
  other: '#e9d5ff',
};

/** Kinds whose colour is a choice (the others are their material: grass, asphalt…) */
export const TINTED: ShapeType[] = ['unit', 'building', 'common', 'entrance', 'other'];

/**
 * How each kind starts, as a share of the canvas's shorter side: a street long and narrow,
 * parking and green areas wider than tall, an entrance a small triangle pointing in (up,
 * from a street below; rotate it to face its street)
 */
export const STARTS: Record<ShapeType, { width: number; height: number; triangle?: boolean }> = {
  unit: { width: 0.12, height: 0.1 },
  building: { width: 0.18, height: 0.12 },
  street: { width: 0.6, height: 0.08 },
  green: { width: 0.28, height: 0.2 },
  common: { width: 0.2, height: 0.15 },
  parking: { width: 0.26, height: 0.18 },
  playground: { width: 0.18, height: 0.13 },
  entrance: { width: 0.07, height: 0.06, triangle: true },
  other: { width: 0.12, height: 0.1 },
};

/** What a shape is: a new one's name, and what hovering one without a name says */
export const kindLabel = (kind: ShapeType) =>
  ({
    unit: null,
    building: _('Building'),
    street: _('Street'),
    green: _('Green area'),
    common: _('Common space'),
    parking: _('Parking'),
    playground: _('Playground'),
    entrance: _('Entrance'),
    other: null,
  })[kind];

/**
 * In the order they are drawn: units on a layer of their own, over every other shape (a
 * unit is what people choose by: nothing ever covers it); each layer keeps its own order
 */
export const layered = <T extends { type: string }>(items: T[]) => [
  ...items.filter((item) => item.type !== 'unit'),
  ...items.filter((item) => item.type === 'unit'),
];
