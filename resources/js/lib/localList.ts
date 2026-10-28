import { useState } from 'react';

/** Text as compared when searching: no case, no accents ("Pérez" is found by "perez") */
export const fold = (text: string) =>
  text
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLowerCase();

/** Whether any of these texts contains the query (an empty query matches everything) */
export const matches = (
  query: string | null | undefined,
  ...texts: (string | null | undefined)[]
) => !query || texts.some((text) => text && fold(text).includes(fold(query)));

type Filters = Record<string, string | number | boolean | null>;

/**
 * The filters of a list that came whole: narrowed in the browser, at once, and kept in the
 * address (a reload or a shared link shows the same) without asking the server.
 */
export function useLocalFilters<F extends Filters>(initial: F) {
  const [filters, setFilters] = useState(initial);

  const set = <K extends keyof F & string>(key: K, value: F[K]) => {
    setFilters((current) => ({ ...current, [key]: value }));
    const url = new URL(window.location.href);
    if (value === null || value === '' || value === false) url.searchParams.delete(key);
    else url.searchParams.set(key, value === true ? '1' : String(value));
    window.history.replaceState(window.history.state, '', url);
  };

  return [filters, set] as const;
}

/** The filters in use, as the address carries them */
export const activeFilters = (filters: Record<string, string | number | boolean | null>) =>
  Object.fromEntries(
    Object.entries(filters)
      .filter(([, value]) => value !== null && value !== '' && value !== false)
      .map(([key, value]) => [key, value === true ? 1 : value]),
  );
