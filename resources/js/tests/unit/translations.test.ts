import { _, _n, _nodes, getLocale, loadLocale, setLocale } from '@/lib/translations';
import { render } from '@testing-library/react';
import { createElement, Fragment } from 'react';

// Its words cannot be fetched (a bad connection)
vi.mock('/lang/fr.json', () => {
  throw new Error('Failed to fetch');
});

describe('the words', () => {
  afterEach(() => setLocale('en'));

  it('fill their blanks', () => {
    expect(_('Step {n} of {total}', { n: 2, total: 5 })).toBe('Step 2 of 5');
  });

  it('count in the language’s own plural forms', async () => {
    expect(_n('{count} family', '{count} families', 1)).toBe('1 family');
    expect(_n('{count} family', '{count} families', 0)).toBe('0 families');

    await loadLocale('es_UY');
    expect(_n('{count} family', '{count} families', 1)).toBe('1 familia');
    expect(_n('{count} family', '{count} families', 3)).toBe('3 familias');
  });

  it('put parts of the page where each language wants them', () => {
    setLocale('xx', { 'Saved by {name} on {date}': 'On {date}, {name} saved it' });

    const { container } = render(
      createElement(
        Fragment,
        null,
        _nodes('Saved by {name} on {date}', {
          name: createElement('strong', null, 'Ana'),
          date: createElement('time', null, 'Monday'),
        }),
      ),
    );

    expect(container.textContent).toBe('On Monday, Ana saved it');
    expect(container.querySelector('strong')?.textContent).toBe('Ana');
    expect(container.querySelector('time')?.textContent).toBe('Monday');
  });

  it('keep the English sentence when a language lacks it, and any blank with nothing to fill it', () => {
    const { container } = render(
      createElement(Fragment, null, _nodes('Until {date} or {other}', { date: 'Friday' })),
    );

    expect(container.textContent).toBe('Until Friday or {other}');
  });

  it('open the app in English when a language’s cannot be fetched', async () => {
    await expect(loadLocale('fr')).resolves.toBeUndefined();

    expect(getLocale()).toBe('en');
    expect(_('Saved')).toBe('Saved');
  });

  it('open it in the language asked when they can', async () => {
    await loadLocale('es_UY');

    expect(getLocale()).toBe('es_UY');
    expect(_('Saved')).toBe('Guardado');
  });
});
