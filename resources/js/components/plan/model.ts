import type { Plan, PlanItem, Point } from '@/types/models';

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

export interface EditableItem {
  key: string;
  id: number | null;
  type: ShapeType;
  floor: number;
  name: string;
  color: string;
  polygon: Point[];
  unit: PlanItem['unit'];
}

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

const GRID = 5;

/**
 * How each kind starts, as a share of the canvas's shorter side: a street long and narrow,
 * parking and green areas wider than tall, an entrance a small triangle pointing in (up,
 * from a street below; rotate it to face its street)
 */
const STARTS: Record<ShapeType, { width: number; height: number; triangle?: boolean }> = {
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

/** A key no other shape has, even one drawn before a reload */
const newKey = () => `new-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 7)}`;

/**
 * What a state holds, whatever keys its shapes have: the canvas and each shape by its id
 * (one without an id is unsaved, so the state cannot match the server's)
 */
const signature = (state: string) => {
  const { canvas, items } = JSON.parse(state) as { canvas: unknown; items: EditableItem[] };
  return JSON.stringify({
    canvas,
    items: items.map(({ id, type, floor, name, color, polygon }) => ({
      id,
      type,
      floor,
      name,
      color,
      polygon,
    })),
  });
};

const clone = <T>(value: T): T => JSON.parse(JSON.stringify(value));

/** The box around a polygon */
export const bounds = (polygon: Point[]) => {
  const xs = polygon.map(([x]) => x);
  const ys = polygon.map(([, y]) => y);

  return {
    minX: Math.min(...xs),
    minY: Math.min(...ys),
    maxX: Math.max(...xs),
    maxY: Math.max(...ys),
  };
};

/** The middle of a polygon's box, where its label goes */
export const centre = (polygon: Point[]): Point => {
  const box = bounds(polygon);
  return [(box.minX + box.maxX) / 2, (box.minY + box.maxY) / 2];
};

/**
 * The plan editor's state: the canvas, its shapes, the selection, and an undo history,
 * kept outside any framework (the editor's component follows it with usePlanModel).
 * Geometry works on plain polygons ([[x, y], …]); every change calls the listeners.
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

  private past: string[] = [];
  private future: string[] = [];
  private current: string;
  /** The state last saved: unsaved is anything else */
  private saved: string;
  /** The ids the server gave shapes drawn here, by their key (kept across undo) */
  private ids = new Map<string, number>();
  /** A shape copied (Ctrl+C), to paste as often as wanted */
  private clipboard: EditableItem | null = null;
  private planId: number;
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
    }));
    this.planId = plan.id;
    this.current = this.snapshot();
    this.saved = this.current;
    this.recall();
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
    return this.items.filter((item) => item.floor === this.floor);
  }

  get canUndo() {
    return this.past.length > 0;
  }

  get canRedo() {
    return this.future.length > 0;
  }

  get dirty() {
    return this.current !== this.saved;
  }

  round = (value: number) =>
    this.snap ? Math.round(value / GRID) * GRID : Math.round(value * 10) / 10;

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

  /** Change the selected shape (its kind, name, colour, floor) */
  update(changes: Partial<Pick<EditableItem, 'type' | 'name' | 'color' | 'floor'>>) {
    const item = this.selectedItem;
    if (!item) return;
    Object.assign(item, changes);
    if (changes.floor !== undefined) this.floor = changes.floor;
    this.commit();
  }

  setCanvasSize(width: number, height: number) {
    this.canvas.width = width;
    this.canvas.height = height;
    this.commit();
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

  /** History */
  private snapshot() {
    return JSON.stringify({ canvas: this.canvas, items: this.items });
  }

  commit() {
    const next = this.snapshot();
    if (next !== this.current) {
      this.past.push(this.current);
      if (this.past.length > 100) this.past.shift();
      this.future = [];
      this.current = next;
      this.remember();
    }
    this.changed();
  }

  private restore(state: string) {
    const parsed = JSON.parse(state);
    this.canvas = parsed.canvas;
    // A shape saved since that state keeps the id it got
    this.items = parsed.items.map((item: EditableItem) => ({
      ...item,
      id: item.id ?? this.ids.get(item.key) ?? null,
    }));
    this.current = state;
    if (this.selected !== 'boundary' && !this.items.some((item) => item.key === this.selected)) {
      this.selected = null;
    }
    this.changed();
  }

  undo() {
    const previous = this.past.pop();
    if (previous === undefined) return;
    this.future.push(this.current);
    this.restore(previous);
    this.remember();
  }

  redo() {
    const next = this.future.pop();
    if (next === undefined) return;
    this.past.push(this.current);
    this.restore(next);
    this.remember();
  }

  /**
   * What was saved is now the plan: the ids its new shapes got (by key), kept for undo.
   * `state` is what was sent (changes since then stay unsaved).
   */
  markSaved(state: string = this.current, ids: Record<string, number> = {}) {
    Object.entries(ids).forEach(([key, id]) => {
      this.ids.set(key, id);
      const item = this.items.find((one) => one.key === key);
      if (item) item.id = id;
    });
    this.saved = state === this.current ? this.snapshot() : state;
    this.current = this.snapshot();
    this.remember();
    this.changed();
  }

  /** The state as it is now, to send and to mark saved once sent */
  get state() {
    return this.current;
  }

  /**
   * The history kept in the browser, so undo survives a reload: taken back only when what
   * it last saved is still the plan as the server has it (else someone changed it since)
   */
  private get storageKey() {
    return `mtav.plan-history.${this.planId}`;
  }

  private remember() {
    try {
      localStorage.setItem(
        this.storageKey,
        JSON.stringify({
          saved: signature(this.saved),
          current: this.current,
          past: this.past.slice(-40),
          future: this.future.slice(-40),
          ids: [...this.ids],
        }),
      );
    } catch {
      // No room, or no storage: undo lasts until the page closes
    }
  }

  private recall() {
    try {
      const kept = JSON.parse(localStorage.getItem(this.storageKey) ?? 'null');
      if (!kept || kept.saved !== signature(this.current) || signature(kept.current) !== kept.saved)
        return;
      this.ids = new Map(kept.ids);
      this.past = kept.past;
      this.future = kept.future;
      this.restore(kept.current);
      this.saved = this.current;
    } catch {
      // Unreadable: start afresh
    }
  }

  /** Bring the selected shape to the front of the stack, or send it to the back */
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

  /** What lies beneath the floor shown, nearest first: seen faintly, to build on */
  get beneath() {
    return this.items.filter((item) => item.floor < this.floor);
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
      polygon: this.translate(this.clipboard.polygon, GRID * 4, GRID * 4),
    };
    this.clipboard = copy;
    this.items.push(copy);
    this.selected = copy.key;
    this.commit();
  }

  /** Geometry */
  translate(polygon: Point[], dx: number, dy: number): Point[] {
    const box = bounds(polygon);
    const sx = this.round(box.minX + dx) - box.minX;
    const sy = this.round(box.minY + dy) - box.minY;
    return polygon.map(([x, y]) => [x + sx, y + sy]);
  }

  /** Scale a polygon so that its box spans from `anchor` to `to` */
  /**
   * An edge moved straight out or in, its two corners together, so a street widens or
   * narrows and a rectangle stays one
   */
  moveEdge(polygon: Point[], index: number, start: Point, at: Point): Point[] {
    const a = polygon[index]!;
    const b = polygon[(index + 1) % polygon.length]!;
    const length = Math.hypot(b[0] - a[0], b[1] - a[1]) || 1;
    const normal: Point = [-(b[1] - a[1]) / length, (b[0] - a[0]) / length];
    const distance = (at[0] - start[0]) * normal[0] + (at[1] - start[1]) * normal[1];
    const step = this.snap ? Math.round(distance / GRID) * GRID : Math.round(distance);
    const shift = ([x, y]: Point): Point => [x + normal[0] * step, y + normal[1] * step];
    const next = [...polygon];
    next[index] = shift(a);
    next[(index + 1) % polygon.length] = shift(b);
    return next;
  }

  /** Turned around its middle by an angle in degrees (a tenth of a unit is precise enough) */
  rotate(polygon: Point[], degrees: number): Point[] {
    const [cx, cy] = centre(polygon);
    const angle = (degrees * Math.PI) / 180;
    const [cos, sin] = [Math.cos(angle), Math.sin(angle)];
    const tenth = (value: number) => Math.round(value * 10) / 10;
    return polygon.map(([x, y]) => [
      tenth(cx + (x - cx) * cos - (y - cy) * sin),
      tenth(cy + (x - cx) * sin + (y - cy) * cos),
    ]);
  }

  resize(polygon: Point[], anchor: Point, from: Point, to: Point): Point[] {
    const target: Point = [this.round(to[0]), this.round(to[1])];
    const sx =
      Math.abs(from[0] - anchor[0]) < 1e-6 ? 1 : (target[0] - anchor[0]) / (from[0] - anchor[0]);
    const sy =
      Math.abs(from[1] - anchor[1]) < 1e-6 ? 1 : (target[1] - anchor[1]) / (from[1] - anchor[1]);
    if (Math.abs(sx) < 0.05 || Math.abs(sy) < 0.05) return polygon;
    return polygon.map(([x, y]) => [
      anchor[0] + (x - anchor[0]) * sx,
      anchor[1] + (y - anchor[1]) * sy,
    ]);
  }

  /** Insert a corner on an edge, at the point closest to `at` */
  insertVertex(polygon: Point[], edge: number, at: Point): Point[] {
    const [a, b] = [polygon[edge]!, polygon[(edge + 1) % polygon.length]!];
    const [dx, dy] = [b[0] - a[0], b[1] - a[1]];
    const t = Math.max(
      0,
      Math.min(1, ((at[0] - a[0]) * dx + (at[1] - a[1]) * dy) / (dx * dx + dy * dy || 1)),
    );
    const point: Point = [this.round(a[0] + t * dx), this.round(a[1] + t * dy)];
    return [...polygon.slice(0, edge + 1), point, ...polygon.slice(edge + 1)];
  }

  /** Shapes */
  add(type: ShapeType) {
    const unit = Math.min(this.canvas.width, this.canvas.height);
    const start = STARTS[type];
    const size = (share: number) => Math.max(20, Math.round((unit * share) / GRID) * GRID);
    const [w, h] = [size(start.width), size(start.height)];
    const [cx, cy] = [this.round(this.canvas.width / 2), this.round(this.canvas.height / 2)];
    const item: EditableItem = {
      key: newKey(),
      id: null,
      type,
      floor: this.floor,
      name: '',
      color: DEFAULT_COLORS[type],
      polygon: start.triangle
        ? [
            [cx, cy - h / 2],
            [cx + w / 2, cy + h / 2],
            [cx - w / 2, cy + h / 2],
          ]
        : [
            [cx - w / 2, cy - h / 2],
            [cx + w / 2, cy - h / 2],
            [cx + w / 2, cy + h / 2],
            [cx - w / 2, cy + h / 2],
          ],
      unit: null,
    };
    this.items.push(item);
    this.selected = item.key;
    this.commit();
  }

  duplicate() {
    const item = this.selectedItem;
    if (!item || item.unit) return;
    const copy = {
      ...clone(item),
      key: newKey(),
      id: null,
      polygon: this.translate(item.polygon, GRID * 2, GRID * 2),
    };
    this.items.push(copy);
    this.selected = copy.key;
    this.commit();
  }

  /** Remove the selected corner, or the selected shape (never a unit) */
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

  addFloor() {
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
      })),
    };
  }
}
