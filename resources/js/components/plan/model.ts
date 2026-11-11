import type { Plan, PlanItem, Point } from '@/types/models';
import { HIGHEST_FLOOR, withinFloors } from './floors';
import { bounds } from './geometry';
import * as grid from './grid';
import { PlanHistory } from './history';
import { DEFAULT_COLORS, layered, type ShapeType } from './kinds';

export interface EditableItem {
  key: string;
  id: number | null;
  type: ShapeType;
  floor: number;
  name: string;
  color: string;
  polygon: Point[];
  unit: PlanItem['unit'];
  /** On the plan; false: a unit waiting beside the canvas to be placed */
  placed: boolean;
}

/** A key no other shape has, even one drawn before a reload */
const newKey = () => `new-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 7)}`;

const clone = <T>(value: T): T => JSON.parse(JSON.stringify(value));

/** A state as the history holds it */
interface State {
  canvas: PlanModel['canvas'];
  items: EditableItem[];
}

/**
 * The plan editor's state: the canvas, its shapes, the selection, and an undo history
 * (`PlanHistory`), kept outside any framework (the editor follows it with
 * useSyncExternalStore). Geometry works on plain polygons ([[x, y], …]), on the grid while
 * snapping; every change calls the listeners.
 */
export class PlanModel {
  canvas: { width: number; height: number; polygon: Point[] };
  items: EditableItem[];
  floor = 0;
  extraFloors: number[] = [];
  /** A shape's key, 'boundary', or nothing */
  selected: string | null = null;
  selectedVertex: number | null = null;
  snap = true;

  private history: PlanHistory;
  /** A shape copied (Ctrl+C), to paste as often as wanted */
  private clipboard: EditableItem | null = null;
  private listeners = new Set<() => void>();
  /** Grows on every change: what React compares */
  version = 0;

  constructor(plan: Plan) {
    this.canvas = { width: plan.width, height: plan.height, polygon: clone(plan.polygon) };
    this.items = plan.items.map((item) => ({
      key: `item-${item.id}`,
      id: item.id,
      type: item.type as ShapeType,
      floor: item.floor,
      name: item.name ?? '',
      color:
        (item.metadata?.color as string) ??
        DEFAULT_COLORS[item.type as ShapeType] ??
        DEFAULT_COLORS.other,
      polygon: clone(item.polygon),
      unit: item.unit,
      placed: item.placed ?? true,
    }));
    this.history = new PlanHistory(plan.id, this.snapshot());
    // Undo outlives a reload: the history kept in the browser, when it still fits the plan
    const kept = this.history.recall();
    if (kept !== null) this.restore(kept);
  }

  subscribe = (listener: () => void) => {
    this.listeners.add(listener);
    return () => {
      this.listeners.delete(listener);
    };
  };

  getVersion = () => this.version;

  /** Something changed that should be drawn (but not kept in the history) */
  changed() {
    this.version++;
    this.listeners.forEach((listener) => listener());
  }

  get floors() {
    return [...new Set([0, ...this.items.map((item) => item.floor)])].sort((a, b) => a - b);
  }

  get allFloors() {
    return [...new Set([...this.floors, ...this.extraFloors])].sort((a, b) => a - b);
  }

  get selectedItem() {
    return this.items.find((item) => item.key === this.selected) ?? null;
  }

  get editingBoundary() {
    return this.selected === 'boundary';
  }

  /** The polygon being edited: the boundary's or the selected shape's */
  get activePolygon(): Point[] | null {
    return this.editingBoundary ? this.canvas.polygon : (this.selectedItem?.polygon ?? null);
  }

  get visible() {
    return layered(this.items.filter((item) => item.placed && item.floor === this.floor));
  }

  /** Units added elsewhere, waiting beside the canvas */
  get waiting() {
    return this.items.filter((item) => !item.placed);
  }

