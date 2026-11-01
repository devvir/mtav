import { toLocalInput } from '@/lib/forms';
import { detailsQuery, setFilter } from '@/lib/listFilters';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { get } = vi.hoisted(() => ({ get: vi.fn() }));
vi.mock('@inertiajs/react', () => ({ router: { get } }));

const visited = () => get.mock.calls.at(-1)![0] as string;

describe('a list’s address', () => {
  beforeEach(() => window.history.replaceState(null, '', '/files?tab=photos&page=3'));

  it('takes a filter, back on the first page, keeping the rest', () => {
    setFilter('q', 'ana');

    expect(visited()).toBe('/files?tab=photos&q=ana');
    expect(get.mock.calls.at(-1)![2]).toMatchObject({ preserveState: true, replace: true });
  });

  it('loses a filter that is emptied or turned off', () => {
    window.history.replaceState(null, '', '/files?q=ana&archived=1&page=2');

    setFilter('q', '');
    expect(visited()).toBe('/files?archived=1');

    setFilter('archived', false);
    expect(visited()).toBe('/files?q=ana');
  });

  it('writes a filter turned on as 1', () => {
    setFilter('archived', true);

    expect(visited()).toBe('/files?tab=photos&archived=1');
  });

  it('keeps the query with a thing’s details open', () => {
    expect(detailsQuery('file', 7)).toEqual({ tab: 'photos', page: '3', file: 7 });
    expect(detailsQuery('event', 9)).toEqual({ tab: 'photos', page: '3', event: 9 });
  });
});

describe('a moment for a datetime field', () => {
  it('is empty when there is none', () => {
    expect(toLocalInput(null)).toBe('');
    expect(toLocalInput(undefined)).toBe('');
    expect(toLocalInput('')).toBe('');
  });

  it('is in local time, to the minute, without a zone', () => {
    const local = new Date(2026, 10, 1, 18, 30);

    expect(toLocalInput(local.toISOString())).toBe('2026-11-01T18:30');
  });
});
