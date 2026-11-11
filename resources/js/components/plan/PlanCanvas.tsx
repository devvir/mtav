import { _ } from '@/lib/translations';
import type { RefObject } from 'react';
import { toPlan, usePlanDrag } from './drag';
import { floorsBeneath } from './floors';
import { DEFAULT_COLORS, kindLabel } from './kinds';
import { PlanDefs } from './materials';
import type { EditableItem, PlanModel } from './model';
import { PlanGround, PlanShape } from './render';
import { SelectionHandles } from './SelectionHandles';

/** Where on screen a menu opens */
export interface MenuSpot {
  x: number;
  y: number;
}

/** The colour of a kind's own material is not a choice: only tints count */
const tint = (item: EditableItem) =>
  item.color === DEFAULT_COLORS[item.type] ? undefined : item.color;

/** The same trees as where people read the plan */
const seedOf = (item: EditableItem) => (item.id ? String(item.id) : item.key);

/**
 * The drawing being edited (`svg`, the canvas): the grid, the land within its boundary, the
 * floors beneath seen faintly, the floor's shapes, the floor above outlined, the guides of a
 * drag and the selection's handles. A shape is chosen as it is pressed (or reached by the
 * keyboard), right-clicked for its menu (`onMenu`) and double-clicked to be named (`onName`).
 */
export function PlanCanvas({
  model,
  look,
  svg,
  onMenu,
  onName,
}: {
  model: PlanModel;
  /** The plan's defs prefix */
  look: string;
  svg: RefObject<SVGSVGElement | null>;
  onMenu: (at: MenuSpot) => void;
  onName: (item: EditableItem) => void;
}) {
  const drag = usePlanDrag(model, svg);
  const { canvas } = model;
  const active = model.activePolygon;
  const handle = Math.max(4, Math.min(canvas.width, canvas.height) / 120);
  const margin = handle * 2;

  return (
    <svg
      ref={svg}
      data-tour="plan.canvas"
      // A margin around the plan, so that handles on its edge can be grabbed whole
      viewBox={`${-margin} ${-margin} ${canvas.width + margin * 2} ${canvas.height + margin * 2}`}
      className="plan-canvas ds-surface"
      role="application"
      aria-label={_('Plan editor')}
      onPointerMove={drag.onPointerMove}
      onPointerUp={drag.onPointerUp}
      onPointerCancel={drag.onPointerUp}
      onPointerDown={(event) => {
        const target = event.target as SVGElement;
        if (target === svg.current || target.dataset.background !== undefined) model.select(null);
      }}
    >
      <PlanDefs id={look} />
      <defs>
        <pattern id="plan-grid" width={25} height={25} patternUnits="userSpaceOnUse">
          <path d="M 25 0 L 0 0 0 25" fill="none" stroke="var(--mtav-border)" strokeWidth={0.5} />
        </pattern>
      </defs>
      <rect width={canvas.width} height={canvas.height} fill="url(#plan-grid)" data-background />

      <PlanGround
        id={look}
        polygon={canvas.polygon}
        // The grid shows through a little: what the drawing aligns to
        fillOpacity={0.82}
        strokeWidth={model.editingBoundary ? 3 : 2}
        stroke={model.editingBoundary ? 'var(--mtav-brand)' : 'var(--mtav-border-strong)'}
        data-background
        onPointerDown={(event) => model.editingBoundary && drag.startMove(event, 'boundary')}
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
                  seed={seedOf(item)}
                  kind={item.type}
                  polygon={item.polygon}
                  color={tint(item)}
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
          role="button"
          tabIndex={0}
          aria-label={item.unit?.identifier ?? (item.name || (kindLabel(item.type) ?? _('Other')))}
          aria-pressed={item.key === model.selected}
          // Reached by the keyboard, it is chosen; the pointer chooses as it presses
          onFocus={(event) => {
            if (event.currentTarget.matches(':focus-visible')) model.select(item.key);
          }}
          onPointerDown={(event) => drag.startMove(event, item.key)}
          onContextMenu={(event) => {
            event.preventDefault();
            model.select(item.key);
            onMenu({ x: event.clientX, y: event.clientY });
          }}
          onDoubleClick={() => !item.unit && onName(item)}
        >
          <PlanShape
            id={look}
            seed={seedOf(item)}
            kind={item.type}
            polygon={item.polygon}
            color={tint(item)}
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
      {drag.guides.map((guide) => {
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

      {active ? (
        <SelectionHandles
          model={model}
          polygon={active}
          size={handle}
          drag={drag}
          onAddCorner={(index, event) => {
            model.setActivePolygon(model.insertVertex(active, index, toPlan(svg, event)));
            model.commit();
          }}
          onCornerMenu={(index, event) => {
            model.selectVertex(index);
            onMenu({ x: event.clientX, y: event.clientY });
          }}
        />
      ) : null}
    </svg>
  );
}
