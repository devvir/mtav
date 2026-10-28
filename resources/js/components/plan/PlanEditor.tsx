import { _ } from '@/lib/translations';
import type { Plan, Point } from '@/types/models';
import {
  ActionIcon,
  Button,
  Card,
  ColorInput,
  Divider,
  Group,
  NativeSelect,
  SimpleGrid,
  Stack,
  Switch,
  Text,
  TextInput,
  Title,
  Tooltip,
} from '@mantine/core';
import {
  IconArrowBackUp,
  IconArrowForwardUp,
  IconCopy,
  IconDeviceFloppy,
  IconPlus,
  IconStack2,
  IconTrash,
} from '@tabler/icons-react';
import {
  forwardRef,
  useEffect,
  useImperativeHandle,
  useMemo,
  useRef,
  useState,
  useSyncExternalStore,
  type PointerEvent as ReactPointerEvent,
} from 'react';
import { floorLabel } from './floors';
import { bounds, centre, PlanModel, type ShapeType } from './model';

export type PlanPayload = ReturnType<PlanModel['payload']>;

export interface PlanEditorHandle {
  markSaved: () => void;
  dirty: () => boolean;
}

type Drag =
  | { kind: 'move'; start: Point; original: Point[] }
  | { kind: 'vertex'; index: number }
  | { kind: 'resize'; anchor: Point; from: Point; original: Point[] };

const points = (polygon: Point[]) => polygon.map(([x, y]) => `${x},${y}`).join(' ');

const CORNERS: [number, number][] = [
  [0, 0],
  [1, 0],
  [1, 1],
  [0, 1],
];

/**
 * Draw the project plan: move shapes, resize them from their corners, edit their corners
 * (drag; double-click an edge to add one; Delete removes the selected one), add buildings,
 * streets, green areas…, spread them over floors, and edit the project's boundary. Arrow
 * keys nudge the selection; Ctrl+Z / Ctrl+Shift+Z undo and redo.
 */
export const PlanEditor = forwardRef<
  PlanEditorHandle,
  { plan: Plan; processing?: boolean; onSave: (payload: PlanPayload) => void }
