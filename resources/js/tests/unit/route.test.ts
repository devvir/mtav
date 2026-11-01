import { leavesPage } from '@/lib/route';
import { beforeEach, describe, expect, it } from 'vitest';

const visit = (address: string, rest = {}) => ({
  url: new URL(address, window.location.origin),
  method: 'get' as const,
  prefetch: false,
  only: [] as string[],
  except: [] as string[],
  ...rest,
});

describe('a visit', () => {
  beforeEach(() => window.history.replaceState(null, '', '/units?floor=1'));

  it('leaves the page for another address, or the same one with another query', () => {
    expect(leavesPage(visit('/lottery'))).toBe(true);
    expect(leavesPage(visit('/units?floor=2'))).toBe(true);
    expect(leavesPage(visit('/units'))).toBe(true);
  });

  it('stays for a reload of part of the page, wherever it says it goes', () => {
    expect(leavesPage(visit('/units?floor=1', { only: ['project'] }))).toBe(false);
    expect(leavesPage(visit('/units?floor=1', { except: ['plan'] }))).toBe(false);
  });

  it('stays for its own address again', () => {
    expect(leavesPage(visit('/units?floor=1'))).toBe(false);
  });

  it('stays for a link hovered, loaded for later', () => {
    expect(leavesPage(visit('/lottery', { prefetch: true }))).toBe(false);
  });

  it("is not the guard's to stop when it changes something", () => {
    expect(leavesPage(visit('/lottery', { method: 'post' }))).toBe(false);
  });
});
