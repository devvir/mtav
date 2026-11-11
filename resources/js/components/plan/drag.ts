import type { Point } from '@/types/models';
import { useRef, useState, type PointerEvent as ReactPointerEvent, type RefObject } from 'react';
import { align, type Guide } from './align';
import { angleTo, bounds } from './geometry';
import type { PlanModel } from './model';

/** A box corner, as shares of its width and height ([1, 0]: the top right) */
export type Corner = [number, number];

/** What is being dragged, and what it was like when the drag began */
type Drag =
  | { kind: 'move'; start: Point; original: Point[] }
  | { kind: 'vertex'; index: number }
  | { kind: 'edge'; index: number; start: Point; original: Point[] }
  | { kind: 'rotate'; from: number; original: Point[] }
  | { kind: 'resize'; anchor: Point; from: Point; original: Point[] };

/** Closer than this, two corners are level (or plumb) */
const SAME_LINE = 0.01;

/** A pointer must move this far (in pixels) before a press becomes a drag */
const DRAG_START = 3;

/** The pointer's position in plan coordinates */
export const toPlan = (
  svg: RefObject<SVGSVGElement | null>,
  event: { clientX: number; clientY: number },
): Point => {
  const matrix = svg.current!.getScreenCTM()!.inverse();
  const point = new DOMPoint(event.clientX, event.clientY).matrixTransform(matrix);
  return [point.x, point.y];
};

/** A few pixels on screen, in plan units: how near counts as lined up */
const reach = (svg: RefObject<SVGSVGElement | null>) => 6 / (svg.current?.getScreenCTM()?.a || 1);

/**
 * The corners a drag lines up with: the other shapes on this floor, beneath and above, the
 * boundary, the canvas; `own` adds the dragged shape's corners that stay where they are
 */
const anchors = (model: PlanModel, own: Point[] = []): Point[] => {
  const { width, height, polygon } = model.canvas;
  const others = [...model.visible, ...model.beneath, ...model.above].filter(
    (item) => item.key !== model.selected,
  );
  return [
    ...others.flatMap((item) => item.polygon),
    ...(model.editingBoundary ? [] : polygon),
    [0, 0],
    [width, 0],
    [0, height],
    [width, height],
    ...own,
  ];
};

const shift = (points: Point[], dx: number, dy: number) =>
  points.map(([x, y]): Point => [x + dx, y + dy]);

/**
 * Dragging on the plan's canvas (`svg`): a shape moved (Ctrl or Alt + drag takes a copy along),
 * a corner, a side moved square to itself, the shape turned from its handle or resized from a
 * corner of its box. What it is dragged to snaps to what it lines up with (other shapes, the
 * floors beneath and above, the boundary, its own corners), and `guides` show it. A right-click
 * never drags: it opens the menu. Each drag is one step in the history.
 */
