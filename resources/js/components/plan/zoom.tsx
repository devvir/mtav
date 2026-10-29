import { _ } from '@/lib/translations';
import { ActionIcon, Tooltip } from '@mantine/core';
import { IconMinus, IconPlus, IconZoomReset } from '@tabler/icons-react';
import { useEffect, useLayoutEffect, useRef, useState, type RefObject } from 'react';

/** A part of the plan shown, in plan units (an SVG viewBox) */
export interface Box {
  x: number;
  y: number;
  w: number;
  h: number;
}

/** As near as it gets: an eighth of the whole plan across */
const MAX_ZOOM = 8;
/** How long the word on zooming stays */
const HINT_MS = 1600;

export const parseBox = (viewBox: string): Box => {
  const [x = 0, y = 0, w = 1, h = 1] = viewBox.split(' ').map(Number);
  return { x, y, w, h };
};

/** A box zoomed by `factor` around a point of the plan, kept within the whole */
export function zoomBox(view: Box, fit: Box, factor: number, [px, py]: [number, number]): Box {
  const w = Math.min(fit.w, Math.max(fit.w / MAX_ZOOM, view.w / factor));
  const scale = w / view.w;
  return keepWithin(
    { x: px - (px - view.x) * scale, y: py - (py - view.y) * scale, w, h: view.h * scale },
    fit,
  );
}

/** A box moved so it never leaves the whole */
export function keepWithin(view: Box, fit: Box): Box {
  return {
    ...view,
    x: Math.min(fit.x + fit.w - view.w, Math.max(fit.x, view.x)),
    y: Math.min(fit.y + fit.h - view.h, Math.max(fit.y, view.y)),
  };
}

/**
 * Zooming into a plan and moving around it, as on a map: Ctrl (⌘) + scroll or a pinch zooms
 * where the pointer is, dragging moves once zoomed (but for what can be picked up off it),
 * two fingers on a touch screen. A plain scroll stays the page's: it only says how to zoom.
 * Back to the whole plan when it changes.
 */
