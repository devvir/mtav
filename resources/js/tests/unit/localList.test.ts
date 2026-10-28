import { activeFilters, matches } from '@/lib/localList';
import { describe, expect, it } from 'vitest';

describe('searching a list in the browser', () => {
  it('ignores case and accents', () => {
    expect(matches('perez', 'Bruno Pérez')).toBe(true);
    expect(matches('GARCÍA', 'Ana García')).toBe(true);
  });

  it('looks in every text it is given, and skips the missing ones', () => {
    expect(matches('studio', 'García', null, 'Studio')).toBe(true);
    expect(matches('loft', 'García', undefined)).toBe(false);
  });

  it('matches everything with no query', () => {
    expect(matches('', 'anything')).toBe(true);
    expect(matches(null)).toBe(true);
  });
});

describe('filters in the address', () => {
  it('keeps only the filters in use, a true one as 1', () => {
    expect(activeFilters({ q: 'ana', family: null, admins: true, unit_type: '' })).toEqual({
      q: 'ana',
      admins: 1,
    });
  });
});
