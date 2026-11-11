import { _ } from '@/lib/translations';
import { layered } from './kinds';

/** The floors a plan holds, as the server takes them: five basements, a hundred floors up */
export const LOWEST_FLOOR = -5;
export const HIGHEST_FLOOR = 100;

/** A floor brought within the ones a plan holds */
export const withinFloors = (floor: number) =>
  Math.min(HIGHEST_FLOOR, Math.max(LOWEST_FLOOR, floor));

/** How a floor is called: ground floor at 0, basements below, floors above */
export const floorLabel = (floor: number) =>
  floor === 0
    ? _('Ground floor')
    : floor < 0
      ? _('Basement {n}', { n: -floor })
      : _('Floor {n}', { n: floor });

/** The floors beneath one, lowest first, each with its shapes in the order they are drawn */
export const floorsBeneath = <T extends { type: string; floor: number }>(
  items: T[],
  floor: number,
) =>
  [...new Set(items.filter((item) => item.floor < floor).map((item) => item.floor))]
    .sort((a, b) => a - b)
    .map((below) => ({
      floor: below,
      items: layered(items.filter((item) => item.floor === below)),
    }));
