import { router } from '@inertiajs/react';

/** Long enough for the phone's menu, as it closes, to give the focus back to its button */
const SETTLES_MS = 50;

/**
 * On arriving at another page the focus goes to its heading, so a screen reader says where
 * it is and the keyboard goes on from there. Someone already typing keeps their field, and
 * visits within a page (filters, details, its address staying the same) leave it alone.
 */
export function followPagesWithFocus() {
  let at = window.location.pathname;

  router.on('navigate', () => {
    if (window.location.pathname === at) return;
    at = window.location.pathname;

    window.setTimeout(() => {
      const page = document.querySelector<HTMLElement>('.shell-page');
      const active = document.activeElement;
      if (!page || (page.contains(active) && active?.matches('input, textarea, select'))) return;

      const heading = page.querySelector('h1');
      heading?.setAttribute('tabindex', '-1');
      (heading ?? page).focus({ preventScroll: true });
    }, SETTLES_MS);
  });
}
