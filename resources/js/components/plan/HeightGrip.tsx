import { _ } from '@/lib/translations';
import { Tooltip } from '@mantine/core';
import type { PointerEvent as ReactPointerEvent, RefObject } from 'react';
import type { PlanModel } from './model';

/**
 * The canvas's bottom edge: dragged, the canvas (`svg`) grows taller or shorter, never past
 * what is drawn on it. The whole drag is one step in the history.
 */
export function HeightGrip({
  model,
  svg,
}: {
  model: PlanModel;
  svg: RefObject<SVGSVGElement | null>;
}) {
  const startGrow = (event: ReactPointerEvent<HTMLDivElement>) => {
    const grip = event.currentTarget;
    const [from, height] = [event.clientY, model.canvas.height];
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

  return (
    <Tooltip label={_('Drag to make the plan area taller or shorter')} position="bottom">
      <div
        className="plan-grip"
        role="separator"
        aria-orientation="horizontal"
        aria-label={_('Plan area height')}
        onPointerDown={startGrow}
      />
    </Tooltip>
  );
}
