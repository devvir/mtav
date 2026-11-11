import { router } from '@inertiajs/react';

/** Long enough for the phone's menu, as it closes, to give the focus back to its button */
const SETTLES_MS = 50;

const dialogs = (selector = '[role="dialog"]') => [...document.querySelectorAll(selector)];

/**
 * On arriving at another page the focus goes to its heading, so a screen reader says where
 * it is and the keyboard goes on from there. Someone already typing keeps their field, a
 * dialog the new page opened on arrival keeps it (one already open as the trip began, the
 * phone's menu, does not), and visits within a page (filters, details, its address staying
 * the same) leave it alone.
 */
export function followPagesWithFocus() {
  let at = window.location.pathname;
  let before = new Set<Element>();

  router.on('start', () => {
    before = new Set(dialogs());
  });

  router.on('navigate', () => {
    if (window.location.pathname === at) return;
    at = window.location.pathname;

    window.setTimeout(() => {
      const page = document.querySelector<HTMLElement>('.shell-page');
      const active = document.activeElement;
      if (!page || (page.contains(active) && active?.matches('input, textarea, select'))) return;
      // A dialog the page opened on arrival (?new=1, ?person=19) holds the focus
      const opened = (dialog: Element | null | undefined) => Boolean(dialog && !before.has(dialog));
      if (
        opened(active?.closest('[role="dialog"]')) ||
        dialogs('[role="dialog"][aria-modal="true"]').some(opened)
      )
        return;

      const heading = page.querySelector('h1');
      heading?.setAttribute('tabindex', '-1');
      (heading ?? page).focus({ preventScroll: true });
    }, SETTLES_MS);
  });
}