>(function PlanEditor({ plan, processing, onSave }, ref) {
  const model = useMemo(() => new PlanModel(plan), [plan]);
  useSyncExternalStore(model.subscribe, model.getVersion);

  useImperativeHandle(
    ref,
    () => ({ markSaved: () => model.markSaved(), dirty: () => model.dirty }),
    [model],
  );

  const svg = useRef<SVGSVGElement>(null);
  const drag = useRef<Drag | null>(null);
  const [newShape, setNewShape] = useState<ShapeType>('building');

  const shapeTypes: { value: ShapeType; label: string }[] = [
    { value: 'building', label: _('Building') },
    { value: 'street', label: _('Street') },
    { value: 'green', label: _('Green area') },
    { value: 'common', label: _('Common space') },
    { value: 'other', label: _('Other') },
  ];

  const { canvas } = model;
  const active = model.activePolygon;
  const selectedItem = model.selectedItem;
  const handle = Math.max(4, Math.min(canvas.width, canvas.height) / 120);

  /** The pointer's position in plan coordinates */
  const toPlan = (event: { clientX: number; clientY: number }): Point => {
    const matrix = svg.current!.getScreenCTM()!.inverse();
    const point = new DOMPoint(event.clientX, event.clientY).matrixTransform(matrix);
    return [point.x, point.y];
  };

  const capture = (event: ReactPointerEvent) => svg.current?.setPointerCapture(event.pointerId);

  const startMove = (event: ReactPointerEvent, key: string) => {
    event.stopPropagation();
    model.select(key);
    drag.current = {
      kind: 'move',
      start: toPlan(event),
      original: structuredClone(model.activePolygon!),
    };
    capture(event);
  };

  const startVertex = (event: ReactPointerEvent, index: number) => {
    event.stopPropagation();
    model.selectedVertex = index;
    model.changed();
    drag.current = { kind: 'vertex', index };
    capture(event);
  };

  const startResize = (event: ReactPointerEvent, corner: [number, number]) => {
    event.stopPropagation();
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

  const onPointerMove = (event: ReactPointerEvent) => {
    const current = drag.current;
    const polygon = model.activePolygon;
    if (!current || !polygon) return;
    const at = toPlan(event);

    if (current.kind === 'move') {
      model.setActivePolygon(
        model.translate(current.original, at[0] - current.start[0], at[1] - current.start[1]),
      );
    } else if (current.kind === 'vertex') {
      const next = [...polygon];
      next[current.index] = [model.round(at[0]), model.round(at[1])];
      model.setActivePolygon(next);
    } else {
      model.setActivePolygon(model.resize(current.original, current.anchor, current.from, at));
    }
  };

  const onPointerUp = () => {
    if (drag.current) model.commit();
    drag.current = null;
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

      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
        event.preventDefault();
        if (event.shiftKey) model.redo();
        else model.undo();
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
      } else if ((event.key === 'Delete' || event.key === 'Backspace') && model.selectedItem) {
        event.preventDefault();
        model.remove();
      } else if (event.key === 'Escape') {
        model.select(null);
      }
    };

    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [model]);

  const box = active ? bounds(active) : null;

  return (
    <div className="plan-editor">
      <Stack gap="sm" style={{ minWidth: 0 }}>
        {/* Toolbar */}
        <Card p="xs" data-tour="plan.toolbar">
          <Group gap="xs">
            <NativeSelect
              aria-label={_('Shape to add')}
              value={newShape}
              onChange={(event) => setNewShape(event.currentTarget.value as ShapeType)}
              data={shapeTypes}
              w={170}
            />
            <Button
              variant="default"
              leftSection={<IconPlus size={16} />}
              onClick={() => model.add(newShape)}
            >
              {_('Add')}
            </Button>
            <Divider orientation="vertical" />
            <Tooltip label={_('Undo')}>
              <ActionIcon
                size="lg"
                disabled={!model.canUndo}
                aria-label={_('Undo')}
                onClick={() => model.undo()}
              >
                <IconArrowBackUp size={20} />
              </ActionIcon>
            </Tooltip>
            <Tooltip label={_('Redo')}>
              <ActionIcon
                size="lg"
                disabled={!model.canRedo}
                aria-label={_('Redo')}
                onClick={() => model.redo()}
              >
                <IconArrowForwardUp size={20} />
              </ActionIcon>
            </Tooltip>
            <Button
              variant={model.editingBoundary ? 'light' : 'subtle'}
              aria-pressed={model.editingBoundary}
              onClick={() => model.select(model.editingBoundary ? null : 'boundary')}
            >
              {_('Edit the boundary')}
            </Button>
            <Switch
              label={_('Snap to grid')}
              checked={model.snap}
              onChange={(event) => model.setSnap(event.currentTarget.checked)}
            />
            <Button
              ml="auto"
              loading={processing}
              leftSection={<IconDeviceFloppy size={18} data-tour="plan.save" />}
              onClick={() => onSave(model.payload())}
            >
              {_('Save the plan')}
            </Button>
          </Group>
        </Card>

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

        {/* Canvas */}
        <svg
          ref={svg}
          data-tour="plan.canvas"
          viewBox={`0 0 ${canvas.width} ${canvas.height}`}
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

          <polygon
            points={points(canvas.polygon)}
            fill="var(--mtav-surface-2)"
            fillOpacity={0.6}
            stroke="var(--mtav-border-strong)"
            strokeWidth={model.editingBoundary ? 3 : 2}
            strokeDasharray={model.editingBoundary ? '8 4' : undefined}
            data-background
            onPointerDown={(event) => model.editingBoundary && startMove(event, 'boundary')}
          />

          {model.visible.map((item) => {
            const [x, y] = centre(item.polygon);
            const isSelected = item.key === model.selected;
            return (
              <g
                key={item.key}
                data-shape={item.unit?.identifier ?? item.name}
                className="plan-shape"
                onPointerDown={(event) => startMove(event, item.key)}
              >
                <polygon
                  points={points(item.polygon)}
                  fill={item.color}
                  fillOpacity={0.85}
                  stroke={isSelected ? 'var(--mtav-brand)' : 'var(--mtav-text)'}
                  strokeOpacity={isSelected ? 1 : 0.4}
                  strokeWidth={isSelected ? 2.5 : 1}
                />
                <text
                  x={x}
                  y={y}
                  textAnchor="middle"
                  dominantBaseline="middle"
                  fontSize={12}
                  fontWeight={650}
                  fill="#1c1917"
                  pointerEvents="none"
                >
                  {item.unit?.identifier ?? item.name}
                </text>
              </g>
            );
          })}

          {/* The selected shape's handles */}
          {active && box ? (
            <g>
              {active.map((point, index) => {
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
                    onDoubleClick={(event) => {
                      event.stopPropagation();
                      model.setActivePolygon(model.insertVertex(active, index, toPlan(event)));
                      model.commit();
                    }}
                  />
                );
              })}
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
              {active.map((point, index) => (
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
                />
              ))}
            </g>
          ) : null}
        </svg>
        <Text className="ds-caption">
          {_(
            'Drag to move. Corners resize. Round handles move a corner of the shape; double-click an edge to add one, Delete removes it.',
          )}
        </Text>
      </Stack>

      {/* The selected shape */}
      <Card p="md" h="fit-content">
        {selectedItem ? (
          <Stack gap="sm">
            <Title order={2} fz="md">
              {selectedItem.unit
                ? _('Unit {name}', { name: selectedItem.unit.identifier })
                : _('Shape')}
            </Title>
            {!selectedItem.unit ? (
              <>
                <NativeSelect
                  label={_('Kind')}
                  name="shape_type"
                  value={selectedItem.type}
                  onChange={(event) =>
                    model.update({ type: event.currentTarget.value as ShapeType })
                  }
                  data={shapeTypes}
                />
                <TextInput
                  label={_('Name')}
                  name="shape_name"
                  value={selectedItem.name}
                  onChange={(event) => {
                    selectedItem.name = event.currentTarget.value;
                    model.changed();
                  }}
                  onBlur={() => model.commit()}
                />
              </>
            ) : null}
            <ColorInput
              label={_('Colour')}
              name="shape_color"
              value={selectedItem.color}
              onChange={(color) => {
                selectedItem.color = color;
                model.changed();
              }}
              onChangeEnd={() => model.commit()}
              format="hex"
            />
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
              {_('The outline of the land. Move its corners, or double-click an edge to add one.')}
            </Text>
          </Stack>
        ) : (
          <Stack gap="sm">
            <Title order={2} fz="md">
              {_('Canvas')}
            </Title>
            <SimpleGrid cols={2}>
              <TextInput
                label={_('Width')}
                type="number"
                min={100}
                max={10000}
                value={canvas.width}
                onChange={(event) =>
                  model.setCanvasSize(Number(event.currentTarget.value), canvas.height)
                }
              />
              <TextInput
                label={_('Height')}
                type="number"
                min={100}
                max={10000}
                value={canvas.height}
                onChange={(event) =>
                  model.setCanvasSize(canvas.width, Number(event.currentTarget.value))
                }
              />
            </SimpleGrid>
            <Text className="ds-caption">{_('Select a shape to change it.')}</Text>
          </Stack>
        )}
      </Card>
    </div>
  );
});
