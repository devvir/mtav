import { followPagesWithFocus } from '@/lib/pageFocus';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const handlers = vi.hoisted(() => ({ navigate: (() => undefined) as () => void }));
vi.mock('@inertiajs/react', () => ({
  router: {
    on: (_event: string, handler: () => void) => {
      handlers.navigate = handler;
      return () => undefined;
    },
  },
}));

const arriveAt = (path: string) => {
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

  it('does nothing on a page outside the frame', () => {
    document.querySelector('main')!.remove();

    arriveAt('/lottery');

    expect(document.activeElement?.id).toBe('outside');
  });
});
