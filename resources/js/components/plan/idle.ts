import { useEffect, useRef, useState } from 'react';

/** What counts as someone still at work */
const ACTIVITY = ['pointerdown', 'pointermove', 'keydown', 'wheel'] as const;

/**
 * Someone gone quiet: after `warnAfter` without a touch of the pointer or the keyboard, the
 * seconds left before `closeAfter` (null while active); at `closeAfter`, `onClose` (which
 * may answer false: not closed, so the count starts again). Any activity resets it.
 */
export function useIdle(
  active: boolean,
  {
    warnAfter,
    closeAfter,
    onClose,
  }: { warnAfter: number; closeAfter: number; onClose: () => Promise<boolean> },
) {
  const [left, setLeft] = useState<number | null>(null);
  const close = useRef(onClose);
  useEffect(() => {
    close.current = onClose;
  });

  useEffect(() => {
    if (!active) return undefined;
    let last = Date.now();
    let closing = false;
    const touch = () => {
      last = Date.now();
    };
    ACTIVITY.forEach((type) => window.addEventListener(type, touch, { passive: true }));
    const tick = window.setInterval(() => {
      if (closing) return;
      const idle = Date.now() - last;
      if (idle < closeAfter) {
        setLeft(idle >= warnAfter ? Math.ceil((closeAfter - idle) / 1000) : null);
        return;
      }
      closing = true;
      void close.current().then((closed) => {
        closing = false;
        if (!closed) touch();
      });
    }, 1000);

    return () => {
      ACTIVITY.forEach((type) => window.removeEventListener(type, touch));
      window.clearInterval(tick);
      setLeft(null);
    };
  }, [active, warnAfter, closeAfter]);

  return active ? left : null;
}
