import { scrollBehavior } from '@/lib/motion';
import { _ } from '@/lib/translations';
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

/** What a ranking orders: anything with an id (its name is the list's `label`) */
export interface Ranked {
  id: number;
}

/** The items in a new order: one moved from a place to another */
export const moved = <Item,>(items: Item[], from: number, to: number): Item[] =>
  arrayMove(items, from, to);

/**
 * A ranking, most wanted first: a family's units, or what an open lottery shares out.
 * Reordered by dragging a row (a short press on touch screens), with the keyboard (Space
 * picks a row up, the arrows move it, Space drops it), or with the arrow buttons; every move
 * is announced. The one being moved or pointed at is told (`onFocus`, for the plan beside it
 * to light it up); `flash` lights one up a moment, brought into view (one picked on the plan).
 */
export function RankingList<Item extends Ranked>({
  items,
  label,
  onChange,
  disabled,
  onFocus,
  flash,
  itemsName,
}: {
  items: Item[];
  /** How an item is called */
  label: (item: Item) => string;
  /** What the items are called, all together, where they are not units (an open lottery's own word) */
  itemsName?: string;
  onChange: (items: Item[]) => void;
  disabled?: boolean;
  onFocus?: (id: number | null) => void;
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

  const nameOf = (id: string | number) => {
    const item = items.find((one) => one.id === id);
    return item ? label(item) : '';
  };
  const position = (id: string | number) => items.findIndex((item) => item.id === id) + 1;

  const announcements: Announcements = {
    onDragStart: ({ active }) =>
      _('Picked up {unit}, number {position}', {
        unit: nameOf(active.id),
        position: position(active.id),
      }),
    onDragOver: ({ active, over }) =>
      over
        ? _('{unit} goes to number {position}', {
            unit: nameOf(active.id),
            position: position(over.id),
          })
        : undefined,
    onDragEnd: ({ active, over }) =>
      over
        ? _('{unit} is now number {position}', {
            unit: nameOf(active.id),
            position: position(over.id),
          })
        : undefined,
    onDragCancel: ({ active }) => _('{unit} stays where it was', { unit: nameOf(active.id) }),
  };

  const move = (from: number, to: number) => {
    if (disabled || to < 0 || to >= items.length || from === to) return;
    const item = items[from]!;
    onChange(moved(items, from, to));
    setAnnouncement(_('{unit} is now number {position}', { unit: label(item), position: to + 1 }));
  };

  const onDragEnd = ({ active, over }: DragEndEvent) => {
    if (!over || active.id === over.id) return;
    onChange(moved(items, position(active.id) - 1, position(over.id) - 1));
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
          draggable: itemsName
            ? _(
                '{items}: to move one, press Space, move it with the arrow keys, and press Space again to drop it. Escape cancels.',
                { items: itemsName },
              )
            : _(
                'To move a unit, press Space, move it with the arrow keys, and press Space again to drop it. Escape cancels.',
              ),
        },
      }}
    >
      <SortableContext
        items={items.map((item) => item.id)}
        strategy={verticalListSortingStrategy}
        disabled={disabled}
      >
        <ol className="ranking">
          {items.map((item, index) => (
            <RankingItem
              key={item.id}
              id={item.id}
              name={label(item)}
              index={index}
              last={index === items.length - 1}
              disabled={disabled}
              onMove={move}
              onFocus={onFocus}
              flash={flash?.unit === item.id ? flash.at : null}
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
  id,
  name,
  index,
  last,
  disabled,
  onMove,
  onFocus,
  flash,
}: {
  id: number;
  name: string;
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
  } = useSortable({ id, disabled });

  const row = useRef<HTMLLIElement | null>(null);
  useEffect(() => {
    if (flash) row.current?.scrollIntoView({ block: 'nearest', behavior: scrollBehavior() });
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
      onMouseEnter={() => onFocus?.(id)}
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
          aria-label={_('Move {unit}, number {position}', { unit: name, position: index + 1 })}
        >
          <IconGripVertical size={18} aria-hidden />
        </button>
      ) : null}
      <span className="ds-truncate" style={{ flex: 1, fontWeight: 650 }} title={name}>
        {name}
      </span>
      {!disabled ? (
        <span style={{ display: 'flex', gap: 4 }}>
          {/* Not `disabled` at the ends: a button that was just pressed keeps the focus */}
          <ActionIcon
            size="lg"
            data-disabled={index === 0 || undefined}
            aria-disabled={index === 0}
            aria-label={_('Move {unit} up', { unit: name })}
            onClick={() => index > 0 && onMove(index, index - 1)}
          >
            <IconArrowUp size={18} />
          </ActionIcon>
          <ActionIcon
            size="lg"
            data-disabled={last || undefined}
            aria-disabled={last}
            aria-label={_('Move {unit} down', { unit: name })}
            onClick={() => !last && onMove(index, index + 1)}
          >
            <IconArrowDown size={18} />
          </ActionIcon>
        </span>
      ) : null}
    </li>
  );
}