  /** A waiting unit onto the floor shown, in the middle of the canvas, selected */
  place(key: string) {
    const item = this.items.find((one) => one.key === key);
    if (!item || item.placed) return;
    const box = bounds(item.polygon);
    item.placed = true;
    item.floor = this.floor;
    item.polygon = this.translate(
      item.polygon,
      this.canvas.width / 2 - (box.minX + box.maxX) / 2,
      this.canvas.height / 2 - (box.minY + box.maxY) / 2,
    );
    this.selected = item.key;
    this.selectedVertex = null;
    this.commit();
  }

  get canUndo() {
    return this.history.canUndo;
  }

  get canRedo() {
    return this.history.canRedo;
  }

  get dirty() {
    return this.history.dirty;
  }

  round = (value: number) => grid.round(value, this.snap);

  select(key: string | null) {
    this.selected = key;
    this.selectedVertex = null;
    this.changed();
  }

  setActivePolygon(polygon: Point[]) {
    if (this.editingBoundary) this.canvas.polygon = polygon;
    else if (this.selectedItem) this.selectedItem.polygon = polygon;
    this.changed();
  }

  /** Pick one of the active polygon's corners */
  selectVertex(index: number | null) {
    this.selectedVertex = index;
    this.changed();
  }

  /** Change the selected shape as one types (kept in the history on commit) */
  edit(changes: Partial<Pick<EditableItem, 'name' | 'color'>>) {
    const item = this.selectedItem;
    if (!item) return;
    Object.assign(item, changes);
    this.changed();
  }

  /** Name a shape (what people read on it) */
  rename(key: string, name: string) {
    const item = this.items.find((one) => one.key === key);
    if (!item) return;
    item.name = name;
    this.commit();
  }

  /**
   * Change the selected shape (its name, colour, floor: the floor shown follows it, never past
   * the floors a plan holds)
   */
  update(changes: Partial<Pick<EditableItem, 'name' | 'color' | 'floor'>>) {
    const item = this.selectedItem;
    if (!item) return;
    const floor = changes.floor === undefined ? undefined : withinFloors(changes.floor);
    Object.assign(item, changes, floor === undefined ? {} : { floor });
    if (floor !== undefined) this.floor = floor;
    this.commit();
  }

  /** Whether the selected shape can go a floor up (1) or down (-1): not past the ends */
  canMoveFloor(by: 1 | -1) {
    const item = this.selectedItem;
    return Boolean(item) && withinFloors(item!.floor + by) === item!.floor + by;
  }

  /** The selected shape a floor up (1) or down (-1), followed there */
  moveFloor(by: 1 | -1) {
    const item = this.selectedItem;
    if (item && this.canMoveFloor(by)) this.update({ floor: item.floor + by });
  }

  /** The canvas no shorter than what is drawn on it (a boundary that is its edge follows it) */
  get minHeight() {
    const drawn = this.items.filter((item) => item.placed).flatMap((item) => item.polygon);
    const edge = this.boundaryIsCanvas ? [] : this.canvas.polygon;
    return Math.max(100, Math.ceil(Math.max(0, ...[...drawn, ...edge].map(([, y]) => y))));
  }

  /** Whether the boundary is still just the canvas's edge */
  private get boundaryIsCanvas() {
    const { width, height, polygon } = this.canvas;
    return polygon.every(([x, y]) => (x === 0 || x === width) && (y === 0 || y === height));
  }

  /** The canvas taller or shorter, as it is dragged (kept in the history on commit) */
  setCanvasHeight(height: number) {
    const next = Math.min(
      10000,
      Math.max(this.minHeight, Math.round(height / grid.GRID) * grid.GRID),
    );
    if (next === this.canvas.height) return;
    if (this.boundaryIsCanvas)
      this.canvas.polygon = this.canvas.polygon.map(([x, y]): Point => [
        x,
        y === this.canvas.height ? next : y,
      ]);
    this.canvas.height = next;
    this.changed();
  }

