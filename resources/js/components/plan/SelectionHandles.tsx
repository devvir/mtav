import { _ } from '@/lib/translations';
import type { Point } from '@/types/models';
import type { MouseEvent as ReactMouseEvent } from 'react';
import type { Corner, PlanDrag } from './drag';
import { bounds } from './geometry';
import type { PlanModel } from './model';

/** The corners of a shape's box that resize it */
const CORNERS: Corner[] = [
  [0, 0],
  [1, 0],
  [1, 1],
  [0, 1],
];

/**
 * The handles of what is selected (`polygon`: a shape's, or the boundary's): its sides (drag
 * one to move it; double-click to add a corner there), the rotate handle above a shape, the
 * corners of its box that resize it, and its own corners (drag; right-click for their menu).
 * A small mark (an entrance) is moved, resized and turned, never reshaped: it has no sides or
 * corners to drag. `size` is a handle's half width, in plan units.
 */
export function SelectionHandles({
  model,
  polygon,
  size,
  drag,
  onAddCorner,
  onCornerMenu,
}: {
  model: PlanModel;
  polygon: Point[];
  size: number;
  drag: PlanDrag;
  /** A side double-clicked, by its index: a corner added where */
  onAddCorner: (index: number, event: ReactMouseEvent) => void;
  /** A corner right-clicked, by its index */
  onCornerMenu: (index: number, event: ReactMouseEvent) => void;
}) {
  const box = bounds(polygon);
  const reshapable = model.selectedItem?.type !== 'entrance';
  const middle = (box.minX + box.maxX) / 2;

  return (
    <g>
      {(reshapable ? polygon : []).map((point, index) => {
        const next = polygon[(index + 1) % polygon.length]!;
        return (
          <line
            key={`edge-${index}`}
            x1={point[0]}
            y1={point[1]}
            x2={next[0]}
            y2={next[1]}
            stroke="transparent"
            strokeWidth={size * 2}
            className="plan-edge"
            // Lengthwise along the edge, the pointer says which way it moves
            style={{
              cursor:
                Math.abs(next[0] - point[0]) >= Math.abs(next[1] - point[1])
                  ? 'ns-resize'
                  : 'ew-resize',
            }}
            onPointerDown={(event) => drag.startEdge(event, index)}
            onDoubleClick={(event) => {
              event.stopPropagation();
              onAddCorner(index, event);
            }}
          />
        );
      })}
      {model.selectedItem ? (
        <g className="plan-rotate" onPointerDown={drag.startRotate}>
          <line
            x1={middle}
            y1={box.minY}
            x2={middle}
            y2={box.minY - size * 4}
            stroke="var(--mtav-brand)"
            strokeWidth={1.2}
          />
          <circle
            cx={middle}
            cy={box.minY - size * 4}
            r={size}
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
          x={(corner[0] ? box.maxX : box.minX) - size}
          y={(corner[1] ? box.maxY : box.minY) - size}
          width={size * 2}
          height={size * 2}
          fill="var(--mtav-surface-solid)"
          stroke="var(--mtav-brand)"
          strokeWidth={1.5}
          className={corner[0] === corner[1] ? 'plan-resize-nwse' : 'plan-resize-nesw'}
          onPointerDown={(event) => drag.startResize(event, corner)}
        />
      ))}
      {(reshapable ? polygon : []).map((point, index) => (
        <circle
          key={`vertex-${index}`}
          cx={point[0]}
          cy={point[1]}
          r={size * 0.8}
          fill={model.selectedVertex === index ? 'var(--mtav-brand)' : 'var(--mtav-surface-solid)'}
          stroke="var(--mtav-brand)"
          strokeWidth={1.5}
          className="plan-vertex"
          onPointerDown={(event) => drag.startVertex(event, index)}
          onContextMenu={(event) => {
            event.preventDefault();
            onCornerMenu(index, event);
          }}
        />
      ))}
    </g>
  );
}
