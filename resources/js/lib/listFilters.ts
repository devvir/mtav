import { router } from '@inertiajs/react';

/** Sets (or, empty, clears) one of a list's filters in the address, back on its first page */
export function setFilter(param: string, value: string | number | boolean | null) {
  const url = new URL(window.location.href);
  if (value === null || value === '' || value === false) url.searchParams.delete(param);
  else url.searchParams.set(param, value === true ? '1' : String(value));
  url.searchParams.delete('page');
  router.get(
    url.pathname + url.search,
    {},
    { preserveState: true, preserveScroll: true, replace: true },
  );
}

/** The current query (the tab, a search), with one thing's details open */
export const detailsQuery = (param: string, id: number) => ({
  ...Object.fromEntries(new URLSearchParams(window.location.search)),
  [param]: id,
});