  showFloor(floor: number) {
    this.floor = floor;
    this.selected = null;
    this.selectedVertex = null;
    this.changed();
  }

  setSnap(snap: boolean) {
    this.snap = snap;
    this.changed();
  }

  /** History: each change kept (`commit`), undone and redone, and what was saved */
  private snapshot() {
    return JSON.stringify({ canvas: this.canvas, items: this.items } satisfies State);
  }

  commit() {
    this.history.push(this.snapshot());
    this.changed();
  }

  private restore(state: string) {
    const parsed = JSON.parse(state) as State;
    this.canvas = parsed.canvas;
    // A shape saved since that state keeps the id it got (one brought back by undo, the id
    // the server gave it anew)
    this.items = parsed.items.map((item) => ({
      ...item,
      id: this.history.idOf(item.key) ?? item.id ?? null,
    }));
    if (this.selected !== 'boundary' && !this.items.some((item) => item.key === this.selected)) {
      this.selected = null;
    }
    this.changed();
  }

  undo() {
    const previous = this.history.undo();
    if (previous !== null) this.restore(previous);
  }

  redo() {
    const next = this.history.redo();
    if (next !== null) this.restore(next);
  }

  /**
   * What was saved is now the plan: the ids its new shapes got (by key), kept for undo.
   * `state` is what was sent (changes since then stay unsaved).
   */
  markSaved(state: string = this.history.current, ids: Record<string, number> = {}) {
    Object.entries(ids).forEach(([key, id]) => {
      const item = this.items.find((one) => one.key === key);
      if (item) item.id = id;
    });
    this.history.markSaved(state, this.snapshot(), ids);
    this.changed();
  }

  /** The state as it is now, to send and to mark saved once sent */
  get state() {
    return this.history.current;
  }

  /** Bring the selected shape to the front of its layer, or send it to the back */
  restack(where: 'front' | 'back') {
    const item = this.selectedItem;
    if (!item) return;
    this.items = this.items.filter((one) => one !== item);
    if (where === 'front') this.items.push(item);
    else this.items.unshift(item);
    this.commit();
  }

  /** A copy of the selected shape right on top of it, selected (Ctrl+drag takes it away) */
  cloneInPlace() {
    const item = this.selectedItem;
    if (!item || item.unit) return false;
    const copy = { ...clone(item), key: newKey(), id: null };
    this.items.push(copy);
    this.selected = copy.key;
    this.changed();
    return true;
  }

  /** The floor just above the one shown: outlined, to line up with */
  get above() {
    return this.items.filter((item) => item.placed && item.floor === this.floor + 1);
  }

  /** What lies beneath the floor shown: seen faintly, to build on */
  get beneath() {
    return this.items.filter((item) => item.placed && item.floor < this.floor);
  }

  /** Copy the selected shape (never a unit: a unit is one of a kind) */
  copy() {
    const item = this.selectedItem;
    if (item && !item.unit) this.clipboard = clone(item);
  }

  get canPaste() {
    return this.clipboard !== null;
  }

  /** A copy of what was copied, a little aside, on the floor shown */
  paste() {
    if (!this.clipboard) return;
    const copy = {
      ...clone(this.clipboard),
      key: newKey(),
      id: null,
      floor: this.floor,
      polygon: this.translate(this.clipboard.polygon, grid.GRID * 4, grid.GRID * 4),
    };
    this.clipboard = copy;
    this.items.push(copy);
    this.selected = copy.key;
    this.commit();
  }

  /** Geometry, on the grid while snapping (grid.ts) */
  translate(polygon: Point[], dx: number, dy: number): Point[] {
    return grid.translate(polygon, dx, dy, this.snap);
  }

  /** An edge moved straight out or in, its two corners together */
  moveEdge(polygon: Point[], index: number, start: Point, at: Point): Point[] {
    return grid.moveEdge(polygon, index, start, at, this.snap);
  }

