import type { Plan, PlanItem, Point } from '@/types/models';

export type ShapeType = 'unit' | 'building' | 'street' | 'green' | 'common' | 'other';

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
  other: '#e9d5ff',
};

const GRID = 5;

let counter = 0;
const newKey = () => `new-${++counter}`;

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
    this.current = this.snapshot();
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
    return this.past.length > 0;
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
    }
    this.changed();
  }

  private restore(state: string) {
    const parsed = JSON.parse(state);
    this.canvas = parsed.canvas;
    this.items = parsed.items;
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
  }

  redo() {
    const next = this.future.pop();
    if (next === undefined) return;
    this.past.push(this.current);
    this.restore(next);
  }

  markSaved() {
    this.past = [];
    this.future = [];
    this.current = this.snapshot();
    this.changed();
  }

  /** Geometry */
  translate(polygon: Point[], dx: number, dy: number): Point[] {
    const box = bounds(polygon);
    const sx = this.round(box.minX + dx) - box.minX;
    const sy = this.round(box.minY + dy) - box.minY;
    return polygon.map(([x, y]) => [x + sx, y + sy]);
  }

  /** Scale a polygon so that its box spans from `anchor` to `to` */
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
    const side = Math.max(
      20,
      Math.round(Math.min(this.canvas.width, this.canvas.height) / 10 / GRID) * GRID,
    );
    const [cx, cy] = [this.round(this.canvas.width / 2), this.round(this.canvas.height / 2)];
    const item: EditableItem = {
      key: newKey(),
      id: null,
      type,
      floor: this.floor,
      name: '',
      color: DEFAULT_COLORS[type],
      polygon: [
        [cx - side / 2, cy - side / 2],
        [cx + side / 2, cy - side / 2],
        [cx + side / 2, cy + side / 2],
        [cx - side / 2, cy + side / 2],
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
        type: item.type,
        floor: item.floor,
        name: item.name || null,
        color: item.color,
        polygon: item.polygon,
      })),
    };
  }
}