export function usePanZoom(svg: RefObject<SVGSVGElement | null>, fitBox: string) {
  const fit = parseBox(fitBox);
  const [view, setView] = useState<Box>(fit);
  const [shownFit, setShownFit] = useState(fitBox);
  if (shownFit !== fitBox) {
    setShownFit(fitBox);
    setView(fit);
  }
  // The word on how to zoom: kept as it fades away
  const [hint, setHint] = useState<{ text: string; shown: boolean }>({ text: '', shown: false });

  // What the listeners read: the box shown now, the whole
  const current = useRef({ view, fit });
  useLayoutEffect(() => {
    current.current = { view, fit };
  });

  useEffect(() => {
    const element = svg.current;
    if (!element) return undefined;
    let hintTimer = 0;
    const say = (text: string) => {
      window.clearTimeout(hintTimer);
      setHint({ text, shown: true });
      hintTimer = window.setTimeout(() => setHint({ text, shown: false }), HINT_MS);
    };
    const toPlan = (x: number, y: number): [number, number] => {
      const point = new DOMPoint(x, y).matrixTransform(element.getScreenCTM()!.inverse());
      return [point.x, point.y];
    };
    const apply = (next: Box) => {
      current.current.view = next;
      setView(next);
    };
    const zoomed = () => current.current.view.w < current.current.fit.w - 1e-6;

    const onWheel = (event: WheelEvent) => {
      if (!(event.ctrlKey || event.metaKey)) {
        say(
          /Mac|iPhone|iPad/.test(navigator.platform)
            ? _('Hold ⌘ and scroll to zoom the plan')
            : _('Hold Ctrl and scroll to zoom the plan'),
        );
        return;
      }
      event.preventDefault();
      setHint((said) => ({ ...said, shown: false }));
      const { view: shown, fit: whole } = current.current;
      apply(
        zoomBox(
          shown,
          whole,
          Math.exp(-event.deltaY * 0.002),
          toPlan(event.clientX, event.clientY),
        ),
      );
    };

    // The pointers down on the plan, where each was last
    const pointers = new Map<number, { x: number; y: number }>();
    const middle = () => {
      const [a, b] = [...pointers.values()];
      return {
        x: (a!.x + b!.x) / 2,
        y: (a!.y + b!.y) / 2,
        gap: Math.hypot(a!.x - b!.x, a!.y - b!.y),
      };
    };

    const onDown = (event: PointerEvent) => {
      if (event.pointerType === 'mouse' && event.button !== 0) return;
      // What can be picked up (a unit, into a ranking) is carried, never the plan
      if (event.pointerType !== 'touch' && (event.target as Element).closest('[data-press]'))
        return;
      pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
      if (event.pointerType === 'mouse' && !zoomed()) return;
      try {
        element.setPointerCapture(event.pointerId);
      } catch {
        // Moving still works over the plan
      }
    };
    const onMove = (event: PointerEvent) => {
      const last = pointers.get(event.pointerId);
      if (!last) return;
      const { view: shown, fit: whole } = current.current;
      const perPixel = 1 / (element.getScreenCTM()?.a || 1);

      if (pointers.size === 2) {
        const before = middle();
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
        const after = middle();
        const moved = keepWithin(
          {
            ...shown,
            x: shown.x - (after.x - before.x) * perPixel,
            y: shown.y - (after.y - before.y) * perPixel,
          },
          whole,
        );
        apply(zoomBox(moved, whole, after.gap / (before.gap || 1), toPlan(after.x, after.y)));
        return;
      }
      pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
      // One finger scrolls the page: a touch screen moves the plan with two
      if (event.pointerType === 'touch') {
        if (zoomed()) say(_('Use two fingers to move the plan'));
        return;
      }
      if (!zoomed()) return;
      apply(
        keepWithin(
          {
            ...shown,
            x: shown.x - (event.clientX - last.x) * perPixel,
            y: shown.y - (event.clientY - last.y) * perPixel,
          },
          whole,
        ),
      );
    };
    const onUp = (event: PointerEvent) => {
      pointers.delete(event.pointerId);
    };

    element.addEventListener('wheel', onWheel, { passive: false });
    element.addEventListener('pointerdown', onDown);
    element.addEventListener('pointermove', onMove);
    element.addEventListener('pointerup', onUp);
    element.addEventListener('pointercancel', onUp);
    return () => {
      window.clearTimeout(hintTimer);
      element.removeEventListener('wheel', onWheel);
      element.removeEventListener('pointerdown', onDown);
      element.removeEventListener('pointermove', onMove);
      element.removeEventListener('pointerup', onUp);
      element.removeEventListener('pointercancel', onUp);
    };
  }, [svg]);

  const zoomed = view.w < fit.w - 1e-6;
  /** Zoomed by a step around the middle of what is shown (the buttons) */
  const step = (factor: number) =>
    setView((shown) => zoomBox(shown, fit, factor, [shown.x + shown.w / 2, shown.y + shown.h / 2]));

  return {
    viewBox: `${view.x} ${view.y} ${view.w} ${view.h}`,
    zoomed,
    hint,
    zoomIn: () => step(1.5),
    zoomOut: () => step(1 / 1.5),
    reset: () => setView(fit),
  };
}

/** The zoom buttons over a plan's corner, and the word on how to zoom */
export function ZoomControls({ zoom }: { zoom: ReturnType<typeof usePanZoom> }) {
  return (
    <>
      <div className="plan-zoom">
        <Tooltip label={_('Zoom in')} position="left">
          <ActionIcon variant="default" aria-label={_('Zoom in')} onClick={zoom.zoomIn}>
            <IconPlus size={16} />
          </ActionIcon>
        </Tooltip>
        <Tooltip label={_('Zoom out')} position="left">
          <ActionIcon
            variant="default"
            aria-label={_('Zoom out')}
            disabled={!zoom.zoomed}
            onClick={zoom.zoomOut}
          >
            <IconMinus size={16} />
          </ActionIcon>
        </Tooltip>
        <Tooltip label={_('The whole plan')} position="left">
          <ActionIcon
            variant="default"
            aria-label={_('The whole plan')}
            disabled={!zoom.zoomed}
            onClick={zoom.reset}
          >
            <IconZoomReset size={16} />
          </ActionIcon>
        </Tooltip>
      </div>
      <div className="plan-zoom-hint" data-shown={zoom.hint.shown || undefined} aria-live="polite">
        {zoom.hint.text}
      </div>
    </>
  );
}