  /** Turned around its middle by an angle in degrees */
  rotate(polygon: Point[], degrees: number): Point[] {
    return grid.rotate(polygon, degrees);
  }

  /** Scaled so that the corner at `from` goes `to` (taken as it is when `exact`: lined up) */
  resize(polygon: Point[], anchor: Point, from: Point, to: Point, exact = false): Point[] {
    const target: Point = exact ? to : [this.round(to[0]), this.round(to[1])];
    return grid.scale(polygon, anchor, from, target);
  }

  /** Insert a corner on an edge, at the point closest to `at` */
  insertVertex(polygon: Point[], edge: number, at: Point): Point[] {
    return grid.insertVertex(polygon, edge, at, this.snap);
  }

  /** Shapes: a new one named as its kind is (`name`), a name that may be cleared */
  add(type: ShapeType, name = '') {
    const item: EditableItem = {
      key: newKey(),
      id: null,
      type,
      floor: this.floor,
      name,
      color: DEFAULT_COLORS[type],
      polygon: this.startOf(type),
      unit: null,
      placed: true,
    };
    this.items.push(item);
    this.selected = item.key;
    this.commit();
  }

  /**
   * A unit the server just created where it was drawn: on top, selected, and in every state
   * of the history (undoing past it never takes it off the plan, which holds it anyway)
   */
  adopt(saved: PlanItem) {
    const item: EditableItem = {
      key: `item-${saved.id}`,
      id: saved.id,
      type: saved.type as ShapeType,
      floor: saved.floor,
      name: saved.name ?? '',
      color: DEFAULT_COLORS.unit,
      polygon: clone(saved.polygon),
      unit: saved.unit,
      placed: true,
    };
    const withIt = (state: string) => {
      const parsed = JSON.parse(state) as State;
      parsed.items.push(item);
      return JSON.stringify(parsed);
    };
    this.items.push(item);
    this.history.includeEverywhere(withIt, this.snapshot());
    this.selected = item.key;
    this.changed();
  }

  /** Where a new shape of a kind starts: in the middle of the canvas, in its kind's shape */
  startOf(type: ShapeType): Point[] {
    return grid.startShape(type, this.canvas, this.snap);
  }

  duplicate() {
    const item = this.selectedItem;
    if (!item || item.unit) return;
    const copy = {
      ...clone(item),
      key: newKey(),
      id: null,
      polygon: this.translate(item.polygon, grid.GRID * 2, grid.GRID * 2),
    };
    this.items.push(copy);
    this.selected = copy.key;
    this.commit();
  }

  /** Removes the selected corner (of a shape or the boundary; three always stay), else the shape */
  remove() {
    const polygon = this.activePolygon;
    if (this.selectedVertex !== null && polygon) {
      if (polygon.length > 3) {
        this.setActivePolygon(polygon.filter((_, index) => index !== this.selectedVertex));
        this.selectedVertex = null;
        this.commit();
      }
      return;
    }

    const item = this.selectedItem;
    if (!item) return;

    if (!item.unit) {
      this.items = this.items.filter((other) => other.key !== item.key);
      this.selected = null;
    }
    this.commit();
  }

  /** Whether there is a floor left above the highest one shown */
  get canAddFloor() {
    return Math.max(...this.allFloors) < HIGHEST_FLOOR;
  }

  addFloor() {
    if (!this.canAddFloor) return;
    const next = Math.max(...this.allFloors) + 1;
    this.extraFloors.push(next);
    this.floor = next;
    this.changed();
  }

  /** What the server stores */
  payload() {
    return {
      width: this.canvas.width,
      height: this.canvas.height,
      polygon: this.canvas.polygon,
      items: this.items.map((item) => ({
        id: item.id,
        key: item.key,
        type: item.type,
        floor: item.floor,
        name: item.name || null,
        color: item.color,
        polygon: item.polygon,
        placed: item.placed,
      })),
    };
  }
}
