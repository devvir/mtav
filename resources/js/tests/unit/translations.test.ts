import { _, _n, setLocale } from '@/lib/translations';

describe('the words', () => {
  afterEach(() => setLocale('en'));

  it('fill their blanks', () => {
    expect(_('Step {n} of {total}', { n: 2, total: 5 })).toBe('Step 2 of 5');
  });

  it('count in the language’s own plural forms', () => {
    expect(_n('{count} family', '{count} families', 1)).toBe('1 family');
    expect(_n('{count} family', '{count} families', 0)).toBe('0 families');

    setLocale('es_UY');
    expect(_n('{count} family', '{count} families', 1)).toBe('1 familia');
    expect(_n('{count} family', '{count} families', 3)).toBe('3 familias');
  });
});
