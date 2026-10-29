import { router } from '@inertiajs/react';

/**
 * Details shown over a list (a person over the people, …), with the item in the address
 * (?person=19): opening and closing are visits that reload only that prop, so a link opens
 * it, and the browser's back button closes it.
 */
export function useDetailsInAddress(param: string, alongside: string[] = []) {
  const visit = (id: number | null, replace = false) => {
    const url = new URL(window.location.href);
    if (id === null) url.searchParams.delete(param);
    else url.searchParams.set(param, String(id));
    router.get(
      url.pathname + url.search,
      {},
      { preserveState: true, preserveScroll: true, only: [param, ...alongside], replace },
    );
  };

  return {
    open: (id: number) => visit(id),
    // From one item to the next (a carousel's): Back still leaves the details at once
    move: (id: number) => visit(id, true),
    close: () => visit(null),
  };
}
