import type { Point } from '@/types/models';
import { area, bounds, inside } from './geometry';

/**
 * What is scattered over a shape: trees over a green area, play equipment over a playground.
 * Each shape's own seed keeps them where they are from one drawing to the next.
 */

/** Something scattered: where it stands, how large, which of its sort, how it is turned */
export interface Scattered {
  x: number;
  y: number;
  r: number;
  kind: number;
  turn: number;
}

/** Play equipment seen from above (each drawn in materials.tsx as `play-<name>`) */
export const PLAY = ['swing', 'slide', 'sandpit', 'roundabout'] as const;

/** A repeatable sequence of numbers between 0 and 1, from a seed (the shape's identity) */
const random = (seed: string) => {
  let state = [...seed].reduce((hash, char) => (hash * 31 + char.charCodeAt(0)) >>> 0, 7);
  return () => {
    state = (state * 1664525 + 1013904223) >>> 0;
    return state / 2 ** 32;
  };
};

/** Where the trees of a green area stand: scattered, never outside it, more as it grows */
export function treesOf(polygon: Point[], seed: string) {
  const box = bounds(polygon);
  const size = Math.sqrt(area(polygon));
  const radius = Math.max(7, Math.min(18, size / 7));
  const step = radius * 2.1;
  const next = random(seed);
  const trees: Scattered[] = [];
  for (let y = box.minY + radius; y <= box.maxY - radius * 0.6; y += step) {
    for (let x = box.minX + radius; x <= box.maxX - radius * 0.6; x += step) {
      const spot: Point = [x + (next() - 0.5) * step * 0.7, y + (next() - 0.5) * step * 0.7];
      // A few clearings, so it never looks planted in rows
      if (next() < 0.22 || !inside(spot, polygon)) continue;
      trees.push({
        x: spot[0],
        y: spot[1],
        r: radius * (0.75 + next() * 0.45),
        kind: Math.floor(next() * 3),
        turn: Math.round(next() * 360),
      });
    }
  }
  return trees.slice(0, 60);
}

/**
 * What a playground holds, scattered over it as trees are over a green area: fewer, apart,
 * each turned its own way (never outside it)
 */
export function playOf(polygon: Point[], seed: string) {
  const box = bounds(polygon);
  const size = Math.sqrt(area(polygon));
  const half = Math.max(8, Math.min(16, size / 6));
  const step = half * 3;
  const next = random(`${seed}-play`);
  const items: Scattered[] = [];
  for (let y = box.minY + half * 1.3; y <= box.maxY - half; y += step) {
    for (let x = box.minX + half * 1.3; x <= box.maxX - half; x += step) {
      const spot: Point = [x + (next() - 0.5) * half, y + (next() - 0.5) * half];
      if (next() < 0.15 || !inside(spot, polygon)) continue;
      items.push({
        x: spot[0],
        y: spot[1],
        r: half * (0.85 + next() * 0.3),
        kind: Math.floor(next() * PLAY.length),
        turn: Math.round(next() * 4) * 90 + Math.round((next() - 0.5) * 20),
      });
    }
  }
  return items.slice(0, 16);
}
