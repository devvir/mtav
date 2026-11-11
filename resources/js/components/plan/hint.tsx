import { useEffect, useRef, useState, type RefObject } from 'react';

/**
 * What a shape is, said by hovering it a moment (its label, or more): only this redraws as
 * the pointer moves, never the plan. `host` is the element around the plan.
 */
export function PlanHint({ host }: { host: RefObject<HTMLElement | null> }) {
  const [hint, setHint] = useState<{ text: string; x: number; y: number } | null>(null);
  const shown = useRef<string | null>(null);

  useEffect(() => {
    const element = host.current;
    if (!element) return undefined;
    let timer = 0;
    const hide = () => {
      window.clearTimeout(timer);
      shown.current = null;
      setHint(null);
    };
    const move = (event: PointerEvent) => {
      const text = (event.target as Element).closest('[data-hint]')?.getAttribute('data-hint');
      if (!text || event.buttons) return hide();
      const box = element.getBoundingClientRect();
      const at = { text, x: event.clientX - box.left, y: event.clientY - box.top };
      if (shown.current === text) return setHint(at);
      window.clearTimeout(timer);
      timer = window.setTimeout(() => {
        shown.current = text;
        setHint(at);
      }, 200);
    };
    element.addEventListener('pointermove', move);
    element.addEventListener('pointerleave', hide);
    element.addEventListener('pointerdown', hide);
    return () => {
      hide();
      element.removeEventListener('pointermove', move);
      element.removeEventListener('pointerleave', hide);
      element.removeEventListener('pointerdown', hide);
    };
  }, [host]);

  return hint ? (
    <div className="plan-hint" style={{ left: hint.x + 14, top: hint.y + 16 }} role="tooltip">
      {hint.text}
    </div>
  ) : null;
}
