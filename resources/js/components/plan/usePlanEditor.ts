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

let counter = 0;
const newKey = () => `new-${++counter}`;

const clone = <T>(value: T): T => JSON.parse(JSON.stringify(value));

/**
 * The plan editor's state: the canvas, its shapes, the selection, and an undo
 * history. Geometry helpers work on plain polygons ([[x, y], …]).
 */
export function usePlanEditor(plan: Plan) {
  const canvas = reactive({
    width: plan.width,
    height: plan.height,
    polygon: clone(plan.polygon) as Point[],
  });

  const items = ref<EditableItem[]>(
    plan.items.map((item) => ({
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
    })),
  );

  const floor = ref(0);
  const floors = computed(() =>
    [...new Set([0, ...items.value.map((item) => item.floor)])].sort((a, b) => a - b),
  );
  const extraFloors = ref<number[]>([]);
  const allFloors = computed(() =>
    [...new Set([...floors.value, ...extraFloors.value])].sort((a, b) => a - b),
  );

  const selected = ref<string | null>(null);
  const selectedItem = computed(
    () => items.value.find((item) => item.key === selected.value) ?? null,
  );
  const selectedVertex = ref<number | null>(null);

  const snap = ref(true);
  const grid = 5;
  const round = (value: number) =>
    snap.value ? Math.round(value / grid) * grid : Math.round(value * 10) / 10;

  /** Undo history */
  const past = ref<string[]>([]);
  const future = ref<string[]>([]);
  const snapshot = () => JSON.stringify({ canvas, items: items.value });
  let current = snapshot();

  function commit() {
    const next = snapshot();
    if (next === current) return;
    past.value.push(current);
    if (past.value.length > 100) past.value.shift();
    future.value = [];
    current = next;
  }

  function restore(state: string) {
    const parsed = JSON.parse(state);
    Object.assign(canvas, parsed.canvas);
    items.value = parsed.items;
    current = state;

    if (selected.value !== 'boundary' && !items.value.some((item) => item.key === selected.value)) {
      selected.value = null;
    }
  }

  function undo() {
    const previous = past.value.pop();
    if (previous === undefined) return;
    future.value.push(current);
    restore(previous);
  }

  function redo() {
    const next = future.value.pop();
    if (next === undefined) return;
    past.value.push(current);
    restore(next);
  }

  const dirty = computed(() => past.value.length > 0);

  /** Geometry */
  const bounds = (polygon: Point[]) => {
    const xs = polygon.map(([x]) => x);
    const ys = polygon.map(([, y]) => y);
    return {
      minX: Math.min(...xs),
      minY: Math.min(...ys),
      maxX: Math.max(...xs),
      maxY: Math.max(...ys),
    };
  };

  const centre = (polygon: Point[]): Point => {
    const b = bounds(polygon);
    return [(b.minX + b.maxX) / 2, (b.minY + b.maxY) / 2];
  };

  function translate(polygon: Point[], dx: number, dy: number): Point[] {
    const b = bounds(polygon);
    const sx = round(b.minX + dx) - b.minX;
    const sy = round(b.minY + dy) - b.minY;
    return polygon.map(([x, y]) => [x + sx, y + sy]);
  }

  /** Scale a polygon so that its bounding box spans from `anchor` to `to` */
  function resize(polygon: Point[], anchor: Point, from: Point, to: Point): Point[] {
    const target: Point = [round(to[0]), round(to[1])];
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

  /** Insert a vertex on an edge, at the point closest to `at` */
  function insertVertex(polygon: Point[], edge: number, at: Point): Point[] {
    const [a, b] = [polygon[edge], polygon[(edge + 1) % polygon.length]];
    const [dx, dy] = [b[0] - a[0], b[1] - a[1]];
    const t = Math.max(
      0,
      Math.min(1, ((at[0] - a[0]) * dx + (at[1] - a[1]) * dy) / (dx * dx + dy * dy || 1)),
    );
    const point: Point = [round(a[0] + t * dx), round(a[1] + t * dy)];
    return [...polygon.slice(0, edge + 1), point, ...polygon.slice(edge + 1)];
  }

  /** Shapes */
  function add(type: ShapeType) {
    const side = Math.max(20, Math.round(Math.min(canvas.width, canvas.height) / 10 / grid) * grid);
    const [cx, cy] = [round(canvas.width / 2), round(canvas.height / 2)];
    const item: EditableItem = {
      key: newKey(),
      id: null,
      type,
      floor: floor.value,
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
    items.value.push(item);
    selected.value = item.key;
    commit();
  }

  function duplicate() {
    const item = selectedItem.value;
    if (!item || item.unit) return;
    const copy = {
      ...clone(item),
      key: newKey(),
      id: null,
      polygon: translate(item.polygon, grid * 2, grid * 2),
    };
    items.value.push(copy);
    selected.value = copy.key;
    commit();
  }

  function remove() {
    const item = selectedItem.value;
    if (!item) return;

    if (selectedVertex.value !== null && item.polygon.length > 3) {
      item.polygon.splice(selectedVertex.value, 1);
      selectedVertex.value = null;
    } else if (!item.unit) {
      items.value = items.value.filter((other) => other.key !== item.key);
      selected.value = null;
    }
    commit();
  }

  function addFloor() {
    const next = Math.max(...allFloors.value) + 1;
    extraFloors.value.push(next);
    floor.value = next;
  }

  /** What the server stores */
  const payload = () => ({
    width: canvas.width,
    height: canvas.height,
    polygon: canvas.polygon,
    items: items.value.map((item) => ({
      id: item.id,
      type: item.type,
      floor: item.floor,
      name: item.name || null,
      color: item.color,
      polygon: item.polygon,
    })),
  });

  return {
    canvas,
    items,
    floor,
    allFloors,
    selected,
    selectedItem,
    selectedVertex,
    snap,
    commit,
    undo,
    redo,
    dirty,
    canUndo: computed(() => past.value.length > 0),
    canRedo: computed(() => future.value.length > 0),
    bounds,
    centre,
    translate,
    resize,
    insertVertex,
    round,
    add,
    duplicate,
    remove,
    addFloor,
    payload,
    markSaved() {
      past.value = [];
      future.value = [];
      current = snapshot();
    },
  };
}
