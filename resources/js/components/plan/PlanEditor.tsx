import { UnitForm } from '@/components/forms/UnitForm';
import { _ } from '@/lib/translations';
import type { Plan, Point, UnitType } from '@/types/models';
import {
  ActionIcon,
  Button,
  Card,
  ColorInput,
  ColorSwatch,
  Group,
  Kbd,
  Loader,
  Menu,
  Modal,
  NativeSelect,
  Stack,
  Switch,
  Text,
  TextInput,
  Title,
  Tooltip,
  UnstyledButton,
} from '@mantine/core';
import {
  IconArrowBackUp,
  IconArrowDown,
  IconArrowForwardUp,
  IconArrowUp,
  IconClipboard,
  IconClipboardCopy,
  IconCloudCheck,
  IconCloudOff,
  IconCopy,
  IconPencil,
  IconRotateClockwise,
  IconStack2,
  IconStackBack,
  IconStackFront,
  IconTrash,
} from '@tabler/icons-react';
import {
  forwardRef,
  useCallback,
  useEffect,
  useId,
  useImperativeHandle,
  useMemo,
  useRef,
  useState,
  useSyncExternalStore,
  type PointerEvent as ReactPointerEvent,
} from 'react';
import { align, type Guide } from './align';
import { floorLabel } from './floors';
import { bounds, centre, DEFAULT_COLORS, floorsBeneath, PlanModel, type ShapeType } from './model';
import { kindLabel, PlanDefs, PlanGround, PlanHint, PlanShape } from './render';

export type PlanPayload = ReturnType<PlanModel['payload']>;

export interface PlanEditorHandle {
  /** Saves what is pending now; whether everything is saved */
  flush: () => Promise<boolean>;
  dirty: () => boolean;
}

export type SaveStatus = 'saved' | 'unsaved' | 'saving' | 'failed';

/** How long after the last change the plan saves itself (changes made meanwhile go along) */
const AUTOSAVE_MS = 1200;

/** Closer than this, two corners are level (or plumb) */
const SAME_LINE = 0.01;

type Drag =
  | { kind: 'move'; start: Point; original: Point[] }
  | { kind: 'vertex'; index: number }
  | { kind: 'edge'; index: number; start: Point; original: Point[] }
  | { kind: 'rotate'; from: number; original: Point[] }
  | { kind: 'resize'; anchor: Point; from: Point; original: Point[] };

/** Kinds whose colour is a choice (the others are their material: grass, asphalt…) */
const TINTED: ShapeType[] = ['unit', 'building', 'common', 'entrance', 'other'];

/** The colours offered at hand (the shape's details offer any) */
const SWATCHES = [
  '#93c5fd',
  '#a5b4fc',
  '#e9d5ff',
  '#f9a8d4',
  '#fca5a5',
  '#fdba74',
  '#fcd34d',
  '#86efac',
  '#d6d3d1',
];

/** The angle, in degrees, from a shape's middle to a point */
const angleTo = (polygon: Point[], [x, y]: Point) => {
  const [cx, cy] = centre(polygon);
  return (Math.atan2(y - cy, x - cx) * 180) / Math.PI;
};

const CORNERS: [number, number][] = [
  [0, 0],
  [1, 0],
  [1, 1],
  [0, 1],
];

/** Where saving is: quiet when all is saved, a retry when it could not be */
export function SaveState({ status, onRetry }: { status: SaveStatus; onRetry: () => void }) {
  if (status === 'failed')
    return (
      <Group gap={6} c="var(--mtav-warning)" fz="sm" fw={600} role="status">
        <IconCloudOff size={16} />
        {_('Not saved yet')}
        <Button variant="subtle" size="compact-xs" onClick={onRetry}>
          {_('Try again')}
        </Button>
      </Group>
    );

  return (
    <Group gap={6} className="ds-caption" role="status" aria-live="polite">
      {status === 'saved' ? <IconCloudCheck size={16} /> : <Loader size={12} />}
      {status === 'saved' ? _('Saved') : _('Saving…')}
    </Group>
  );
}

/**
 * Naming a shape where it stands: a small field over it, taken on Enter or on leaving it,
 * left as it was on Escape. `at` is where, within the plan's frame.
 */
function NameField({
  item,
  at,
  onClose,
  onSave,
}: {
  item: { name: string; type: ShapeType };
  at: { x: number; y: number };
  onClose: () => void;
  onSave: (name: string) => void;
}) {
  const [name, setName] = useState(item.name);
  const left = useRef(false);
  const finish = (save: boolean) => {
    if (left.current) return;
    left.current = true;
    if (save) onSave(name.trim());
    else onClose();
  };

  return (
    <div className="plan-name-field" style={{ left: at.x, top: at.y }}>
      <TextInput
        size="xs"
        name="shape_name"
        aria-label={_('Name')}
        value={name}
        maxLength={80}
        autoFocus
        onFocus={(event) => event.currentTarget.select()}
        onChange={(event) => setName(event.currentTarget.value)}
        onBlur={() => finish(true)}
        onKeyDown={(event) => {
          if (event.key === 'Enter') finish(true);
          else if (event.key === 'Escape') finish(false);
        }}
      />
    </div>
  );
}

