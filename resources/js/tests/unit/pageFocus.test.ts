import { followPagesWithFocus } from '@/lib/pageFocus';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const handlers = vi.hoisted(() => ({
  start: (() => undefined) as () => void,
  navigate: (() => undefined) as () => void,
}));
vi.mock('@inertiajs/react', () => ({
  router: {
    on: (event: 'start' | 'navigate', handler: () => void) => {
      handlers[event] = handler;
      return () => undefined;
    },
  },
}));

/** A trip to another page; `meanwhile`, what the new page opens as it arrives */
const arriveAt = (path: string, meanwhile = () => undefined as void) => {
  handlers.start();
  meanwhile();
  window.history.pushState({}, '', path);
  handlers.navigate();
  vi.advanceTimersByTime(50);
};

describe('the focus, going from page to page', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    window.history.pushState({}, '', '/start');
    document.body.innerHTML = `
      <button id="outside">Link</button>
      <main class="shell-page" tabindex="-1"><h1>Lottery</h1><input id="field" /></main>`;
    followPagesWithFocus();
    document.getElementById('outside')!.focus();
  });

  afterEach(() => {
    vi.useRealTimers();
    document.body.innerHTML = '';
  });

  it('goes to the heading of the new page', () => {
    arriveAt('/lottery');

    expect(document.activeElement?.tagName).toBe('H1');
    expect(document.activeElement?.getAttribute('tabindex')).toBe('-1');
  });

  it('goes to the page itself when it has no heading', () => {
    document.querySelector('h1')!.remove();

    arriveAt('/lottery');

    expect(document.activeElement?.tagName).toBe('MAIN');
  });

  it('stays where it is on a visit within the page', () => {
    arriveAt('/start?q=ana');

    expect(document.activeElement?.id).toBe('outside');
  });

  it('leaves a field someone is typing in', () => {
    document.getElementById('field')!.focus();

    arriveAt('/lottery');

    expect(document.activeElement?.id).toBe('field');
  });

  it('stays in a dialog the new page opened (it sits apart from the page)', () => {
    arriveAt('/lottery', () => {
      document.body.insertAdjacentHTML(
        'beforeend',
        '<section role="dialog" aria-modal="true"><input id="in-dialog" /><button id="close">Close</button></section>',
      );
      document.getElementById('close')!.focus();
    });

    expect(document.activeElement?.id).toBe('close');
  });

  it('leaves alone a dialog opening as the page arrives, before it takes the focus', () => {
    arriveAt('/lottery', () =>
      document.body.insertAdjacentHTML(
        'beforeend',
        '<section role="dialog" aria-modal="true"></section>',
      ),
    );

    expect(document.activeElement?.id).toBe('outside');
  });

  it("goes to the heading from a dialog open as the trip began (the phone's menu)", () => {
    document.body.insertAdjacentHTML(
      'beforeend',
      '<section role="dialog" aria-modal="true"><a id="menu-link">Lottery</a></section>',
    );
    document.getElementById('menu-link')!.setAttribute('tabindex', '0');
    document.getElementById('menu-link')!.focus();

    arriveAt('/lottery');

    expect(document.activeElement?.tagName).toBe('H1');
  });

  it('does nothing on a page outside the frame', () => {
    document.querySelector('main')!.remove();

    arriveAt('/lottery');

    expect(document.activeElement?.id).toBe('outside');
  });
});
