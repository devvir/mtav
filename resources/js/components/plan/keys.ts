import { useEffect, useEffectEvent } from 'react';
import type { PlanModel } from './model';

/** Which way each arrow nudges the selection */
const STEPS: Record<string, [number, number]> = {
  ArrowLeft: [-1, 0],
  ArrowRight: [1, 0],
  ArrowUp: [0, -1],
  ArrowDown: [0, 1],
};

/** How far a nudge goes, in plan units: a step, or a long one with Shift */
const NUDGE = 5;
const LONG_NUDGE = 25;

/** How far R turns the selection, in degrees (Shift + R the other way) */
const TURN = 15;

/**
 * The editor's keys, where it is at work (never in a field, a dialog or elsewhere on the
 * page): Ctrl+Z / Ctrl+Shift+Z undo and redo; Ctrl+C, Ctrl+V and Ctrl+D copy, paste and
 * duplicate; the arrows nudge the selection; Delete removes it (or its corner); R turns it;
 * Page Up / Page Down move it a floor; Escape lets it go (and `onEscape`, the menu closed).
 */
export function usePlanKeys(model: PlanModel, onEscape: () => void) {
  const escape = useEffectEvent(onEscape);

  useEffect(() => {
    const onKey = (event: KeyboardEvent) => {
      const target = event.target as HTMLElement;
      if (target.closest('input, textarea, select')) return;
      // Keys are the editor's only where it is at work, not in a dialog or elsewhere on the page
      if (target !== document.body && !target.closest('.plan-panel')) return;

      const command = event.ctrlKey || event.metaKey;
      const key = event.key.toLowerCase();
      // Text selected (a toast's, a caption's): copying it is the browser's
      if (command && key === 'c' && window.getSelection()?.toString()) return;

      if (command && key === 'z') {
        event.preventDefault();
        if (event.shiftKey) model.redo();
        else model.undo();
        return;
      }
      if (command && ['c', 'v', 'd'].includes(key)) {
        event.preventDefault();
        if (key === 'c') model.copy();
        else if (key === 'v') model.paste();
        else model.duplicate();
        return;
      }

      const step = STEPS[event.key];
      if (step && model.activePolygon) {
        event.preventDefault();
        const size = event.shiftKey ? LONG_NUDGE : NUDGE;
        model.setActivePolygon(
          model.translate(model.activePolygon, step[0] * size, step[1] * size),
        );
        model.commit();
      } else if (
        (event.key === 'Delete' || event.key === 'Backspace') &&
        (model.selectedItem || model.selectedVertex !== null)
      ) {
        event.preventDefault();
        model.remove();
      } else if (key === 'r' && model.selectedItem) {
        event.preventDefault();
        model.setActivePolygon(model.rotate(model.activePolygon!, event.shiftKey ? -TURN : TURN));
        model.commit();
      } else if ((event.key === 'PageUp' || event.key === 'PageDown') && model.selectedItem) {
        event.preventDefault();
        model.moveFloor(event.key === 'PageUp' ? 1 : -1);
      } else if (event.key === 'Escape' && model.selected) {
        // Used: what else listens (a full screen) leaves it be
        event.preventDefault();
        escape();
        model.select(null);
      }
    };

    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [model]);
}
