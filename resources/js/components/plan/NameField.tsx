import { _ } from '@/lib/translations';
import type { Point } from '@/types/models';
import { TextInput } from '@mantine/core';
import { useRef, useState, type RefObject } from 'react';
import { centre } from './geometry';
import type { ShapeType } from './kinds';

/** Where a shape's name field goes: over its middle, within the frame around the canvas */
export const nameSpot = (
  svg: RefObject<SVGSVGElement | null>,
  frame: RefObject<HTMLElement | null>,
  polygon: Point[],
) => {
  const matrix = svg.current?.getScreenCTM();
  const box = frame.current?.getBoundingClientRect();
  if (!matrix || !box) return { x: 0, y: 0 };
  const [x, y] = centre(polygon);
  const point = new DOMPoint(x, y).matrixTransform(matrix);
  return { x: point.x - box.left, y: point.y - box.top };
};

/**
 * Naming a shape where it stands: a small field over it, taken on Enter or on leaving it,
 * left as it was on Escape. `at` is where, within the plan's frame.
 */
export function NameField({
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