/**
 * Draw the project plan: move shapes, resize them from their corners, move one side (drag
 * an edge), rotate them (the handle above, or R), edit their corners (drag; double-click an
 * edge to add one; Delete removes the selected one), add units, streets, green areas… from
 * the palette, spread them over floors (Page Up / Page Down), and edit the project's
 * boundary; the canvas grows or shrinks from its bottom edge. Arrow keys nudge the
 * selection; Ctrl+Z / Ctrl+Shift+Z undo and redo.
 */
export const PlanEditor = forwardRef<
  PlanEditorHandle,
  {
    plan: Plan;
    /** Sends the plan; the ids the server gave new shapes, by their key */
    save: (payload: PlanPayload) => Promise<Record<string, number>>;
    /** Where saving is, for the panel to show */
    onStatus?: (status: SaveStatus) => void;
    /** The project's unit types: given, units are added here, drawn where they go */
    unitTypes?: UnitType[];
    /** A unit was added on the plan (the units listed elsewhere follow) */
    onUnitAdded?: () => void;
  }
>(function PlanEditor({ plan, save, onStatus, unitTypes, onUnitAdded }, ref) {
  // One per editing session (the panel hands the plan as it was when editing began)
  const model = useMemo(() => new PlanModel(plan), [plan]);
  const version = useSyncExternalStore(model.subscribe, model.getVersion);

  // The plan saves itself a moment after each change; failures retry
  const [sending, setSending] = useState<'saving' | 'failed' | null>(null);
  const status: SaveStatus = sending ?? (model.dirty ? 'unsaved' : 'saved');
  const saving = useRef<Promise<boolean> | null>(null);
  const flush = useCallback(async (): Promise<boolean> => {
    if (saving.current) await saving.current;
    if (!model.dirty) return true;
    const sent = model.state;
    setSending('saving');
    saving.current = save(model.payload())
      .then((ids) => {
        model.markSaved(sent, ids);
        setSending(null);
        return !model.dirty;
      })
      .catch(() => {
        setSending('failed');
        return false;
      })
      .finally(() => {
        saving.current = null;
      });
    return saving.current;
  }, [model, save]);

  // A moment after the last change (longer after a failure), the plan saves itself
  const failed = sending === 'failed';
  useEffect(() => {
    if (!model.dirty) return undefined;
    const timer = window.setTimeout(() => void flush(), failed ? 5000 : AUTOSAVE_MS);
    return () => window.clearTimeout(timer);
  }, [version, flush, failed, model]);

  useImperativeHandle(ref, () => ({ flush, dirty: () => model.dirty }), [model, flush]);
  useEffect(() => onStatus?.(status), [onStatus, status]);

  // The shape a menu was opened on (right-click), where; the one being named
  const [menu, setMenu] = useState<{ x: number; y: number } | null>(null);
  const [naming, setNaming] = useState<{ key: string; at: { x: number; y: number } } | null>(null);

  const svg = useRef<SVGSVGElement>(null);
  const frame = useRef<HTMLDivElement>(null);
  const look = `plan${useId().replace(/:/g, '')}`;
  const drag = useRef<Drag | null>(null);
  // What the shape being dragged lines up with, shown while it does
  const [guides, setGuides] = useState<Guide[]>([]);

  // A unit being added: where it starts, while its details are asked
  const [newUnit, setNewUnit] = useState<{ polygon: Point[]; floor: number } | null>(null);

  // What is added from the palette: a unit asks its details first
  const palette: { value: ShapeType; label: string }[] = [
    ...(unitTypes ? [{ value: 'unit' as const, label: _('Unit') }] : []),
    { value: 'street', label: _('Street') },
    { value: 'green', label: _('Green area') },
    { value: 'common', label: _('Common space') },
    { value: 'parking', label: _('Parking') },
    { value: 'playground', label: _('Playground') },
    { value: 'entrance', label: _('Entrance') },
    { value: 'other', label: _('Other') },
  ];

  /** What a kind looks like, small, in the palette */
  const swatch = (type: ShapeType) =>
    ({
      street: `url(#${look}-asphalt)`,
      parking: `url(#${look}-asphalt)`,
      green: `url(#${look}-grass)`,
      playground: `url(#${look}-play)`,
    })[type as string] ?? DEFAULT_COLORS[type];

  const { canvas } = model;
  const active = model.activePolygon;
  const selectedItem = model.selectedItem;
  const handle = Math.max(4, Math.min(canvas.width, canvas.height) / 120);
  const margin = handle * 2;

  /** The pointer's position in plan coordinates */
  const toPlan = (event: { clientX: number; clientY: number }): Point => {
    const matrix = svg.current!.getScreenCTM()!.inverse();
    const point = new DOMPoint(event.clientX, event.clientY).matrixTransform(matrix);
    return [point.x, point.y];
  };

  // A press that may become a drag: the pointer is held only once it moves a little, so that
  // a double-click still reaches the shape clicked
  const pressed = useRef<{ id: number; x: number; y: number } | null>(null);
  const capture = (event: ReactPointerEvent) => {
    pressed.current = { id: event.pointerId, x: event.clientX, y: event.clientY };
  };
  /** The drag follows the pointer off the canvas (one not pressed, as a script's, cannot be held) */
  const hold = (event: ReactPointerEvent) => {
    const press = pressed.current;
    if (!press || Math.hypot(event.clientX - press.x, event.clientY - press.y) < 3) return;
    pressed.current = null;
    try {
      svg.current?.setPointerCapture(press.id);
    } catch {
      // Dragging still works within the canvas
    }
  };

  const startMove = (event: ReactPointerEvent, key: string) => {
    event.stopPropagation();
    if (event.button === 2) return;
    model.select(key);
    // Ctrl (or Alt) + drag leaves the shape where it was and takes a copy along
    if ((event.ctrlKey || event.altKey || event.metaKey) && key !== 'boundary')
      model.cloneInPlace();
    drag.current = {
      kind: 'move',
      start: toPlan(event),
      original: structuredClone(model.activePolygon!),
    };
    capture(event);
  };

  const startVertex = (event: ReactPointerEvent, index: number) => {
    event.stopPropagation();
    // A right-click opens the menu, never a drag
    if (event.button === 2) return;
    model.selectVertex(index);
    drag.current = { kind: 'vertex', index };
    capture(event);
  };

  const startEdge = (event: ReactPointerEvent, index: number) => {
    event.stopPropagation();
    // A right-click opens the menu, never a drag
    if (event.button === 2) return;
    drag.current = {
      kind: 'edge',
      index,
      start: toPlan(event),
      original: structuredClone(model.activePolygon!),
    };
    capture(event);
  };

  const startRotate = (event: ReactPointerEvent) => {
    event.stopPropagation();
    if (event.button === 2) return;
    const original = structuredClone(model.activePolygon!);
    drag.current = { kind: 'rotate', from: angleTo(original, toPlan(event)), original };
    capture(event);
  };

  const startResize = (event: ReactPointerEvent, corner: [number, number]) => {
    event.stopPropagation();
    // A right-click opens the menu, never a drag
    if (event.button === 2) return;
    const box = bounds(model.activePolygon!);
    const from: Point = [corner[0] ? box.maxX : box.minX, corner[1] ? box.maxY : box.minY];
    const anchor: Point = [corner[0] ? box.minX : box.maxX, corner[1] ? box.minY : box.maxY];
    drag.current = {
      kind: 'resize',
      anchor,
      from,
      original: structuredClone(model.activePolygon!),
    };
    capture(event);
  };

  /**
   * The corners a drag lines up with: the other shapes on this floor, beneath and above, the
   * boundary, the canvas; `own` adds the dragged shape's corners that stay where they are
   */
  const anchors = (own: Point[] = []): Point[] => {
    const { width, height } = canvas;
    const others = [...model.visible, ...model.beneath, ...model.above].filter(
      (item) => item.key !== model.selected,
    );
    return [
      ...others.flatMap((item) => item.polygon),
      ...(model.editingBoundary ? [] : canvas.polygon),
      [0, 0],
      [width, 0],
      [0, height],
      [width, height],
      ...own,
    ];
  };
  /** A few pixels on screen, in plan units: how near counts as lined up */
  const reach = () => 6 / (svg.current?.getScreenCTM()?.a || 1);
  const showGuides = (next: Guide[]) =>
    setGuides((shown) => (shown.length || next.length ? next : shown));

  const onPointerMove = (event: ReactPointerEvent) => {
    const current = drag.current;
    const polygon = model.activePolygon;
    if (!current || !polygon) return;
    hold(event);
    const at = toPlan(event);
    const shift = (points: Point[], dx: number, dy: number) =>
      points.map(([x, y]): Point => [x + dx, y + dy]);

    if (current.kind === 'move') {
      const moved = model.translate(
        current.original,
        at[0] - current.start[0],
        at[1] - current.start[1],
      );
      const lined = align(moved, anchors(), reach());
      model.setActivePolygon(shift(moved, lined.dx, lined.dy));
      showGuides(lined.guides);
    } else if (current.kind === 'vertex') {
      const next = [...polygon];
      const point: Point = [model.round(at[0]), model.round(at[1])];
      // Lined up with its own neighbours too: that is how a side ends up straight
      const lined = align(
        [point],
        anchors(polygon.filter((_, index) => index !== current.index)),
        reach(),
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
        anchors(next.filter((_, index) => !ends.includes(index))),
        reach(),
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
      const lined = align([corner], anchors(), reach());
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

  const onPointerUp = () => {
    if (drag.current) model.commit();
    drag.current = null;
    pressed.current = null;
    showGuides([]);
  };

  /** The canvas's bottom edge, dragged: the canvas taller or shorter (never past the drawing) */
  const startGrow = (event: ReactPointerEvent<HTMLDivElement>) => {
    const grip = event.currentTarget;
    const [from, height] = [event.clientY, canvas.height];
    const perPixel = 1 / (svg.current?.getScreenCTM()?.a || 1);
    try {
      grip.setPointerCapture(event.pointerId);
    } catch {
      // The drag still works over the grip
    }
    const move = (moved: PointerEvent) =>
      model.setCanvasHeight(height + (moved.clientY - from) * perPixel);
    const end = () => {
      grip.removeEventListener('pointermove', move);
      grip.removeEventListener('pointerup', end);
      grip.removeEventListener('pointercancel', end);
      model.commit();
    };
    grip.addEventListener('pointermove', move);
    grip.addEventListener('pointerup', end);
    grip.addEventListener('pointercancel', end);
  };

  /** Where a shape's name field goes: over its middle, within the frame */
  const nameSpot = (polygon: Point[]) => {
    const matrix = svg.current?.getScreenCTM();
    const box = frame.current?.getBoundingClientRect();
    if (!matrix || !box) return { x: 0, y: 0 };
    const [x, y] = centre(polygon);
    const point = new DOMPoint(x, y).matrixTransform(matrix);
    return { x: point.x - box.left, y: point.y - box.top };
  };

  /** Keyboard: nudge, delete, undo and redo */
  useEffect(() => {
    const steps: Record<string, [number, number]> = {
      ArrowLeft: [-1, 0],
      ArrowRight: [1, 0],
      ArrowUp: [0, -1],
      ArrowDown: [0, 1],
    };

    const onKey = (event: KeyboardEvent) => {
      if ((event.target as HTMLElement).closest('input, textarea, select')) return;

      const command = event.ctrlKey || event.metaKey;
      if (command && event.key.toLowerCase() === 'z') {
        event.preventDefault();
        if (event.shiftKey) model.redo();
        else model.undo();
        return;
      }
      if (command && ['c', 'v', 'd'].includes(event.key.toLowerCase())) {
        event.preventDefault();
        if (event.key.toLowerCase() === 'c') model.copy();
        else if (event.key.toLowerCase() === 'v') model.paste();
        else model.duplicate();
        return;
      }

      const step = steps[event.key];
      if (step && model.activePolygon) {
        event.preventDefault();
        const size = event.shiftKey ? 25 : 5;
        model.setActivePolygon(
          model.translate(model.activePolygon, step[0] * size, step[1] * size),
        );
        model.commit();
      } else if (
        (event.key === 'Delete' || event.key === 'Backspace') &&
        (model.selectedItem || model.selectedVertex !== null)
      ) {
        event.preventDefault();
        model.remove();
      } else if (event.key.toLowerCase() === 'r' && model.selectedItem) {
        event.preventDefault();
        model.setActivePolygon(model.rotate(model.activePolygon!, event.shiftKey ? -15 : 15));
        model.commit();
      } else if ((event.key === 'PageUp' || event.key === 'PageDown') && model.selectedItem) {
        event.preventDefault();
        model.moveFloor(event.key === 'PageUp' ? 1 : -1);
      } else if (event.key === 'Escape' && model.selected) {
        // Used: what else listens (a full screen) leaves it be
        event.preventDefault();
        setMenu(null);
        model.select(null);
      }
    };

    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [model]);

  const box = active ? bounds(active) : null;
  // A small mark (an entrance) is moved, resized and turned, never reshaped
  const reshapable = selectedItem?.type !== 'entrance';
  const turn = (degrees: number) => {
    if (!model.activePolygon) return;
    model.setActivePolygon(model.rotate(model.activePolygon, degrees));
    model.commit();
  };
  const named = model.items.find((item) => item.key === naming?.key) ?? null;
  const name = (item: { key: string; polygon: Point[] }) =>
    setNaming({ key: item.key, at: nameSpot(item.polygon) });

  return (
    <>
      <div className="plan-editor">
        {/* What can be added, each as it looks: one click puts one on the plan */}
        <nav
          className="plan-palette ds-surface"
          data-tour="plan.toolbar"
          aria-label={_('Add shapes')}
        >
          {palette.map((type) => (
            <UnstyledButton
              key={type.value}
              className="plan-tool"
              onClick={() =>
                type.value === 'unit'
                  ? setNewUnit({ polygon: model.startOf('unit'), floor: model.floor })
                  : model.add(type.value, kindLabel(type.value) ?? '')
              }
            >
              <svg viewBox="0 0 28 18" width={28} height={18} aria-hidden>
                {type.value === 'entrance' ? (
                  <polygon points="14,1 24,17 4,17" fill={swatch(type.value)} />
                ) : (
                  <rect x={1} y={1} width={26} height={16} rx={3} fill={swatch(type.value)} />
                )}
              </svg>
              <span>{type.label}</span>
            </UnstyledButton>
          ))}
          {/* Units added elsewhere, waiting to be put on the plan */}
          {model.waiting.length ? (
            <div className="plan-waiting" role="group" aria-label={_('Units to place')}>
              <span className="plan-waiting-title">{_('To place')}</span>
              {model.waiting.map((item) => (
                <UnstyledButton
                  key={item.key}
                  className="unit-chip"
                  aria-label={_('Place {unit} on the plan', { unit: item.unit?.identifier ?? '' })}
                  onClick={() => model.place(item.key)}
                >
                  {item.unit?.identifier}
                </UnstyledButton>
              ))}
            </div>
          ) : null}
        </nav>

        <Stack gap="xs" style={{ minWidth: 0 }}>
          <Group justify="space-between" gap="xs" wrap="wrap">
            {/* Floors */}
            <Group gap="xs" data-tour="plan.floors" role="tablist" aria-label={_('Floors')}>
              {model.allFloors.map((floor) => (
                <button
                  key={floor}
                  type="button"
                  role="tab"
                  className="ds-pill floor-tab"
                  aria-selected={model.floor === floor}
                  data-active={model.floor === floor || undefined}
                  onClick={() => model.showFloor(floor)}
                >
                  {floorLabel(floor)}
                </button>
              ))}
              <Button
                variant="subtle"
                size="compact-sm"
                leftSection={<IconStack2 size={16} />}
                onClick={() => model.addFloor()}
              >
                {_('Add a floor')}
              </Button>
            </Group>

            {/* Tools that are there when reached for, never in the way */}
            <Group gap={4} className="plan-tools">
              <Tooltip label={_('Undo')}>
                <ActionIcon
                  variant="subtle"
                  color="gray"
                  disabled={!model.canUndo}
                  aria-label={_('Undo')}
                  onClick={() => model.undo()}
                >
                  <IconArrowBackUp size={18} />
                </ActionIcon>
              </Tooltip>
              <Tooltip label={_('Redo')}>
                <ActionIcon
                  variant="subtle"
                  color="gray"
                  disabled={!model.canRedo}
                  aria-label={_('Redo')}
                  onClick={() => model.redo()}
                >
                  <IconArrowForwardUp size={18} />
                </ActionIcon>
              </Tooltip>
              <Button
                variant={model.editingBoundary ? 'light' : 'subtle'}
                color={model.editingBoundary ? undefined : 'gray'}
                size="compact-xs"
                aria-pressed={model.editingBoundary}
                onClick={() => model.select(model.editingBoundary ? null : 'boundary')}
              >
                {_('Edit the boundary')}
              </Button>
              <Switch
                size="xs"
                className="plan-snap"
                data-on={model.snap || undefined}
                label={_('Snap to grid')}
                checked={model.snap}
                onChange={(event) => model.setSnap(event.currentTarget.checked)}
              />
            </Group>
          </Group>

          {/* Canvas */}
          <div className="plan-frame" ref={frame}>
            <svg
              ref={svg}
              data-tour="plan.canvas"
              // A margin around the plan, so that handles on its edge can be grabbed whole
              viewBox={`${-margin} ${-margin} ${canvas.width + margin * 2} ${canvas.height + margin * 2}`}
              className="plan-canvas ds-surface"
              role="application"
              aria-label={_('Plan editor')}
              onPointerMove={onPointerMove}
              onPointerUp={onPointerUp}
              onPointerCancel={onPointerUp}
              onPointerDown={(event) => {
                const target = event.target as SVGElement;
                if (target === svg.current || target.dataset.background !== undefined)
                  model.select(null);
              }}
            >
              <PlanDefs id={look} />
              <defs>
                <pattern id="plan-grid" width={25} height={25} patternUnits="userSpaceOnUse">
                  <path
                    d="M 25 0 L 0 0 0 25"
                    fill="none"
                    stroke="var(--mtav-border)"
                    strokeWidth={0.5}
                  />
                </pattern>
              </defs>
              <rect
                width={canvas.width}
                height={canvas.height}
                fill="url(#plan-grid)"
                data-background
              />

              <PlanGround
                id={look}
                polygon={canvas.polygon}
                // The grid shows through a little: what the drawing aligns to
                fillOpacity={0.82}
                strokeWidth={model.editingBoundary ? 3 : 2}
                stroke={model.editingBoundary ? 'var(--mtav-brand)' : 'var(--mtav-border-strong)'}
                data-background
                onPointerDown={(event) => model.editingBoundary && startMove(event, 'boundary')}
              />

              {/* Each floor beneath seen as it looks: its shapes one faint image */}
              {model.beneath.length ? (
                <g className="plan-beneath" aria-hidden>
                  {floorsBeneath(model.beneath, model.floor).map((below) => (
                    <g key={below.floor} data-depth={Math.min(model.floor - below.floor, 3)}>
                      {below.items.map((item) => (
                        <PlanShape
                          key={item.key}
                          id={look}
                          seed={item.id ? String(item.id) : item.key}
                          kind={item.type}
                          polygon={item.polygon}
                          color={item.color === DEFAULT_COLORS[item.type] ? undefined : item.color}
                        />
                      ))}
                    </g>
                  ))}
                </g>
              ) : null}

              {model.visible.map((item, index, shown) => (
                <g
                  key={item.key}
                  data-shape={item.unit?.identifier ?? item.name}
                  className="plan-shape"
                  onPointerDown={(event) => startMove(event, item.key)}
                  onContextMenu={(event) => {
                    event.preventDefault();
                    model.select(item.key);
                    setMenu({ x: event.clientX, y: event.clientY });
                  }}
                  onDoubleClick={() => !item.unit && name(item)}
                >
                  <PlanShape
                    id={look}
                    // The same trees as where people read the plan
                    seed={item.id ? String(item.id) : item.key}
                    kind={item.type}
                    polygon={item.polygon}
                    // The colour of a kind's own material is not a choice: only tints count
                    color={item.color === DEFAULT_COLORS[item.type] ? undefined : item.color}
                    label={item.unit?.identifier ?? item.name}
                    covers={shown.slice(index + 1).map((above) => above.polygon)}
                    selected={item.key === model.selected}
                  />
                </g>
              ))}

              {/* The floor just above, outlined over the drawing: what will stand on it */}
              {model.above.length ? (
                <g className="plan-above" aria-hidden>
                  {model.above.map((item) => (
                    <polygon
                      key={item.key}
                      points={item.polygon.map((point) => point.join(',')).join(' ')}
                      vectorEffect="non-scaling-stroke"
                    />
                  ))}
                </g>
              ) : null}

              {/* What the dragged shape lines up with */}
              {guides.map((guide) => {
                const reachOut = handle * 3;
                const [x1, y1, x2, y2] =
                  guide.axis === 'x'
                    ? [guide.at, guide.from - reachOut, guide.at, guide.to + reachOut]
                    : [guide.from - reachOut, guide.at, guide.to + reachOut, guide.at];
                return (
                  <line
                    key={`${guide.axis}${guide.at}`}
                    className="plan-guide"
                    x1={x1}
                    y1={y1}
                    x2={x2}
                    y2={y2}
                    vectorEffect="non-scaling-stroke"
                    aria-hidden
                  />
                );
              })}

              {/* The selected shape's handles */}
              {active && box ? (
                <g>
                  {(reshapable ? active : []).map((point, index) => {
                    const next = active[(index + 1) % active.length]!;
                    return (
                      <line
                        key={`edge-${index}`}
                        x1={point[0]}
                        y1={point[1]}
                        x2={next[0]}
                        y2={next[1]}
                        stroke="transparent"
                        strokeWidth={handle * 2}
                        className="plan-edge"
                        // Lengthwise along the edge, the pointer says which way it moves
                        style={{
                          cursor:
                            Math.abs(next[0] - point[0]) >= Math.abs(next[1] - point[1])
                              ? 'ns-resize'
                              : 'ew-resize',
                        }}
                        onPointerDown={(event) => startEdge(event, index)}
                        onDoubleClick={(event) => {
                          event.stopPropagation();
                          model.setActivePolygon(model.insertVertex(active, index, toPlan(event)));
                          model.commit();
                        }}
                      />
                    );
                  })}
                  {selectedItem ? (
                    <g className="plan-rotate" onPointerDown={startRotate}>
                      <line
                        x1={(box.minX + box.maxX) / 2}
                        y1={box.minY}
                        x2={(box.minX + box.maxX) / 2}
                        y2={box.minY - handle * 4}
                        stroke="var(--mtav-brand)"
                        strokeWidth={1.2}
                      />
                      <circle
                        cx={(box.minX + box.maxX) / 2}
                        cy={box.minY - handle * 4}
                        r={handle}
                        fill="var(--mtav-surface-solid)"
                        stroke="var(--mtav-brand)"
                        strokeWidth={1.5}
                      >
                        <title>{_('Rotate (R)')}</title>
                      </circle>
                    </g>
                  ) : null}
                  {CORNERS.map((corner) => (
                    <rect
                      key={`corner-${corner.join()}`}
                      x={(corner[0] ? box.maxX : box.minX) - handle}
                      y={(corner[1] ? box.maxY : box.minY) - handle}
                      width={handle * 2}
                      height={handle * 2}
                      fill="var(--mtav-surface-solid)"
                      stroke="var(--mtav-brand)"
                      strokeWidth={1.5}
                      className={corner[0] === corner[1] ? 'plan-resize-nwse' : 'plan-resize-nesw'}
                      onPointerDown={(event) => startResize(event, corner)}
                    />
                  ))}
                  {(reshapable ? active : []).map((point, index) => (
                    <circle
                      key={`vertex-${index}`}
                      cx={point[0]}
                      cy={point[1]}
                      r={handle * 0.8}
                      fill={
                        model.selectedVertex === index
                          ? 'var(--mtav-brand)'
                          : 'var(--mtav-surface-solid)'
                      }
                      stroke="var(--mtav-brand)"
                      strokeWidth={1.5}
                      className="plan-vertex"
                      onPointerDown={(event) => startVertex(event, index)}
                      onContextMenu={(event) => {
                        event.preventDefault();
                        model.selectVertex(index);
                        setMenu({ x: event.clientX, y: event.clientY });
                      }}
                    />
                  ))}
                </g>
              ) : null}
            </svg>
            {/* The canvas's bottom edge: dragged, the canvas grows or shrinks */}
            <Tooltip label={_('Drag to make the canvas taller or shorter')} position="bottom">
              <div
                className="plan-grip"
                role="separator"
                aria-orientation="horizontal"
                aria-label={_('Canvas height')}
                onPointerDown={startGrow}
              />
            </Tooltip>
            {named ? (
              <NameField
                key={named.key}
                item={named}
                at={naming!.at}
                onClose={() => setNaming(null)}
                onSave={(name) => {
                  model.rename(named.key, name);
                  setNaming(null);
                }}
              />
            ) : null}
            <PlanHint host={frame} />
          </div>
          {/* What to know */}
          <Text className="ds-caption">
            {_(
              'Drag to move, or drag an edge to move that side; Ctrl + drag takes a copy. Corners resize; round handles move a corner (double-click an edge to add one). The handle above rotates it, and so does R. Double-click a shape to name it; right-click for more.',
            )}
          </Text>
        </Stack>

        {/* The selected shape, where there is room beside the canvas (else its menu has it all) */}
        <Card p="sm" h="fit-content" className="plan-details">
          {selectedItem ? (
            <Stack gap="sm">
              <Title order={2} fz="md">
                {selectedItem.unit
                  ? _('Unit {name}', { name: selectedItem.unit.identifier })
                  : _('Shape')}
              </Title>
              {!selectedItem.unit ? (
                <TextInput
                  label={_('Name')}
                  name="shape_details_name"
                  value={selectedItem.name}
                  onChange={(event) => {
                    model.edit({ name: event.currentTarget.value });
                  }}
                  onBlur={() => model.commit()}
                />
              ) : null}
              {TINTED.includes(selectedItem.type) ? (
                <ColorInput
                  label={_('Colour')}
                  name="shape_color"
                  value={selectedItem.color}
                  onChange={(color) => {
                    model.edit({ color });
                  }}
                  onChangeEnd={() => model.commit()}
                  format="hex"
                />
              ) : null}
              <NativeSelect
                label={_('Floor')}
                name="shape_floor"
                value={String(selectedItem.floor)}
                onChange={(event) => model.update({ floor: Number(event.currentTarget.value) })}
                data={model.allFloors.map((floor) => ({
                  value: String(floor),
                  label: floorLabel(floor),
                }))}
              />
              <Group gap="xs">
                {!selectedItem.unit ? (
                  <Button
                    variant="default"
                    size="xs"
                    leftSection={<IconCopy size={14} />}
                    onClick={() => model.duplicate()}
                  >
                    {_('Duplicate')}
                  </Button>
                ) : null}
                {!selectedItem.unit || model.selectedVertex !== null ? (
                  <Button
                    variant="default"
                    color="red"
                    c="red"
                    size="xs"
                    leftSection={<IconTrash size={14} />}
                    onClick={() => model.remove()}
                  >
                    {model.selectedVertex !== null ? _('Remove the corner') : _('Remove')}
                  </Button>
                ) : null}
              </Group>
            </Stack>
          ) : model.editingBoundary ? (
            <Stack gap="xs">
              <Title order={2} fz="md">
                {_('Boundary')}
              </Title>
              <Text className="ds-caption">
                {_(
                  'The outline of the land. Move its corners, or double-click an edge to add one.',
                )}
              </Text>
              {model.selectedVertex !== null && model.activePolygon!.length > 3 ? (
                <Button
                  variant="default"
                  color="red"
                  c="red"
                  size="xs"
                  w="fit-content"
                  leftSection={<IconTrash size={14} />}
                  onClick={() => model.remove()}
                >
                  {_('Remove the corner')}
                </Button>
              ) : null}
            </Stack>
          ) : (
            <Text className="ds-caption">{_('Select a shape to change it.')}</Text>
          )}
        </Card>
      </div>

      {/* Right-click on a shape (or on a corner): all it takes, wherever its details are */}
      <Menu
        opened={Boolean(menu && (selectedItem || model.editingBoundary))}
        onChange={(open) => !open && setMenu(null)}
        withinPortal
      >
        <Menu.Target>
          <div
            style={{
              position: 'fixed',
              left: menu?.x ?? 0,
              top: menu?.y ?? 0,
              width: 1,
              height: 1,
            }}
          />
        </Menu.Target>
        <Menu.Dropdown>
          {model.selectedVertex !== null ? (
            <Menu.Item
              color="red"
              leftSection={<IconTrash size={16} />}
              rightSection={<Kbd size="xs">Del</Kbd>}
              disabled={(model.activePolygon?.length ?? 0) <= 3}
              onClick={() => model.remove()}
            >
              {_('Remove the corner')}
            </Menu.Item>
          ) : selectedItem ? (
            <>
              {!selectedItem.unit ? (
                <Menu.Item
                  leftSection={<IconPencil size={16} />}
                  onClick={() => name(selectedItem)}
                >
                  {_('Name it')}
                </Menu.Item>
              ) : null}
              <Menu.Item
                leftSection={<IconRotateClockwise size={16} />}
                rightSection={<Kbd size="xs">R</Kbd>}
                onClick={() => turn(15)}
              >
                {_('Rotate')}
              </Menu.Item>
              {TINTED.includes(selectedItem.type) ? (
                <div className="plan-swatches" role="group" aria-label={_('Colour')}>
                  {SWATCHES.map((color) => (
                    <ColorSwatch
                      key={color}
                      component="button"
                      type="button"
                      color={color}
                      size={20}
                      aria-label={color}
                      aria-pressed={selectedItem.color === color}
                      onClick={() => {
                        model.update({ color });
                        setMenu(null);
                      }}
                    />
                  ))}
                </div>
              ) : null}
              <Menu.Divider />
              <Menu.Item
                leftSection={<IconArrowUp size={16} />}
                rightSection={<Kbd size="xs">PgUp</Kbd>}
                onClick={() => model.moveFloor(1)}
              >
                {_('One floor up')}
              </Menu.Item>
              <Menu.Item
                leftSection={<IconArrowDown size={16} />}
                rightSection={<Kbd size="xs">PgDn</Kbd>}
                onClick={() => model.moveFloor(-1)}
              >
                {_('One floor down')}
              </Menu.Item>
              <Menu.Divider />
              <Menu.Item
                leftSection={<IconStackFront size={16} />}
                onClick={() => model.restack('front')}
              >
                {_('Bring to front')}
              </Menu.Item>
              <Menu.Item
                leftSection={<IconStackBack size={16} />}
                onClick={() => model.restack('back')}
              >
                {_('Send to back')}
              </Menu.Item>
              {!selectedItem.unit ? (
                <>
                  <Menu.Divider />
                  <Menu.Item
                    leftSection={<IconCopy size={16} />}
                    rightSection={<Kbd size="xs">Ctrl D</Kbd>}
                    onClick={() => model.duplicate()}
                  >
                    {_('Duplicate')}
                  </Menu.Item>
                  <Menu.Item
                    leftSection={<IconClipboardCopy size={16} />}
                    rightSection={<Kbd size="xs">Ctrl C</Kbd>}
                    onClick={() => model.copy()}
                  >
                    {_('Copy')}
                  </Menu.Item>
                </>
              ) : null}
              {model.canPaste ? (
                <Menu.Item
                  leftSection={<IconClipboard size={16} />}
                  rightSection={<Kbd size="xs">Ctrl V</Kbd>}
                  onClick={() => model.paste()}
                >
                  {_('Paste')}
                </Menu.Item>
              ) : null}
              {!selectedItem.unit ? (
                <>
                  <Menu.Divider />
                  <Menu.Item
                    color="red"
                    leftSection={<IconTrash size={16} />}
                    rightSection={<Kbd size="xs">Del</Kbd>}
                    onClick={() => model.remove()}
                  >
                    {_('Remove')}
                  </Menu.Item>
                </>
              ) : null}
            </>
          ) : null}
        </Menu.Dropdown>
      </Menu>

      {unitTypes ? (
        <Modal opened={Boolean(newUnit)} onClose={() => setNewUnit(null)} title={_('Add a unit')}>
          {newUnit ? (
            <UnitForm
              unitTypes={unitTypes}
              place={{
                ...newUnit,
                onPlaced: (item) => {
                  model.adopt(item);
                  setNewUnit(null);
                  onUnitAdded?.();
                },
              }}
              onDone={() => setNewUnit(null)}
            />
          ) : null}
        </Modal>
      ) : null}
    </>
  );
});
