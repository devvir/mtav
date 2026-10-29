import { _ } from '@/lib/translations';
import type { Unit } from '@/types/models';
import {
  closestCenter,
  DndContext,
  KeyboardSensor,
  MouseSensor,
  TouchSensor,
  useSensor,
  useSensors,
  type Announcements,
  type DragEndEvent,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ActionIcon, VisuallyHidden } from '@mantine/core';
import { IconArrowDown, IconArrowUp, IconGripVertical } from '@tabler/icons-react';
import {
  useEffect,
  useRef,
  useState,
  type KeyboardEventHandler,
  type MouseEventHandler,
  type TouchEventHandler,
} from 'react';

/** The units in a new order: one moved from a place to another */
export const moved = (units: Unit[], from: number, to: number): Unit[] =>
  arrayMove(units, from, to);

/**
 * A family's ranking, most wanted first. Reordered by dragging a row (a short press on touch screens),
 * with the keyboard (Space picks a row up, the arrows move it, Space drops it), or with the
 * arrow buttons; every move is announced. The unit being moved or pointed at is told
 * (`onFocus`, for the plan beside it to light it up); `flash` lights one up a moment,
 * brought into view (one picked on the plan).
 */
export function RankingList({
  units,
  onChange,
  disabled,
  onFocus,
  flash,
}: {
  units: Unit[];
  onChange: (units: Unit[]) => void;
  disabled?: boolean;
  onFocus?: (unitId: number | null) => void;
  flash?: { unit: number; at: number } | null;
}) {
  const [announcement, setAnnouncement] = useState('');
  const sensors = useSensors(
    // A few pixels before a drag starts, so that a click on a button stays a click; on a
    // touch screen a short press, so that a swipe still scrolls the page
    useSensor(MouseSensor, { activationConstraint: { distance: 6 } }),
    useSensor(TouchSensor, { activationConstraint: { delay: 250, tolerance: 6 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
  );

  const identifier = (id: string | number) =>
    units.find((unit) => unit.id === id)?.identifier ?? '';
  const position = (id: string | number) => units.findIndex((unit) => unit.id === id) + 1;

  const announcements: Announcements = {
    onDragStart: ({ active }) =>
      _('Picked up {unit}, number {position}', {
        unit: identifier(active.id),
        position: position(active.id),
      }),
    onDragOver: ({ active, over }) =>
      over
        ? _('{unit} goes to number {position}', {
            unit: identifier(active.id),
            position: position(over.id),
          })
        : undefined,
    onDragEnd: ({ active, over }) =>
      over
        ? _('{unit} is now number {position}', {
            unit: identifier(active.id),
            position: position(over.id),
          })
        : undefined,
    onDragCancel: ({ active }) => _('{unit} stays where it was', { unit: identifier(active.id) }),
  };

  const move = (from: number, to: number) => {
    if (disabled || to < 0 || to >= units.length || from === to) return;
    const unit = units[from]!;
    onChange(moved(units, from, to));
    setAnnouncement(
      _('{unit} is now number {position}', { unit: unit.identifier, position: to + 1 }),
    );
  };

  const onDragEnd = ({ active, over }: DragEndEvent) => {
    if (!over || active.id === over.id) return;
    onChange(moved(units, position(active.id) - 1, position(over.id) - 1));
  };

  return (
    <DndContext
      sensors={sensors}
      collisionDetection={closestCenter}
      onDragStart={({ active }) => onFocus?.(Number(active.id))}
      onDragCancel={() => onFocus?.(null)}
      onDragEnd={(event) => {
        onFocus?.(null);
        onDragEnd(event);
      }}
      accessibility={{
        announcements,
        screenReaderInstructions: {
          draggable: _(
            'To move a unit, press Space, move it with the arrow keys, and press Space again to drop it. Escape cancels.',
          ),
        },
      }}
    >
      <SortableContext
        items={units.map((unit) => unit.id)}
        strategy={verticalListSortingStrategy}
        disabled={disabled}
      >
        <ol className="ranking">
          {units.map((unit, index) => (
            <RankingItem
              key={unit.id}
              unit={unit}
              index={index}
              last={index === units.length - 1}
              disabled={disabled}
              onMove={move}
              onFocus={onFocus}
              flash={flash?.unit === unit.id ? flash.at : null}
            />
          ))}
        </ol>
      </SortableContext>
      <VisuallyHidden aria-live="polite" data-ranking-announcement>
        {announcement}
      </VisuallyHidden>
    </DndContext>
  );
}

function RankingItem({
  unit,
  index,
  last,
  disabled,
  onMove,
  onFocus,
  flash,
}: {
  unit: Unit;
  index: number;
  last: boolean;
  disabled?: boolean;
  onMove: (from: number, to: number) => void;
  onFocus?: (unitId: number | null) => void;
  /** When it was picked elsewhere: lit up a moment, brought into view */
  flash: number | null;
}) {
  const {
    attributes,
    listeners,
    setNodeRef,
    setActivatorNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({
    id: unit.id,
    disabled,
  });

  const row = useRef<HTMLLIElement | null>(null);
  useEffect(() => {
    if (flash) row.current?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }, [flash]);

  // The whole row follows the pointer; the keyboard picks it up from its grip, a button of its own
  return (
    <li
      ref={(node) => {
        setNodeRef(node);
        row.current = node;
      }}
      key={flash ?? undefined}
      data-flash={flash ? true : undefined}
      onMouseEnter={() => onFocus?.(unit.id)}
      onMouseLeave={() => onFocus?.(null)}
      className="ranking-item ds-surface"
      data-first={index === 0 || undefined}
      data-dragging={isDragging || undefined}
      data-draggable={!disabled || undefined}
      style={{ transform: CSS.Transform.toString(transform), transition }}
      onMouseDown={listeners?.onMouseDown as MouseEventHandler | undefined}
      onTouchStart={listeners?.onTouchStart as TouchEventHandler | undefined}
    >
      <span className="ranking-position">{index + 1}</span>
      {!disabled ? (
        <button
          type="button"
          ref={setActivatorNodeRef}
          className="ranking-grip"
          {...attributes}
          onKeyDown={listeners?.onKeyDown as KeyboardEventHandler | undefined}
          aria-label={_('Move {unit}, number {position}', {
            unit: unit.identifier,
            position: index + 1,
          })}
        >
          <IconGripVertical size={18} aria-hidden />
        </button>
      ) : null}
      <span className="ds-truncate" style={{ flex: 1, fontWeight: 650 }}>
        {unit.identifier}
      </span>
      {!disabled ? (
        <span style={{ display: 'flex', gap: 4 }}>
          <ActionIcon
            size="lg"
            disabled={index === 0}
            aria-label={_('Move {unit} up', { unit: unit.identifier })}
            onClick={() => onMove(index, index - 1)}
          >
            <IconArrowUp size={18} />
          </ActionIcon>
          <ActionIcon
            size="lg"
            disabled={last}
            aria-label={_('Move {unit} down', { unit: unit.identifier })}
            onClick={() => onMove(index, index + 1)}
          >
            <IconArrowDown size={18} />
          </ActionIcon>
        </span>
      ) : null}
    </li>
  );
}