export function usePlanDrag(model: PlanModel, svg: RefObject<SVGSVGElement | null>) {
  const drag = useRef<Drag | null>(null);
  // What the shape being dragged lines up with, shown while it does
  const [guides, setGuides] = useState<Guide[]>([]);
  const showGuides = (next: Guide[]) =>
    setGuides((shown) => (shown.length || next.length ? next : shown));

  // A press that may become a drag: the pointer is held only once it moves a little, so that
  // a double-click still reaches the shape clicked
  const pressed = useRef<{ id: number; x: number; y: number } | null>(null);
  const capture = (event: ReactPointerEvent) => {
    pressed.current = { id: event.pointerId, x: event.clientX, y: event.clientY };
  };
  /** The drag follows the pointer off the canvas (one not pressed, as a script's, cannot be held) */
  const hold = (event: ReactPointerEvent) => {
    const press = pressed.current;
    if (!press || Math.hypot(event.clientX - press.x, event.clientY - press.y) < DRAG_START) return;
    pressed.current = null;
    try {
      svg.current?.setPointerCapture(press.id);
    } catch {
      // Dragging still works within the canvas
    }
  };

  /** A press on a handle: this drag begins, unless it is a right-click (the menu's) */
  const begin = (event: ReactPointerEvent, next: () => Drag) => {
    event.stopPropagation();
    if (event.button === 2) return;
    drag.current = next();
    capture(event);
  };

  const startMove = (event: ReactPointerEvent, key: string) =>
    begin(event, () => {
      model.select(key);
      // Ctrl (or Alt) + drag leaves the shape where it was and takes a copy along
      if ((event.ctrlKey || event.altKey || event.metaKey) && key !== 'boundary')
        model.cloneInPlace();
      return {
        kind: 'move',
        start: toPlan(svg, event),
        original: structuredClone(model.activePolygon!),
      };
    });

  const startVertex = (event: ReactPointerEvent, index: number) =>
    begin(event, () => {
      model.selectVertex(index);
      return { kind: 'vertex', index };
    });

  const startEdge = (event: ReactPointerEvent, index: number) =>
    begin(event, () => ({
      kind: 'edge',
      index,
      start: toPlan(svg, event),
      original: structuredClone(model.activePolygon!),
    }));

  const startRotate = (event: ReactPointerEvent) =>
    begin(event, () => {
      const original = structuredClone(model.activePolygon!);
      return { kind: 'rotate', from: angleTo(original, toPlan(svg, event)), original };
    });

  /** The box's `corner` follows the pointer; the corner across from it stays */
  const startResize = (event: ReactPointerEvent, corner: Corner) =>
    begin(event, () => {
      const box = bounds(model.activePolygon!);
      const from: Point = [corner[0] ? box.maxX : box.minX, corner[1] ? box.maxY : box.minY];
      const anchor: Point = [corner[0] ? box.minX : box.maxX, corner[1] ? box.minY : box.maxY];
      return {
        kind: 'resize',
        anchor,
        from,
        original: structuredClone(model.activePolygon!),
      };
    });

  const onPointerMove = (event: ReactPointerEvent) => {
    const current = drag.current;
    const polygon = model.activePolygon;
    if (!current || !polygon) return;
    hold(event);
    const at = toPlan(svg, event);

    if (current.kind === 'move') {
      const moved = model.translate(
        current.original,
        at[0] - current.start[0],
        at[1] - current.start[1],
      );
      const lined = align(moved, anchors(model), reach(svg));
      model.setActivePolygon(shift(moved, lined.dx, lined.dy));
      showGuides(lined.guides);
    } else if (current.kind === 'vertex') {
      const next = [...polygon];
      const point: Point = [model.round(at[0]), model.round(at[1])];
      // Lined up with its own neighbours too: that is how a side ends up straight
      const lined = align(
        [point],
        anchors(
          model,
          polygon.filter((_, index) => index !== current.index),
        ),
        reach(svg),
      );
      next[current.index] = [point[0] + lined.dx, point[1] + lined.dy];
      model.setActivePolygon(next);
      showGuides(lined.guides);
    } else if (current.kind === 'edge') {
      const next = model.moveEdge(current.original, current.index, current.start, at);
      const ends = [current.index, (current.index + 1) % next.length];
      const [a, b] = [next[ends[0]!]!, next[ends[1]!]!];
      // A straight side moves square to itself: it lines up along that one direction
      const across = Math.abs(a[0] - b[0]) < SAME_LINE;
      const down = Math.abs(a[1] - b[1]) < SAME_LINE;
      const lined = align(
        [a, b],
        anchors(
          model,
          next.filter((_, index) => !ends.includes(index)),
        ),
        reach(svg),
        { x: across, y: down },
      );
      ends.forEach((index) => {
        const [x, y] = next[index]!;
        next[index] = [x + lined.dx, y + lined.dy];
      });
      model.setActivePolygon(next);
      showGuides(lined.guides);
    } else if (current.kind === 'rotate') {
      // Free with the grid off; in 5° steps with it on, 15° with Shift
      const turn = angleTo(current.original, at) - current.from;
      const step = event.shiftKey ? 15 : model.snap ? 5 : 0;
      model.setActivePolygon(
        model.rotate(current.original, step ? Math.round(turn / step) * step : turn),
      );
    } else {
      const corner: Point = [model.round(at[0]), model.round(at[1])];
      const lined = align([corner], anchors(model), reach(svg));
      model.setActivePolygon(
        model.resize(
          current.original,
          current.anchor,
          current.from,
          [corner[0] + lined.dx, corner[1] + lined.dy],
          true,
        ),
      );
      showGuides(lined.guides);
    }
  };

  /** The drag ends: what it did is one step in the history */
  const onPointerUp = () => {
    if (drag.current) model.commit();
    drag.current = null;
    pressed.current = null;
    showGuides([]);
  };

  return {
    guides,
    startMove,
    startVertex,
    startEdge,
    startRotate,
    startResize,
    onPointerMove,
    onPointerUp,
  };
}

/** The dragging a canvas offers its handles */
export type PlanDrag = ReturnType<typeof usePlanDrag>;
